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
        // Horizon metrics snapshots (every 5 minutes)
        $schedule->command('horizon:snapshot')->everyFiveMinutes();

        // Purge des événements alarme (hebdomadaire, dimanche 3h)
        $schedule->command('alarm:prune-events')
            ->weekly()
            ->sundays()
            ->at('03:00')
            ->withoutOverlapping()
            ->appendOutputTo(storage_path('logs/alarm-prune.log'));
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
