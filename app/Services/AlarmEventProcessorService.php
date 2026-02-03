<?php

namespace App\Services;

use App\Jobs\CreateAlertFromAlarmJob;
use App\Models\AlarmDevice;
use App\Models\AlarmEvent;
use App\Models\Alert;
use Exception;
use Illuminate\Support\Facades\Log;

/**
 * Service de traitement des événements d'alarme Hikvision.
 *
 * Analyse les événements CID, détermine la classification, et crée
 * des alertes si nécessaire. Utilisé par le ProcessAlarmEventJob.
 */
class AlarmEventProcessorService
{
    /**
     * Mapping des codes CID vers les types d'alertes.
     * Chargé depuis la configuration.
     */
    protected array $cidMapping;

    /**
     * Constructor
     */
    public function __construct()
    {
        $this->cidMapping = config('hikvision.cid_mapping', []);
    }

    /**
     * Traite un événement d'alarme complet.
     *
     * @param AlarmEvent $event
     * @return array{success: bool, message: string, alert_created: bool, alert_uuid?: string}
     */
    public function process(AlarmEvent $event): array
    {
        Log::info('AlarmEventProcessor: Processing event', [
            'event_uuid' => $event->uuid,
            'event_type' => $event->event_type,
            'cid_code' => $event->cid_code,
            'device_uuid' => $event->alarm_device_uuid,
        ]);

        try {
            // 1. Check for duplicates
            if ($event->isDuplicate()) {
                Log::info('AlarmEventProcessor: Duplicate event skipped', [
                    'event_uuid' => $event->uuid,
                    'event_hash' => $event->event_hash,
                ]);

                $event->markAsProcessed('Duplicate event');

                return [
                    'success' => true,
                    'message' => 'Duplicate event skipped',
                    'alert_created' => false,
                ];
            }

            // 2. Classify the event based on CID code
            $classification = $this->classifyEvent($event);

            // 3. Update event with classification
            $event->setClassification(
                $classification['type'],
                $classification['severity'],
                $classification['description']
            );

            // 4. Update device status if applicable
            $this->updateDeviceFromEvent($event);

            // 5. Create alert if needed
            $alertCreated = false;
            $alertUuid = null;

            if ($this->shouldCreateAlert($event, $classification)) {
                $alert = $this->createAlert($event, $classification);

                if ($alert) {
                    $event->linkAlert($alert);
                    $alertCreated = true;
                    $alertUuid = $alert->uuid;

                    // Dispatch async job for further processing (notifications, etc.)
                    CreateAlertFromAlarmJob::dispatch($alert, $event)
                        ->onQueue(config('hikvision.events.queue', 'alarm-events'));

                    Log::info('AlarmEventProcessor: Alert created', [
                        'event_uuid' => $event->uuid,
                        'alert_uuid' => $alert->uuid,
                        'type' => $classification['type'],
                        'severity' => $classification['severity'],
                    ]);
                }
            }

            // 6. Mark as processed
            $event->markAsProcessed();

            return [
                'success' => true,
                'message' => 'Event processed successfully',
                'alert_created' => $alertCreated,
                'alert_uuid' => $alertUuid,
            ];

        } catch (Exception $e) {
            Log::error('AlarmEventProcessor: Processing failed', [
                'event_uuid' => $event->uuid,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            $event->markAsProcessed($e->getMessage());

            return [
                'success' => false,
                'message' => 'Processing failed: ' . $e->getMessage(),
                'alert_created' => false,
            ];
        }
    }

    /**
     * Classifie un événement basé sur son code CID.
     *
     * @param AlarmEvent $event
     * @return array{type: string|null, severity: string|null, description: string|null}
     */
    public function classifyEvent(AlarmEvent $event): array
    {
        // Try standard CID code first, then original code
        $code = $event->standard_cid_code ?? $event->cid_code;

        if ($code === null) {
            return [
                'type' => AlarmEvent::ALARM_SYSTEM,
                'severity' => null,
                'description' => $event->event_description ?? 'Unknown event',
            ];
        }

        // Look up in mapping
        $mapping = $this->cidMapping[$code] ?? null;

        if ($mapping === null) {
            // System event (explicitly null in mapping = no alert)
            if (array_key_exists($code, $this->cidMapping)) {
                return [
                    'type' => AlarmEvent::ALARM_SYSTEM,
                    'severity' => null,
                    'description' => $this->getSystemEventDescription($code),
                ];
            }

            // Unknown code - log but don't create alert
            Log::warning('AlarmEventProcessor: Unknown CID code', [
                'cid_code' => $code,
                'event_uuid' => $event->uuid,
            ]);

            return [
                'type' => AlarmEvent::ALARM_OTHER,
                'severity' => AlarmEvent::SEVERITY_LOW,
                'description' => "Unknown event (CID: {$code})",
            ];
        }

        return [
            'type' => $mapping['type'],
            'severity' => $mapping['severity'],
            'description' => $mapping['description'],
        ];
    }

    /**
     * Détermine si un événement doit créer une alerte.
     *
     * @param AlarmEvent $event
     * @param array $classification
     * @return bool
     */
    protected function shouldCreateAlert(AlarmEvent $event, array $classification): bool
    {
        // No alert for system events
        if ($classification['type'] === AlarmEvent::ALARM_SYSTEM) {
            return false;
        }

        // No alert if type is null
        if ($classification['type'] === null) {
            return false;
        }

        // No alert for restore/inactive events
        if (in_array($event->event_state, [AlarmEvent::STATE_INACTIVE, AlarmEvent::STATE_RESTORE])) {
            // TODO: Could resolve existing alert instead
            return false;
        }

        // Check if device is linked to a client/contract
        $device = $event->alarmDevice;
        if (!$device || !$device->installation) {
            Log::warning('AlarmEventProcessor: No installation linked to device', [
                'event_uuid' => $event->uuid,
                'device_uuid' => $device?->uuid,
            ]);
            // Still create alert but with warning
        }

        return true;
    }

    /**
     * Crée une alerte dans la table alerts.
     *
     * @param AlarmEvent $event
     * @param array $classification
     * @return Alert|null
     */
    protected function createAlert(AlarmEvent $event, array $classification): ?Alert
    {
        $device = $event->alarmDevice;
        $installation = $device?->installation;
        $client = $installation?->client;
        $contract = $installation?->contract;

        // Build description
        $description = $this->buildAlertDescription($event, $classification, $device);

        try {
            $alert = Alert::create([
                'client_uuid' => $client?->uuid,
                'contract_uuid' => $contract?->uuid,
                'type' => $classification['type'],
                'severity' => $classification['severity'],
                'description' => $description,
                'triggered_at' => $event->triggered_at,
                'resolved' => false,
            ]);

            return $alert;

        } catch (Exception $e) {
            Log::error('AlarmEventProcessor: Failed to create alert', [
                'event_uuid' => $event->uuid,
                'error' => $e->getMessage(),
            ]);

            return null;
        }
    }

    /**
     * Construit la description de l'alerte.
     */
    protected function buildAlertDescription(
        AlarmEvent $event,
        array $classification,
        ?AlarmDevice $device
    ): string {
        $parts = [];

        // Classification description
        if ($classification['description']) {
            $parts[] = $classification['description'];
        }

        // Zone info
        if ($event->zone_number) {
            $parts[] = "Zone {$event->zone_number}";
        }

        // Device info
        if ($device) {
            $parts[] = "Centrale: {$device->name}";
            if ($device->installation) {
                $parts[] = "Adresse: {$device->installation->address}";
            }
        }

        // CID code for reference
        if ($event->cid_code) {
            $parts[] = "(CID: {$event->cid_code})";
        }

        return implode(' | ', $parts);
    }

    /**
     * Met à jour le statut de la centrale basé sur l'événement.
     */
    protected function updateDeviceFromEvent(AlarmEvent $event): void
    {
        $device = $event->alarmDevice;

        if (!$device) {
            return;
        }

        // Record event received (updates heartbeat)
        $device->recordEventReceived();

        // Update arm status based on CID codes
        $code = $event->cid_code ?? $event->standard_cid_code;

        if ($code === null) {
            return;
        }

        $armStatusMap = [
            3401 => AlarmDevice::ARM_AWAY,    // Armement total
            3441 => AlarmDevice::ARM_STAY,    // Armement partiel
            1401 => AlarmDevice::DISARMED,    // Désarmement
            3456 => AlarmDevice::ARM_STAY,    // Armement partiel instant
            3407 => AlarmDevice::ARM_AWAY,    // Armement avec bypass
        ];

        if (isset($armStatusMap[$code])) {
            $device->updateArmStatus($armStatusMap[$code]);

            Log::info('AlarmEventProcessor: Device arm status updated', [
                'device_uuid' => $device->uuid,
                'arm_status' => $armStatusMap[$code],
                'cid_code' => $code,
            ]);
        }
    }

    /**
     * Retourne une description pour les événements système.
     */
    protected function getSystemEventDescription(int $code): string
    {
        $descriptions = [
            3401 => 'Système armé (mode total)',
            3441 => 'Système armé (mode partiel)',
            1401 => 'Système désarmé',
            3456 => 'Système armé (partiel instant)',
            3407 => 'Système armé avec bypass',
            401 => 'Armement/Désarmement utilisateur',
            402 => 'Armement partiel utilisateur',
            403 => 'Armement automatique',
            407 => 'Armement à distance',
            408 => 'Armement rapide',
            409 => 'Armement par téléphone',
            570 => 'Bypass zone activé',
            574 => 'Bypass groupe activé',
        ];

        return $descriptions[$code] ?? "Événement système (CID: {$code})";
    }

    /**
     * Traite un batch d'événements non traités.
     *
     * @param int $limit Nombre max d'événements à traiter
     * @return array{processed: int, failed: int, alerts_created: int}
     */
    public function processBatch(int $limit = 100): array
    {
        $events = AlarmEvent::unprocessed()
            ->orderBy('triggered_at', 'asc')
            ->limit($limit)
            ->get();

        $stats = [
            'processed' => 0,
            'failed' => 0,
            'alerts_created' => 0,
        ];

        foreach ($events as $event) {
            $result = $this->process($event);

            if ($result['success']) {
                $stats['processed']++;
                if ($result['alert_created']) {
                    $stats['alerts_created']++;
                }
            } else {
                $stats['failed']++;
            }
        }

        Log::info('AlarmEventProcessor: Batch processing complete', $stats);

        return $stats;
    }

    /**
     * Résout une alerte existante basée sur un événement de restoration.
     *
     * @param AlarmEvent $restoreEvent
     * @return bool
     */
    public function resolveAlertFromEvent(AlarmEvent $restoreEvent): bool
    {
        // Find the original alert for this device/zone
        $device = $restoreEvent->alarmDevice;

        if (!$device) {
            return false;
        }

        // Find unresolved alert with same zone
        $alert = Alert::unresolved()
            ->where('client_uuid', $device->installation?->client_uuid)
            ->whereHas('events', function ($query) use ($restoreEvent) {
                $query->where('zone_number', $restoreEvent->zone_number);
            })
            ->orderBy('triggered_at', 'desc')
            ->first();

        if (!$alert) {
            return false;
        }

        $alert->update([
            'resolved' => true,
            'resolved_at' => now(),
        ]);

        Log::info('AlarmEventProcessor: Alert resolved from restore event', [
            'alert_uuid' => $alert->uuid,
            'event_uuid' => $restoreEvent->uuid,
        ]);

        return true;
    }
}
