<?php

namespace Tests\Feature;

use App\Models\Client;
use App\Models\Contract;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PaymentInitializationBugFixTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Test payment initialization with valid contract amount
     * Should succeed and create payment with correct amount
     */
    public function test_payment_initialization_with_valid_amount()
    {
        // Arrange - Create contract with valid monthly_amount_cents
        $client = Client::factory()->create();
        $contract = Contract::factory()->create([
            'client_id' => $client->id,
            'monthly_amount_cents' => 4999,  // 49.99 EUR
            'vat_rate_percentage' => 20,     // 20% VAT
            'currency' => 'EUR',
        ]);

        // Act
        $response = $this->postJson('/api/payments/deposit/init', [
            'client_uuid' => $client->uuid,
            'contract_uuid' => $contract->uuid,
        ]);

        // Assert - Response is successful
        $response->assertStatus(200)
            ->assertJson(['success' => true])
            ->assertJsonPath('data.amount', 59.99);  // 49.99 + 20% = 59.99

        // Assert - Payment created with correct amount
        $this->assertDatabaseHas('payments', [
            'contract_id' => $contract->id,
            'amount' => 59.99,
            'currency' => 'EUR',
            'status' => 'pending',
        ]);
    }

    /**
     * Test payment initialization with zero amount (should fail)
     * Contract without monthly_amount_cents should be rejected
     */
    public function test_payment_initialization_with_zero_amount_fails()
    {
        // Arrange - Create contract with null monthly_amount_cents
        $client = Client::factory()->create();
        $contract = Contract::factory()->create([
            'client_id' => $client->id,
            'monthly_amount_cents' => null,  // ❌ Invalid
            'currency' => 'EUR',
        ]);

        // Act
        $response = $this->postJson('/api/payments/deposit/init', [
            'client_uuid' => $client->uuid,
            'contract_uuid' => $contract->uuid,
        ]);

        // Assert - Response is error
        $response->assertStatus(400)
            ->assertJson([
                'success' => false,
                'message' => 'Contract must have a valid amount greater than 0',
            ]);

        // Assert - Payment NOT created
        $this->assertDatabaseMissing('payments', [
            'contract_id' => $contract->id,
        ]);
    }

    /**
     * Test payment initialization with MAD currency
     * Should accept contract currency and pass it through
     */
    public function test_payment_initialization_with_mad_currency()
    {
        // Arrange
        $client = Client::factory()->create();
        $contract = Contract::factory()->create([
            'client_id' => $client->id,
            'monthly_amount_cents' => 4999,
            'vat_rate_percentage' => 20,
            'currency' => 'MAD',  // Moroccan Dirham
        ]);

        // Act
        $response = $this->postJson('/api/payments/deposit/init', [
            'client_uuid' => $client->uuid,
            'contract_uuid' => $contract->uuid,
        ]);

        // Assert
        $response->assertStatus(200)
            ->assertJsonPath('data.currency', 'MAD');

        $this->assertDatabaseHas('payments', [
            'contract_id' => $contract->id,
            'currency' => 'MAD',
        ]);
    }

    /**
     * Test payment initialization with very large amount
     * Should handle decimal amounts correctly
     */
    public function test_payment_initialization_with_large_amount()
    {
        // Arrange
        $client = Client::factory()->create();
        $contract = Contract::factory()->create([
            'client_id' => $client->id,
            'monthly_amount_cents' => 1000000,  // 10,000.00 EUR
            'vat_rate_percentage' => 20,
            'currency' => 'EUR',
        ]);

        // Act
        $response = $this->postJson('/api/payments/deposit/init', [
            'client_uuid' => $client->uuid,
            'contract_uuid' => $contract->uuid,
        ]);

        // Assert
        $response->assertStatus(200)
            ->assertJsonPath('data.amount', 12000.00);  // 10000 + 20% = 12000

        $this->assertDatabaseHas('payments', [
            'contract_id' => $contract->id,
            'amount' => 12000.00,
        ]);
    }

    /**
     * Test payment data integrity
     * All fields should be stored correctly in database
     */
    public function test_payment_data_integrity()
    {
        // Arrange
        $client = Client::factory()->create();
        $contract = Contract::factory()->create([
            'client_id' => $client->id,
            'monthly_amount_cents' => 9999,  // 99.99
            'vat_rate_percentage' => 10,     // 10% VAT
            'currency' => 'EUR',
        ]);

        // Act
        $this->postJson('/api/payments/deposit/init', [
            'client_uuid' => $client->uuid,
            'contract_uuid' => $contract->uuid,
        ]);

        // Assert - Verify all payment fields
        $payment = $contract->payments()->first();
        $this->assertNotNull($payment);
        $this->assertEquals($contract->id, $payment->contract_id);
        $this->assertEquals(109.99, $payment->amount);  // 99.99 + 10% = 109.99
        $this->assertEquals('EUR', $payment->currency);
        $this->assertEquals('pending', $payment->status);
        $this->assertEquals('credit_card', $payment->method);
        $this->assertStringContainsString('Deposit payment', $payment->notes);
        $this->assertNotNull($payment->transaction_id);  // PaymentIntent ID from Stripe
        $this->assertNull($payment->payment_date);  // Not paid yet
        $this->assertNotNull($payment->uuid);  // Payment UUID
    }

    /**
     * Test SQL injection protection
     * Special characters in notes should be escaped
     */
    public function test_payment_initialization_sql_injection_protection()
    {
        // Arrange
        $client = Client::factory()->create();
        $contract = Contract::factory()->create([
            'client_id' => $client->id,
            'monthly_amount_cents' => 4999,
            'uuid' => "'; DROP TABLE payments; --",  // Attempted SQL injection
            'currency' => 'EUR',
        ]);

        // Act
        $response = $this->postJson('/api/payments/deposit/init', [
            'client_uuid' => $client->uuid,
            'contract_uuid' => $contract->uuid,
        ]);

        // Assert - Should not crash or execute injection
        $response->assertStatus(200);
        $this->assertDatabaseHas('payments', [
            'contract_id' => $contract->id,
        ]);

        // Verify table still exists and is not corrupted
        $this->assertTrue(\DB::table('payments')->exists());
    }
}

