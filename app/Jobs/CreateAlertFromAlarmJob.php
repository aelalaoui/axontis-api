<?php

namespace App\Jobs;

use App\Models\AlarmEvent;
use App\Models\Alert;
use Exception;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

/**
 * Job pour traiter une alerte créée depuis un événement d'alarme.
 *
 * Ce job est dispatché après la création d'une Alert pour :
 * - Envoyer des notifications (email, SMS, push)
 * - Déclencher des actions automatiques
 * - Enrichir l'alerte avec des données supplémentaires
 *
 * NOTE: Les implémentations de notifications sont à personnaliser
 * selon les besoins du projet.
 */
class CreateAlertFromAlarmJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * Nombre maximum de tentatives
     */
    public int $tries = 3;

    /**
     * Délais de backoff exponentiels (en secondes)
     */
    public function backoff(): array
    {
        return [15, 30, 60];
    }

    /**
     * Temps maximum d'exécution (en secondes)
     */
    public int $timeout = 120;

    /**
     * L'alerte à traiter
     */
    protected Alert $alert;

    /**
     * L'événement source
     */
    protected AlarmEvent $event;

    /**
     * Create a new job instance.
     */
    public function __construct(Alert $alert, AlarmEvent $event)
    {
        $this->alert = $alert;
        $this->event = $event;
        $this->onQueue(config('hikvision.events.queue', 'alarm-events'));
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        Log::info('CreateAlertFromAlarmJob: Processing alert', [
            'alert_uuid' => $this->alert->uuid,
            'event_uuid' => $this->event->uuid,
            'type' => $this->alert->type,
            'severity' => $this->alert->severity,
        ]);

        try {
            // 1. Load related data for notifications
            $this->loadRelatedData();

            // 2. Send notifications based on severity
            $this->sendNotifications();

            // 3. Log for audit trail
            $this->logAlertCreation();

            // 4. Trigger any automated responses
            $this->triggerAutomatedResponses();

            Log::info('CreateAlertFromAlarmJob: Complete', [
                'alert_uuid' => $this->alert->uuid,
            ]);

        } catch (Exception $e) {
            Log::error('CreateAlertFromAlarmJob: Failed', [
                'alert_uuid' => $this->alert->uuid,
                'error' => $e->getMessage(),
            ]);

            throw $e;
        }
    }

    /**
     * Load related data for notifications.
     */
    protected function loadRelatedData(): void
    {
        // Eager load relationships if needed
        $this->alert->load(['client', 'contract']);
        $this->event->load(['alarmDevice', 'alarmDevice.installation']);
    }

    /**
     * Send notifications based on alert severity.
     */
    protected function sendNotifications(): void
    {
        $severity = $this->alert->severity;
        $client = $this->alert->client;

        if (!$client) {
            Log::warning('CreateAlertFromAlarmJob: No client for alert', [
                'alert_uuid' => $this->alert->uuid,
            ]);
            return;
        }

        // TODO: Implement actual notification logic
        // Examples:
        // - Critical: SMS + Email + Push + Call center
        // - Medium: Email + Push
        // - Low: Email only

        switch ($severity) {
            case 'critical':
                $this->sendCriticalNotifications($client);
                break;
            case 'medium':
                $this->sendMediumNotifications($client);
                break;
            case 'low':
                $this->sendLowNotifications($client);
                break;
        }
    }

    /**
     * Send notifications for critical alerts.
     */
    protected function sendCriticalNotifications($client): void
    {
        Log::info('CreateAlertFromAlarmJob: Sending critical notifications', [
            'alert_uuid' => $this->alert->uuid,
            'client_uuid' => $client->uuid,
        ]);

        // TODO: Implement
        // - Send SMS to client and emergency contacts
        // - Send email
        // - Send push notification
        // - Notify call center / monitoring station
        // Example:
        // $client->notify(new CriticalAlarmNotification($this->alert, $this->event));
    }

    /**
     * Send notifications for medium severity alerts.
     */
    protected function sendMediumNotifications($client): void
    {
        Log::info('CreateAlertFromAlarmJob: Sending medium notifications', [
            'alert_uuid' => $this->alert->uuid,
            'client_uuid' => $client->uuid,
        ]);

        // TODO: Implement
        // - Send email
        // - Send push notification
    }

    /**
     * Send notifications for low severity alerts.
     */
    protected function sendLowNotifications($client): void
    {
        Log::info('CreateAlertFromAlarmJob: Sending low notifications', [
            'alert_uuid' => $this->alert->uuid,
            'client_uuid' => $client->uuid,
        ]);

        // TODO: Implement
        // - Send email summary (batch)
    }

    /**
     * Log alert creation for audit.
     */
    protected function logAlertCreation(): void
    {
        $device = $this->event->alarmDevice;
        $installation = $device?->installation;

        Log::channel(config('hikvision.logging.channel', 'stack'))->info('ALARM_ALERT_CREATED', [
            'alert_uuid' => $this->alert->uuid,
            'event_uuid' => $this->event->uuid,
            'type' => $this->alert->type,
            'severity' => $this->alert->severity,
            'client_uuid' => $this->alert->client_uuid,
            'contract_uuid' => $this->alert->contract_uuid,
            'device_uuid' => $device?->uuid,
            'device_name' => $device?->name,
            'installation_address' => $installation?->address,
            'zone_number' => $this->event->zone_number,
            'cid_code' => $this->event->cid_code,
            'triggered_at' => $this->alert->triggered_at?->toIso8601String(),
        ]);
    }

    /**
     * Trigger automated responses based on alert type.
     */
    protected function triggerAutomatedResponses(): void
    {
        // TODO: Implement automated responses
        // Examples:
        // - Turn on lights (integration with smart home)
        // - Trigger video recording
        // - Lock doors
        // - Dispatch security patrol

        $type = $this->alert->type;

        switch ($type) {
            case 'intrusion':
                $this->handleIntrusionAutomation();
                break;
            case 'fire':
                $this->handleFireAutomation();
                break;
            case 'flood':
                $this->handleFloodAutomation();
                break;
        }
    }

    /**
     * Handle intrusion-specific automation.
     */
    protected function handleIntrusionAutomation(): void
    {
        // TODO: Implement
        // - Start video recording
        // - Turn on exterior lights
        // - Sound alarm (if not already)
    }

    /**
     * Handle fire-specific automation.
     */
    protected function handleFireAutomation(): void
    {
        // TODO: Implement
        // - Alert fire department (via call center)
        // - Turn on emergency lights
        // - Unlock doors for evacuation
    }

    /**
     * Handle flood-specific automation.
     */
    protected function handleFloodAutomation(): void
    {
        // TODO: Implement
        // - Shut off water main (if connected)
        // - Alert maintenance
    }

    /**
     * Handle a job failure.
     */
    public function failed(?Exception $exception): void
    {
        Log::error('CreateAlertFromAlarmJob: Job failed permanently', [
            'alert_uuid' => $this->alert->uuid,
            'event_uuid' => $this->event->uuid,
            'error' => $exception?->getMessage(),
        ]);
    }

    /**
     * Get the tags for the job.
     */
    public function tags(): array
    {
        return [
            'alert-notification',
            'alert:' . $this->alert->uuid,
            'event:' . $this->event->uuid,
            'severity:' . $this->alert->severity,
        ];
    }
}
