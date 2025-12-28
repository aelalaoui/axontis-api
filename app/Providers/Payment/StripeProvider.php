<?php

namespace App\Providers\Payment;

use App\Models\Payment;
use App\Models\Contract;
use App\Models\Client;
use Illuminate\Support\Facades\Log;
use Stripe\Stripe;
use Stripe\PaymentIntent;
use Stripe\Refund;
use Stripe\Webhook;
use Stripe\Exception\SignatureVerificationException;

class StripeProvider implements PaymentProviderInterface
{
    /**
     * Stripe API key
     */
    protected string $apiKey;

    /**
     * Stripe webhook secret
     */
    protected string $webhookSecret;

    public function __construct()
    {
        $this->apiKey = config('services.stripe.secret');
        $this->webhookSecret = config('services.stripe.webhook_secret');

        // Set Stripe API key
        Stripe::setApiKey($this->apiKey);
    }

    /**
     * Create a payment intent
     */
    public function createPaymentIntent(array $data): array
    {
        try {
            // Validate required fields
            $this->validatePaymentIntentData($data);

            // Create Stripe PaymentIntent
            $paymentIntent = PaymentIntent::create([
                'amount' => (int)($data['amount'] * 100), // Convert to cents
                'currency' => strtolower($data['currency']),
                'automatic_payment_methods' => [
                    'enabled' => true,
                ],
                'metadata' => [
                    'payment_uuid' => $data['payment_uuid'] ?? null,
                    'contract_uuid' => $data['contract_uuid'] ?? null,
                    'client_uuid' => $data['client_uuid'] ?? null,
                    'description' => $data['description'] ?? 'Payment',
                ],
            ]);

            Log::info('Stripe PaymentIntent created', [
                'payment_intent_id' => $paymentIntent->id,
                'amount' => $data['amount'],
                'currency' => $data['currency'],
            ]);

            return [
                'success' => true,
                'client_secret' => $paymentIntent->client_secret,
                'payment_intent_id' => $paymentIntent->id,
                'amount' => $data['amount'],
                'currency' => $data['currency'],
            ];

        } catch (\Exception $e) {
            Log::error('Stripe PaymentIntent creation error', [
                'exception' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return [
                'success' => false,
                'message' => 'Failed to create payment intent',
                'error' => $e->getMessage(),
            ];
        }
    }

    /**
     * Handle Stripe webhook
     */
    public function handleWebhook(array $payload): void
    {
        try {
            $sigHeader = $_SERVER['HTTP_STRIPE_SIGNATURE'] ?? '';

            // Verify webhook signature
            try {
                $event = Webhook::constructEvent(
                    file_get_contents('php://input'),
                    $sigHeader,
                    $this->webhookSecret
                );
            } catch (SignatureVerificationException $e) {
                Log::error('Stripe webhook signature verification failed', [
                    'error' => $e->getMessage(),
                ]);
                throw $e;
            }

            Log::info('Stripe webhook received', [
                'event_type' => $event->type,
                'event_id' => $event->id,
            ]);

            // Handle different event types
            switch ($event->type) {
                case 'payment_intent.succeeded':
                    $this->handlePaymentIntentSucceeded($event->data->object);
                    break;

                case 'payment_intent.payment_failed':
                    $this->handlePaymentIntentFailed($event->data->object);
                    break;

                case 'payment_intent.canceled':
                    $this->handlePaymentIntentCanceled($event->data->object);
                    break;

                default:
                    Log::info('Unhandled Stripe webhook event type', [
                        'type' => $event->type,
                    ]);
            }

        } catch (\Exception $e) {
            Log::error('Stripe webhook handling error', [
                'exception' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
            throw $e;
        }
    }

    /**
     * Refund a payment
     */
    public function refund(string $providerPaymentId, float $amount): array
    {
        try {
            if (empty($providerPaymentId)) {
                return [
                    'success' => false,
                    'message' => 'Payment Intent ID is required for refund',
                ];
            }

            // Create Stripe refund
            $refund = Refund::create([
                'payment_intent' => $providerPaymentId,
                'amount' => (int)($amount * 100), // Convert to cents
            ]);

            Log::info('Stripe refund created', [
                'refund_id' => $refund->id,
                'payment_intent_id' => $providerPaymentId,
                'amount' => $amount,
            ]);

            return [
                'success' => true,
                'refund_id' => $refund->id,
                'payment_intent_id' => $providerPaymentId,
                'amount' => $amount,
                'status' => $refund->status,
                'message' => 'Refund processed successfully',
            ];

        } catch (\Exception $e) {
            Log::error('Stripe refund error', [
                'exception' => $e->getMessage(),
                'payment_intent_id' => $providerPaymentId,
            ]);

            return [
                'success' => false,
                'message' => 'Stripe refund failed',
                'error' => $e->getMessage(),
            ];
        }
    }

    /**
     * Handle successful payment intent
     */
    protected function handlePaymentIntentSucceeded($paymentIntent): void
    {
        $paymentUuid = $paymentIntent->metadata->payment_uuid ?? null;
        $contractUuid = $paymentIntent->metadata->contract_uuid ?? null;
        $clientUuid = $paymentIntent->metadata->client_uuid ?? null;

        if (is_null($paymentUuid)) {
            Log::warning('Payment UUID not found in PaymentIntent metadata', [
                'payment_intent_id' => $paymentIntent->id,
            ]);
            return;
        }

        // Find payment record
        $payment = Payment::fromUuid($paymentUuid);

        if (is_null($payment)) {
            Log::error('Payment not found', [
                'payment_uuid' => $paymentUuid,
                'payment_intent_id' => $paymentIntent->id,
            ]);
            return;
        }

        // Update payment status
        $payment->update([
            'status' => 'successful',
            'transaction_id' => $paymentIntent->id,
            'payment_date' => now(),
            'provider_response' => json_encode([
                'payment_intent_id' => $paymentIntent->id,
                'status' => $paymentIntent->status,
                'amount_received' => $paymentIntent->amount_received / 100,
                'charges' => $paymentIntent->charges->data ?? [],
            ]),
        ]);

        Log::info('Payment marked as successful', [
            'payment_uuid' => $paymentUuid,
            'payment_intent_id' => $paymentIntent->id,
        ]);

        // Update contract and client status
        if ($contractUuid && $clientUuid) {
            $contract = Contract::fromUuid($contractUuid);
            $client = Client::fromUuid($clientUuid);

            if (!is_null($contract)) {
                $contract->update(['status' => 'active']);
                Log::info('Contract activated', ['contract_uuid' => $contractUuid]);
            }

            if (!is_null($client)) {
                $client->update(['status' => 'paid']);
                Log::info('Client marked as paid', ['client_uuid' => $clientUuid]);
            }
        }
    }

    /**
     * Handle failed payment intent
     */
    protected function handlePaymentIntentFailed($paymentIntent): void
    {
        $paymentUuid = $paymentIntent->metadata->payment_uuid ?? null;

        if (!$paymentUuid) {
            Log::warning('Payment UUID not found in failed PaymentIntent metadata', [
                'payment_intent_id' => $paymentIntent->id,
            ]);
            return;
        }

        $payment = Payment::where('uuid', $paymentUuid)->first();

        if (!$payment) {
            Log::error('Payment not found for failed intent', [
                'payment_uuid' => $paymentUuid,
                'payment_intent_id' => $paymentIntent->id,
            ]);
            return;
        }

        // Update payment status
        $payment->update([
            'status' => 'failed',
            'transaction_id' => $paymentIntent->id,
            'provider_response' => json_encode([
                'payment_intent_id' => $paymentIntent->id,
                'status' => $paymentIntent->status,
                'last_payment_error' => $paymentIntent->last_payment_error,
            ]),
        ]);

        Log::info('Payment marked as failed', [
            'payment_uuid' => $paymentUuid,
            'payment_intent_id' => $paymentIntent->id,
            'error' => $paymentIntent->last_payment_error->message ?? 'Unknown error',
        ]);
    }

    /**
     * Handle canceled payment intent
     */
    protected function handlePaymentIntentCanceled($paymentIntent): void
    {
        $paymentUuid = $paymentIntent->metadata->payment_uuid ?? null;

        if (!$paymentUuid) {
            return;
        }

        $payment = Payment::where('uuid', $paymentUuid)->first();

        if ($payment) {
            $payment->update([
                'status' => 'canceled',
                'transaction_id' => $paymentIntent->id,
                'provider_response' => json_encode([
                    'payment_intent_id' => $paymentIntent->id,
                    'status' => $paymentIntent->status,
                ]),
            ]);

            Log::info('Payment marked as canceled', [
                'payment_uuid' => $paymentUuid,
                'payment_intent_id' => $paymentIntent->id,
            ]);
        }
    }

    /**
     * Validate payment intent data
     */
    protected function validatePaymentIntentData(array $data): void
    {
        $required = ['amount', 'currency'];

        foreach ($required as $field) {
            if (!isset($data[$field]) || empty($data[$field])) {
                throw new \InvalidArgumentException("Missing required field: {$field}");
            }
        }

        if ($data['amount'] <= 0) {
            throw new \InvalidArgumentException("Amount must be greater than 0");
        }
    }
}

