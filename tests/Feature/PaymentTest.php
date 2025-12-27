<?php

namespace Tests\Feature;

use App\Models\Client;
use App\Models\Contract;
use App\Models\Payment;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PaymentTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Test payment intent initialization
     */
    public function test_payment_intent_initialization_success()
    {
        // Arrange
        $client = Client::factory()->create();
        $contract = Contract::factory()->create([
            'client_id' => $client->id,
            'monthly_ttc' => 49.99,
            'currency' => 'EUR',
        ]);

        // Act
        $response = $this->postJson('/api/payments/deposit/init', [
            'client_uuid' => $client->uuid,
            'contract_uuid' => $contract->uuid,
        ]);

        // Assert
        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
            ])
            ->assertJsonStructure([
                'success',
                'message',
                'data' => [
                    'payment_uuid',
                    'client_secret',
                    'payment_intent_id',
                    'amount',
                    'currency',
                    'stripe_public_key',
                ],
            ]);

        // Verify payment record created
        $this->assertDatabaseHas('payments', [
            'contract_id' => $contract->id,
            'amount' => 49.99,
            'currency' => 'EUR',
            'status' => 'pending',
        ]);
    }

    /**
     * Test payment intent with invalid client
     */
    public function test_payment_intent_with_invalid_client()
    {
        // Arrange
        $contract = Contract::factory()->create();

        // Act
        $response = $this->postJson('/api/payments/deposit/init', [
            'client_uuid' => 'invalid-uuid',
            'contract_uuid' => $contract->uuid,
        ]);

        // Assert
        $response->assertStatus(422)
            ->assertJson([
                'success' => false,
                'message' => 'Validation failed',
            ]);
    }

    /**
     * Test payment intent with mismatched contract
     */
    public function test_payment_intent_with_mismatched_contract()
    {
        // Arrange
        $client1 = Client::factory()->create();
        $client2 = Client::factory()->create();
        $contract = Contract::factory()->create([
            'client_id' => $client2->id,
        ]);

        // Act
        $response = $this->postJson('/api/payments/deposit/init', [
            'client_uuid' => $client1->uuid,
            'contract_uuid' => $contract->uuid,
        ]);

        // Assert
        $response->assertStatus(400)
            ->assertJson([
                'success' => false,
                'message' => 'Contract does not belong to this client',
            ]);
    }

    /**
     * Test webhook payment intent succeeded
     */
    public function test_webhook_payment_intent_succeeded()
    {
        // Arrange
        $client = Client::factory()->create(['status' => 'pending']);
        $contract = Contract::factory()->create([
            'client_id' => $client->id,
            'status' => 'draft',
        ]);
        $payment = Payment::factory()->create([
            'contract_id' => $contract->id,
            'status' => 'pending',
            'transaction_id' => 'pi_test_123',
        ]);

        // Mock Stripe webhook payload
        $payload = [
            'type' => 'payment_intent.succeeded',
            'data' => [
                'object' => [
                    'id' => 'pi_test_123',
                    'status' => 'succeeded',
                    'amount_received' => 4999,
                    'metadata' => [
                        'payment_uuid' => $payment->uuid,
                        'contract_uuid' => $contract->uuid,
                        'client_uuid' => $client->uuid,
                    ],
                ],
            ],
        ];

        // Act
        // Note: In real tests, you would mock Stripe's signature verification
        $response = $this->postJson('/api/webhooks/stripe', $payload);

        // Assert
        $response->assertStatus(200);

        // Verify payment updated
        $this->assertDatabaseHas('payments', [
            'uuid' => $payment->uuid,
            'status' => 'successful',
        ]);

        // Verify contract activated
        $this->assertDatabaseHas('contracts', [
            'uuid' => $contract->uuid,
            'status' => 'active',
        ]);

        // Verify client marked as paid
        $this->assertDatabaseHas('clients', [
            'uuid' => $client->uuid,
            'status' => 'paid',
        ]);
    }

    /**
     * Test webhook payment intent failed
     */
    public function test_webhook_payment_intent_failed()
    {
        // Arrange
        $payment = Payment::factory()->create([
            'status' => 'pending',
            'transaction_id' => 'pi_test_456',
        ]);

        // Mock Stripe webhook payload
        $payload = [
            'type' => 'payment_intent.payment_failed',
            'data' => [
                'object' => [
                    'id' => 'pi_test_456',
                    'status' => 'failed',
                    'last_payment_error' => [
                        'message' => 'Your card was declined',
                    ],
                    'metadata' => [
                        'payment_uuid' => $payment->uuid,
                    ],
                ],
            ],
        ];

        // Act
        $response = $this->postJson('/api/webhooks/stripe', $payload);

        // Assert
        $response->assertStatus(200);

        // Verify payment marked as failed
        $this->assertDatabaseHas('payments', [
            'uuid' => $payment->uuid,
            'status' => 'failed',
        ]);
    }

    /**
     * Test payment refund
     */
    public function test_payment_refund_success()
    {
        // Arrange
        $payment = Payment::factory()->create([
            'status' => 'successful',
            'amount' => 49.99,
            'transaction_id' => 'pi_test_789',
        ]);

        // Act
        $response = $this->postJson("/api/payments/{$payment->uuid}/refund", [
            'amount' => 49.99,
        ]);

        // Assert
        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
            ]);

        // Verify payment marked as refunded
        $this->assertDatabaseHas('payments', [
            'uuid' => $payment->uuid,
            'status' => 'refunded',
        ]);
    }

    /**
     * Test partial payment refund
     */
    public function test_payment_partial_refund()
    {
        // Arrange
        $payment = Payment::factory()->create([
            'status' => 'successful',
            'amount' => 100.00,
            'transaction_id' => 'pi_test_partial',
        ]);

        // Act
        $response = $this->postJson("/api/payments/{$payment->uuid}/refund", [
            'amount' => 50.00,
        ]);

        // Assert
        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
            ]);

        // Verify payment marked as partially refunded
        $this->assertDatabaseHas('payments', [
            'uuid' => $payment->uuid,
            'status' => 'partially_refunded',
        ]);
    }

    /**
     * Test refund on non-successful payment
     */
    public function test_refund_on_pending_payment_fails()
    {
        // Arrange
        $payment = Payment::factory()->create([
            'status' => 'pending',
        ]);

        // Act
        $response = $this->postJson("/api/payments/{$payment->uuid}/refund");

        // Assert
        $response->assertStatus(400)
            ->assertJson([
                'success' => false,
                'message' => 'Only successful payments can be refunded',
            ]);
    }

    /**
     * Test get payment details
     */
    public function test_get_payment_details()
    {
        // Arrange
        $payment = Payment::factory()->create([
            'status' => 'successful',
            'amount' => 49.99,
            'currency' => 'EUR',
        ]);

        // Act
        $response = $this->getJson("/api/payments/{$payment->uuid}");

        // Assert
        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
            ])
            ->assertJsonStructure([
                'success',
                'data' => [
                    'payment_uuid',
                    'status',
                    'amount',
                    'currency',
                    'method',
                    'transaction_id',
                ],
            ]);
    }

    /**
     * Test get non-existent payment
     */
    public function test_get_nonexistent_payment_returns_404()
    {
        // Act
        $response = $this->getJson('/api/payments/invalid-uuid');

        // Assert
        $response->assertStatus(404)
            ->assertJson([
                'success' => false,
            ]);
    }

    /**
     * Test payment security - no card data in database
     */
    public function test_no_card_data_stored_in_database()
    {
        // Arrange
        $payment = Payment::factory()->create();

        // Assert - ensure no card-related columns exist
        $paymentData = $payment->toArray();
        $this->assertArrayNotHasKey('card_number', $paymentData);
        $this->assertArrayNotHasKey('card_holder', $paymentData);
        $this->assertArrayNotHasKey('expiry_date', $paymentData);
        $this->assertArrayNotHasKey('cvv', $paymentData);
    }
}

