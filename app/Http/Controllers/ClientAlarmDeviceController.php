<?php

namespace App\Http\Controllers;

use App\Jobs\CommandRetryJob;
use App\Models\AlarmEvent;
use App\Models\Client;
use App\Models\Device;
use App\Models\Installation;
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
     * GET /client/alarm/devices/{uuid}
     *
     * Détail d'une centrale avec son statut.
     */
    public function show(Request $request, string $uuid): InertiaResponse
    {
        $device = $this->resolveClientDevice($request, $uuid);

        $recentEvents = AlarmEvent::where('device_uuid', $device->uuid)
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
                'zone_count' => $device->getProperty('panel_zone_count', 0),
                'user_count' => $device->getProperty('panel_user_count', 0),
            ],
            'recentEvents' => $recentEvents,
        ]);
    }

    /**
     * POST /client/alarm/devices/{uuid}/arm
     *
     * Armer la centrale.
     */
    public function arm(Request $request, string $uuid): JsonResponse|\Illuminate\Http\RedirectResponse
    {
        $request->validate([
            'mode' => 'required|in:away,stay,instant',
            'force' => 'sometimes|boolean',
        ]);

        $device = $this->resolveClientDevice($request, $uuid);

        // Vérifier que l'utilisateur a le droit d'armer
        $user = $request->user();
        $this->authorizeArmAction($user, $request->boolean('force'));

        // Vérifier que le device est en ligne
        if ($device->getConnectionStatus() === 'offline') {
            return back()->withErrors([
                'device' => 'La centrale est hors ligne. Impossible d\'envoyer la commande.',
            ]);
        }

        try {
            // Vérifier les zones ouvertes si pas de force
            if (!$request->boolean('force')) {
                $status = $this->hpp->getDeviceStatus($device);
                $openZones = $status['openZones'] ?? [];

                if (!empty($openZones)) {
                    return response()->json([
                        'status' => 'zones_open',
                        'message' => 'Des zones sont ouvertes. Confirmez pour forcer l\'armement.',
                        'open_zones' => $openZones,
                    ], 422);
                }
            }

            // Envoyer la commande d'armement
            $this->hpp->arm($device, $request->input('mode'));

            // Créer un événement de commande
            AlarmEvent::create([
                'device_uuid' => $device->uuid,
                'installation_uuid' => $device->installation_uuid,
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
            // HPP indisponible → dispatch retry job
            CommandRetryJob::dispatch($device, 'arm', ['mode' => $request->input('mode')])
                ->onQueue(config('hikvision.events.queue', 'alarm-events'));

            return back()->with('warning', 'La commande a été mise en file d\'attente et sera réessayée automatiquement.');
        }
    }

    /**
     * POST /client/alarm/devices/{uuid}/disarm
     *
     * Désarmer la centrale.
     */
    public function disarm(Request $request, string $uuid): JsonResponse|\Illuminate\Http\RedirectResponse
    {
        $device = $this->resolveClientDevice($request, $uuid);

        $user = $request->user();
        $this->authorizeArmAction($user, false);

        if ($device->getConnectionStatus() === 'offline') {
            return back()->withErrors([
                'device' => 'La centrale est hors ligne. Impossible d\'envoyer la commande.',
            ]);
        }

        try {
            $this->hpp->disarm($device);

            AlarmEvent::create([
                'device_uuid' => $device->uuid,
                'installation_uuid' => $device->installation_uuid,
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
            CommandRetryJob::dispatch($device, 'disarm')
                ->onQueue(config('hikvision.events.queue', 'alarm-events'));

            return back()->with('warning', 'La commande a été mise en file d\'attente et sera réessayée automatiquement.');
        }
    }

    // ─── Helpers ─────────────────────────────────────────────

    /**
     * Résout un device alarm_panel appartenant au client authentifié.
     */
    private function resolveClientDevice(Request $request, string $uuid): Device
    {
        $user = $request->user();
        $client = Client::where('email', $user->email)->firstOrFail();

        $installationUuids = Installation::where('client_uuid', $client->uuid)
            ->pluck('uuid');

        $device = Device::alarmPanels()
            ->where('uuid', $uuid)
            ->whereIn('installation_uuid', $installationUuids)
            ->firstOrFail();

        return $device;
    }

    /**
     * Vérifie que l'utilisateur a le droit d'armer/désarmer.
     *
     * Rôles autorisés : administrator, manager, operator
     * Force : administrator uniquement
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




