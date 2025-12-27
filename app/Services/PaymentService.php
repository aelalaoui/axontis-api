<?php

namespace App\Services;

use App\Managers\PaymentManager;
use App\Models\Client;
use App\Models\Contract;
use App\Models\Payment;
use Exception;

class PaymentService
{
    protected PaymentManager $paymentManager;

    public function __construct(PaymentManager $paymentManager)
    {
        $this->paymentManager = $paymentManager;
    }

    /**
     * Process payment for a contract
     */
    public function processPayment(array $paymentData): array
    {
        try {
            // Validate payment data
            if (!$this->validatePaymentData($paymentData)) {
                return [
                    'success' => false,
                    'message' => 'Invalid payment data provided'
                ];
            }

            // Find client and contract
            $client = Client::where('uuid', $paymentData['client_uuid'])->first();
            $contract = Contract::where('uuid', $paymentData['contract_uuid'])->first();

            if (!$client || !$contract) {
                return [
                    'success' => false,
                    'message' => 'Client or contract not found'
                ];
            }

            // Verify contract amount matches
            if ((float)$paymentData['amount'] !== (float)$contract->monthly_ttc) {
                return [
                    'success' => false,
                    'message' => 'Payment amount does not match contract amount'
                ];
            }

            // Get payment provider (default: Stripe)
            $provider = $this->paymentManager->getProvider();

            // Process payment through provider
            $providerResponse = $provider->processPayment([
                'card_number' => $paymentData['card_number'],
                'card_holder' => $paymentData['card_holder'],
                'expiry_date' => $paymentData['expiry_date'],
                'cvv' => $paymentData['cvv'],
                'amount' => $paymentData['amount'],
                'currency' => $contract->currency,
                'description' => "Payment for contract {$contract->uuid}",
            ]);

            // Create payment record
            $payment = Payment::create([
                'contract_id' => $contract->id,
                'amount' => $paymentData['amount'],
                'currency' => $contract->currency,
                'status' => $providerResponse['success'] ? 'successful' : 'failed',
                'payment_method' => 'credit_card',
                'transaction_id' => $providerResponse['transaction_id'] ?? null,
                'provider_response' => json_encode($providerResponse),
                'paid_at' => $providerResponse['success'] ? now() : null,
            ]);

            if ($providerResponse['success']) {
                // Update contract status to active
                $contract->update(['status' => 'active']);

                // Update client status to paid
                $client->update(['status' => 'paid']);

                return [
                    'success' => true,
                    'message' => 'Payment processed successfully',
                    'data' => [
                        'payment_uuid' => $payment->uuid,
                        'transaction_id' => $providerResponse['transaction_id'],
                        'amount' => $payment->amount,
                        'status' => $payment->status,
                        'contract_uuid' => $contract->uuid,
                        'client_uuid' => $client->uuid,
                    ]
                ];
            }

            return [
                'success' => false,
                'message' => $providerResponse['message'] ?? 'Payment failed',
                'error' => $providerResponse['error'] ?? null
            ];

        } catch (Exception $e) {
            \Log::error('Payment processing error', [
                'exception' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return [
                'success' => false,
                'message' => 'Payment processing error',
                'error' => $e->getMessage()
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
                    'message' => 'Payment not found'
                ];
            }

            if ($payment->status !== 'successful') {
                return [
                    'success' => false,
                    'message' => 'Only successful payments can be refunded'
                ];
            }

            $refundAmount = $amount ?? $payment->amount;

            if ($refundAmount > $payment->amount) {
                return [
                    'success' => false,
                    'message' => 'Refund amount cannot exceed payment amount'
                ];
            }

            // Get provider and process refund
            $provider = $this->paymentManager->getProvider();
            $refundResponse = $provider->refundPayment(
                $payment->transaction_id,
                $refundAmount
            );

            if ($refundResponse['success']) {
                // Update payment status
                $newStatus = $refundAmount === $payment->amount ? 'refunded' : 'partially_refunded';
                $payment->update([
                    'status' => $newStatus,
                    'provider_response' => json_encode($refundResponse),
                ]);

                return [
                    'success' => true,
                    'message' => 'Refund processed successfully',
                    'data' => [
                        'payment_uuid' => $payment->uuid,
                        'refund_amount' => $refundAmount,
                        'status' => $newStatus,
                    ]
                ];
            }

            return [
                'success' => false,
                'message' => $refundResponse['message'] ?? 'Refund failed',
                'error' => $refundResponse['error'] ?? null
            ];

        } catch (Exception $e) {
            \Log::error('Payment refund error', [
                'exception' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return [
                'success' => false,
                'message' => 'Refund processing error',
                'error' => $e->getMessage()
            ];
        }
    }

    /**
     * Get payment status
     */
    public function getPaymentStatus(string $paymentUuid): array
    {
        try {
            $payment = Payment::where('uuid', $paymentUuid)->first();

            if (!$payment) {
                return [
                    'success' => false,
                    'message' => 'Payment not found'
                ];
            }

            $provider = $this->paymentManager->getProvider();
            $statusResponse = $provider->getPaymentStatus($payment->transaction_id);

            return [
                'success' => true,
                'data' => [
                    'payment_uuid' => $payment->uuid,
                    'status' => $payment->status,
                    'amount' => $payment->amount,
                    'currency' => $payment->currency,
                    'transaction_id' => $payment->transaction_id,
                    'created_at' => $payment->created_at,
                    'paid_at' => $payment->paid_at,
                    'provider_status' => $statusResponse['status'] ?? null,
                ]
            ];

        } catch (Exception $e) {
            return [
                'success' => false,
                'message' => 'Failed to retrieve payment status',
                'error' => $e->getMessage()
            ];
        }
    }

    /**
     * Validate payment data
     */
    protected function validatePaymentData(array $paymentData): bool
    {
        $requiredFields = ['client_uuid', 'contract_uuid', 'card_number', 'card_holder', 'expiry_date', 'cvv', 'amount'];

        foreach ($requiredFields as $field) {
            if (empty($paymentData[$field])) {
                return false;
            }
        }

        // Validate card number (basic Luhn check)
        if (!$this->validateCardNumber($paymentData['card_number'])) {
            return false;
        }

        // Validate expiry date format
        if (!preg_match('/^\d{2}\/\d{2}$/', $paymentData['expiry_date'])) {
            return false;
        }

        // Validate CVV
        if (!preg_match('/^\d{3,4}$/', $paymentData['cvv'])) {
            return false;
        }

        return true;
    }

    /**
     * Validate card number using Luhn algorithm
     */
    protected function validateCardNumber(string $cardNumber): bool
    {
        $cardNumber = preg_replace('/\s+/', '', $cardNumber);

        if (!preg_match('/^\d{13,19}$/', $cardNumber)) {
            return false;
        }

        $sum = 0;
        $isEven = false;

        for ($i = strlen($cardNumber) - 1; $i >= 0; $i--) {
            $digit = (int)$cardNumber[$i];

            if ($isEven) {
                $digit *= 2;
                if ($digit > 9) {
                    $digit -= 9;
                }
            }

            $sum += $digit;
            $isEven = !$isEven;
        }

        return $sum % 10 === 0;
    }
}

