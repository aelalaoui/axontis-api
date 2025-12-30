<?php

namespace App\Services;

use App\Managers\PaymentManager;
use App\Models\Client;
use App\Models\Contract;
use App\Models\Payment;
use Exception;
use Illuminate\Support\Facades\Log;

class PaymentService
{
    protected PaymentManager $paymentManager;

    public function __construct(PaymentManager $paymentManager)
    {
        $this->paymentManager = $paymentManager;
    }

    /**
     * Initialize payment intent for a contract deposit
     */
    public function initializePayment(string $clientUuid, string $contractUuid): array
    {
        try {
            // Find client and contract
            $client = Client::where('uuid', $clientUuid)->first();
            $contract = Contract::where('uuid', $contractUuid)->first();

            if (!$client) {
                return [
                    'success' => false,
                    'message' => 'Client not found',
                ];
            }

            if (!$contract) {
                return [
                    'success' => false,
                    'message' => 'Contract not found',
                ];
            }

            // Verify contract belongs to client
            if ($contract->client_id !== $client->id) {
                return [
                    'success' => false,
                    'message' => 'Contract does not belong to this client',
                ];
            }

            // Use subscription price (initial deposit/caution) for first payment
            $amount = $contract->subscription_ttc ?? 0;
            $currency = $contract->currency ?? 'EUR';

            if ($amount <= 0) {
                return [
                    'success' => false,
                    'message' => 'Contract must have a valid subscription price greater than 0',
                ];
            }

            // Create payment record in PENDING status
            $payment = Payment::create([
                'contract_id' => $contract->id,
                'amount' => $amount,
                'currency' => $currency,
                'status' => 'pending',
                'method' => 'card',
                'notes' => 'Initial subscription payment (deposit) for contract ' . $contract->uuid,
            ]);

            // Get payment provider
            $provider = $this->paymentManager->getProvider();

            // Create PaymentIntent via provider
            $intentResponse = $provider->createPaymentIntent([
                'amount' => $amount,
                'currency' => $currency,
                'payment_uuid' => $payment->uuid,
                'contract_uuid' => $contract->uuid,
                'client_uuid' => $client->uuid,
                'description' => "Deposit payment for contract {$contract->uuid}",
            ]);

            if (!$intentResponse['success']) {
                // Mark payment as failed
                $payment->update(['status' => 'failed']);

                return [
                    'success' => false,
                    'message' => $intentResponse['message'] ?? 'Failed to create payment intent',
                    'error' => $intentResponse['error'] ?? null,
                ];
            }

            // Update payment with provider payment intent ID
            $payment->update([
                'transaction_id' => $intentResponse['payment_intent_id'],
                'provider_response' => json_encode($intentResponse),
            ]);

            Log::info('Payment initialized', [
                'payment_uuid' => $payment->uuid,
                'payment_intent_id' => $intentResponse['payment_intent_id'],
                'client_uuid' => $clientUuid,
                'contract_uuid' => $contractUuid,
            ]);

            return [
                'success' => true,
                'message' => 'Payment intent created successfully',
                'data' => [
                    'payment_uuid' => $payment->uuid,
                    'client_secret' => $intentResponse['client_secret'],
                    'payment_intent_id' => $intentResponse['payment_intent_id'],
                    'amount' => $amount,
                    'currency' => $currency,
                    'stripe_public_key' => config('services.stripe.key'),
                ],
            ];

        } catch (Exception $e) {
            Log::error('Payment initialization error', [
                'exception' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
                'client_uuid' => $clientUuid,
                'contract_uuid' => $contractUuid,
            ]);

            return [
                'success' => false,
                'message' => 'Payment initialization error',
                'error' => $e->getMessage(),
            ];
        }
    }

    /**
     * Refund a payment
     */
    public function refundPayment(string $paymentUuid, float $amount = null): array
    {
        try {
            $payment = Payment::where('uuid', $paymentUuid)->first();

            if (!$payment) {
                return [
                    'success' => false,
                    'message' => 'Payment not found',
                ];
            }

            if ($payment->status !== 'successful') {
                return [
                    'success' => false,
                    'message' => 'Only successful payments can be refunded',
                ];
            }

            $refundAmount = $amount ?? $payment->amount;

            if ($refundAmount > $payment->amount) {
                return [
                    'success' => false,
                    'message' => 'Refund amount cannot exceed payment amount',
                ];
            }

            if (!$payment->transaction_id) {
                return [
                    'success' => false,
                    'message' => 'Payment has no transaction ID',
                ];
            }

            // Get provider and process refund
            $provider = $this->paymentManager->getProvider();
            $refundResponse = $provider->refund(
                $payment->transaction_id,
                $refundAmount
            );

            if ($refundResponse['success']) {
                // Update payment status
                $newStatus = $refundAmount >= $payment->amount ? 'refunded' : 'partially_refunded';
                $payment->update([
                    'status' => $newStatus,
                    'provider_response' => json_encode($refundResponse),
                ]);

                Log::info('Payment refunded', [
                    'payment_uuid' => $paymentUuid,
                    'refund_amount' => $refundAmount,
                    'status' => $newStatus,
                ]);

                return [
                    'success' => true,
                    'message' => 'Refund processed successfully',
                    'data' => [
                        'payment_uuid' => $payment->uuid,
                        'refund_amount' => $refundAmount,
                        'status' => $newStatus,
                        'refund_id' => $refundResponse['refund_id'] ?? null,
                    ],
                ];
            }

            return [
                'success' => false,
                'message' => $refundResponse['message'] ?? 'Refund failed',
                'error' => $refundResponse['error'] ?? null,
            ];

        } catch (Exception $e) {
            Log::error('Payment refund error', [
                'exception' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
                'payment_uuid' => $paymentUuid,
            ]);

            return [
                'success' => false,
                'message' => 'Refund processing error',
                'error' => $e->getMessage(),
            ];
        }
    }

    /**
     * Get payment details
     */
    public function getPaymentDetails(string $paymentUuid): array
    {
        try {
            $payment = Payment::with('contract')->where('uuid', $paymentUuid)->first();

            if (!$payment) {
                return [
                    'success' => false,
                    'message' => 'Payment not found',
                ];
            }

            return [
                'success' => true,
                'data' => [
                    'payment_uuid' => $payment->uuid,
                    'status' => $payment->status,
                    'amount' => $payment->amount,
                    'currency' => $payment->currency,
                    'method' => $payment->method,
                    'transaction_id' => $payment->transaction_id,
                    'created_at' => $payment->created_at,
                    'payment_date' => $payment->payment_date,
                    'contract' => [
                        'uuid' => $payment->contract->uuid ?? null,
                    ],
                ],
            ];

        } catch (Exception $e) {
            Log::error('Get payment details error', [
                'exception' => $e->getMessage(),
                'payment_uuid' => $paymentUuid,
            ]);

            return [
                'success' => false,
                'message' => 'Failed to retrieve payment details',
                'error' => $e->getMessage(),
            ];
        }
    }
}

