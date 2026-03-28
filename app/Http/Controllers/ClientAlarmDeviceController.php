<?php

namespace App\Http\Controllers;

use App\Jobs\CommandRetryJob;
use App\Models\AlarmEvent;
use App\Models\Client;
use App\Models\Installation;
use App\Models\InstallationDevice;
use App\Services\HikPartnerProService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response as InertiaResponse;

/**
 * Contrôleur client pour les centrales d'alarme — détail, arm/disarm.
 */
class ClientAlarmDeviceController extends Controller
{
    public function __construct(
        private HikPartnerProService $hpp
    ) {}

    /**
     * GET /client/installations/{installationUuid}/alarm/devices/{installationDeviceUuid}
     */
    public function show(Request $request, string $installationUuid, string $uuid): InertiaResponse
    {
        $installationDevice = $this->resolveClientInstallationDevice($request, $installationUuid, $uuid);

        $recentEvents = AlarmEvent::where('installation_device_uuid', $installationDevice->uuid)
            ->latest('triggered_at')
            ->take(20)
            ->get()
            ->map(fn(AlarmEvent $event) => [
                'uuid' => $event->uuid,
                'event_type' => $event->event_type,
                'category' => $event->category,
                'severity' => $event->severity,
                'category_label' => $event->category_label,
                'severity_label' => $event->severity_label,
                'zone_number' => $event->zone_number,
                'zone_name' => $event->zone_name,
                'triggered_at' => $event->triggered_at?->toIso8601String(),
                'has_alert' => $event->has_alert,
            ]);

        return Inertia::render('Client/Alarm/DeviceShow', [
            'device' => [
                'uuid' => $installationDevice->uuid,
                'brand' => $installationDevice->device?->brand,
                'model' => $installationDevice->device?->model,
                'description' => $installationDevice->device?->description,
                'installation_uuid' => $installationDevice->installation_uuid,
                'arm_status' => $installationDevice->getArmStatus(),
                'connection_status' => $installationDevice->getConnectionStatus(),
                'serial_number' => $installationDevice->getPanelSerialNumber(),
                'last_event_at' => $installationDevice->getProperty('last_event_at'),
                'last_heartbeat_at' => $installationDevice->getProperty('last_heartbeat_at'),
                'zone_count' => $installationDevice->getProperty('panel_zone_count', 0),
                'user_count' => $installationDevice->getProperty('panel_user_count', 0),
            ],
            'recentEvents' => $recentEvents,
        ]);
    }

    /**
     * POST /client/installations/{installationUuid}/alarm/devices/{installationDeviceUuid}/arm
     */
    public function arm(Request $request, string $installationUuid, string $uuid): JsonResponse|\Illuminate\Http\RedirectResponse
    {
        $request->validate([
            'mode' => 'required|in:away,stay,instant',
            'force' => 'sometimes|boolean',
        ]);

        $installationDevice = $this->resolveClientInstallationDevice($request, $installationUuid, $uuid);

        $user = $request->user();
        $this->authorizeArmAction($user, $request->boolean('force'));

        if ($installationDevice->getConnectionStatus() === 'offline') {
            return back()->withErrors([
                'device' => 'La centrale est hors ligne. Impossible d\'envoyer la commande.',
            ]);
        }

        try {
            if (!$request->boolean('force')) {
                $status = $this->hpp->getDeviceStatus($installationDevice);
                $openZones = $status['openZones'] ?? [];

                if (!empty($openZones)) {
                    return response()->json([
                        'status' => 'zones_open',
                        'message' => 'Des zones sont ouvertes. Confirmez pour forcer l\'armement.',
                        'open_zones' => $openZones,
                    ], 422);
                }
            }

            $this->hpp->arm($installationDevice, $request->input('mode'));

            AlarmEvent::create([
                'installation_device_uuid' => $installationDevice->uuid,
                'installation_uuid' => $installationUuid,
                'event_type' => 'pwa_command',
                'category' => 'arming',
                'severity' => 'info',
                'triggered_at' => now(),
                'source_ip' => $request->ip(),
                'raw_payload' => [
                    'source' => 'pwa',
                    'command' => 'arm',
                    'mode' => $request->input('mode'),
                    'user' => $user->email,
                    'forced' => $request->boolean('force'),
                ],
                'processed' => true,
                'processed_at' => now(),
            ]);

            return back()->with('success', 'Commande d\'armement envoyée. En attente de confirmation...');

        } catch (\Exception $e) {
            CommandRetryJob::dispatch($installationDevice, 'arm', ['mode' => $request->input('mode')])
                ->onQueue(config('hikvision.events.queue', 'alarm-events'));

            return back()->with('warning', 'La commande a été mise en file d\'attente et sera réessayée automatiquement.');
        }
    }

