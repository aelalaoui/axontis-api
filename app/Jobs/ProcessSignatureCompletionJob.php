<?php

namespace App\Jobs;

use App\Models\Signature;
use App\Services\SignatureService;
use Exception;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

/**
 * Job pour traiter les signatures complétées
 *
 * Ce job est utilisé pour:
 * - Traitement asynchrone des webhooks
 * - Retry automatique en cas d'échec temporaire
 * - Téléchargement des documents signés depuis DocuSign
 */
class ProcessSignatureCompletionJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * Nombre maximum de tentatives
     */
    public int $tries = 5;

    /**
     * Délai avant le premier retry (en secondes)
     */
    public int $backoff = 30;

    /**
     * Les tentatives suivantes utilisent un délai exponentiel
     *
     * @return int[]
     */
    public function backoff(): array
    {
        return [30, 60, 120, 300, 600]; // 30s, 1m, 2m, 5m, 10m
    }

    /**
     * Temps maximum d'exécution (en secondes)
     */
    public int $timeout = 120;

    protected Signature $signature;
    protected array $payload;
    protected string $provider;

    /**
     * Create a new job instance.
     */
    public function __construct(Signature $signature, array $payload = [], string $provider = 'docusign')
    {
        $this->signature = $signature;
        $this->payload = $payload;
        $this->provider = $provider;
    }

    /**
     * Execute the job.
     */
    public function handle(SignatureService $signatureService): void
    {
        Log::info('ProcessSignatureCompletionJob: Starting', [
            'signature_id' => $this->signature->id,
            'envelope_id' => $this->signature->provider_envelope_id,
            'attempt' => $this->attempts()
        ]);

        try {
            $signatureService->processSignatureCompleted(
                $this->signature,
                $this->payload,
                $this->provider
            );

            Log::info('ProcessSignatureCompletionJob: Completed successfully', [
                'signature_id' => $this->signature->id
            ]);

        } catch (Exception $e) {
            Log::error('ProcessSignatureCompletionJob: Failed', [
                'signature_id' => $this->signature->id,
                'attempt' => $this->attempts(),
                'error' => $e->getMessage()
            ]);

            // Re-throw pour que le job soit réessayé
            throw $e;
        }
    }

    /**
     * Handle a job failure.
     */
    public function failed(Exception $exception): void
    {
        Log::error('ProcessSignatureCompletionJob: All attempts failed', [
            'signature_id' => $this->signature->id,
            'envelope_id' => $this->signature->provider_envelope_id,
            'error' => $exception->getMessage()
        ]);

        // Mettre à jour la signature avec l'erreur finale
        $this->signature->update([
            'metadata' => array_merge($this->signature->metadata ?? [], [
                'job_failed' => true,
                'job_failed_at' => now()->toISOString(),
                'job_failure_reason' => $exception->getMessage(),
            ])
        ]);

        // Ici on pourrait envoyer une notification à l'équipe
        // Notification::route('slack', config('services.slack.webhook'))
        //     ->notify(new SignatureProcessingFailed($this->signature, $exception));
    }

    /**
     * Determine the unique ID for this job.
     */
    public function uniqueId(): string
    {
        return 'signature_completion_' . $this->signature->provider_envelope_id;
    }

    /**
     * Get the tags that should be assigned to the job.
     *
     * @return array<int, string>
     */
    public function tags(): array
    {
        return [
            'signature',
            'docusign',
            'envelope:' . $this->signature->provider_envelope_id,
        ];
    }
}

