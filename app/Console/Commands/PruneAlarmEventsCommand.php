<?php

namespace App\Console\Commands;

use App\Models\AlarmEvent;
use Illuminate\Console\Command;

/**
 * Purge les événements alarme plus anciens que la période de rétention.
 *
 * Par défaut : 365 jours (configurable via HIKVISION_EVENT_RETENTION_DAYS).
 */
class PruneAlarmEventsCommand extends Command
{
    protected $signature = 'alarm:prune-events
                            {--days= : Nombre de jours de rétention (défaut: config)}
                            {--dry-run : Affiche le nombre sans supprimer}';

    protected $description = 'Supprime les événements alarme plus anciens que la période de rétention';

    public function handle(): int
    {
        $retentionDays = $this->option('days')
            ?? config('hikvision.events.retention_days', 365);

        $cutoffDate = now()->subDays((int) $retentionDays);

        $query = AlarmEvent::where('triggered_at', '<', $cutoffDate)
            ->where('processed', true);

        $count = $query->count();

        if ($count === 0) {
            $this->info('Aucun événement à purger.');
            return self::SUCCESS;
        }

        if ($this->option('dry-run')) {
            $this->info("[DRY RUN] {$count} événement(s) seraient supprimés (antérieurs au {$cutoffDate->format('d/m/Y')}).");
            return self::SUCCESS;
        }

        $this->info("Suppression de {$count} événement(s) antérieurs au {$cutoffDate->format('d/m/Y')}...");

        // Supprimer par chunks pour ne pas surcharger la mémoire
        $deleted = 0;
        AlarmEvent::where('triggered_at', '<', $cutoffDate)
            ->where('processed', true)
            ->chunkById(1000, function ($events) use (&$deleted) {
                $ids = $events->pluck('id')->toArray();
                AlarmEvent::whereIn('id', $ids)->delete();
                $deleted += count($ids);
            }, 'id');

        $this->info("✅ {$deleted} événement(s) purgé(s).");

        return self::SUCCESS;
    }
}

