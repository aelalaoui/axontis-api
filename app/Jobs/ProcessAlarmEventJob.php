<?php

namespace App\Jobs;

use App\Models\AlarmEvent;
use App\Services\AlarmEventProcessorService;
use Exception;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

/**
 * Job pour traiter un événement d'alarme de manière asynchrone.
 *
 * Ce job est dispatcheé quand un webhook est reçu d'une centrale Hikvision.
 * Il analyse l'événement, le classifie, et crée une alerte si nécessaire.
 */
class ProcessAlarmEventJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * Nombre maximum de tentatives
     */
    public int $tries = 5;

    /**
     * Délais de backoff exponentiels (en secondes)
     */
    public function backoff(): array
    {
        return [10, 30, 60, 120, 300]; // 10s, 30s, 1m, 2m, 5m
    }

    /**
     * Temps maximum d'exécution (en secondes)
     */
    public int $timeout = 60;

    /**
     * L'événement à traiter
     */
    protected AlarmEvent $event;

    /**
     * Create a new job instance.
     */
    public function __construct(AlarmEvent $event)
    {
        $this->event = $event;
        $this->onQueue(config('hikvision.events.queue', 'alarm-events'));
    }

    /**
     * Execute the job.
     */
    public function handle(AlarmEventProcessorService $processor): void
    {
        Log::info('ProcessAlarmEventJob: Starting', [
            'event_uuid' => $this->event->uuid,
            'event_type' => $this->event->event_type,
            'cid_code' => $this->event->cid_code,
            'attempt' => $this->attempts(),
        ]);

        // Skip if already processed
        if ($this->event->processed) {
            Log::info('ProcessAlarmEventJob: Event already processed', [
                'event_uuid' => $this->event->uuid,
            ]);
            return;
        }

        try {
            $result = $processor->process($this->event);

            Log::info('ProcessAlarmEventJob: Complete', [
                'event_uuid' => $this->event->uuid,
                'success' => $result['success'],
                'alert_created' => $result['alert_created'],
                'message' => $result['message'],
            ]);

        } catch (Exception $e) {
            Log::error('ProcessAlarmEventJob: Failed', [
                'event_uuid' => $this->event->uuid,
                'error' => $e->getMessage(),
                'attempt' => $this->attempts(),
            ]);

            // Re-throw to trigger retry
            throw $e;
        }
    }

    /**
     * Handle a job failure.
     */
    public function failed(?Exception $exception): void
    {
        Log::error('ProcessAlarmEventJob: Job failed permanently', [
            'event_uuid' => $this->event->uuid,
            'error' => $exception?->getMessage(),
            'attempts' => $this->attempts(),
        ]);

        // Mark event with error
        $this->event->markAsProcessed(
            'Job failed after ' . $this->attempts() . ' attempts: ' . ($exception?->getMessage() ?? 'Unknown error')
        );
    }

    /**
     * Get the tags for the job.
     */
    public function tags(): array
    {
        return [
            'alarm-event',
            'event:' . $this->event->uuid,
            'device:' . ($this->event->alarm_device_uuid ?? 'unknown'),
        ];
    }

    /**
     * Determine the unique ID for the job.
     */
    public function uniqueId(): string
    {
        return 'process-alarm-event:' . $this->event->uuid;
    }
}
