<?php

namespace App\Services;

use DocuSign\eSign\Client\ApiClient;
use DocuSign\eSign\Api\EnvelopesApi;
use DocuSign\eSign\Model\EnvelopeDefinition;
use DocuSign\eSign\Model\Document;
use DocuSign\eSign\Model\Signer;
use DocuSign\eSign\Model\SignHere;
use DocuSign\eSign\Model\Tabs;
use DocuSign\eSign\Model\RecipientViewRequest;
use Illuminate\Support\Facades\Log;

class DocuSignService
{
    protected $apiClient;
    protected $accountId;

    public function __construct()
    {
        $this->apiClient = new ApiClient();
        $this->apiClient->getOAuth()->setOAuthBasePath(env('DOCUSIGN_OAUTH_BASE_PATH', 'account-d.docusign.com')); // Default to demo
        $this->accountId = config('services.docusign.account_id');

        // FIX: Disable SSL verification for local development (WAMP)
        //$this->apiClient->getConfig()->setSSLVerification(false);

        $this->authenticate();
    }

    /**
     * Authenticate using JWT
     */
    protected function authenticate()
    {
        try {
            $clientId = config('services.docusign.client_id');
            $userId = config('services.docusign.user_id');
            $rsaKey = config('services.docusign.rsa_key');

            // Handle RSA key formatting
            // 1. Replace literal \n from .env with actual newlines
            $rsaKey = str_replace('\n', "\n", $rsaKey);

            // 2. Check if it already has headers (either PKCS#1 or PKCS#8)
            if (!str_contains($rsaKey, 'BEGIN RSA PRIVATE KEY') && !str_contains($rsaKey, 'BEGIN PRIVATE KEY')) {
                $rsaKey = "-----BEGIN RSA PRIVATE KEY-----\n" .
                    wordwrap($rsaKey, 64, "\n", true) .
                    "\n-----END RSA PRIVATE KEY-----";
            }

            $scopes = ['signature', 'impersonation'];

            $token = $this->apiClient->requestJWTUserToken(
                $clientId,
                $userId,
                $rsaKey,
                $scopes,
                3600
            );

            $this->apiClient->getConfig()->setAccessToken($token[0]['access_token']);
            $this->apiClient->getConfig()->setHost(config('services.docusign.base_path')); // e.g. https://demo.docusign.net/restapi

        } catch (\Exception $e) {
            Log::error('DocuSign Authentication Failed: ' . $e->getMessage());
            throw $e;
        }
    }

    /**
     * Create an envelope and get embedded signing URL
     *
     * @param string $pdfContent Raw PDF content
     * @param string $clientName
     * @param string $clientEmail
     * @param string $clientUuid
     * @param string $returnUrl
     * @return string Signing URL
     */
    public function sendEnvelopeForEmbeddedSigning($pdfContent, $clientName, $clientEmail, $clientUuid, $returnUrl)
    {
        try {
            // 1. Create Envelope Definition
            $envelopeDefinition = $this->createEnvelopeDefinition($pdfContent, $clientName, $clientEmail, $clientUuid);

            // 2. Send Envelope
            $envelopesApi = new EnvelopesApi($this->apiClient);
            $envelopeSummary = $envelopesApi->createEnvelope($this->accountId, $envelopeDefinition);
            $envelopeId = $envelopeSummary->getEnvelopeId();

            // 3. Create Recipient View (Embedded Signing URL)
            $recipientViewRequest = new RecipientViewRequest([
                'authentication_method' => 'none',
                'client_user_id' => $clientUuid, // Must match client_user_id in Signer
                'recipient_id' => '1',
                'return_url' => $returnUrl,
                'user_name' => $clientName,
                'email' => $clientEmail
            ]);

            $viewUrl = $envelopesApi->createRecipientView($this->accountId, $envelopeId, $recipientViewRequest);

            return $viewUrl->getUrl();

        } catch (\Exception $e) {
            Log::error('DocuSign Send Envelope Failed: ' . $e->getMessage());
            throw $e;
        }
    }

    /**
     * Helper to create Envelope Definition
     */
    protected function createEnvelopeDefinition($pdfContent, $clientName, $clientEmail, $clientUuid)
    {
        $documentBase64 = base64_encode($pdfContent);

        // Document
        $document = new Document([
            'document_base_path' => null, // Using base64
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
            'client_user_id' => $clientUuid // Required for embedded signing
        ]);

        // Sign Here Tab
        $signHere = new SignHere([
            'document_id' => '1',
            'page_number' => '1',
            'recipient_id' => '1',
            'tab_label' => 'SignHereTab',
            'x_position' => '150',
            'y_position' => '600' // Adjust based on your PDF layout
        ]);

        $tabs = new Tabs([
            'sign_here_tabs' => [$signHere]
        ]);

        $signer->setTabs($tabs);

        // Envelope Definition
        return new EnvelopeDefinition([
            'email_subject' => 'Please sign your contract',
            'documents' => [$document],
            'recipients' => ['signers' => [$signer]],
            'status' => 'sent' // Sent immediately
        ]);
    }
}
