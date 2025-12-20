<?php

namespace App\Http\Controllers;

use App\Models\Signature;
use App\Models\Contract;
use App\Models\Client;
use App\Enums\ContractStatus;
use App\Enums\ClientStatus;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class SignatureController extends Controller
{
    /**
     * Handle incoming webhook from a signature provider
     * 
     * @param Request $request
     * @param string $provider
     * @return \Illuminate\Http\JsonResponse
     */
    public function handleWebhook(Request $request, string $provider)
    {
        try {
            // Get the complete payload
            $payload = $request->all();

            // Log the webhook for debugging
            Log::info(ucfirst($provider) . ' Webhook Received', ['payload' => $payload]);

            // Extract provider-specific data
            $envelopeId = $this->extractEnvelopeId($provider, $payload);
            $event = $this->extractEvent($provider, $payload);
            $status = $this->extractStatus($provider, $payload);

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

            // If the document is signed/completed, process it
            if ($this->isCompleted($provider, $payload, $status)) {
                $this->processSignatureCompleted($signature, $payload);
            }

            return response()->json([
                'success' => true,
                'message' => 'Webhook processed successfully'
            ], 200);

        } catch (\Exception $e) {
            Log::error('Signature Webhook Error (' . $provider . '): ' . $e->getMessage(), [
                'payload' => $request->all(),
                'trace' => $e->getTraceAsString()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Error processing webhook'
            ], 500);
        }
    }

    /**
     * Extract Envelope ID based on provider
     */
    protected function extractEnvelopeId(string $provider, array $payload): ?string
    {
        return match ($provider) {
            'docusign' => $payload['data']['envelopeId'] ?? $payload['envelopeId'] ?? null,
            default => null,
        };
    }

    /**
     * Extract Event based on provider
     */
    protected function extractEvent(string $provider, array $payload): ?string
    {
        return match ($provider) {
            'docusign' => $payload['event'] ?? 'unknown',
            default => 'unknown',
        };
    }

    /**
     * Extract Status based on provider
     */
    protected function extractStatus(string $provider, array $payload): ?string
    {
        return match ($provider) {
            'docusign' => $payload['data']['status'] ?? $payload['status'] ?? 'unknown',
            default => 'unknown',
        };
    }

    /**
     * Check if signature is completed based on provider and status
     */
    protected function isCompleted(string $provider, array $payload, ?string $status): bool
    {
        return match ($provider) {
            'docusign' => $status === 'completed' || ($payload['event'] ?? '') === 'envelope-completed',
            default => false,
        };
    }

    /**
     * Process signature completed event
     */
    protected function processSignatureCompleted(Signature $signature, array $payload)
    {
        try {
            // Update the signature timestamp if not set
            if (!$signature->signed_at) {
                $signature->update(['signed_at' => now()]);
            }

            // Find associated signable (Contract, etc.)
            $contract = $signature->signable;
            $client = $signature->signableBy;

            if ($contract instanceof Contract) {
                $contract->update(['status' => ContractStatus::SIGNED->value]);
            }

            if ($client instanceof Client) {
                $client->update(['status' => ClientStatus::SIGNED->value]);
            }

            Log::info('Contract and Client status updated to signed', [
                'contract_uuid' => $contract?->uuid ?? 'N/A',
                'client_uuid' => $client?->uuid ?? 'N/A'
            ]);

            Log::info('Signature completed processed', [
                'signature_id' => $signature->id,
                'envelope_id' => $signature->provider_envelope_id
            ]);

        } catch (\Exception $e) {
            Log::error('Error processing signature completion: ' . $e->getMessage());
        }
    }

    /**
     * View signature webhooks in web interface
     */
    public function viewWebhooks()
    {
        $signatures = Signature::whereNotNull('webhook_payload')
            ->orderBy('webhook_received_at', 'desc')
            ->paginate(20);

        return view('signatures.webhooks', compact('signatures'));
    }
}
