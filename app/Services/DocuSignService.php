<?php

namespace App\Services;

use App\Models\Client;
use App\Models\Signature;
use DocuSign\eSign\Api\EnvelopesApi;
use DocuSign\eSign\Client\ApiClient;
use DocuSign\eSign\Model\Document;
use DocuSign\eSign\Model\EnvelopeDefinition;
use DocuSign\eSign\Model\RecipientViewRequest;
use DocuSign\eSign\Model\Signer;
use DocuSign\eSign\Model\SignHere;
use DocuSign\eSign\Model\Tabs;
use Exception;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

/**
 * Service DocuSign pour la gestion des signatures électroniques
 *
 * Ce service gère:
 * - L'authentification JWT avec l'API DocuSign
 * - La validation des webhooks (HMAC signature)
 * - La création et l'envoi d'enveloppes pour signature
 * - Le téléchargement des documents signés
 */
class DocuSignService
{
    protected ?ApiClient $apiClient = null;
    protected string $accountId;
    protected int $maxRetries = 3;
    protected int $retryDelayMs = 1000;

    public function __construct()
    {
        $this->accountId = config('services.docusign.account_id');
    }

    /**
     * Initialise le client API (lazy loading)
     * Permet d'utiliser le service pour la validation webhook sans authentification
     */
    protected function initializeApiClient(): void
    {
        if ($this->apiClient !== null) {
            return;
        }

        $this->apiClient = new ApiClient();
        $this->apiClient->getOAuth()->setOAuthBasePath(
            config('services.docusign.oauth_base_path', 'account-d.docusign.com')
        );

        $this->authenticate();
    }

    /**
     * Authenticate using JWT with token caching
     *
     * @throws Exception Si l'authentification échoue
     */
    protected function authenticate(): void
    {
        // Vérifier le cache pour un token existant
        $cachedToken = Cache::get('docusign_access_token');
        if ($cachedToken) {
            $this->apiClient->getConfig()->setAccessToken($cachedToken);
            $this->apiClient->getConfig()->setHost(config('services.docusign.base_path'));
            return;
        }

        try {
            $clientId = config('services.docusign.client_id');
            $userId = config('services.docusign.user_id');
            $rsaKeyPath = config('services.docusign.rsa_key_path');

            if (!file_exists($rsaKeyPath)) {
                throw new Exception("DocuSign RSA key file not found at: {$rsaKeyPath}");
            }

            $rsaKey = file_get_contents($rsaKeyPath);
            $scopes = ['signature', 'impersonation'];

            $token = $this->apiClient->requestJWTUserToken(
                $clientId,
                $userId,
                $rsaKey,
                $scopes,
                3600
            );

            $accessToken = $token[0]['access_token'];
            $expiresIn = $token[0]['expires_in'] ?? 3600;

            // Mettre en cache le token (avec une marge de 5 minutes)
            Cache::put('docusign_access_token', $accessToken, now()->addSeconds($expiresIn - 300));

            $this->apiClient->getConfig()->setAccessToken($accessToken);
            $this->apiClient->getConfig()->setHost(config('services.docusign.base_path'));

            Log::info('DocuSign: Authentication successful');

        } catch (Exception $e) {
            // Vérifier si c'est une erreur de consentement
            if (strpos($e->getMessage(), 'consent_required') !== false) {
                $consentUrl = $this->getConsentUrl();
                Log::error('DocuSign Consent Required. Please visit: ' . $consentUrl);
                throw new Exception(
                    "DocuSign consent required. Please visit this URL to grant consent: {$consentUrl}\n\n" .
                    "After granting consent, try again. This is a one-time setup step."
                );
            }

            Log::error('DocuSign Authentication Failed: ' . $e->getMessage());
            throw $e;
        }
    }

    /**
     * Valide la signature HMAC du webhook DocuSign
     *
     * DocuSign utilise HMAC-SHA256 pour signer les webhooks.
     * La signature est envoyée dans le header X-DocuSign-Signature-1
     *
     * @param string $payload Le body brut de la requête webhook
     * @param string $signature La signature du header X-DocuSign-Signature-1
     * @return bool True si la signature est valide
     */
    public function validateWebhookSignature(string $payload, string $signature): bool
    {
        $connectKey = config('services.docusign.hmac_key');

        if (empty($connectKey)) {
            Log::warning('DocuSign: HMAC key not configured, skipping webhook validation');
            return true; // En développement, on peut ignorer la validation
        }

        // DocuSign envoie la signature en base64
        $expectedSignature = base64_encode(
            hash_hmac('sha256', $payload, $connectKey, true)
        );

        $isValid = hash_equals($expectedSignature, $signature);

        if (!$isValid) {
            Log::warning('DocuSign: Invalid webhook signature', [
                'expected' => substr($expectedSignature, 0, 10) . '...',
                'received' => substr($signature, 0, 10) . '...'
            ]);
        }

        return $isValid;
    }

