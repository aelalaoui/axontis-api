<?php

namespace App\Providers\Payment;

interface PaymentProviderInterface
{
    /**
     * Create a payment intent (deposit / one-shot)
     *
     * @param array $data Payment intent data (amount, currency, metadata)
     * @return array Response with 'client_secret', 'payment_intent_id'
     */
    public function createPaymentIntent(array $data): array;

    /**
     * Handle provider webhook
     *
     * @param array $payload Webhook payload from payment provider
     * @return void
     */
    public function handleWebhook(array $payload): void;

    /**
     * Refund a payment
     *
     * @param string $providerPaymentId Payment ID from provider
     * @param float $amount Refund amount
     * @return array Response with 'success' flag and refund details
     */
    public function refund(string $providerPaymentId, float $amount): array;
}

