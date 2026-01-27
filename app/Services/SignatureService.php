<?php

namespace App\Services;

use App\Enums\ClientStatus;
use App\Enums\ContractStatus;
use App\Jobs\ProcessSignatureCompletionJob;
use App\Models\Client;
use App\Models\Contract;
use App\Models\File;
use App\Models\Signature;
use Exception;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

/**
 * Service de gestion des signatures électroniques
 *
 * Ce service orchestre le flux de signature:
 * - Réception et validation des webhooks
 * - Téléchargement des documents signés
 * - Stockage des fichiers via FileService
 * - Mise à jour des statuts Contract/Client
 */
class SignatureService
{
    protected DocuSignService $docuSignService;
    protected FileService $fileService;

    public function __construct(DocuSignService $docuSignService, FileService $fileService)
    {
        $this->docuSignService = $docuSignService;
        $this->fileService = $fileService;
    }

    /**
     * Valide la signature HMAC d'un webhook
     *
     * @param string $provider Le fournisseur de signature
     * @param string $rawPayload Le body brut de la requête
     * @param string|null $signature La signature du header
     * @return bool True si valide
     * @throws Exception Si la validation échoue
     */
    public function validateWebhookSignature(string $provider, string $rawPayload, ?string $signature): bool
    {
        if (empty($signature)) {
            Log::warning("SignatureService: Missing webhook signature for provider {$provider}");
            // En production, on devrait rejeter les webhooks sans signature
            return config('app.env') !== 'production';
        }

        return match ($provider) {
            'docusign' => $this->docuSignService->validateWebhookSignature($rawPayload, $signature),
            default => true,
        };
    }

    /**
     * Handle incoming webhook from a signature provider
     *
     * @param string $provider
     * @param array $payload
     * @param string|null $rawPayload Le body brut pour validation de signature
     * @param string|null $webhookSignature La signature du header pour validation
     * @return Signature
     * @throws Exception Si le traitement échoue
     */
    public function handleWebhook(
        string $provider,
        array $payload,
        ?string $rawPayload = null,
        ?string $webhookSignature = null
    ): Signature {
        Log::info("SignatureService: Webhook received from {$provider}", [
            'event' => $this->extractEvent($provider, $payload)
        ]);

        // Valider la signature du webhook si fournie
        if ($rawPayload && $webhookSignature) {
            if (!$this->validateWebhookSignature($provider, $rawPayload, $webhookSignature)) {
                throw new Exception("Invalid webhook signature for provider: {$provider}");
            }
        }

        // Extract provider-specific data
        $envelopeId = $this->extractEnvelopeId($provider, $payload);
        $status = $this->extractStatus($provider, $payload);
        $event = $this->extractEvent($provider, $payload);

        if (empty($envelopeId)) {
            throw new Exception("Missing envelope ID in webhook payload");
        }

        // Find or create signature record
        $signature = Signature::updateOrCreate(
            [
                'provider' => $provider,
                'provider_envelope_id' => $envelopeId,
            ],
            [
                'provider_status' => $status,
                'webhook_payload' => $payload,
                'webhook_received_at' => now(),
            ]
        );

        Log::info("SignatureService: Signature record updated", [
            'signature_id' => $signature->id,
            'envelope_id' => $envelopeId,
            'status' => $status,
            'event' => $event
        ]);

        // If the document is signed/completed, process it
        if ($this->isCompleted($provider, $payload, $status)) {
            $this->processSignatureCompleted($signature, $payload, $provider);
        }

        return $signature;
    }

    /**
     * Extract Envelope ID based on provider
     */
    public function extractEnvelopeId(string $provider, array $payload): ?string
    {
        return match ($provider) {
            'docusign' => $payload['data']['envelopeId'] ?? $payload['envelopeId'] ?? null,
            default => null,
        };
    }

    /**
     * Extract Event based on provider
     */
    public function extractEvent(string $provider, array $payload): ?string
    {
        return match ($provider) {
            'docusign' => $payload['event'] ?? 'unknown',
            default => 'unknown',
        };
    }

