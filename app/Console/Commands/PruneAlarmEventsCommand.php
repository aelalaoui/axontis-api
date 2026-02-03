<?php

namespace App\Console\Commands;

use App\Models\AlarmEvent;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

/**
 * Commande pour purger les anciens événements d'alarme.
 *
 * Maintient la table alarm_events à une taille raisonnable en supprimant
 * les événements plus anciens que la période de rétention configurée.
 *
 * Usage:
 *   php artisan hikvision:prune-events              # Purge selon config
 *   php artisan hikvision:prune-events --days=90   # Garde les 90 derniers jours
 *   php artisan hikvision:prune-events --dry-run   # Affiche sans supprimer
 */
class PruneAlarmEventsCommand extends Command
{
    protected $signature = 'hikvision:prune-events
                            {--days= : Nombre de jours à conserver (défaut: config)}
                            {--keep-alerts : Conserver les événements liés à des alertes}
                            {--dry-run : Affiche le nombre sans supprimer}
                            {--chunk=1000 : Taille des chunks pour la suppression}';

    protected $description = 'Purge les anciens événements d\'alarme';

    public function handle(): int
    {
        $days = (int) ($this->option('days') ?: config('hikvision.events.retention_days', 365));
        $keepAlerts = $this->option('keep-alerts');
        $dryRun = $this->option('dry-run');
        $chunkSize = (int) $this->option('chunk');

        $cutoffDate = now()->subDays($days);

        $this->info("Pruning events older than {$days} days (before {$cutoffDate->toDateString()})");

        // Build query
        $query = AlarmEvent::where('triggered_at', '<', $cutoffDate);

        if ($keepAlerts) {
            $query->whereNull('alert_uuid');
            $this->info("Keeping events with alerts");
        }

        $count = $query->count();

        if ($count === 0) {
            $this->info("No events to prune");
            return self::SUCCESS;
        }

        $this->warn("Found {$count} events to delete");

        if ($dryRun) {
            $this->info("[DRY-RUN] Would delete {$count} events");

            // Show breakdown
            $breakdown = AlarmEvent::where('triggered_at', '<', $cutoffDate)
                ->when($keepAlerts, fn($q) => $q->whereNull('alert_uuid'))
                ->selectRaw('alarm_type, count(*) as count')
                ->groupBy('alarm_type')
                ->pluck('count', 'alarm_type');

            $this->table(
                ['Type', 'Count'],
                $breakdown->map(fn($count, $type) => [$type ?? 'null', $count])->values()->toArray()
            );

            return self::SUCCESS;
        }

        if (!$this->confirm("Delete {$count} events?")) {
            $this->info("Cancelled");
            return self::SUCCESS;
        }

        // Delete in chunks to avoid memory issues
        $deleted = 0;
        $progressBar = $this->output->createProgressBar((int) ceil($count / $chunkSize));
        $progressBar->start();

        do {
            $chunkDeleted = AlarmEvent::where('triggered_at', '<', $cutoffDate)
                ->when($keepAlerts, fn($q) => $q->whereNull('alert_uuid'))
                ->limit($chunkSize)
                ->delete();

            $deleted += $chunkDeleted;
            $progressBar->advance();

        } while ($chunkDeleted > 0);

        $progressBar->finish();
        $this->newLine(2);

        $this->info("✓ Deleted {$deleted} events");

        Log::info('hikvision:prune-events completed', [
            'deleted' => $deleted,
            'cutoff_date' => $cutoffDate->toDateString(),
            'kept_alerts' => $keepAlerts,
        ]);

        return self::SUCCESS;
    }
}