    /**
     * POST /client/installations/{installationUuid}/alarm/devices/{installationDeviceUuid}/disarm
     */
    public function disarm(Request $request, string $installationUuid, string $uuid): JsonResponse|\Illuminate\Http\RedirectResponse
    {
        $installationDevice = $this->resolveClientInstallationDevice($request, $installationUuid, $uuid);

        $user = $request->user();
        $this->authorizeArmAction($user, false);

        if ($installationDevice->getConnectionStatus() === 'offline') {
            return back()->withErrors([
                'device' => 'La centrale est hors ligne. Impossible d\'envoyer la commande.',
            ]);
        }

        try {
            $this->hpp->disarm($installationDevice);

            AlarmEvent::create([
                'installation_device_uuid' => $installationDevice->uuid,
                'installation_uuid' => $installationUuid,
                'event_type' => 'pwa_command',
                'category' => 'arming',
                'severity' => 'info',
                'triggered_at' => now(),
                'source_ip' => $request->ip(),
                'raw_payload' => [
                    'source' => 'pwa',
                    'command' => 'disarm',
                    'user' => $user->email,
                ],
                'processed' => true,
                'processed_at' => now(),
            ]);

            return back()->with('success', 'Commande de désarmement envoyée. En attente de confirmation...');

        } catch (\Exception $e) {
            CommandRetryJob::dispatch($installationDevice, 'disarm')
                ->onQueue(config('hikvision.events.queue', 'alarm-events'));

            return back()->with('warning', 'La commande a été mise en file d\'attente et sera réessayée automatiquement.');
        }
    }

    // ─── Helpers ─────────────────────────────────────────────

    /**
     * Résout un InstallationDevice alarm_panel appartenant à l'installation du client authentifié.
     * L'installationUuid dans l'URL fournit une autorisation de première ligne.
     */
    private function resolveClientInstallationDevice(Request $request, string $installationUuid, string $uuid): InstallationDevice
    {
        $user = $request->user();
        $client = Client::where('email', $user->email)->firstOrFail();

        // Vérifier que l'installation appartient bien au client
        $installation = Installation::where('uuid', $installationUuid)
            ->where('client_uuid', $client->uuid)
            ->firstOrFail();

        return InstallationDevice::alarmPanels()
            ->where('uuid', $uuid)
            ->whereHas('task', function ($q) use ($installation) {
                $q->where('taskable_uuid', $installation->uuid)
                  ->where('taskable_type', Installation::class);
            })
            ->with(['device', 'task.taskable'])
            ->firstOrFail();
    }

    /**
     * Vérifie que l'utilisateur a le droit d'armer/désarmer.
     */
    private function authorizeArmAction($user, bool $force = false): void
    {
        $allowedRoles = [
            \App\Enums\UserRole::ADMINISTRATOR,
            \App\Enums\UserRole::MANAGER,
            \App\Enums\UserRole::OPERATOR,
        ];

        if (!$user->hasAnyRole($allowedRoles)) {
            abort(403, 'Vous n\'avez pas les droits nécessaires pour cette action.');
        }

        if ($force && $user->role !== \App\Enums\UserRole::ADMINISTRATOR->value) {
            abort(403, 'Seul un administrateur peut forcer l\'armement avec des zones ouvertes.');
        }
    }
}