    /**
     * Extract Status based on provider
     */
    public function extractStatus(string $provider, array $payload): ?string
    {
        return match ($provider) {
            'docusign' => $payload['data']['envelopeSummary']['status']
                          ?? $payload['data']['event']
                          ?? 'unknown',
            default => 'unknown',
        };
    }

    /**
     * Check if signature is completed based on provider and status
     */
    public function isCompleted(string $provider, array $payload, ?string $status): bool
    {
        return match ($provider) {
            'docusign' => $status === 'completed' || ($payload['event'] ?? '') === 'envelope-completed',
            default => false,
        };
    }

    /**
     * Process signature completed event
     *
     * Cette méthode:
     * 1. Télécharge les documents signés depuis le provider
     * 2. Les stocke via FileService
     * 3. Met à jour la Signature avec les références des fichiers
     * 4. Met à jour les statuts Contract et Client
     *
     * @param Signature $signature
     * @param array $payload
     * @param string $provider
     */
    public function processSignatureCompleted(Signature $signature, array $payload, string $provider = 'docusign'): void
    {
        try {
            Log::info('SignatureService: Processing signature completion', [
                'signature_id' => $signature->id,
                'envelope_id' => $signature->provider_envelope_id,
                'provider' => $provider
            ]);

            // Update the signature timestamp if not set
            if (!$signature->signed_at) {
                $signature->update(['signed_at' => now()]);
            }

            // Télécharger et stocker les documents signés
            $uploadedFiles = $this->downloadAndStoreSignedDocuments($signature, $provider);

            // Mettre à jour la signature avec le fichier principal
            if (!empty($uploadedFiles)) {
                $primaryFile = $uploadedFiles[0];
                $signature->update([
                    'signature_file' => $primaryFile->file_path,
                    'metadata' => array_merge($signature->metadata ?? [], [
                        'downloaded_files' => collect($uploadedFiles)->map(fn($f) => [
                            'uuid' => $f->uuid,
                            'name' => $f->file_name,
                            'path' => $f->file_path
                        ])->toArray(),
                        'download_completed_at' => now()->toISOString(),
                    ])
                ]);

                Log::info('SignatureService: Documents stored successfully', [
                    'signature_id' => $signature->id,
                    'file_count' => count($uploadedFiles)
                ]);
            }

            // Find associated signable (Contract, etc.)
            $contract = $signature->signable;
            $client = $signature->signableBy;

            if ($contract instanceof Contract) {
                $contract->update(['status' => ContractStatus::SIGNED->value]);
                Log::info('SignatureService: Contract status updated to signed', [
                    'contract_uuid' => $contract->uuid
                ]);
            }

            if ($client instanceof Client) {
                $client->update(['status' => ClientStatus::SIGNED->value]);
                Log::info('SignatureService: Client status updated to signed', [
                    'client_uuid' => $client->uuid
                ]);
            }

            Log::info('SignatureService: Signature completed processed successfully', [
                'signature_id' => $signature->id,
                'envelope_id' => $signature->provider_envelope_id,
                'contract_uuid' => $contract?->uuid ?? 'N/A',
                'client_uuid' => $client?->uuid ?? 'N/A'
            ]);

        } catch (Exception $e) {
            Log::error('SignatureService: Error processing signature completion', [
                'signature_id' => $signature->id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            // Mettre à jour la signature avec l'erreur dans les metadata
            $signature->update([
                'metadata' => array_merge($signature->metadata ?? [], [
                    'processing_error' => $e->getMessage(),
                    'processing_failed_at' => now()->toISOString(),
                ])
            ]);

            throw $e;
        }
    }

    /**
     * Télécharge les documents signés et les stocke via FileService
     *
     * @param Signature $signature
     * @param string $provider
     * @return File[] Array de fichiers uploadés
     */
    protected function downloadAndStoreSignedDocuments(Signature $signature, string $provider): array
    {
        $uploadedFiles = [];
        $envelopeId = $signature->provider_envelope_id;
        $signable = $signature->signable;

        if (empty($envelopeId)) {
            Log::warning('SignatureService: No envelope ID to download documents', [
                'signature_id' => $signature->id
            ]);
            return [];
        }

        try {
            if ($provider === 'docusign') {
                $uploadedFiles = $this->downloadDocuSignDocuments($envelopeId, $signable, $signature);
            }
            // Ajouter d'autres providers ici si nécessaire

        } catch (Exception $e) {
            Log::error('SignatureService: Failed to download signed documents', [
                'signature_id' => $signature->id,
                'envelope_id' => $envelopeId,
                'provider' => $provider,
                'error' => $e->getMessage()
            ]);
            throw $e;
        }

        return $uploadedFiles;
    }

    /**
     * Télécharge et stocke les documents DocuSign
     *
     * @param string $envelopeId
     * @param mixed $signable Le modèle associé (Contract, etc.)
     * @param Signature $signature
     * @return File[]
     */
    protected function downloadDocuSignDocuments(string $envelopeId, $signable, Signature $signature): array
    {
        $uploadedFiles = [];

        // Le fichier doit être lié au Contract (signable), pas au Client
        // Si signable n'existe pas, on lie à la Signature en fallback
        $fileable = $signable;

        if (!$fileable) {
            Log::warning('SignatureService: No signable (Contract) found, linking file to Signature', [
                'signature_id' => $signature->id,
                'envelope_id' => $envelopeId
            ]);
            $fileable = $signature;
        } else {
            Log::info('SignatureService: File will be linked to Contract', [
                'contract_uuid' => $signable->uuid ?? 'unknown',
                'envelope_id' => $envelopeId
            ]);
        }

        // Option 1: Télécharger le document combiné (tous les documents en un PDF)
        try {
            $combinedContent = $this->docuSignService->downloadDocument($envelopeId, 'combined');

            if (!empty($combinedContent)) {
                $file = $this->storeDocumentFromBinaryContent(
                    $combinedContent,
                    "contrat_signe_{$envelopeId}.pdf",
                    $fileable,
                    'signed_contract'
                );

                if ($file) {
                    $uploadedFiles[] = $file;
                }
            }
        } catch (Exception $e) {
            Log::warning('SignatureService: Failed to download combined document, trying individual', [
                'envelope_id' => $envelopeId,
                'error' => $e->getMessage()
            ]);

            // Option 2: Fallback - télécharger les documents individuellement
            $documents = $this->docuSignService->downloadAllDocuments($envelopeId);

            foreach ($documents as $doc) {
                $file = $this->storeDocumentFromBinaryContent(
                    $doc['content'],
                    $doc['name'] . '.pdf',
                    $fileable,
                    'signed_document'
                );

                if ($file) {
                    $uploadedFiles[] = $file;
                }
            }
        }

        return $uploadedFiles;
    }

    /**
     * Convertit le contenu binaire en UploadedFile et le stocke
     *
     * @param string $binaryContent Le contenu binaire du fichier
     * @param string $filename Le nom du fichier
     * @param mixed $fileable Le modèle auquel attacher le fichier
     * @param string $type Le type de fichier
     * @return File|null
     */
    protected function storeDocumentFromBinaryContent(
        string $binaryContent,
        string $filename,
        $fileable,
        string $type = 'signed_document'
    ): ?File {
        try {
            // Créer un fichier temporaire
            $tempPath = sys_get_temp_dir() . '/' . Str::uuid() . '_' . $filename;
            file_put_contents($tempPath, $binaryContent);

            // Créer un UploadedFile à partir du fichier temporaire
            $uploadedFile = new UploadedFile(
                $tempPath,
                $filename,
                'application/pdf',
                null,
                true // test mode: permet d'utiliser move() au lieu de move_uploaded_file()
            );

            // Utiliser FileService pour stocker le fichier
            $file = $this->fileService->uploadFile(
                $uploadedFile,
                $fileable,
                $type,
                "Document signé - {$filename}"
            );

            Log::info('SignatureService: Document stored successfully', [
                'file_uuid' => $file->uuid,
                'filename' => $filename,
                'size' => strlen($binaryContent)
            ]);

            // Nettoyer le fichier temporaire si encore présent
            if (file_exists($tempPath)) {
                unlink($tempPath);
            }

            return $file;

        } catch (Exception $e) {
            Log::error('SignatureService: Failed to store document', [
                'filename' => $filename,
                'error' => $e->getMessage()
            ]);

            // Nettoyer le fichier temporaire en cas d'erreur
            if (isset($tempPath) && file_exists($tempPath)) {
                unlink($tempPath);
            }

            return null;
        }
    }

    /**
     * Récupère une signature par son envelope ID
     *
     * @param string $provider
     * @param string $envelopeId
     * @return Signature|null
     */
    public function findByEnvelopeId(string $provider, string $envelopeId): ?Signature
    {
        return Signature::where('provider', $provider)
            ->where('provider_envelope_id', $envelopeId)
            ->first();
    }

    /**
     * Relance le traitement d'une signature complétée
     * Utile pour les cas où le téléchargement initial a échoué
     *
     * @param Signature $signature
     * @return bool
     */
    public function retryProcessingCompletion(Signature $signature): bool
    {
        if ($signature->provider_status !== 'completed') {
            Log::warning('SignatureService: Cannot retry - signature not completed', [
                'signature_id' => $signature->id,
                'status' => $signature->provider_status
            ]);
            return false;
        }

        try {
            $this->processSignatureCompleted(
                $signature,
                $signature->webhook_payload ?? [],
                $signature->provider
            );
            return true;
        } catch (Exception $e) {
            Log::error('SignatureService: Retry failed', [
                'signature_id' => $signature->id,
                'error' => $e->getMessage()
            ]);
            return false;
        }
    }

    /**
     * Dispatch le traitement d'une signature de manière asynchrone
     *
     * @param Signature $signature
     * @param array $payload
     * @param string $provider
     * @return void
     */
    public function dispatchProcessingJob(Signature $signature, array $payload = [], string $provider = 'docusign'): void
    {
        ProcessSignatureCompletionJob::dispatch($signature, $payload, $provider);

        Log::info('SignatureService: Processing job dispatched', [
            'signature_id' => $signature->id,
            'envelope_id' => $signature->provider_envelope_id
        ]);
    }

    /**
     * Traite un webhook de manière asynchrone (via job queue)
     *
     * @param string $provider
     * @param array $payload
     * @param string|null $rawPayload
     * @param string|null $webhookSignature
     * @return Signature
     */
    public function handleWebhookAsync(
        string $provider,
        array $payload,
        ?string $rawPayload = null,
        ?string $webhookSignature = null
    ): Signature {
        Log::info("SignatureService: Async webhook received from {$provider}", [
            'event' => $this->extractEvent($provider, $payload)
        ]);

        // Valider la signature du webhook si fournie
        if ($rawPayload && $webhookSignature) {
            if (!$this->validateWebhookSignature($provider, $rawPayload, $webhookSignature)) {
                throw new Exception("Invalid webhook signature for provider: {$provider}");
            }
        }

        // Extract provider-specific data
        $envelopeId = $this->extractEnvelopeId($provider, $payload);
        $status = $this->extractStatus($provider, $payload);

        if (empty($envelopeId)) {
            throw new Exception("Missing envelope ID in webhook payload");
        }

        // Find or create signature record
        $signature = Signature::updateOrCreate(
            [
                'provider' => $provider,
                'provider_envelope_id' => $envelopeId,
            ],
            [
                'provider_status' => $status,
                'webhook_payload' => $payload,
                'webhook_received_at' => now(),
            ]
        );

        // If the document is signed/completed, dispatch async processing
        if ($this->isCompleted($provider, $payload, $status)) {
            $this->dispatchProcessingJob($signature, $payload, $provider);
        }

        return $signature;
    }
}
