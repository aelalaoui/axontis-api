<?php

namespace App\Http\Controllers;

use App\Enums\DeviceCategory;
use App\Models\Installation;
use App\Models\InstallationDevice;
use App\Services\HikPartnerProService;
use Illuminate\Http\Request;
use Inertia\Inertia;

class CrmInstallationController extends Controller
{
    public function __construct(
        private HikPartnerProService $hpp
    ) {}

    /**
     * GET /crm/installations/{uuid}
     * Détail d'une installation — accessible aux rôles internes.
     */
    public function show(string $uuid)
    {
        $installation = Installation::where('uuid', $uuid)
            ->with([
                'client',
                'contract',
                'tasks.user:id,name,role',
                'tasks.installationDevices.device',
            ])
            ->firstOrFail();

        // Regrouper tous les équipements installés (à travers toutes les tâches)
        $devices = $installation->tasks
            ->flatMap(fn ($task) => $task->installationDevices)
            ->map(function (InstallationDevice $id) use ($installation) {
                $device = $id->device;
                $isAlarmPanel = $device?->category === DeviceCategory::ALARM_PANEL->value
                    || $device?->category === 'alarm_panel';

                return [
                    'uuid'              => $id->uuid,
                    'serial_number'     => $id->serial_number,
                    'status'            => $id->status,
                    'notes'             => $id->notes,
                    'is_alarm_panel'    => $isAlarmPanel,
                    'connection_status' => $isAlarmPanel ? $id->getConnectionStatus() : null,
                    'arm_status'        => $isAlarmPanel ? $id->getArmStatus() : null,
                    'last_heartbeat_at' => $isAlarmPanel ? $id->getProperty('last_heartbeat_at') : null,
                    'hpp_device_id'     => $isAlarmPanel ? $id->getHppDeviceId() : null,
                    'hpp_site_id'       => $isAlarmPanel ? $id->getProperty('hpp_site_id') : null,
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

        // Tâches rattachées
        $tasks = $installation->tasks->map(fn ($task) => [
            'uuid'              => $task->uuid,
            'type'              => $task->type,
            'status'            => $task->status,
            'address'           => $task->address,
            'notes'             => $task->notes,
            'scheduled_date'    => $task->scheduled_date?->format('Y-m-d'),
            'is_overdue'        => $task->is_overdue,
            'created_at'        => $task->created_at->format('Y-m-d H:i'),
            'devices_count'     => $task->installationDevices->count(),
            'technician'        => $task->user ? [
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
                    'status'    => $installation->contract->status ?? null,
                ] : null,
            ],
            'devices' => $devices,
            'tasks'   => $tasks,
        ]);
    }

    /**
     * POST /crm/installations/{uuid}/alarm-devices/{deviceUuid}/test-heartbeat
     * Teste la connectivité (heartbeat) d'une centrale d'alarme.
     */
    public function testHeartbeat(Request $request, string $uuid, string $deviceUuid)
    {
        // Vérifier que l'installation existe
        Installation::where('uuid', $uuid)->firstOrFail();

        $installationDevice = InstallationDevice::where('uuid', $deviceUuid)
            ->whereHas('task', fn ($q) => $q->where('taskable_uuid', $uuid))
            ->with('device')
            ->firstOrFail();

        if (!$installationDevice->isAlarmPanel()) {
            return back()->withErrors(['error' => 'Cet équipement n\'est pas une centrale d\'alarme.']);
        }

        try {
            $result = $this->hpp->testHeartbeat($installationDevice);

            // Mettre à jour le statut de connexion et le heartbeat
            $connectionStatus = $result['online'] ? 'online' : 'offline';
            $installationDevice->setProperty('connection_status', $connectionStatus);
            $installationDevice->setProperty('last_heartbeat_at', $result['checked_at'], 'date');

            return back()->with('success', 'Heartbeat testé avec succès — centrale ' . ($connectionStatus === 'online' ? 'en ligne ✓' : 'hors ligne'));
        } catch (\Exception $e) {
            // On marque offline si la requête échoue
            $installationDevice->setProperty('connection_status', 'offline');

            return back()->withErrors(['error' => 'Impossible de contacter la centrale : ' . $e->getMessage()]);
        }
    }
}


