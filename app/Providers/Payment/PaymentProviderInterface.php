<?php

namespace App\Providers\Payment;

interface PaymentProviderInterface
{
    /**
     * Process payment
     *
     * @param array $paymentData Payment information including card details
     * @return array Response with 'success' flag and transaction details
     */
    public function processPayment(array $paymentData): array;

    /**
     * Refund payment
     *
     * @param string $transactionId Original transaction identifier
     * @param float $amount Refund amount
     * @return array Response with 'success' flag and refund details
     */
    public function refundPayment(string $transactionId, float $amount): array;

    /**
     * Get payment status
     *
     * @param string $transactionId Transaction identifier
     * @return array Response with payment status
     */
    public function getPaymentStatus(string $transactionId): array;
}

