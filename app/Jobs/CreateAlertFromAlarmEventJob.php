<?php

namespace App\Jobs;

use App\Events\AlarmReceivedEvent;
use App\Models\AlarmEvent;
use App\Models\Alert;
use App\Models\InstallationDevice;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

/**
 * Crée une Alert depuis un AlarmEvent critique/élevé.
 *
 * Pipeline :
 * 1. Créer Alert (modèle existant Axontis)
 * 2. Lier alert_uuid dans l'AlarmEvent
 * 3. Broadcast Reverb → channel private-installation.{uuid}
 * 4. Notifications multi-canal (selon les préférences du client)
 */
class CreateAlertFromAlarmEventJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;

    public function backoff(): array
    {
        return [10, 30, 90];
    }

    public int $timeout = 60;

    public function __construct(
        protected AlarmEvent $alarmEvent
    ) {}

    public function handle(): void
    {
        Log::info('CreateAlertFromAlarmEventJob: start', [
            'event_uuid' => $this->alarmEvent->uuid,
        ]);

        try {
            /** @var InstallationDevice|null $installationDevice */
            $installationDevice = $this->alarmEvent->installationDevice;
            $installation = $this->alarmEvent->installation;

            if (!$installationDevice || !$installation) {
                Log::error('CreateAlertFromAlarmEventJob: installationDevice or installation not found', [
                    'event_uuid' => $this->alarmEvent->uuid,
                ]);
                $this->fail(new \RuntimeException('InstallationDevice or Installation not found'));
                return;
            }

            // Device catalogue (brand/model uniquement)
            $device = $installationDevice->device;

            // 1. Créer l'Alert (utilise le modèle existant)
            $clientUuid = $installation->client_uuid;
            $contractUuid = $installation->contract_uuid;

            $cidMapping = config('hikvision.cid_mapping');
            $cidInfo = $cidMapping[$this->alarmEvent->cid_code]
                ?? $cidMapping[$this->alarmEvent->standard_cid_code]
                ?? null;

            $description = $this->buildAlertDescription($cidInfo, $installationDevice, $device);

            $alert = Alert::create([
                'client_uuid' => $clientUuid,
                'contract_uuid' => $contractUuid,
                'type' => $this->mapCategoryToAlertType($this->alarmEvent->category),
                'severity' => $this->alarmEvent->severity ?? 'high',
                'description' => $description,
                'triggered_at' => $this->alarmEvent->triggered_at,
                'resolved' => false,
            ]);

            // 2. Lier l'alert à l'AlarmEvent
            $this->alarmEvent->update([
                'alert_uuid' => $alert->uuid,
            ]);

            // 3. Broadcast via Reverb
            try {
                broadcast(new AlarmReceivedEvent($alert, $installationDevice, $this->alarmEvent));
            } catch (\Exception $e) {
                // Ne pas échouer le job pour un broadcast raté
                Log::warning('CreateAlertFromAlarmEventJob: broadcast failed', [
                    'error' => $e->getMessage(),
                ]);
            }

            Log::info('CreateAlertFromAlarmEventJob: alert created', [
                'alert_uuid' => $alert->uuid,
                'event_uuid' => $this->alarmEvent->uuid,
                'severity' => $this->alarmEvent->severity,
            ]);

        } catch (\Exception $e) {
            Log::error('CreateAlertFromAlarmEventJob: failed', [
                'event_uuid' => $this->alarmEvent->uuid,
                'error' => $e->getMessage(),
            ]);
            throw $e;
        }
    }

    /**
     * Mappe la category AlarmEvent vers le type Alert (enum: intrusion, fire, flood, other).
     */
    private function mapCategoryToAlertType(?string $category): string
    {
        return match ($category) {
            'intrusion', 'panic', 'tamper' => 'intrusion',
            'fire'                          => 'fire',
            'flood', 'water'               => 'flood',
            default                        => 'other',
        };
    }

    private function buildAlertDescription(?array $cidInfo, InstallationDevice $installationDevice, $device): string
    {
        $parts = [];

        $parts[] = $cidInfo['description'] ?? 'Alarme détectée';

        if ($device) {
            $parts[] = "Centrale: {$device->brand} {$device->model}";
        }

        $serialNumber = $installationDevice->getPanelSerialNumber();
        if ($serialNumber) {
            $parts[] = "SN: {$serialNumber}";
        }

        if ($this->alarmEvent->zone_number !== null) {
            $zoneName = $this->alarmEvent->zone_name
                ? "{$this->alarmEvent->zone_name} (zone {$this->alarmEvent->zone_number})"
                : "Zone {$this->alarmEvent->zone_number}";
            $parts[] = $zoneName;
        }

        return implode(' — ', $parts);
    }

    public function tags(): array
    {
        return [
            'alarm-alert',
            'installation_device:' . $this->alarmEvent->installation_device_uuid,
            'installation:' . $this->alarmEvent->installation_uuid,
        ];
    }
}

