<?php

namespace App\Http\Controllers;

use App\Models\Alert;
use App\Models\Device;
use App\Models\Installation;
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

        // Récupérer toutes les installations du client
        $installationUuids = Installation::where('client_uuid', $client->uuid)
            ->pluck('uuid');

        // Centrales alarme du client avec properties
        $devices = Device::alarmPanels()
            ->whereIn('installation_uuid', $installationUuids)
            ->with('properties')
            ->get()
            ->map(fn(Device $device) => [
                'uuid' => $device->uuid,
                'brand' => $device->brand,
                'model' => $device->model,
                'description' => $device->description,
                'installation_uuid' => $device->installation_uuid,
                'arm_status' => $device->getArmStatus(),
                'connection_status' => $device->getConnectionStatus(),
                'serial_number' => $device->getPanelSerialNumber(),
                'last_event_at' => $device->getProperty('last_event_at'),
                'last_heartbeat_at' => $device->getProperty('last_heartbeat_at'),
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
            'online' => $devices->where('connection_status', 'online')->count(),
            'offline' => $devices->where('connection_status', 'offline')->count(),
            'armed' => $devices->whereIn('arm_status', ['armed_away', 'armed_stay'])->count(),
            'disarmed' => $devices->where('arm_status', 'disarmed')->count(),
            'alarm' => $activeAlerts->where('severity', 'critical')->count(),
            'total_devices' => $devices->count(),
            'total_active_alerts' => $activeAlerts->count(),
        ];

        return Inertia::render('Client/Alarm/Dashboard', [
            'devices' => $devices,
            'activeAlerts' => $activeAlerts,
            'stats' => $stats,
            'installationUuids' => $installationUuids->toArray(),
        ]);
    }
}

