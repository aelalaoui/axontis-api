<?php

namespace App\Jobs;

use App\Enums\AlarmEventSeverity;
use App\Models\AlarmEvent;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

/**
 * Traitement asynchrone d'un événement alarme brut.
 *
 * Pipeline :
 * 1. Enrichissement CID (category, severity, zone_name)
 * 2. Si code arming → device.setProperty('arm_status', ...)
 * 3. Mise à jour device.setProperty('last_event_at', now())
 * 4. Si severity critical/high → Dispatch CreateAlertFromAlarmEventJob
 * 5. Mark processed = true
 */
class ProcessAlarmEventJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 5;

    public function backoff(): array
    {
        return [10, 30, 60, 120, 300];
    }

    public int $timeout = 60;

    public function __construct(
        protected AlarmEvent $alarmEvent
    ) {}

    public function handle(): void
    {
        Log::info('ProcessAlarmEventJob: start', [
            'event_uuid' => $this->alarmEvent->uuid,
            'cid_code' => $this->alarmEvent->cid_code,
            'attempt' => $this->attempts(),
        ]);

        try {
            $device = $this->alarmEvent->device;

            if (!$device) {
                Log::error('ProcessAlarmEventJob: device not found', [
                    'event_uuid' => $this->alarmEvent->uuid,
                    'device_uuid' => $this->alarmEvent->device_uuid,
                ]);
                $this->fail(new \RuntimeException('Device not found'));
                return;
            }

            // 1. Enrichissement CID
            $this->enrichFromCid();

            // 2. Si code arming → mettre à jour arm_status
            $this->handleArmingUpdate($device);

            // 3. Mettre à jour last_event_at
            $device->setProperty('last_event_at', now()->toIso8601String(), 'date');

            // Update heartbeat si c'est un heartbeat
            if ($this->alarmEvent->event_type === 'heartbeat') {
                $device->setProperty('last_heartbeat_at', now()->toIso8601String(), 'date');
                $device->setProperty('connection_status', 'online');
            }

            // 4. Si severity critical/high → dispatch alert job
            $severity = AlarmEventSeverity::tryFrom($this->alarmEvent->severity ?? '');

            if ($severity && $severity->shouldCreateAlert()) {
                CreateAlertFromAlarmEventJob::dispatch($this->alarmEvent)
                    ->onQueue(config('hikvision.events.queue', 'alarm-events'));
            }

            // 5. Mark processed
            $this->alarmEvent->update([
                'processed' => true,
                'processed_at' => now(),
            ]);

            Log::info('ProcessAlarmEventJob: completed', [
                'event_uuid' => $this->alarmEvent->uuid,
            ]);

        } catch (\Exception $e) {
            Log::error('ProcessAlarmEventJob: failed', [
                'event_uuid' => $this->alarmEvent->uuid,
                'attempt' => $this->attempts(),
                'error' => $e->getMessage(),
            ]);
            throw $e;
        }
    }

    /**
     * Enrichir les champs category/severity depuis la config CID.
     */
    private function enrichFromCid(): void
    {
        $cidMapping = config('hikvision.cid_mapping');

        $info = $cidMapping[$this->alarmEvent->cid_code]
            ?? $cidMapping[$this->alarmEvent->standard_cid_code]
            ?? null;

        if ($info) {
            $updates = [];

            if (!$this->alarmEvent->category && isset($info['category'])) {
                $updates['category'] = $info['category'];
            }
            if (!$this->alarmEvent->severity && isset($info['severity'])) {
                $updates['severity'] = $info['severity'];
            }

            if (!empty($updates)) {
                $this->alarmEvent->update($updates);
            }
        }
    }

    /**
     * Gère les mises à jour de statut d'armement.
     */
    private function handleArmingUpdate($device): void
    {
        $cidMapping = config('hikvision.cid_mapping');

        $info = $cidMapping[$this->alarmEvent->cid_code]
            ?? $cidMapping[$this->alarmEvent->standard_cid_code]
            ?? null;

        if ($info && isset($info['arm_status'])) {
            $device->setProperty('arm_status', $info['arm_status']);

            Log::info('ProcessAlarmEventJob: arm_status updated', [
                'device_uuid' => $device->uuid,
                'arm_status' => $info['arm_status'],
            ]);
        }
    }

    /**
     * Tags pour le monitoring Horizon.
     */
    public function tags(): array
    {
        return [
            'alarm-event',
            'device:' . $this->alarmEvent->device_uuid,
            'installation:' . $this->alarmEvent->installation_uuid,
        ];
    }
}


