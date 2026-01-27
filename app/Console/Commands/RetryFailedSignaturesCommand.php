<?php

namespace App\Console\Commands;

use App\Models\Signature;
use App\Services\SignatureService;
use Illuminate\Console\Command;

/**
 * Commande pour retraiter les signatures DocuSign en échec
 *
 * Usage:
 *   php artisan docusign:retry-failed          # Retraite toutes les signatures en échec
 *   php artisan docusign:retry-failed --limit=10  # Limite à 10 signatures
 *   php artisan docusign:retry-failed --envelope=xxx  # Retraite une enveloppe spécifique
 */
class RetryFailedSignaturesCommand extends Command
{
    protected $signature = 'docusign:retry-failed
                            {--limit=50 : Nombre maximum de signatures à retraiter}
                            {--envelope= : ID d\'enveloppe spécifique à retraiter}
                            {--async : Dispatcher les jobs de manière asynchrone}';

    protected $description = 'Retraite les signatures DocuSign qui ont échoué lors du téléchargement';

    public function handle(SignatureService $signatureService): int
    {
        $envelopeId = $this->option('envelope');
        $limit = (int) $this->option('limit');
        $async = $this->option('async');

        if ($envelopeId) {
            return $this->retrySpecificEnvelope($signatureService, $envelopeId, $async);
        }

        return $this->retryFailedSignatures($signatureService, $limit, $async);
    }

    protected function retrySpecificEnvelope(
        SignatureService $signatureService,
        string $envelopeId,
        bool $async
    ): int {
        $signature = Signature::where('provider', 'docusign')
            ->where('provider_envelope_id', $envelopeId)
            ->first();

        if (!$signature) {
            $this->error("Signature not found for envelope: {$envelopeId}");
            return self::FAILURE;
        }

        $this->info("Processing envelope: {$envelopeId}");

        if ($async) {
            $signatureService->dispatchProcessingJob(
                $signature,
                $signature->webhook_payload ?? [],
                'docusign'
            );
            $this->info("Job dispatched for envelope: {$envelopeId}");
        } else {
            $success = $signatureService->retryProcessingCompletion($signature);

            if ($success) {
                $this->info("Successfully processed envelope: {$envelopeId}");
            } else {
                $this->error("Failed to process envelope: {$envelopeId}");
                return self::FAILURE;
            }
        }

        return self::SUCCESS;
    }

    protected function retryFailedSignatures(
        SignatureService $signatureService,
        int $limit,
        bool $async
    ): int {
        // Trouver les signatures complétées mais sans fichier téléchargé
        $failedSignatures = Signature::where('provider', 'docusign')
            ->where('provider_status', 'completed')
            ->where(function ($query) {
                $query->whereNull('signature_file')
                    ->orWhere('signature_file', '');
            })
            ->limit($limit)
            ->get();

        if ($failedSignatures->isEmpty()) {
            $this->info('No failed signatures found to retry.');
            return self::SUCCESS;
        }

        $this->info("Found {$failedSignatures->count()} failed signatures to retry.");

        $successCount = 0;
        $failCount = 0;

        $bar = $this->output->createProgressBar($failedSignatures->count());
        $bar->start();

        foreach ($failedSignatures as $signature) {
            if ($async) {
                $signatureService->dispatchProcessingJob(
                    $signature,
                    $signature->webhook_payload ?? [],
                    'docusign'
                );
                $successCount++;
            } else {
                $success = $signatureService->retryProcessingCompletion($signature);
                if ($success) {
                    $successCount++;
                } else {
                    $failCount++;
                }
            }

            $bar->advance();
        }

        $bar->finish();
        $this->newLine();

        $this->info("Completed: {$successCount} successful, {$failCount} failed");

        return $failCount > 0 ? self::FAILURE : self::SUCCESS;
    }
}

