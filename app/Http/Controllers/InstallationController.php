<?php

namespace App\Http\Controllers;

use App\Enums\DeviceCategory;
use App\Models\Installation;
use App\Models\InstallationDevice;
use App\Models\Product;
use App\Notifications\InstallationChoiceNotification;
use App\Services\InstallationService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Inertia\Inertia;

class InstallationController extends Controller
{
    protected InstallationService $installationService;

    public function __construct(InstallationService $installationService)
    {
        $this->installationService = $installationService;
    }

    /**
     * Display a listing of the resource.
     */
    public function index(): JsonResponse
    {
        try {
            $installations = $this->installationService->getAllInstallations();
            return response()->json([
                'success' => true,
                'data' => $installations
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to retrieve installations',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * create a newly created resource in storage.
     */
    public function create(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'client_uuid' => 'required|exists:clients,uuid',
            'city_id' => 'required|integer|exists:cities,id',
            'address' => 'required|string|min:3|max:255',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            $installation = $this->installationService->createInstallation($request->all());
            return response()->json([
                'success' => true,
                'message' => 'Installation created successfully',
                'data' => $installation
            ], 201);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to create installation',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Display the specified resource.
     */
    public function show(string $uuid): JsonResponse
    {
        try {
            $installation = $this->installationService->findInstallationByUuid($uuid);

            if (!$installation) {
                return response()->json([
                    'success' => false,
                    'message' => 'Installation not found'
                ], 404);
            }

            return response()->json([
                'success' => true,
                'data' => $installation
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to retrieve installation',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, string $uuid): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'client_uuid' => 'sometimes|exists:clients,uuid',
            'contract_uuid' => 'sometimes|exists:contracts,uuid',
            'city' => 'nullable|string|max:255',
            'address' => 'nullable|string|max:255',
            'zip_code' => 'nullable|string|max:20',
            'country' => 'nullable|string|max:255',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            $installation = $this->installationService->findInstallationByUuid($uuid);

            if (!$installation) {
                return response()->json([
                    'success' => false,
                    'message' => 'Installation not found'
                ], 404);
            }

            $updatedInstallation = $this->installationService->updateInstallation($installation, $request->all());

            return response()->json([
                'success' => true,
                'message' => 'Installation updated successfully',
                'data' => $updatedInstallation
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to update installation',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $uuid): JsonResponse
    {
        try {
            $installation = $this->installationService->findInstallationByUuid($uuid);

            if (!$installation) {
                return response()->json([
                    'success' => false,
                    'message' => 'Installation not found'
                ], 404);
            }

            $this->installationService->deleteInstallation($installation);

            return response()->json([
                'success' => true,
                'message' => 'Installation deleted successfully'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to delete installation',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Display installation schedule form
     */
    public function toSchedule(Request $request, string $uuid)
    {
        /** @var Installation $installation */
        $installation = Installation::fromUuid($uuid);

        if (is_null($installation)) {
            abort(404, 'Installation not found');
        }

        // Load related data
        $installation->load(['client', 'contract']);

        // Verify that the installation belongs to the authenticated client
        $authenticatedClient = $request->get('client');

        if ($installation->client_uuid !== $authenticatedClient->uuid) {
            abort(403, 'Unauthorized: This installation does not belong to your account');
        }

        return Inertia::render('Client/Operations/Schedule', [
            'installation' => [
                'uuid' => $installation->uuid,
                'address' => $installation->address,
                'zip_code' => $installation->zip_code ?? '',
                'city' => $installation->city_fr,
                'scheduled_date' => $installation->scheduled_date ? $installation->scheduled_date->format('Y-m-d') : null,
                'scheduled_time' => $installation->scheduled_time ? $installation->scheduled_time->format('H:i') : null,
            ],
            'client' => [
                'uuid' => $installation->client->uuid,
                'first_name' => $installation->client->first_name,
                'last_name' => $installation->client->last_name,
            ],
            'contract' => [
                'uuid' => $installation->contract->uuid,
            ],
        ]);
    }

    /**
     * Store installation schedule (Inertia form submission)
     */
    public function storeSchedule(Request $request, string $uuid): RedirectResponse
    {
        $validated = $request->validate([
            'scheduled_date' => 'required|date|date_format:Y-m-d',
            'scheduled_time' => 'required|date_format:H:i',
        ]);

        $installation = Installation::fromUuid($uuid);

        if (is_null($installation)) {
            return redirect()->back()->with('error', 'Installation non trouvée.');
        }

        try {
            $this->installationService->scheduleInstallation(
                $installation,
                $validated['scheduled_date'],
                $validated['scheduled_time'],
                $request->get('client')
            );

            // Send confirmation email for technician mode now that we have the scheduled date.
            $client = $request->get('client');
            if ($client && $client->getProperty('installation_mode') === 'technician' && $client->user) {
                $feeProduct = Product::where('property_name', 'installation_mode')
                    ->where('default_value', 'technician')
                    ->where('name', 'Installation Technicien')
                    ->first();

                $feeAmount = ($feeProduct?->caution_price_cents ?? 50000) / 100;
                $currency  = $installation->contract?->currency ?? 'MAD';

                $client->user->notify(new InstallationChoiceNotification(
                    clientName:            $client->full_name,
                    installationMode:      'technician',
                    deliveryAddress:       null,
                    installationFeeAmount: $feeAmount,
                    currency:              $currency,
                    scheduledDate:         $validated['scheduled_date'],
                    scheduledTime:         $validated['scheduled_time'],
                ));
            }

            return redirect()->route('client.home');

        } catch (\Exception $e) {
            return redirect()->back()->with('error', $e->getMessage());
        }
    }

    // ─── CRM ─────────────────────────────────────────────────────────────────

    /**
     * GET /crm/installations/{uuid}
     * Détail d'une installation pour le back-office (rôles internes).
     */
    public function crmShow(string $uuid)
    {
        $installation = Installation::where('uuid', $uuid)
            ->with([
                'client',
                'contract',
                'tasks.user:id,name,role',
                'tasks.installationDevices.device',
            ])
            ->firstOrFail();

        $devices = $installation->tasks
            ->flatMap(fn ($task) => $task->installationDevices)
            ->map(function (InstallationDevice $id) {
                $device       = $id->device;
                $isAlarmPanel = $device?->category === DeviceCategory::ALARM_PANEL->value
                    || $device?->category === 'alarm_panel';

                return [
                    'uuid'              => $id->uuid,
                    'serial_number'     => $id->serial_number,
                    'status'            => $id->status,
                    'notes'             => $id->notes,
                    'is_alarm_panel'    => $isAlarmPanel,
                    'connection_status' => $isAlarmPanel ? $id->getConnectionStatus() : null,
                    'arm_status'        => $isAlarmPanel ? $id->getArmStatus()        : null,
                    'last_heartbeat_at' => $isAlarmPanel ? $id->getProperty('last_heartbeat_at') : null,
                    'hpp_device_id'     => $isAlarmPanel ? $id->getHppDeviceId()      : null,
                    'device' => $device ? [
                        'id'        => $device->id,
                        'uuid'      => $device->uuid,
                        'brand'     => $device->brand,
                        'model'     => $device->model,
                        'category'  => $device->category,
                        'full_name' => $device->full_name,
                    ] : null,
                    'task_uuid'   => $id->task?->uuid,
                    'task_status' => $id->task?->status,
                ];
            })
            ->values()
            ->all();

        $tasks = $installation->tasks->map(fn ($task) => [
            'uuid'           => $task->uuid,
            'type'           => $task->type,
            'status'         => $task->status,
            'address'        => $task->address,
            'notes'          => $task->notes,
            'scheduled_date' => $task->scheduled_date?->format('Y-m-d'),
            'is_overdue'     => $task->is_overdue,
            'created_at'     => $task->created_at->format('Y-m-d H:i'),
            'devices_count'  => $task->installationDevices->count(),
            'technician'     => $task->user ? [
                'id'   => $task->user->id,
                'name' => $task->user->name,
                'role' => $task->user->role instanceof \App\Enums\UserRole
                    ? $task->user->role->value
                    : $task->user->role,
            ] : null,
        ])->values()->all();

        return Inertia::render('CRM/Installations/Show', [
            'installation' => [
                'uuid'           => $installation->uuid,
                'type'           => $installation->type,
                'address'        => $installation->address,
                'city_fr'        => $installation->city_fr,
                'country'        => $installation->country,
                'scheduled_date' => $installation->scheduled_date?->format('Y-m-d'),
                'scheduled_time' => $installation->scheduled_time
                    ? (is_string($installation->scheduled_time)
                        ? $installation->scheduled_time
                        : $installation->scheduled_time->format('H:i'))
                    : null,
                'client' => $installation->client ? [
                    'uuid'      => $installation->client->uuid,
                    'full_name' => $installation->client->full_name,
                    'email'     => $installation->client->email,
                    'phone'     => $installation->client->phone,
                ] : null,
                'contract' => $installation->contract ? [
                    'uuid'      => $installation->contract->uuid,
                    'reference' => $installation->contract->reference ?? null,
                    'status'    => $installation->contract->status    ?? null,
                ] : null,
            ],
            'devices' => $devices,
            'tasks'   => $tasks,
        ]);
    }

    /**
     * POST /crm/installations/{uuid}/alarm-devices/{deviceUuid}/test-heartbeat
     *
     * Rafraîchit et retourne le statut de connectivité d'une centrale d'alarme
     * en lisant les propriétés locales mises à jour passivement via webhook.
     * (Pas d'appel sortant vers HPP — le heartbeat est reçu, jamais émis.)
     */
    public function testHeartbeat(string $uuid, string $deviceUuid)
    {
        Installation::where('uuid', $uuid)->firstOrFail();

        /** @var $installationDevice $installationDevice */
        $installationDevice = InstallationDevice::where('uuid', $deviceUuid)
            ->whereHas('task', fn ($q) => $q->where('taskable_uuid', $uuid))
            ->with('device')
            ->firstOrFail();

        if (!$installationDevice->isAlarmPanel()) {
            return back()->withErrors(['error' => 'Cet équipement n\'est pas une centrale d\'alarme.']);
        }

        $lastHeartbeatAt = $installationDevice->getProperty('last_heartbeat_at');
        $connectionStatus = $installationDevice->getConnectionStatus();

        // Déduire le statut à partir du dernier heartbeat reçu
        // (si aucun heartbeat depuis X minutes → offline)
        $offlineThresholdSeconds = config('hikvision.heartbeat.offline_threshold', 600);

        if ($lastHeartbeatAt) {
            try {
                $lastHeartbeat = new \Carbon\Carbon($lastHeartbeatAt);
                $isRecent = $lastHeartbeat->diffInSeconds(now()) < $offlineThresholdSeconds;
                $connectionStatus = $isRecent ? 'online' : 'offline';
            } catch (\Exception) {
                // date invalide → on garde le statut stocké
            }
        } else {
            // Aucun heartbeat reçu → offline
            $connectionStatus = 'offline';
        }

        // Persister le statut recalculé
        $installationDevice->setProperty('connection_status', $connectionStatus);

        $label = $connectionStatus === 'online'
            ? 'Centrale en ligne ✓'
            : 'Centrale hors ligne — aucun heartbeat reçu récemment';

        if ($lastHeartbeatAt) {
            $label .= ' (dernier heartbeat : ' . \Carbon\Carbon::parse($lastHeartbeatAt)->locale('fr')->diffForHumans() . ')';
        }

        return back()->with('success', $label);
    }
}
