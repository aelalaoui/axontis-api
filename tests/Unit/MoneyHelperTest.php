<?php

namespace Tests\Unit;

use App\Helpers\MoneyHelper;
use Tests\TestCase;

class MoneyHelperTest extends TestCase
{
    /**
     * Test conversion from major units to cents.
     */
    public function test_to_cents_conversion(): void
    {
        $this->assertEquals(15050, MoneyHelper::toCents(150.50));
        $this->assertEquals(10000, MoneyHelper::toCents(100.00));
        $this->assertEquals(12345, MoneyHelper::toCents(123.45));
        $this->assertEquals(0, MoneyHelper::toCents(0));
        $this->assertEquals(1, MoneyHelper::toCents(0.01));
    }

    /**
     * Test conversion from cents to major units.
     */
    public function test_from_cents_conversion(): void
    {
        $this->assertEquals(150.50, MoneyHelper::fromCents(15050));
        $this->assertEquals(100.00, MoneyHelper::fromCents(10000));
        $this->assertEquals(123.45, MoneyHelper::fromCents(12345));
        $this->assertEquals(0, MoneyHelper::fromCents(0));
        $this->assertEquals(0.01, MoneyHelper::fromCents(1));
    }

    /**
     * Test VAT calculation.
     */
    public function test_calculate_vat(): void
    {
        // 150.50 HT with 20% VAT = 30.10 VAT
        $this->assertEquals(30.10, MoneyHelper::calculateVat(15050, 20));

        // 100.00 HT with 20% VAT = 20.00 VAT
        $this->assertEquals(20.00, MoneyHelper::calculateVat(10000, 20));

        // 100.00 HT with 10% VAT = 10.00 VAT
        $this->assertEquals(10.00, MoneyHelper::calculateVat(10000, 10));

        // 0 HT with 20% VAT = 0 VAT
        $this->assertEquals(0, MoneyHelper::calculateVat(0, 20));
    }

    /**
     * Test TTC (total including VAT) calculation.
     */
    public function test_calculate_ttc(): void
    {
        // 150.50 HT with 20% VAT = 180.60 TTC
        $this->assertEquals(180.60, MoneyHelper::calculateTtc(15050, 20));

        // 100.00 HT with 20% VAT = 120.00 TTC
        $this->assertEquals(120.00, MoneyHelper::calculateTtc(10000, 20));

        // 100.00 HT with 10% VAT = 110.00 TTC
        $this->assertEquals(110.00, MoneyHelper::calculateTtc(10000, 10));

        // 0 HT with 20% VAT = 0 TTC
        $this->assertEquals(0, MoneyHelper::calculateTtc(0, 20));
    }

    /**
     * Test formatting for display.
     */
    public function test_format(): void
    {
        $this->assertEquals('150,50 DH', MoneyHelper::format(150.50));
        $this->assertEquals('1 234,56 DH', MoneyHelper::format(1234.56));
        $this->assertEquals('150.50 EUR', MoneyHelper::format(150.50, 'EUR', '.', ','));
        $this->assertEquals('0,00 DH', MoneyHelper::format(0));
    }

    /**
     * Test rounding behavior for edge cases.
     */
    public function test_rounding_edge_cases(): void
    {
        // Test that we round properly
        $this->assertEquals(12346, MoneyHelper::toCents(123.455)); // Should round to 123.46
        $this->assertEquals(12345, MoneyHelper::toCents(123.454)); // Should round to 123.45
    }
}