    /**
     * Récupère la liste des documents d'une enveloppe
     *
     * @param string $envelopeId L'ID de l'enveloppe DocuSign
     * @return array Liste des documents avec leurs métadonnées
     * @throws Exception Si la récupération échoue
     */
    public function getEnvelopeDocuments(string $envelopeId): array
    {
        $this->initializeApiClient();

        return $this->executeWithRetry(function () use ($envelopeId) {
            $envelopesApi = new EnvelopesApi($this->apiClient);
            $documentsResponse = $envelopesApi->listDocuments($this->accountId, $envelopeId);

            $documents = [];
            foreach ($documentsResponse->getEnvelopeDocuments() as $doc) {
                $documents[] = [
                    'document_id' => $doc->getDocumentId(),
                    'name' => $doc->getName(),
                    'type' => $doc->getType(),
                    'uri' => $doc->getUri(),
                ];
            }

            Log::info('DocuSign: Retrieved envelope documents', [
                'envelope_id' => $envelopeId,
                'document_count' => count($documents)
            ]);

            return $documents;
        }, 'getEnvelopeDocuments');
    }

    /**
     * Télécharge le contenu binaire d'un document signé
     *
     * @param string $envelopeId L'ID de l'enveloppe
     * @param string $documentId L'ID du document (ou 'combined' pour tous les documents)
     * @return string Le contenu binaire du document PDF
     * @throws Exception Si le téléchargement échoue
     */
    public function downloadDocument(string $envelopeId, string $documentId = 'combined'): string
    {
        $this->initializeApiClient();

        return $this->executeWithRetry(function () use ($envelopeId, $documentId) {
            $envelopesApi = new EnvelopesApi($this->apiClient);

            // Télécharger le document
            // L'API retourne un SplFileObject
            $tempFile = $envelopesApi->getDocument(
                $this->accountId,
                $documentId,
                $envelopeId
            );

            // Lire le contenu du fichier temporaire
            $content = file_get_contents($tempFile->getPathname());

            // Nettoyer le fichier temporaire
            if (file_exists($tempFile->getPathname())) {
                unlink($tempFile->getPathname());
            }

            Log::info('DocuSign: Document downloaded successfully', [
                'envelope_id' => $envelopeId,
                'document_id' => $documentId,
                'size' => strlen($content)
            ]);

            return $content;
        }, 'downloadDocument');
    }

    /**
     * Télécharge tous les documents d'une enveloppe
     *
     * @param string $envelopeId L'ID de l'enveloppe
     * @return array Array de documents avec leur contenu et métadonnées
     */
    public function downloadAllDocuments(string $envelopeId): array
    {
        $documentsList = $this->getEnvelopeDocuments($envelopeId);
        $downloadedDocuments = [];

        foreach ($documentsList as $doc) {
            // Ignorer le certificat et le résumé
            if ($doc['type'] === 'summary' || $doc['document_id'] === 'certificate') {
                continue;
            }

            try {
                $content = $this->downloadDocument($envelopeId, $doc['document_id']);
                $downloadedDocuments[] = [
                    'document_id' => $doc['document_id'],
                    'name' => $doc['name'],
                    'content' => $content,
                    'mime_type' => 'application/pdf',
                ];
            } catch (Exception $e) {
                Log::error('DocuSign: Failed to download document', [
                    'envelope_id' => $envelopeId,
                    'document_id' => $doc['document_id'],
                    'error' => $e->getMessage()
                ]);
            }
        }

        return $downloadedDocuments;
    }

    /**
     * Récupère les informations d'une enveloppe
     *
     * @param string $envelopeId L'ID de l'enveloppe
     * @return array Informations de l'enveloppe
     */
    public function getEnvelopeInfo(string $envelopeId): array
    {
        $this->initializeApiClient();

        return $this->executeWithRetry(function () use ($envelopeId) {
            $envelopesApi = new EnvelopesApi($this->apiClient);
            $envelope = $envelopesApi->getEnvelope($this->accountId, $envelopeId);

            return [
                'envelope_id' => $envelope->getEnvelopeId(),
                'status' => $envelope->getStatus(),
                'created_date_time' => $envelope->getCreatedDateTime(),
                'completed_date_time' => $envelope->getCompletedDateTime(),
                'sent_date_time' => $envelope->getSentDateTime(),
                'status_changed_date_time' => $envelope->getStatusChangedDateTime(),
            ];
        }, 'getEnvelopeInfo');
    }

