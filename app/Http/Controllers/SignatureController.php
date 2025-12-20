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

            // Log the webhook for debugging
            Log::info(ucfirst($provider) . ' Webhook Received', ['payload' => $payload]);

            // Delegate processing to the service
            $this->signatureService->handleWebhook($provider, $payload);

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
