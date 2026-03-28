<?php

namespace App\Http\Controllers;

use App\Models\Installation;
use App\Models\Task;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Inertia\Inertia;

class TaskController extends Controller
{
    /**
     * GET /crm/tasks
     * Listing paginé des tâches avec filtres.
     */
    public function index(Request $request)
    {
        $search    = $request->query('search', '');
        $status    = $request->query('status', '');
        $type      = $request->query('type', '');
        $mode      = $request->query('mode', '');      // 'technician' | 'self' | ''
        $unassigned = $request->boolean('unassigned'); // only user_id IS NULL
        $sort      = $request->query('sort', 'created_at');
        $direction = $request->query('direction', 'desc');

        $query = Task::with([
            'user:id,name,role',
            'taskable',
            'installationDevices',
        ])->withCount('installationDevices');

        // ── Filtres ────────────────────────────────────────────────────────
        if ($status) {
            $query->where('status', $status);
        }

        if ($type) {
            $query->where('type', $type);
        }

        if ($unassigned) {
            $query->whereNull('user_id');
        }

        if ($search) {
            $query->where(function ($q) use ($search) {
                $q->where('address', 'like', "%{$search}%")
                  ->orWhere('notes', 'like', "%{$search}%");
            });
        }

        // Filtre sur le mode d'installation (propriété client via taskable)
        if ($mode) {
            $query->whereHasMorph('taskable', [Installation::class], function ($q) use ($mode) {
                $q->whereHas('client.properties', function ($pq) use ($mode) {
                    $pq->where('property', 'installation_mode')->where('value', $mode);
                });
            });
        }

        // Sorting
        $allowedSorts = ['created_at', 'scheduled_date', 'status', 'type'];
        if (in_array($sort, $allowedSorts)) {
            $query->orderBy($sort, $direction === 'asc' ? 'asc' : 'desc');
        } else {
            $query->orderBy('created_at', 'desc');
        }

        $tasks = $query->paginate(20)->withQueryString();

        // Enrichir chaque tâche avec les données du client
        $tasks->getCollection()->transform(function (Task $task) {
            return $this->transformTask($task);
        });

        // Compteur pour le badge sidebar (tâches non-assignées)
        $pendingCount = Task::whereNull('user_id')
            ->whereIn('status', ['scheduled', 'in_progress'])
            ->count();

        return Inertia::render('CRM/Tasks/Index', [
            'tasks'        => $tasks,
            'pendingCount' => $pendingCount,
            'filters'      => [
                'search'     => $search,
                'status'     => $status,
                'type'       => $type,
                'mode'       => $mode,
                'unassigned' => $unassigned,
                'sort'       => $sort,
                'direction'  => $direction,
            ],
            'staff' => $this->getStaff(),
        ]);
    }

    /**
     * PATCH /crm/tasks/{uuid}/assign-technician
     * Assigne un technicien + date à une tâche de type "technician".
     */
    public function assignTechnician(Request $request, string $uuid)
    {
        $request->validate([
            'technician_id'  => 'required|exists:users,id',
            'scheduled_date' => 'nullable|date',
            'devices'        => 'required|array|min:1',
            'devices.*.device_id'     => 'required|exists:devices,id',
            'devices.*.serial_number' => 'nullable|string|max:191',
            'devices.*.status'        => 'nullable|in:assigned,installed,returned,maintenance,replaced',
            'devices.*.notes'         => 'nullable|string',
            'devices.*.properties'    => 'nullable|array',
        ]);

        $task = Task::where('uuid', $uuid)->firstOrFail();

        try {
            DB::transaction(function () use ($request, $task) {
                // Mettre à jour la tâche
                $task->update([
                    'user_id'        => $request->technician_id,
                    'scheduled_date' => $request->scheduled_date,
                    'status'         => 'scheduled',
                ]);

                // Attacher les devices
                $this->attachDevices($task, $request->devices);
            });

            return back()->with('success', 'Technicien assigné avec succès.');
        } catch (\Exception $e) {
            Log::error('TaskController::assignTechnician failed: ' . $e->getMessage());
            return back()->withErrors(['error' => 'Erreur : ' . $e->getMessage()]);
        }
    }

