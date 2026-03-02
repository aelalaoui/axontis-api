<?php

namespace App\Jobs;

use App\Models\Device;
use App\Services\HikPartnerProService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

/**
 * Job de retry pour les commandes HPP (arm/disarm) en cas d'échec temporaire.
 *
 * Backoff exponentiel : 10s, 30s, 90s — max 3 tentatives.
 */
class CommandRetryJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;

    public function backoff(): array
    {
        return [10, 30, 90];
    }

    public int $timeout = 45;

    public function __construct(
        protected Device $device,
        protected string $command,
        protected array $params = []
    ) {}

    public function handle(HikPartnerProService $hpp): void
    {
        Log::info('CommandRetryJob: executing', [
            'device_uuid' => $this->device->uuid,
            'command' => $this->command,
            'attempt' => $this->attempts(),
        ]);

        try {
            match ($this->command) {
                'arm' => $hpp->arm($this->device, $this->params['mode'] ?? 'away'),
                'disarm' => $hpp->disarm($this->device),
                default => throw new \InvalidArgumentException("Unknown command: {$this->command}"),
            };

            Log::info('CommandRetryJob: success', [
                'device_uuid' => $this->device->uuid,
                'command' => $this->command,
            ]);

        } catch (\Exception $e) {
            Log::error('CommandRetryJob: failed', [
                'device_uuid' => $this->device->uuid,
                'command' => $this->command,
                'attempt' => $this->attempts(),
                'error' => $e->getMessage(),
            ]);

            // Si dernière tentative, marquer arm_status comme unknown
            if ($this->attempts() >= $this->tries) {
                $this->device->setProperty('arm_status', 'unknown');
                Log::warning('CommandRetryJob: max retries reached, arm_status set to unknown', [
                    'device_uuid' => $this->device->uuid,
                ]);
            }

            throw $e;
        }
    }

    public function tags(): array
    {
        return [
            'command-retry',
            'device:' . $this->device->uuid,
            'command:' . $this->command,
        ];
    }
}

