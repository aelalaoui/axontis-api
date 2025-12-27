<?php

namespace App\Providers\Payment;

class CmiProvider implements PaymentProviderInterface
{
    public function processPayment(array $paymentData): array
    {
        try {
            // Simulate payment processing delay
            usleep(rand(500000, 1500000));

            // 95% success rate
            $success = rand(1, 100) <= 95;

            if ($success) {
                return [
                    'success' => true,
                    'transaction_id' => 'cmi_' . uniqid() . '_' . time(),
                    'status' => 'succeeded',
                    'message' => 'Payment processed successfully via CMI',
                    'provider' => 'cmi',
                ];
            }

            return [
                'success' => false,
                'message' => 'CMI payment declined',
                'error' => 'Payment could not be processed',
                'provider' => 'cmi',
            ];

        } catch (\Exception $e) {
            return [
                'success' => false,
                'message' => 'CMI payment failed',
                'error' => $e->getMessage(),
                'provider' => 'cmi'
            ];
        }
    }

    public function refundPayment(string $transactionId, float $amount): array
    {
        usleep(rand(500000, 1500000));

        return [
            'success' => true,
            'refund_id' => 'cmi_refund_' . uniqid(),
            'message' => 'Refund processed successfully',
            'provider' => 'cmi',
        ];
    }

    public function getPaymentStatus(string $transactionId): array
    {
        return [
            'success' => true,
            'status' => 'completed',
            'transaction_id' => $transactionId,
            'provider' => 'cmi',
        ];
    }
}

