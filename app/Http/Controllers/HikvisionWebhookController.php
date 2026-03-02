<?php

namespace App\Http\Controllers;

use App\Jobs\ProcessAlarmEventJob;
use App\Models\AlarmEvent;
use App\Models\Device;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * Réception des webhooks depuis les centrales Hikvision AX PRO.
 *
 * Le Device est résolu par le middleware HikvisionWebhookMiddleware
 * et injecté dans $request->attributes.
 */
class HikvisionWebhookController extends Controller
{
    /**
     * POST /api/webhooks/hikvision/{serial_number}
     *
     * Réception d'un événement (alarme, heartbeat, système…).
     * Cible < 50ms — toute la logique métier est déléguée au job.
     */
    public function handle(Request $request, string $serial_number): JsonResponse
    {
        /** @var Device $device */
        $device = $request->attributes->get('alarm_device');

        $payload = $request->all();
        $eventType = $payload['eventType'] ?? 'unknown';
        $triggeredAt = $payload['dateTime'] ?? $payload['CIDEvent']['trigger'] ?? now()->toIso8601String();

        // Extraction des données CID si présentes
        $cidCode = $payload['CIDEvent']['code'] ?? null;
        $standardCidCode = $payload['CIDEvent']['standardCIDcode'] ?? null;
        $zoneNumber = $payload['CIDEvent']['zone'] ?? null;

        // Enrichissement rapide depuis la config CID
        $cidMapping = config('hikvision.cid_mapping');
        $cidInfo = $cidMapping[$cidCode] ?? $cidMapping[$standardCidCode] ?? null;

        // INSERT brut en base — processed = false
        $alarmEvent = AlarmEvent::create([
            'device_uuid' => $device->uuid,
            'installation_uuid' => $device->installation_uuid,
            'cid_code' => $cidCode,
            'standard_cid_code' => $standardCidCode,
            'event_type' => $eventType,
            'category' => $cidInfo['category'] ?? null,
            'severity' => $cidInfo['severity'] ?? null,
            'zone_number' => $zoneNumber,
            'zone_name' => null, // enrichi par le job
            'triggered_at' => $triggeredAt,
            'source_ip' => $request->ip(),
            'raw_payload' => $payload,
            'processed' => false,
        ]);

        // Dispatch job async sur la queue alarm-events
        ProcessAlarmEventJob::dispatch($alarmEvent)
            ->onQueue(config('hikvision.events.queue', 'alarm-events'));

        return response()->json(['status' => 'accepted'], 202);
    }
}



