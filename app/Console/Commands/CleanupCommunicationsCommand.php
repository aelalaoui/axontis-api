<?php

namespace App\Console\Commands;

use App\Models\Communication;
use Illuminate\Console\Command;

/**
 * Commande pour nettoyer les anciennes communications
 *
 * Usage :
 *   php artisan communication:cleanup
 *   php artisan communication:cleanup --days=365
 *   php artisan communication:cleanup --dry-run
 */
class CleanupCommunicationsCommand extends Command
{
    /**
     * Signature de la commande
     */
    protected $signature = 'communication:cleanup
                            {--days=365 : Supprimer les communications plus anciennes que X jours}
                            {--dry-run : Simuler sans supprimer}
                            {--keep-failed : Conserver les communications échouées}';

    /**
     * Description de la commande
     */
    protected $description = 'Nettoyer les anciennes communications de la base de données';

    /**
     * Exécuter la commande
     */
    public function handle(): int
    {
        $days = (int) $this->option('days');
        $dryRun = $this->option('dry-run');
        $keepFailed = $this->option('keep-failed');

        $this->info("🧹 Nettoyage des communications");
        $this->info("   Plus anciennes que : {$days} jours");

        if ($dryRun) {
            $this->warn("   Mode simulation (dry-run) activé");
        }

        if ($keepFailed) {
            $this->info("   Conservation des communications échouées : Oui");
        }

        $this->newLine();

        $cutoffDate = now()->subDays($days);

        // Compter les communications à supprimer
        $query = Communication::where('sent_at', '<', $cutoffDate);

        if ($keepFailed) {
            $query->where('status', '!=', Communication::STATUS_FAILED);
        }

        $count = $query->count();

        if ($count === 0) {
            $this->info("✅ Aucune communication à supprimer.");
            return self::SUCCESS;
        }

        $this->warn("📊 Communications à supprimer : {$count}");
        $this->newLine();

        // Afficher un aperçu par canal
        $byChannel = (clone $query)
            ->selectRaw('channel, count(*) as total')
            ->groupBy('channel')
            ->pluck('total', 'channel')
            ->toArray();

        $this->table(
            ['Canal', 'Nombre'],
            collect($byChannel)->map(fn ($total, $channel) => [ucfirst($channel), $total])->values()->toArray()
        );

        if ($dryRun) {
            $this->newLine();
            $this->info("🔍 Mode simulation - Aucune suppression effectuée.");
            $this->line("   Relancez sans --dry-run pour supprimer.");
            return self::SUCCESS;
        }

        // Confirmation
        if (!$this->confirm("Voulez-vous supprimer ces {$count} communications ?")) {
            $this->info("❌ Opération annulée.");
            return self::SUCCESS;
        }

        // Suppression par lots pour éviter les problèmes de mémoire
        $this->info("🗑️  Suppression en cours...");

        $progressBar = $this->output->createProgressBar($count);
        $progressBar->start();

        $deleted = 0;
        $batchSize = 1000;

        while (true) {
            $batch = Communication::where('sent_at', '<', $cutoffDate);

            if ($keepFailed) {
                $batch->where('status', '!=', Communication::STATUS_FAILED);
            }

            $ids = $batch->limit($batchSize)->pluck('id');

            if ($ids->isEmpty()) {
                break;
            }

            Communication::whereIn('id', $ids)->delete();
            $deleted += $ids->count();
            $progressBar->advance($ids->count());
        }

        $progressBar->finish();
        $this->newLine(2);

        $this->info("✅ {$deleted} communications supprimées.");

        return self::SUCCESS;
    }
}
