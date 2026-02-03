<?php

namespace App\Console;

use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Foundation\Console\Kernel as ConsoleKernel;

class Kernel extends ConsoleKernel
{
    /**
     * Define the application's command schedule.
     */
    protected function schedule(Schedule $schedule): void
    {
        // =============================================================================
        // HIKVISION ALARM INTEGRATION SCHEDULED TASKS
        // =============================================================================

        // Vérifier le statut des centrales "stale" toutes les 5 minutes
        if (config('hikvision.heartbeat.enabled', true)) {
            $schedule->command('hikvision:sync-devices --stale --async')
                ->everyFiveMinutes()
                ->withoutOverlapping()
                ->runInBackground();
        }

        // Purger les anciens événements chaque dimanche à 3h du matin
        $schedule->command('hikvision:prune-events --keep-alerts')
            ->weekly()
            ->sundays()
            ->at('03:00')
            ->withoutOverlapping();

        // Poll des événements (fallback si webhooks désactivés)
        if (config('hikvision.polling.enabled', false)) {
            $interval = config('hikvision.polling.interval', 30);
            $schedule->command('hikvision:poll-events')
                ->everyThirtySeconds()
                ->withoutOverlapping()
                ->runInBackground();
        }
    }

    /**
     * Register the commands for the application.
     */
    protected function commands(): void
    {
        $this->load(__DIR__.'/Commands');

        require base_path('routes/console.php');
    }
}