    /**
     * Récupère les destinataires/signataires d'une enveloppe
     *
     * @param string $envelopeId L'ID de l'enveloppe
     * @return array Liste des signataires avec leurs informations
     */
    public function getEnvelopeRecipients(string $envelopeId): array
    {
        $this->initializeApiClient();

        return $this->executeWithRetry(function () use ($envelopeId) {
            $envelopesApi = new EnvelopesApi($this->apiClient);
            $recipients = $envelopesApi->listRecipients($this->accountId, $envelopeId);

            $signers = [];
            foreach ($recipients->getSigners() ?? [] as $signer) {
                $signers[] = [
                    'recipient_id' => $signer->getRecipientId(),
                    'client_user_id' => $signer->getClientUserId(),
                    'name' => $signer->getName(),
                    'email' => $signer->getEmail(),
                    'status' => $signer->getStatus(),
                    'signed_date_time' => $signer->getSignedDateTime(),
                ];
            }

            return $signers;
        }, 'getEnvelopeRecipients');
    }

    /**
     * Exécute une fonction avec logique de retry
     *
     * @param callable $operation L'opération à exécuter
     * @param string $operationName Nom de l'opération pour les logs
     * @return mixed Le résultat de l'opération
     * @throws Exception Si toutes les tentatives échouent
     */
    protected function executeWithRetry(callable $operation, string $operationName): mixed
    {
        $lastException = null;
        $attempt = 0;

        while ($attempt < $this->maxRetries) {
            $attempt++;

            try {
                return $operation();
            } catch (Exception $e) {
                $lastException = $e;
                $errorMessage = $e->getMessage();

                // Erreurs non-récupérables (ne pas réessayer)
                if ($this->isNonRetryableError($e)) {
                    Log::error("DocuSign: Non-retryable error in {$operationName}", [
                        'error' => $errorMessage,
                        'attempt' => $attempt
                    ]);
                    throw $e;
                }

                // Si le token est expiré, le rafraîchir et réessayer
                if ($this->isTokenExpiredError($e)) {
                    Log::warning("DocuSign: Token expired, refreshing...");
                    Cache::forget('docusign_access_token');
                    $this->apiClient = null;
                    $this->initializeApiClient();
                    continue;
                }

                Log::warning("DocuSign: Retrying {$operationName}", [
                    'attempt' => $attempt,
                    'max_retries' => $this->maxRetries,
                    'error' => $errorMessage
                ]);

                // Attendre avant de réessayer (délai exponentiel)
                usleep($this->retryDelayMs * 1000 * $attempt);
            }
        }

        Log::error("DocuSign: All retry attempts failed for {$operationName}", [
            'error' => $lastException?->getMessage()
        ]);

        throw $lastException ?? new Exception("Unknown error in {$operationName}");
    }

    /**
     * Vérifie si l'erreur est non-récupérable
     */
    protected function isNonRetryableError(Exception $e): bool
    {
        $message = strtolower($e->getMessage());
        $nonRetryablePatterns = [
            'invalid envelope',
            'envelope not found',
            'not found',
            'unauthorized',
            'consent_required',
            'invalid_grant',
        ];

        foreach ($nonRetryablePatterns as $pattern) {
            if (strpos($message, $pattern) !== false) {
                return true;
            }
        }

        return false;
    }

    /**
     * Vérifie si l'erreur est liée à un token expiré
     */
    protected function isTokenExpiredError(Exception $e): bool
    {
        $message = strtolower($e->getMessage());
        return strpos($message, 'token') !== false &&
               (strpos($message, 'expired') !== false || strpos($message, 'invalid') !== false);
    }

    /**
     * Generate the consent URL for JWT authentication
     *
     * @return string The consent URL
     */
    public function getConsentUrl(): string
    {
        $clientId = config('services.docusign.client_id');
        $redirectUri = urlencode(config('app.url') . '/docusign/callback');
        $oauthBasePath = config('services.docusign.oauth_base_path', 'account-d.docusign.com');
        $scopes = 'signature%20impersonation';

        return "https://{$oauthBasePath}/oauth/auth?response_type=code&scope={$scopes}&client_id={$clientId}&redirect_uri={$redirectUri}";
    }

