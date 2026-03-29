<?php

namespace App\Http\Controllers;

use App\Enums\UserRole;
use App\Models\Device;
use App\Models\Installation;
use App\Models\InstallationDevice;
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
     * - manager / administrator : voient TOUTES les tâches
     * - technician / operator / accountant / storekeeper : voient uniquement leurs tâches assignées
     */
    public function index(Request $request)
    {
        $user = $request->user();

        // Rôles qui voient toutes les tâches
        $privilegedRoles = [
            UserRole::MANAGER,
            UserRole::ADMINISTRATOR,
        ];
        $isPrivileged    = $user && in_array($user->role, $privilegedRoles);
        // Rôles qui ne voient que leurs propres tâches
        $restrictedRoles = [
            UserRole::STOREKEEPER,
            UserRole::OPERATOR,
            UserRole::ACCOUNTANT,
            UserRole::STOREKEEPER
        ];
        $isRestricted    = $user && in_array($user->role, $restrictedRoles);

        $search     = $request->query('search', '');
        $status     = $request->query('status', '');
        $type       = $request->query('type', '');
        $mode       = $request->query('mode', '');
        $unassigned = $request->boolean('unassigned');
        $sort       = $request->query('sort', 'created_at');
        $direction  = $request->query('direction', 'desc');

        $query = Task::with([
            'user:id,name,role',
            'taskable.client.properties',
            'taskable.contract',
            'installationDevices',
        ])->withCount('installationDevices');

        // Restriction : les rôles non-privilégiés ne voient que leurs tâches
        if ($isRestricted) {
            $query->where('user_id', $user->id);
        }

        // ── Filtres ────────────────────────────────────────────────────────
        if ($status) {
            $query->where('status', $status);
        }

        if ($type) {
            $query->where('type', $type);
        }


        if ($search) {
            $query->where(function ($q) use ($search) {
                $q->where('address', 'like', "%{$search}%")
                  ->orWhere('notes', 'like', "%{$search}%");
            });
        }

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

        $tasks->getCollection()->transform(function (Task $task) {
            return $this->transformTask($task);
        });

        // Badge sidebar :
        // - Rôles restreints : leurs tâches actives
        // - Rôles privilégiés : tâches non-assignées
        if ($isRestricted) {
            $pendingCount = Task::where('user_id', $user->id)
                ->whereIn('status', ['scheduled', 'in_progress'])
                ->count();
        } else {
            $pendingCount = Task::whereIn('status', ['scheduled', 'in_progress'])->count();
        }

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
     * GET /crm/tasks/{uuid}
     * Affiche le détail d'une tâche avec tout le contexte pour traitement.
     */
    public function show(string $uuid)
    {
        $task = Task::with([
            'user:id,name,role',
            'taskable.client.properties',
            'taskable.contract.product.children.device',
            'installationDevices.device',
        ])->where('uuid', $uuid)->firstOrFail();

        $data = $this->transformTask($task);

        // Enrichir avec les sous-produits du contrat
        $subProducts = [];
        if ($task->taskable instanceof Installation && $task->taskable->contract) {
            $contract = $task->taskable->contract;
            $client   = $task->taskable->client;

            if ($contract->product) {
                $subProducts = $contract->product->children
                    ->filter(function ($child) use ($client) {
                        if (!$child->property_name) return true;
                        $val = $client?->getProperty($child->property_name);
                        return $val !== null && $val == $child->default_value;
                    })
                    ->map(fn($child) => [
                        'id'            => $child->id,
                        'name'          => $child->name,
                        'property_name' => $child->property_name,
                        'default_value' => $child->default_value,
                        'quantity'      => self::resolveQuantity(
                                                $child->property_name,
                                                $child->default_value,
                                                $child->device
                                            ),
                        'device'        => $child->device ? [
                            'id'        => $child->device->id,
                            'uuid'      => $child->device->uuid,
                            'brand'     => $child->device->brand,
                            'model'     => $child->device->model,
                            'category'  => $child->device->category,
                            'stock_qty' => $child->device->stock_qty,
                            'full_name' => $child->device->full_name,
                        ] : null,
                    ])
                    // Exclure les sous-produits sans device ET sans logique d'assignation physique
                    // (Wifi, hasWifi, hasExisting*, etc.) → device null ET pas isTechnicianFee
                    ->filter(fn($item) =>
                        $item['device'] !== null
                        || ($item['property_name'] === 'installation_mode' && $item['default_value'] === 'technician')
                    )
                    // Exclure les sous-produits dont la quantité résolue = 0 (ex: Répéteur 0-100)
                    ->filter(fn($item) => $item['quantity'] > 0)
                    ->values()->all();
            }
        }

        // Devices déjà attachés à cette tâche
        $assignedDevices = $task->installationDevices->map(fn($id) => [
            'uuid'          => $id->uuid,
            'serial_number' => $id->serial_number,
            'status'        => $id->status,
            'notes'         => $id->notes,
            'device'        => $id->device ? [
                'id'       => $id->device->id,
                'brand'    => $id->device->brand,
                'model'    => $id->device->model,
                'category' => $id->device->category,
                'full_name'=> $id->device->full_name,
            ] : null,
        ])->all();

        return Inertia::render('CRM/Tasks/Show', [
            'task'            => $data,
            'subProducts'     => $subProducts,
            'assignedDevices' => $assignedDevices,
            'staff'           => $this->getStaff(),
        ]);
    }

    /**
     * PATCH /crm/tasks/{uuid}/device/{deviceUuid}/serial
     * Met à jour le numéro de série d'un InstallationDevice (manager/administrator seulement).
     */
    public function updateDeviceSerial(Request $request, string $uuid, string $deviceUuid)
    {
        $user = $request->user();
        $role = $user->role instanceof \App\Enums\UserRole ? $user->role->value : $user->role;
        if (!in_array($role, ['manager', 'administrator'])) {
            abort(403, 'Action réservée aux managers et administrateurs.');
        }

        $request->validate([
            'serial_number' => 'nullable|string|max:191',
        ]);

        $installationDevice = InstallationDevice::where('uuid', $deviceUuid)
            ->whereHas('task', fn($q) => $q->where('uuid', $uuid))
            ->firstOrFail();

        $installationDevice->update([
            'serial_number' => $request->serial_number,
        ]);

        return redirect()
            ->route('crm.tasks.show', $uuid)
            ->with('success', 'Numéro de série mis à jour.');
    }

    /**
     * PATCH /crm/tasks/{uuid}/reassign-technician
     * Réassigne le technicien d'une tâche (manager/administrator seulement).
     */
    public function reassignTechnician(Request $request, string $uuid)
    {
        $user = $request->user();
        $role = $user->role instanceof \App\Enums\UserRole ? $user->role->value : $user->role;
        if (!in_array($role, ['manager', 'administrator'])) {
            abort(403, 'Action réservée aux managers et administrateurs.');
        }

        $request->validate([
            'technician_id'  => 'required|exists:users,id',
            'scheduled_date' => 'nullable|date',
            'scheduled_time' => 'nullable|date_format:H:i',
        ]);

        $task = Task::where('uuid', $uuid)->firstOrFail();

        DB::transaction(function () use ($request, $task) {
            $task->update([
                'user_id'        => $request->technician_id,
                'scheduled_date' => $request->scheduled_date ?? $task->scheduled_date,
            ]);

            if ($task->taskable instanceof Installation && $request->scheduled_date) {
                $task->taskable->update([
                    'scheduled_date' => $request->scheduled_date,
                    'scheduled_time' => $request->scheduled_time,
                ]);
            }
        });

        return redirect()
            ->route('crm.tasks.show', $uuid)
            ->with('success', 'Technicien réassigné avec succès.');
    }

    /**
     * PATCH /crm/tasks/{uuid}/assign-technician
     * Assigne un technicien + date à une tâche de type "technician".
     */
    public function assignTechnician(Request $request, string $uuid)
    {
        $request->validate([
            'technician_id'           => 'required|exists:users,id',
            'scheduled_date'          => 'nullable|date',
            'scheduled_time'          => 'nullable|date_format:H:i',
            'devices'                 => 'nullable|array',
            'devices.*.device_id'     => 'nullable|exists:devices,id',
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

                // Mettre à jour la date/heure sur l'Installation si elle existe
                if ($task->taskable instanceof Installation && $request->scheduled_date) {
                    $task->taskable->update([
                        'scheduled_date' => $request->scheduled_date,
                        'scheduled_time' => $request->scheduled_time,
                    ]);
                }

                // Filtrer les devices sans device_id valide (ex: "Installation Technicien" sans device)
                $validDevices = array_values(array_filter($request->devices ?? [], fn($d) => !empty($d['device_id']) && (int)$d['device_id'] > 0));
                $this->attachDevices($task, $validDevices);
            });

            return redirect()
                ->route('crm.tasks.show', $uuid)
                ->with('success', 'Technicien assigné avec succès.');
        } catch (\Exception $e) {
            Log::error('TaskController::assignTechnician failed: ' . $e->getMessage());
            return redirect()
                ->route('crm.tasks.show', $uuid)
                ->withErrors(['error' => 'Erreur : ' . $e->getMessage()]);
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

            return redirect()
                ->route('crm.tasks.show', $uuid)
                ->with('success', 'Expédition enregistrée avec succès.');
        } catch (\Exception $e) {
            Log::error('TaskController::assignPostal failed: ' . $e->getMessage());
            return redirect()
                ->route('crm.tasks.show', $uuid)
                ->withErrors(['error' => 'Erreur : ' . $e->getMessage()]);
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
        $clientEmail      = null;
        $clientPhone      = null;
        $contractUuid     = null;

        if ($task->taskable instanceof Installation) {
            $installation = $task->taskable;

            if ($installation->client) {
                $installationMode = $installation->client->getProperty('installation_mode');
                $deliveryAddress  = $installation->client->getProperty('delivery_address');
                $clientName       = $installation->client->full_name;
                $clientUuid       = $installation->client->uuid;
                $clientEmail      = $installation->client->email;
                $clientPhone      = $installation->client->phone;
            }

            if ($installation->contract) {
                $contractUuid = $installation->contract->uuid;
            }
        }

        // Date et heure d'intervention planifiées par le client (sur l'Installation)
        $scheduledDate = null;
        $scheduledTime = null;
        if ($task->taskable instanceof Installation) {
            $scheduledDate = $task->taskable->scheduled_date?->format('Y-m-d');
            $scheduledTime = $task->taskable->scheduled_time
                ? (is_string($task->taskable->scheduled_time)
                    ? $task->taskable->scheduled_time
                    : $task->taskable->scheduled_time->format('H:i'))
                : null;
        }
        // Fallback sur task.scheduled_date si l'installation n'en a pas
        if (!$scheduledDate) {
            $scheduledDate = $task->scheduled_date?->format('Y-m-d');
        }

        return [
            'uuid'              => $task->uuid,
            'type'              => $task->type,
            'status'            => $task->status,
            'address'           => $task->address,
            'notes'             => $task->notes,
            'scheduled_date'    => $scheduledDate,
            'scheduled_time'    => $scheduledTime,
            'is_overdue'        => $task->is_overdue,
            'created_at'        => $task->created_at->format('Y-m-d H:i'),
            'installation_mode' => $installationMode,
            'delivery_address'  => $deliveryAddress,
            'client_name'       => $clientName,
            'client_uuid'       => $clientUuid,
            'client_email'      => $clientEmail,
            'client_phone'      => $clientPhone,
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
     * Attache des devices à une task via InstallationDevice::create()
     * et déduit 1 unité du stock pour chaque device assigné.
     *
     * On utilise ::create() et non attach() car :
     * – la table installation_devices a une PK `uuid` gérée par HasUuid
     * – attach() ne passe pas par le boot() du modèle et ne génère pas l'uuid
     * – la relation BelongsToMany join sur device.uuid, pas device.id
     *
     * @param Task  $task
     * @param array $devices  [['device_id' => int, 'serial_number' => ?, 'status' => ?, 'notes' => ?, 'properties' => []], …]
     */
    private function attachDevices(Task $task, array $devices): void
    {
        if (empty($devices)) return;

        // Résoudre les device_uuid en une seule requête (id → uuid) et
        // charger les objets Device pour pouvoir déduire le stock.
        $deviceIds     = array_unique(array_column($devices, 'device_id'));
        $deviceObjects = Device::whereIn('id', $deviceIds)
            ->get()
            ->keyBy('id');   // Collection indexée par id

        foreach ($devices as $deviceData) {
            $deviceId     = $deviceData['device_id'] ?? null;
            /** @var Device|null $device */
            $device       = $deviceObjects[$deviceId] ?? null;

            if (!$device) continue; // device introuvable, on saute

            // Éviter les vrais doublons : même task + même device + même serial_number
            // Si serial_number diffère (ou est null), c'est une unité distincte → on l'insère.
            // Cas : auxiliaryEntries × 2 → 2 lignes avec le même device_uuid mais SN différents.
            $serialNumber = $deviceData['serial_number'] ?? null;

            $exists = InstallationDevice::where('task_uuid', $task->uuid)
                ->where('device_uuid', $device->uuid)
                ->where(function ($q) use ($serialNumber) {
                    if ($serialNumber !== null && $serialNumber !== '') {
                        // Doublon exact : même SN → on saute
                        $q->where('serial_number', $serialNumber);
                    } else {
                        // SN vide : on vérifie s'il existe déjà une ligne sans SN
                        // pour ce même device (limite 1 ligne sans SN par device)
                        $q->whereNull('serial_number')->orWhere('serial_number', '');
                    }
                })
                ->exists();

            if ($exists) continue;

            // HasUuid génère automatiquement l'uuid via boot()
            $installationDevice = InstallationDevice::create([
                'task_uuid'     => $task->uuid,
                'device_uuid'   => $device->uuid,
                'serial_number' => $deviceData['serial_number'] ?? null,
                'status'        => $deviceData['status'] ?? 'assigned',
                'notes'         => $deviceData['notes'] ?? null,
            ]);

            // ── Déduction du stock ─────────────────────────────────────────
            // On retire 1 unité par device assigné.
            // removeStock() retourne false si stock < 1 (on logue un warning
            // mais on ne bloque pas l'assignation : le technicien peut avoir
            // du matériel physique non encore enregistré dans le système).
            $stockDeducted = $device->removeStock(1);
            if (!$stockDeducted) {
                Log::warning(
                    "TaskController: stock insuffisant pour {$device->full_name} (uuid: {$device->uuid}) " .
                    "lors de l'assignation à la tâche {$task->uuid}. " .
                    "Stock actuel : {$device->stock_qty}"
                );
                // Force la déduction même si stock ≤ 0 (stock négatif toléré)
                $device->decrement('stock_qty');
            }

            // Propriétés custom sur InstallationDevice (via HasProperties)
            if (!empty($deviceData['properties']) && $installationDevice) {
                foreach ($deviceData['properties'] as $key => $value) {
                    $installationDevice->setProperty($key, $value);
                }
            }
        }
    }

    /**
     * Résout la quantité d'unités physiques à assigner pour un sous-produit.
     *
     * Règles issues de la migration des produits :
     *
     * – mainDoors, auxiliaryEntries, fireSensors, floodSensors
     *     default_value est l'entier = nombre d'appareils (1, 2, 3…)
     *
     * – propertySize (Répéteur)
     *     plage string → quantité de répéteurs nécessaires :
     *       '0-100'   → 0   (aucun répéteur)
     *       '100-200' → 0   (aucun répéteur)
     *       '200-300' → 1
     *       '300-400' → 1
     *       '400-500' → 2
     *       '500-1000'→ 3
     *
     * – propertyType (alarm panel)
     *     toujours 1 unité physique quelle que soit la valeur (bureau, villa…)
     *
     * – installation_mode = 'technician'
     *     sous-produit frais technicien, pas de device → quantity = 1 (pour l'affichage)
     *
     * – tout le reste (hasWifi, hasExisting*, …) sans device → 0 (exclu de l'affichage)
     */
    private static function resolveQuantity(
        ?string $propertyName,
        mixed   $defaultValue,
        mixed   $device
    ): int {
        // Sous-produits avec quantité entière directe
        $quantityProperties = ['mainDoors', 'auxiliaryEntries', 'fireSensors', 'floodSensors'];
        if (in_array($propertyName, $quantityProperties)) {
            $qty = (int) $defaultValue;
            return $qty > 0 ? $qty : 1;
        }

        // Répéteur : plage de surface → nombre de répéteurs
        if ($propertyName === 'propertySize') {
            return match ((string) $defaultValue) {
                '0-100'    => 0,
                '100-200'  => 0,
                '200-300'  => 1,
                '300-400'  => 1,
                '400-500'  => 2,
                '500-1000' => 3,
                default    => 1,
            };
        }

        // Alarm panel : toujours 1 unité physique
        if ($propertyName === 'propertyType') {
            return 1;
        }

        // "Installation Technicien" : sous-produit sans device, mais à afficher
        if ($propertyName === 'installation_mode' && $defaultValue === 'technician') {
            return 1;
        }

        // Sous-produit avec device mais property_name non reconnu → 1 par défaut
        if ($device !== null) {
            return 1;
        }

        // Pas de device, pas de logique connue (Wifi, hasExisting*, …) → exclu
        return 0;
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







