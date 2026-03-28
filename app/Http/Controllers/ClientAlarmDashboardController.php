<?php

namespace App\Http\Controllers;

use App\Models\Alert;
use App\Models\Installation;
use App\Models\InstallationDevice;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response as InertiaResponse;

/**
 * Dashboard temps réel pour les centrales d'alarme du client.
 */
class ClientAlarmDashboardController extends Controller
{
    /**
     * GET /client/alarm/dashboard
     *
     * Affiche le dashboard temps réel avec les centrales, alertes actives et stats.
     */
    public function index(Request $request): InertiaResponse
    {
        $user = $request->user();
        $client = $user->client;

        // Récupérer les IDs des installations du client
        $installations = Installation::where('client_uuid', $client->uuid)->get();
        $installationIds = $installations->pluck('id');
        $installationUuids = $installations->pluck('uuid');

        // Centrales alarme via la chaîne correcte Installation → Task → InstallationDevice
        $installationDevices = InstallationDevice::alarmPanels()
            ->whereHas('task', function ($q) use ($installationUuids) {
                $q->whereIn('taskable_uuid', $installationUuids)
                  ->where('taskable_type', Installation::class);
            })
            ->with(['device', 'properties', 'task.taskable'])
            ->get()
            ->map(fn(InstallationDevice $id) => [
                'uuid' => $id->uuid,
                'brand' => $id->device?->brand,
                'model' => $id->device?->model,
                'description' => $id->device?->description,
                'installation_uuid' => $id->installation_uuid,
                'arm_status' => $id->getArmStatus(),
                'connection_status' => $id->getConnectionStatus(),
                'serial_number' => $id->getPanelSerialNumber(),
                'last_event_at' => $id->getProperty('last_event_at'),
                'last_heartbeat_at' => $id->getProperty('last_heartbeat_at'),
            ]);

        // Alertes actives (non résolues) liées aux installations du client
        $activeAlerts = Alert::where('client_uuid', $client->uuid)
            ->where('resolved', false)
            ->where('type', 'like', 'alarm_%')
            ->latest('triggered_at')
            ->take(20)
            ->get()
            ->map(fn(Alert $alert) => [
                'uuid' => $alert->uuid,
                'type' => $alert->type,
                'severity' => $alert->severity,
                'description' => $alert->description,
                'triggered_at' => $alert->triggered_at?->toIso8601String(),
                'is_critical' => $alert->is_critical,
            ]);

        // Stats
        $stats = [
            'online' => $installationDevices->where('connection_status', 'online')->count(),
            'offline' => $installationDevices->where('connection_status', 'offline')->count(),
            'armed' => $installationDevices->whereIn('arm_status', ['armed_away', 'armed_stay'])->count(),
            'disarmed' => $installationDevices->where('arm_status', 'disarmed')->count(),
            'alarm' => $activeAlerts->where('severity', 'critical')->count(),
            'total_devices' => $installationDevices->count(),
            'total_active_alerts' => $activeAlerts->count(),
        ];

        return Inertia::render('Client/Alarm/Dashboard', [
            'devices' => $installationDevices,
            'activeAlerts' => $activeAlerts,
            'stats' => $stats,
            'installationUuids' => $installationUuids->toArray(),
        ]);
    }
}