    /**
     * Create an envelope and get embedded signing URL
     *
     * @param string $pdfContent Raw PDF content
     * @param string $clientName
     * @param string $clientEmail
     * @param string $clientUuid
     * @param string $returnUrl
     * @param mixed $signable Optional model to link the signature to
     * @return string Signing URL
     */
    public function sendEnvelopeForEmbeddedSigning(
        string $pdfContent,
        string $clientName,
        string $clientEmail,
        string $clientUuid,
        string $returnUrl,
        $signable = null
    ): string {
        $this->initializeApiClient();

        try {
            // 1. Create Envelope Definition
            $envelopeDefinition = $this->createEnvelopeDefinition($pdfContent, $clientName, $clientEmail, $clientUuid);

            // 2. Send Envelope
            $envelopesApi = new EnvelopesApi($this->apiClient);
            $envelopeSummary = $envelopesApi->createEnvelope($this->accountId, $envelopeDefinition);
            $envelopeId = $envelopeSummary->getEnvelopeId();

            Log::info('DocuSign: Envelope created', ['envelope_id' => $envelopeId]);

            // 3. Create Recipient View (Embedded Signing URL)
            $recipientViewRequest = new RecipientViewRequest([
                'authentication_method' => 'none',
                'client_user_id' => $clientUuid,
                'recipient_id' => '1',
                'return_url' => $returnUrl,
                'user_name' => $clientName,
                'email' => $clientEmail
            ]);

            $viewUrl = $envelopesApi->createRecipientView($this->accountId, $envelopeId, $recipientViewRequest);
            $signingUrl = $viewUrl->getUrl();

            // 4. Create Signature record if signable is provided
            if ($signable) {
                $client = Client::where('uuid', $clientUuid)->first();

                Signature::create([
                    'signable_type' => get_class($signable),
                    'signable_uuid' => $signable->uuid,
                    'signable_by_type' => $client ? get_class($client) : null,
                    'signable_by_uuid' => $client ? $client->uuid : null,
                    'provider' => 'docusign',
                    'provider_envelope_id' => $envelopeId,
                    'provider_status' => 'sent',
                    'signing_url' => $signingUrl,
                    'signature_type' => 'digital',
                ]);

                Log::info('DocuSign: Signature record created', [
                    'envelope_id' => $envelopeId,
                    'client_uuid' => $clientUuid
                ]);
            }

            return $signingUrl;

        } catch (Exception $e) {
            Log::error('DocuSign: Send Envelope Failed', [
                'error' => $e->getMessage(),
                'client_email' => $clientEmail
            ]);
            throw $e;
        }
    }

    /**
     * Helper to create Envelope Definition
     */
    protected function createEnvelopeDefinition(
        string $pdfContent,
        string $clientName,
        string $clientEmail,
        string $clientUuid
    ): EnvelopeDefinition {
        $documentBase64 = base64_encode($pdfContent);

        // Document
        $document = new Document([
            'document_base_path' => null,
            'document_base64' => $documentBase64,
            'name' => 'Contrat Axontis',
            'file_extension' => 'pdf',
            'document_id' => '1'
        ]);

        // Signer
        $signer = new Signer([
            'email' => $clientEmail,
            'name' => $clientName,
            'recipient_id' => '1',
            'routing_order' => '1',
            'client_user_id' => $clientUuid
        ]);

        // Sign Here Tab
        $signHere = new SignHere([
            'document_id' => '1',
            'page_number' => '1',
            'recipient_id' => '1',
            'tab_label' => 'SignHereTab',
            'x_position' => '150',
            'y_position' => '600'
        ]);

        $tabs = new Tabs([
            'sign_here_tabs' => [$signHere]
        ]);

        $signer->setTabs($tabs);

        // Envelope Definition
        return new EnvelopeDefinition([
            'email_subject' => 'Veuillez signer votre contrat',
            'documents' => [$document],
            'recipients' => ['signers' => [$signer]],
            'status' => 'sent'
        ]);
    }

    /**
     * Extrait l'ID client (UUID) depuis les données du webhook
     *
     * @param array $payload Le payload du webhook
     * @return string|null L'UUID du client ou null
     */
    public function extractClientUuidFromWebhook(array $payload): ?string
    {
        // Le client_user_id est défini lors de l'envoi de l'enveloppe
        $recipients = $payload['data']['envelopeSummary']['recipients']['signers'] ?? [];

        foreach ($recipients as $signer) {
            if (!empty($signer['clientUserId'])) {
                return $signer['clientUserId'];
            }
        }

        return null;
    }
}
