<?php

namespace App\Jobs;

use App\Models\AlarmDevice;
use App\Services\HikvisionApiService;
use Exception;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

/**
 * Job pour vérifier le statut des centrales d'alarme (heartbeat).
 *
 * Ce job est schedulé périodiquement pour :
 * - Tester la connexion aux centrales
 * - Mettre à jour le statut online/offline
 * - Détecter les centrales non répondantes
 */
class CheckAlarmDeviceStatusJob implements ShouldQueue
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
        return [30, 60, 120];
    }

    /**
     * Temps maximum d'exécution (en secondes)
     */
    public int $timeout = 300; // 5 minutes pour traiter plusieurs centrales

    /**
     * La centrale à vérifier (null = toutes les centrales stale)
     */
    protected ?AlarmDevice $device;

    /**
     * Limite du batch (si device est null)
     */
    protected int $batchSize;

    /**
     * Create a new job instance.
     *
     * @param AlarmDevice|null $device Centrale spécifique ou null pour batch
     * @param int $batchSize Nombre de centrales par batch
     */
    public function __construct(?AlarmDevice $device = null, int $batchSize = 50)
    {
        $this->device = $device;
        $this->batchSize = $batchSize;
        $this->onQueue(config('hikvision.events.queue', 'alarm-events'));
    }

    /**
     * Execute the job.
     */
    public function handle(HikvisionApiService $hikvisionApi): void
    {
        if ($this->device) {
            $this->checkSingleDevice($hikvisionApi, $this->device);
        } else {
            $this->checkStaleDevices($hikvisionApi);
        }
    }

    /**
     * Check a single device status.
     */
    protected function checkSingleDevice(HikvisionApiService $hikvisionApi, AlarmDevice $device): void
    {
        Log::info('CheckAlarmDeviceStatusJob: Checking device', [
            'device_uuid' => $device->uuid,
            'device_name' => $device->name,
            'ip_address' => $device->ip_address,
        ]);

        if (!$device->isConfiguredForApi()) {
            Log::warning('CheckAlarmDeviceStatusJob: Device not configured for API', [
                'device_uuid' => $device->uuid,
            ]);
            return;
        }

        try {
            $result = $hikvisionApi->testConnection($device);

            if ($result['success']) {
                // Device is online
                $device->recordHeartbeat();

                // Also get current status
                $hikvisionApi->getStatus($device);

                Log::info('CheckAlarmDeviceStatusJob: Device online', [
                    'device_uuid' => $device->uuid,
                    'device_name' => $device->name,
                ]);
            } else {
                // Mark as offline
                $device->updateStatus(AlarmDevice::STATUS_OFFLINE);

                Log::warning('CheckAlarmDeviceStatusJob: Device offline', [
                    'device_uuid' => $device->uuid,
                    'device_name' => $device->name,
                    'error' => $result['message'],
                ]);
            }

        } catch (Exception $e) {
            $device->updateStatus(AlarmDevice::STATUS_ERROR);

            Log::error('CheckAlarmDeviceStatusJob: Check failed', [
                'device_uuid' => $device->uuid,
                'error' => $e->getMessage(),
            ]);
        }
    }

    /**
     * Check all stale devices (haven't sent heartbeat recently).
     */
    protected function checkStaleDevices(HikvisionApiService $hikvisionApi): void
    {
        $threshold = config('hikvision.heartbeat.offline_threshold', 600);

        $devices = AlarmDevice::stale($threshold)
            ->webhookEnabled()
            ->whereNotNull('ip_address')
            ->whereNotNull('api_username')
            ->limit($this->batchSize)
            ->get();

        Log::info('CheckAlarmDeviceStatusJob: Checking stale devices', [
            'count' => $devices->count(),
            'threshold_seconds' => $threshold,
        ]);

        $stats = [
            'checked' => 0,
            'online' => 0,
            'offline' => 0,
            'errors' => 0,
        ];

        foreach ($devices as $device) {
            try {
                $result = $hikvisionApi->testConnection($device);

                if ($result['success']) {
                    $device->recordHeartbeat();
                    $stats['online']++;
                } else {
                    $device->updateStatus(AlarmDevice::STATUS_OFFLINE);
                    $stats['offline']++;
                }

                $stats['checked']++;

            } catch (Exception $e) {
                $device->updateStatus(AlarmDevice::STATUS_ERROR);
                $stats['errors']++;

                Log::error('CheckAlarmDeviceStatusJob: Device check error', [
                    'device_uuid' => $device->uuid,
                    'error' => $e->getMessage(),
                ]);
            }
        }

        Log::info('CheckAlarmDeviceStatusJob: Batch complete', $stats);
    }

    /**
     * Handle a job failure.
     */
    public function failed(?Exception $exception): void
    {
        Log::error('CheckAlarmDeviceStatusJob: Job failed', [
            'device_uuid' => $this->device?->uuid,
            'error' => $exception?->getMessage(),
        ]);
    }

    /**
     * Get the tags for the job.
     */
    public function tags(): array
    {
        if ($this->device) {
            return [
                'alarm-device-check',
                'device:' . $this->device->uuid,
            ];
        }

        return ['alarm-device-check', 'batch'];
    }
}
