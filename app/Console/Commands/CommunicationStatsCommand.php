<?php

namespace App\Console\Commands;

use App\Models\Communication;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

/**
 * Commande pour afficher les statistiques des communications
 *
 * Usage :
 *   php artisan communication:stats
 *   php artisan communication:stats --days=7
 *   php artisan communication:stats --channel=email
 */
class CommunicationStatsCommand extends Command
{
    /**
     * Signature de la commande
     */
    protected $signature = 'communication:stats
                            {--days=30 : Nombre de jours à analyser}
                            {--channel= : Filtrer par canal (email, sms, whatsapp, other)}';

    /**
     * Description de la commande
     */
    protected $description = 'Afficher les statistiques des communications par canal';

    /**
     * Exécuter la commande
     */
    public function handle(): int
    {
        $days = (int) $this->option('days');
        $channel = $this->option('channel');

        $this->info("📊 Statistiques des communications");
        $this->info("   Période : {$days} derniers jours");
        $this->newLine();

        $startDate = now()->subDays($days);

        // Stats générales
        $this->displayGeneralStats($startDate, $channel);

        // Stats par canal
        if (!$channel) {
            $this->displayChannelStats($startDate);
        }

        // Stats par statut
        $this->displayStatusStats($startDate, $channel);

        // Stats par provider
        $this->displayProviderStats($startDate, $channel);

        // Évolution quotidienne
        $this->displayDailyStats($startDate, $channel);

        return self::SUCCESS;
    }

    /**
     * Afficher les statistiques générales
     */
    protected function displayGeneralStats(\DateTime $startDate, ?string $channel): void
    {
        $query = Communication::where('sent_at', '>=', $startDate);

        if ($channel) {
            $query->byChannel($channel);
        }

        $total = $query->count();
        $sent = (clone $query)->sent()->count();
        $failed = (clone $query)->failed()->count();
        $pending = (clone $query)->pending()->count();

        $successRate = $total > 0 ? round(($sent / $total) * 100, 1) : 0;

        $this->info("📈 Résumé général");
        $this->table(
            ['Métrique', 'Valeur'],
            [
                ['Total communications', number_format($total)],
                ['Envoyées/Délivrées', number_format($sent) . " ({$successRate}%)"],
                ['Échouées', number_format($failed)],
                ['En attente', number_format($pending)],
            ]
        );
        $this->newLine();
    }

    /**
     * Afficher les statistiques par canal
     */
    protected function displayChannelStats(\DateTime $startDate): void
    {
        $stats = Communication::where('sent_at', '>=', $startDate)
            ->select('channel', DB::raw('count(*) as total'))
            ->groupBy('channel')
            ->orderByDesc('total')
            ->get();

        $rows = $stats->map(function ($stat) {
            $icon = match($stat->channel) {
                'email' => '📧',
                'sms' => '💬',
                'whatsapp' => '📱',
                'phone' => '📞',
                default => '📝',
            };
            return [$icon . ' ' . ucfirst($stat->channel), number_format($stat->total)];
        })->toArray();

        if (count($rows) > 0) {
            $this->info("📡 Par canal");
            $this->table(['Canal', 'Total'], $rows);
            $this->newLine();
        }
    }

    /**
     * Afficher les statistiques par statut
     */
    protected function displayStatusStats(\DateTime $startDate, ?string $channel): void
    {
        $query = Communication::where('sent_at', '>=', $startDate);

        if ($channel) {
            $query->byChannel($channel);
        }

        $stats = $query
            ->select('status', DB::raw('count(*) as total'))
            ->groupBy('status')
            ->orderByDesc('total')
            ->get();

        $rows = $stats->map(function ($stat) {
            $icon = match($stat->status) {
                'sent' => '✈️',
                'delivered' => '✅',
                'failed' => '❌',
                'pending' => '⏳',
                default => '❓',
            };
            return [$icon . ' ' . ucfirst($stat->status ?? 'N/A'), number_format($stat->total)];
        })->toArray();

        if (count($rows) > 0) {
            $this->info("📋 Par statut");
            $this->table(['Statut', 'Total'], $rows);
            $this->newLine();
        }
    }

    /**
     * Afficher les statistiques par provider
     */
    protected function displayProviderStats(\DateTime $startDate, ?string $channel): void
    {
        $query = Communication::where('sent_at', '>=', $startDate)
            ->whereNotNull('provider');

        if ($channel) {
            $query->byChannel($channel);
        }

        $stats = $query
            ->select('provider', DB::raw('count(*) as total'))
            ->groupBy('provider')
            ->orderByDesc('total')
            ->get();

        $rows = $stats->map(function ($stat) {
            return [ucfirst($stat->provider), number_format($stat->total)];
        })->toArray();

        if (count($rows) > 0) {
            $this->info("🔌 Par provider");
            $this->table(['Provider', 'Total'], $rows);
            $this->newLine();
        }
    }

    /**
     * Afficher l'évolution quotidienne
     */
    protected function displayDailyStats(\DateTime $startDate, ?string $channel): void
    {
        $query = Communication::where('sent_at', '>=', $startDate);

        if ($channel) {
            $query->byChannel($channel);
        }

        $stats = $query
            ->select(
                DB::raw('DATE(sent_at) as date'),
                DB::raw('count(*) as total')
            )
            ->groupBy('date')
            ->orderBy('date')
            ->limit(14) // Derniers 14 jours max
            ->get();

        if ($stats->count() > 0) {
            $this->info("📅 Évolution quotidienne (14 derniers jours)");

            $maxTotal = $stats->max('total');
            $barWidth = 40;

            foreach ($stats as $stat) {
                $barLength = $maxTotal > 0
                    ? (int) round(($stat->total / $maxTotal) * $barWidth)
                    : 0;
                $bar = str_repeat('█', $barLength) . str_repeat('░', $barWidth - $barLength);
                $this->line(sprintf(
                    "  %s │ %s │ %s",
                    $stat->date,
                    $bar,
                    number_format($stat->total)
                ));
            }
            $this->newLine();
        }
    }
}
