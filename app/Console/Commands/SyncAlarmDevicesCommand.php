<?php

namespace App\Console\Commands;

use App\Jobs\CheckAlarmDeviceStatusJob;
use App\Models\AlarmDevice;
use App\Services\HikvisionApiService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

/**
 * Commande pour synchroniser les informations des centrales Hikvision.
 *
 * Vérifie le statut, met à jour les informations firmware, etc.
 *
 * Usage:
 *   php artisan hikvision:sync-devices              # Sync toutes les centrales
 *   php artisan hikvision:sync-devices --device=xxx # Sync une centrale spécifique
 *   php artisan hikvision:sync-devices --stale      # Sync uniquement les centrales stale
 *   php artisan hikvision:sync-devices --async      # Dispatch les jobs en async
 */
class SyncAlarmDevicesCommand extends Command
{
    protected $signature = 'hikvision:sync-devices
                            {--device= : UUID d\'une centrale spécifique}
                            {--stale : Sync uniquement les centrales sans heartbeat récent}
                            {--async : Dispatcher les jobs de manière asynchrone}
                            {--limit=100 : Nombre maximum de centrales à synchroniser}';

    protected $description = 'Synchronise les informations des centrales Hikvision';

    protected HikvisionApiService $hikvisionApi;

    public function __construct(HikvisionApiService $hikvisionApi)
    {
        parent::__construct();
        $this->hikvisionApi = $hikvisionApi;
    }

    public function handle(): int
    {
        $deviceUuid = $this->option('device');
        $staleOnly = $this->option('stale');
        $async = $this->option('async');
        $limit = (int) $this->option('limit');

        if ($deviceUuid) {
            return $this->syncSingleDevice($deviceUuid, $async);
        }

        return $this->syncMultipleDevices($staleOnly, $async, $limit);
    }

    protected function syncSingleDevice(string $uuid, bool $async): int
    {
        $device = AlarmDevice::where('uuid', $uuid)->first();

        if (!$device) {
            $this->error("Device not found: {$uuid}");
            return self::FAILURE;
        }

        $this->info("Syncing device: {$device->name} ({$device->ip_address})");

        if ($async) {
            CheckAlarmDeviceStatusJob::dispatch($device)
                ->onQueue(config('hikvision.events.queue', 'alarm-events'));
            $this->info("✓ Job dispatched for {$device->name}");
            return self::SUCCESS;
        }

        $result = $this->syncDevice($device);

        if ($result['success']) {
            $this->info("✓ Synced successfully");
            $this->displayDeviceInfo($device->fresh());
            return self::SUCCESS;
        }

        $this->error("✗ Sync failed: {$result['message']}");
        return self::FAILURE;
    }

    protected function syncMultipleDevices(bool $staleOnly, bool $async, int $limit): int
    {
        $query = AlarmDevice::whereNotNull('ip_address')
            ->whereNotNull('api_username');

        if ($staleOnly) {
            $threshold = config('hikvision.heartbeat.offline_threshold', 600);
            $query->stale($threshold);
            $this->info("Syncing stale devices (no heartbeat in {$threshold}s)...");
        }

        $devices = $query->limit($limit)->get();

        if ($devices->isEmpty()) {
            $this->info('No devices to sync');
            return self::SUCCESS;
        }

        $this->info("Syncing {$devices->count()} devices...");

        if ($async) {
            // Dispatch batch job
            CheckAlarmDeviceStatusJob::dispatch(null, $devices->count())
                ->onQueue(config('hikvision.events.queue', 'alarm-events'));

            $this->info("✓ Batch job dispatched for {$devices->count()} devices");
            return self::SUCCESS;
        }

        // Sync synchronously
        $progressBar = $this->output->createProgressBar($devices->count());
        $progressBar->start();

        $stats = [
            'online' => 0,
            'offline' => 0,
            'error' => 0,
        ];

        foreach ($devices as $device) {
            $result = $this->syncDevice($device);

            if ($result['success']) {
                $stats['online']++;
            } elseif ($result['offline']) {
                $stats['offline']++;
            } else {
                $stats['error']++;
            }

            $progressBar->advance();
        }

        $progressBar->finish();
        $this->newLine(2);

        $this->table(
            ['Status', 'Count'],
            [
                ['Online', $stats['online']],
                ['Offline', $stats['offline']],
                ['Error', $stats['error']],
            ]
        );

        Log::info('hikvision:sync-devices completed', $stats);

        return self::SUCCESS;
    }

    protected function syncDevice(AlarmDevice $device): array
    {
        try {
            // Test connection first
            $connectionResult = $this->hikvisionApi->testConnection($device);

            if (!$connectionResult['success']) {
                $device->updateStatus(AlarmDevice::STATUS_OFFLINE);
                return [
                    'success' => false,
                    'offline' => true,
                    'message' => $connectionResult['message'],
                ];
            }

            // Get device info
            $infoResult = $this->hikvisionApi->getDeviceInfo($device);

            // Get current status
            $statusResult = $this->hikvisionApi->getStatus($device);

            return [
                'success' => true,
                'offline' => false,
                'message' => 'Synced',
            ];

        } catch (\Exception $e) {
            $device->updateStatus(AlarmDevice::STATUS_ERROR);

            return [
                'success' => false,
                'offline' => false,
                'message' => $e->getMessage(),
            ];
        }
    }

    protected function displayDeviceInfo(AlarmDevice $device): void
    {
        $this->table(
            ['Property', 'Value'],
            [
                ['Name', $device->name],
                ['Serial', $device->serial_number],
                ['Model', $device->model],
                ['Firmware', $device->firmware_version ?? 'N/A'],
                ['IP Address', $device->ip_address],
                ['Status', $device->status],
                ['Arm Status', $device->arm_status],
                ['Last Heartbeat', $device->last_heartbeat_at?->diffForHumans() ?? 'Never'],
            ]
        );
    }
}
