<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\HikvisionWebhookRequest;
use App\Jobs\ProcessAlarmEventJob;
use App\Models\AlarmDevice;
use App\Models\AlarmEvent;
use Exception;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Log;

/**
 * Controller pour recevoir les webhooks des centrales Hikvision.
 *
 * Endpoint principal pour la réception des événements d'alarme en temps réel.
 * Les événements sont stockés immédiatement et traités de manière asynchrone.
 */
class AlarmWebhookController extends Controller
{
    /**
     * Receive an alarm event from a Hikvision device.
     *
     * POST /api/webhooks/hikvision/alarm
     *
     * Expected payload format (JSON):
     * {
     *   "ipAddress": "192.168.1.25",
     *   "macAddress": "XX:XX:XX:XX:XX:XX",
     *   "eventType": "cidEvent",
     *   "eventState": "active",
     *   "dateTime": "2026-02-03T21:30:00+01:00",
     *   "CIDEvent": {
     *     "code": 1759,
     *     "standardCIDcode": 3130,
     *     "type": "zoneAlarm",
     *     "trigger": "2026-02-03T21:30:00+01:00",
     *     "zone": 1
     *   }
     * }
     *
     * @param HikvisionWebhookRequest $request
     * @return JsonResponse
     */
    public function handleAlarm(HikvisionWebhookRequest $request): JsonResponse
    {
        $startTime = microtime(true);

        try {
            $payload = $request->getPayload();

            // 1. Identify the device
            $device = $this->identifyDevice($payload, $request->ip());

            // 2. Log the raw event
            if (config('hikvision.logging.enabled', true)) {
                Log::channel(config('hikvision.logging.channel', 'stack'))->info('HIKVISION_WEBHOOK_RECEIVED', [
                    'source_ip' => $request->ip(),
                    'mac_address' => $payload['macAddress'] ?? null,
                    'event_type' => $payload['eventType'] ?? null,
                    'cid_code' => $payload['CIDEvent']['code'] ?? null,
                    'device_uuid' => $device?->uuid,
                ]);
            }

            // 3. Create the event record
            $event = AlarmEvent::createFromWebhook($payload, $device);

            // 4. Check for duplicates before dispatching job
            if ($event->isDuplicate()) {
                Log::info('AlarmWebhook: Duplicate event detected', [
                    'event_uuid' => $event->uuid,
                    'hash' => $event->event_hash,
                ]);

                $event->markAsProcessed('Duplicate event');

                return response()->json([
                    'success' => true,
                    'message' => 'Duplicate event acknowledged',
                    'event_uuid' => $event->uuid,
                ], 200);
            }

            // 5. Dispatch async processing job
            ProcessAlarmEventJob::dispatch($event)
                ->onQueue(config('hikvision.events.queue', 'alarm-events'));

            // 6. Update device heartbeat
            if ($device) {
                $device->recordEventReceived();
            }

            $processingTime = round((microtime(true) - $startTime) * 1000, 2);

            return response()->json([
                'success' => true,
                'message' => 'Event received and queued for processing',
                'event_uuid' => $event->uuid,
                'processing_time_ms' => $processingTime,
            ], 202);

        } catch (Exception $e) {
            Log::error('AlarmWebhook: Error processing webhook', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
                'ip' => $request->ip(),
                'payload' => $request->all(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Error processing event',
                'error' => config('app.debug') ? $e->getMessage() : 'Internal error',
            ], 500);
        }
    }

    /**
     * Identify the device that sent the webhook.
     *
     * Priority:
     * 1. MAC address (most reliable)
     * 2. IP address (may change)
     * 3. Serial number (if in payload)
     *
     * @param array $payload
     * @param string|null $sourceIp
     * @return AlarmDevice|null
     */
    protected function identifyDevice(array $payload, ?string $sourceIp = null): ?AlarmDevice
    {
        // Try MAC address first
        $macAddress = $payload['macAddress'] ?? null;
        if ($macAddress) {
            $device = AlarmDevice::findByMac($macAddress);
            if ($device) {
                return $device;
            }
        }

        // Try IP address
        $ipAddress = $payload['ipAddress'] ?? $sourceIp;
        if ($ipAddress) {
            $device = AlarmDevice::findByIp($ipAddress);
            if ($device) {
                return $device;
            }
        }

        // Device not found - log warning
        Log::warning('AlarmWebhook: Unknown device', [
            'mac_address' => $macAddress,
            'ip_address' => $ipAddress,
            'source_ip' => $sourceIp,
        ]);

        return null;
    }

    /**
     * Handle heartbeat events from devices.
     *
     * POST /api/webhooks/hikvision/heartbeat
     *
     * @param HikvisionWebhookRequest $request
     * @return JsonResponse
     */
    public function handleHeartbeat(HikvisionWebhookRequest $request): JsonResponse
    {
        try {
            $payload = $request->validated();

            // Identify device
            $device = $this->identifyDevice($payload, $request->ip());

            if ($device) {
                $device->recordHeartbeat();

                return response()->json([
                    'success' => true,
                    'message' => 'Heartbeat recorded',
                    'device_uuid' => $device->uuid,
                ], 200);
            }

            return response()->json([
                'success' => false,
                'message' => 'Device not found',
            ], 404);

        } catch (Exception $e) {
            Log::error('AlarmWebhook: Heartbeat error', [
                'error' => $e->getMessage(),
                'ip' => $request->ip(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Error processing heartbeat',
            ], 500);
        }
    }

    /**
     * Health check endpoint for monitoring.
     *
     * GET /api/webhooks/hikvision/health
     *
     * @return JsonResponse
     */
    public function health(): JsonResponse
    {
        return response()->json([
            'status' => 'ok',
            'timestamp' => now()->toIso8601String(),
            'version' => config('app.version', '1.0.0'),
        ], 200);
    }
}
