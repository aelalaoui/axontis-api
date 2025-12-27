<?php

namespace App\Http\Controllers;

use App\Managers\PaymentManager;
use App\Services\PaymentService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;

class PaymentController extends Controller
{
    protected PaymentService $paymentService;
    protected PaymentManager $paymentManager;

    public function __construct(PaymentService $paymentService, PaymentManager $paymentManager)
    {
        $this->paymentService = $paymentService;
        $this->paymentManager = $paymentManager;
    }

    /**
     * Initialize payment intent (deposit / one-shot)
     * POST /api/payments/deposit/init
     */
    public function initializePayment(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'client_uuid' => 'required|string|exists:clients,uuid',
            'contract_uuid' => 'required|string|exists:contracts,uuid',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors(),
            ], 422);
        }

        try {
            $result = $this->paymentService->initializePayment(
                $request->client_uuid,
                $request->contract_uuid
            );

            if ($result['success']) {
                return response()->json([
                    'success' => true,
                    'message' => $result['message'],
                    'data' => $result['data'],
                ], 200);
            }

            return response()->json([
                'success' => false,
                'message' => $result['message'] ?? 'Payment initialization failed',
                'error' => $result['error'] ?? null,
            ], 400);

        } catch (\Exception $e) {
            Log::error('Payment initialization failed', [
                'exception' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Payment initialization failed',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Handle Stripe webhook
     * POST /api/webhooks/stripe
     */
    public function handleStripeWebhook(Request $request): JsonResponse
    {
        try {
            // Get Stripe provider
            $provider = $this->paymentManager->getProvider('stripe');

            // Handle webhook (provider will verify signature and process event)
            $provider->handleWebhook($request->all());

            return response()->json([
                'success' => true,
                'message' => 'Webhook processed successfully',
            ], 200);

        } catch (\Stripe\Exception\SignatureVerificationException $e) {
            Log::error('Stripe webhook signature verification failed', [
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Invalid webhook signature',
            ], 400);

        } catch (\Exception $e) {
            Log::error('Stripe webhook processing failed', [
                'exception' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Webhook processing failed',
            ], 500);
        }
    }

    /**
     * Get payment details
     * GET /api/payments/{paymentUuid}
     */
    public function getPaymentDetails(string $paymentUuid): JsonResponse
    {
        try {
            $result = $this->paymentService->getPaymentDetails($paymentUuid);

            if ($result['success']) {
                return response()->json([
                    'success' => true,
                    'data' => $result['data'],
                ], 200);
            }

            return response()->json([
                'success' => false,
                'message' => $result['message'] ?? 'Payment not found',
            ], 404);

        } catch (\Exception $e) {
            Log::error('Get payment details failed', [
                'exception' => $e->getMessage(),
                'payment_uuid' => $paymentUuid,
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to retrieve payment details',
            ], 500);
        }
    }

    /**
     * Refund a payment
     * POST /api/payments/{paymentUuid}/refund
     */
    public function refundPayment(Request $request, string $paymentUuid): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'amount' => 'nullable|numeric|min:0.01',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors(),
            ], 422);
        }

        try {
            $result = $this->paymentService->refundPayment(
                $paymentUuid,
                $request->amount
            );

            if ($result['success']) {
                return response()->json([
                    'success' => true,
                    'message' => $result['message'],
                    'data' => $result['data'],
                ], 200);
            }

            return response()->json([
                'success' => false,
                'message' => $result['message'] ?? 'Refund failed',
                'error' => $result['error'] ?? null,
            ], 400);

        } catch (\Exception $e) {
            Log::error('Payment refund failed', [
                'exception' => $e->getMessage(),
                'payment_uuid' => $paymentUuid,
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Refund processing failed',
            ], 500);
        }
    }
}

