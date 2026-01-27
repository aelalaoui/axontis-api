<?php

namespace App\Http\Controllers;

use App\Models\Signature;
use App\Services\SignatureService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class SignatureController extends Controller
{
    protected $signatureService;

    public function __construct(SignatureService $signatureService)
    {
        $this->signatureService = $signatureService;
    }

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

            // Get raw content for signature validation
            $rawPayload = $request->getContent();

            // Get webhook signature from header (DocuSign uses X-DocuSign-Signature-1)
            $webhookSignature = $this->extractWebhookSignature($request, $provider);

            // Log the webhook for debugging
            Log::info(ucfirst($provider) . ' Webhook Received', [
                'event' => $payload['event'] ?? 'unknown',
                'has_signature' => !empty($webhookSignature)
            ]);

            // Delegate processing to the service with signature validation
            $this->signatureService->handleWebhook($provider, $payload, $rawPayload, $webhookSignature);

            return response()->json([
                'success' => true,
                'message' => 'Webhook processed successfully'
            ], 200);

        } catch (\Exception $e) {
            Log::error('Signature Webhook Error (' . $provider . '): ' . $e->getMessage(), [
                'payload' => $request->all(),
                'trace' => $e->getTraceAsString()
            ]);

            // Return 400 for validation errors, 500 for processing errors
            $statusCode = str_contains($e->getMessage(), 'Invalid webhook signature') ? 400 : 500;

            return response()->json([
                'success' => false,
                'message' => 'Error processing webhook'
            ], $statusCode);
        }
    }

    /**
     * Extract webhook signature from request headers based on provider
     *
     * @param Request $request
     * @param string $provider
     * @return string|null
     */
    protected function extractWebhookSignature(Request $request, string $provider): ?string
    {
        return match ($provider) {
            'docusign' => $request->header('X-DocuSign-Signature-1'),
            default => null,
        };
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