    /**
     * PATCH /crm/tasks/{uuid}/assign-postal
     * Assigne les devices + tracking pour une tâche de type "self" (livraison postale).
     */
    public function assignPostal(Request $request, string $uuid)
    {
        $request->validate([
            'tracking_code'   => 'nullable|string|max:191',
            'carrier'         => 'nullable|string|max:100',
            'delivery_address'=> 'required|string|max:500',
            'devices'         => 'required|array|min:1',
            'devices.*.device_id'     => 'required|exists:devices,id',
            'devices.*.serial_number' => 'nullable|string|max:191',
            'devices.*.status'        => 'nullable|in:assigned,installed,returned,maintenance,replaced',
            'devices.*.notes'         => 'nullable|string',
            'devices.*.properties'    => 'nullable|array',
        ]);

        $task = Task::where('uuid', $uuid)->firstOrFail();

        try {
            DB::transaction(function () use ($request, $task) {
                // Construire les notes avec tracking
                $notes = 'Livraison postale — ' . $request->delivery_address;
                if ($request->tracking_code) {
                    $notes .= ' | Tracking: ' . $request->tracking_code;
                    if ($request->carrier) {
                        $notes .= ' (' . $request->carrier . ')';
                    }
                }

                $task->update([
                    'status' => 'in_progress',
                    'notes'  => $notes,
                ]);

                // Attacher les devices
                $this->attachDevices($task, $request->devices);
            });

            return back()->with('success', 'Expédition enregistrée avec succès.');
        } catch (\Exception $e) {
            Log::error('TaskController::assignPostal failed: ' . $e->getMessage());
            return back()->withErrors(['error' => 'Erreur : ' . $e->getMessage()]);
        }
    }

    /**
     * Transforme une Task en tableau de données pour le frontend.
     */
    private function transformTask(Task $task): array
    {
        $installationMode = null;
        $deliveryAddress  = null;
        $clientName       = null;
        $clientUuid       = null;
        $contractUuid     = null;

        if ($task->taskable instanceof Installation) {
            $installation = $task->taskable;
            $installation->loadMissing('client.properties', 'contract');

            if ($installation->client) {
                $installationMode = $installation->client->getProperty('installation_mode');
                $deliveryAddress  = $installation->client->getProperty('delivery_address');
                $clientName       = $installation->client->full_name;
                $clientUuid       = $installation->client->uuid;
            }

            if ($installation->contract) {
                $contractUuid = $installation->contract->uuid;
            }
        }

        return [
            'uuid'              => $task->uuid,
            'type'              => $task->type,
            'status'            => $task->status,
            'address'           => $task->address,
            'notes'             => $task->notes,
            'scheduled_date'    => $task->scheduled_date?->format('Y-m-d'),
            'is_overdue'        => $task->is_overdue,
            'created_at'        => $task->created_at->format('Y-m-d H:i'),
            'installation_mode' => $installationMode, // 'technician' | 'self' | null
            'delivery_address'  => $deliveryAddress,
            'client_name'       => $clientName,
            'client_uuid'       => $clientUuid,
            'contract_uuid'     => $contractUuid,
            'technician'        => $task->user ? [
                'id'   => $task->user->id,
                'name' => $task->user->name,
                'role' => $task->user->role instanceof \App\Enums\UserRole
                    ? $task->user->role->value
                    : $task->user->role,
            ] : null,
            'devices_count'     => $task->installation_devices_count ?? 0,
            'taskable_type'     => class_basename($task->taskable_type ?? ''),
            'taskable_uuid'     => $task->taskable_uuid,
        ];
    }

    /**
     * Attache des devices à une task (InstallationDevices).
     * Saute les devices déjà attachés pour éviter les doublons.
     */
    private function attachDevices(Task $task, array $devices): void
    {
        foreach ($devices as $deviceData) {
            // Eviter les doublons si déjà assigné
            $exists = $task->installationDevices()
                ->whereHas('device', fn($q) => $q->where('id', $deviceData['device_id']))
                ->exists();

            if ($exists) continue;

            $task->devices()->attach($deviceData['device_id'], [
                'serial_number' => $deviceData['serial_number'] ?? null,
                'status'        => $deviceData['status'] ?? 'assigned',
                'notes'         => $deviceData['notes'] ?? null,
            ]);

            // Propriétés custom sur InstallationDevice
            if (!empty($deviceData['properties'])) {
                $installationDevice = $task->installationDevices()
                    ->whereHas('device', fn($q) => $q->where('id', $deviceData['device_id']))
                    ->latest()
                    ->first();

                if ($installationDevice) {
                    foreach ($deviceData['properties'] as $key => $value) {
                        $installationDevice->setProperty($key, $value);
                    }
                }
            }
        }
    }

    /**
     * Retourne la liste du staff (non-client, actif) pour les selects.
     */
    private function getStaff(): array
    {
        return User::whereNot('role', \App\Enums\UserRole::CLIENT->value)
            ->where('is_active', true)
            ->orderBy('name')
            ->get(['id', 'name', 'role'])
            ->map(fn($u) => [
                'id'   => $u->id,
                'name' => $u->name,
                'role' => $u->role instanceof \App\Enums\UserRole ? $u->role->value : $u->role,
            ])
            ->toArray();
    }
}


