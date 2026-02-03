<?php

namespace App\Console\Commands;

use App\Jobs\ProcessAlarmEventJob;
use App\Models\AlarmDevice;
use App\Models\AlarmEvent;
use App\Services\HikvisionApiService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

/**
 * Commande pour poller les événements des centrales Hikvision.
 *
 * Utiliser en fallback si les webhooks ne sont pas disponibles.
 * Peut être schedulée toutes les 30 secondes via cron.
 *
 * Usage:
 *   php artisan hikvision:poll-events              # Poll toutes les centrales actives
 *   php artisan hikvision:poll-events --device=xxx # Poll une centrale spécifique
 *   php artisan hikvision:poll-events --limit=50   # Limite le nombre de centrales
 */
class PollHikvisionEventsCommand extends Command
{
    protected $signature = 'hikvision:poll-events
                            {--device= : UUID d\'une centrale spécifique}
                            {--limit=100 : Nombre maximum de centrales à poller}
                            {--dry-run : Affiche les événements sans les enregistrer}';

    protected $description = 'Poll les événements des centrales Hikvision via ISAPI (fallback)';

    protected HikvisionApiService $hikvisionApi;

    public function __construct(HikvisionApiService $hikvisionApi)
    {
        parent::__construct();
        $this->hikvisionApi = $hikvisionApi;
    }

    public function handle(): int
    {
        $deviceUuid = $this->option('device');
        $limit = (int) $this->option('limit');
        $dryRun = $this->option('dry-run');

        if (!config('hikvision.polling.enabled') && !$deviceUuid) {
            $this->warn('Polling is disabled in configuration. Use --device to poll a specific device.');
            return self::SUCCESS;
        }

        if ($deviceUuid) {
            return $this->pollSingleDevice($deviceUuid, $dryRun);
        }

        return $this->pollMultipleDevices($limit, $dryRun);
    }

    protected function pollSingleDevice(string $uuid, bool $dryRun): int
    {
        $device = AlarmDevice::where('uuid', $uuid)->first();

        if (!$device) {
            $this->error("Device not found: {$uuid}");
            return self::FAILURE;
        }

        $this->info("Polling device: {$device->name} ({$device->ip_address})");

        $result = $this->pollDevice($device, $dryRun);

        if ($result['success']) {
            $this->info("✓ Found {$result['events_count']} events");
            return self::SUCCESS;
        }

        $this->error("✗ Failed: {$result['message']}");
        return self::FAILURE;
    }

    protected function pollMultipleDevices(int $limit, bool $dryRun): int
    {
        $devices = AlarmDevice::webhookEnabled()
            ->whereNotNull('ip_address')
            ->whereNotNull('api_username')
            ->limit($limit)
            ->get();

        if ($devices->isEmpty()) {
            $this->info('No devices configured for polling');
            return self::SUCCESS;
        }

        $this->info("Polling {$devices->count()} devices...");

        $progressBar = $this->output->createProgressBar($devices->count());
        $progressBar->start();

        $stats = [
            'success' => 0,
            'failed' => 0,
            'total_events' => 0,
        ];

        foreach ($devices as $device) {
            $result = $this->pollDevice($device, $dryRun);

            if ($result['success']) {
                $stats['success']++;
                $stats['total_events'] += $result['events_count'];
            } else {
                $stats['failed']++;
            }

            $progressBar->advance();
        }

        $progressBar->finish();
        $this->newLine(2);

        $this->table(
            ['Metric', 'Value'],
            [
                ['Devices polled', $stats['success']],
                ['Devices failed', $stats['failed']],
                ['Events found', $stats['total_events']],
            ]
        );

        Log::info('hikvision:poll-events completed', $stats);

        return self::SUCCESS;
    }

    protected function pollDevice(AlarmDevice $device, bool $dryRun): array
    {
        try {
            $result = $this->hikvisionApi->pollEvents($device, 50);

            if (!$result['success']) {
                return [
                    'success' => false,
                    'message' => $result['message'],
                    'events_count' => 0,
                ];
            }

            $events = $result['data']['events'] ?? [];
            $eventsCount = count($events);

            if ($eventsCount === 0) {
                return [
                    'success' => true,
                    'message' => 'No new events',
                    'events_count' => 0,
                ];
            }

            if ($dryRun) {
                $this->info("\n  [DRY-RUN] Would create {$eventsCount} events for {$device->name}");
                foreach ($events as $event) {
                    $this->line("    - " . ($event['eventType'] ?? 'unknown') . " (CID: " . ($event['CIDEvent']['code'] ?? 'N/A') . ")");
                }
            } else {
                foreach ($events as $eventData) {
                    $event = AlarmEvent::createFromWebhook($eventData, $device);

                    if (!$event->isDuplicate()) {
                        ProcessAlarmEventJob::dispatch($event)
                            ->onQueue(config('hikvision.events.queue', 'alarm-events'));
                    }
                }

                $device->recordEventReceived();
            }

            return [
                'success' => true,
                'message' => 'Events polled',
                'events_count' => $eventsCount,
            ];

        } catch (\Exception $e) {
            Log::error('hikvision:poll-events device error', [
                'device_uuid' => $device->uuid,
                'error' => $e->getMessage(),
            ]);

            return [
                'success' => false,
                'message' => $e->getMessage(),
                'events_count' => 0,
            ];
        }
    }
}
