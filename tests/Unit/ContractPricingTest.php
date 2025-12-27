<?php

namespace Tests\Unit;

use App\Models\Client;
use App\Models\Contract;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ContractPricingTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Test contract pricing calculation with new integer columns.
     */
    public function test_contract_calculates_monthly_amounts_correctly(): void
    {
        // Create a test client
        $client = Client::factory()->create();

        // Create a contract with amount in cents and VAT rate
        $contract = Contract::create([
            'client_id' => $client->id,
            'start_date' => now(),
            'end_date' => now()->addYear(),
            'status' => 'pending',
            'monthly_amount_cents' => 15050, // 150.50 DH HT
            'vat_rate_percentage' => 20,      // 20% VAT
            'description' => 'Test contract',
        ]);

        // Test accessors
        $this->assertEquals(150.50, $contract->monthly_ht);
        $this->assertEquals(30.10, $contract->monthly_tva);
        $this->assertEquals(180.60, $contract->monthly_ttc);
    }

    /**
     * Test contract with zero amount.
     */
    public function test_contract_with_zero_amount(): void
    {
        $client = Client::factory()->create();

        $contract = Contract::create([
            'client_id' => $client->id,
            'start_date' => now(),
            'end_date' => now()->addYear(),
            'status' => 'pending',
            'monthly_amount_cents' => 0,
            'vat_rate_percentage' => 20,
            'description' => 'Test contract with zero amount',
        ]);

        $this->assertEquals(0, $contract->monthly_ht);
        $this->assertEquals(0, $contract->monthly_tva);
        $this->assertEquals(0, $contract->monthly_ttc);
    }

    /**
     * Test contract with different VAT rate.
     */
    public function test_contract_with_different_vat_rate(): void
    {
        $client = Client::factory()->create();

        // Create a contract with 10% VAT
        $contract = Contract::create([
            'client_id' => $client->id,
            'start_date' => now(),
            'end_date' => now()->addYear(),
            'status' => 'pending',
            'monthly_amount_cents' => 10000, // 100.00 DH HT
            'vat_rate_percentage' => 10,      // 10% VAT
            'description' => 'Test contract with 10% VAT',
        ]);

        $this->assertEquals(100.00, $contract->monthly_ht);
        $this->assertEquals(10.00, $contract->monthly_tva);
        $this->assertEquals(110.00, $contract->monthly_ttc);
    }

    /**
     * Test contract stores values as integers to avoid decimal precision issues.
     */
    public function test_contract_stores_values_as_integers(): void
    {
        $client = Client::factory()->create();

        $contract = Contract::create([
            'client_id' => $client->id,
            'start_date' => now(),
            'end_date' => now()->addYear(),
            'status' => 'pending',
            'monthly_amount_cents' => 12345, // 123.45 DH
            'vat_rate_percentage' => 20,
            'description' => 'Test contract',
        ]);

        // Verify database stores integers
        $this->assertIsInt($contract->monthly_amount_cents);
        $this->assertIsInt($contract->vat_rate_percentage);

        // Verify accessors return floats
        $this->assertIsFloat($contract->monthly_ht);
        $this->assertIsFloat($contract->monthly_tva);
        $this->assertIsFloat($contract->monthly_ttc);
    }
}

