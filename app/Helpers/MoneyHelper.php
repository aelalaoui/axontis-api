<?php

namespace App\Helpers;

class MoneyHelper
{
    /**
     * Convert amount in major currency units to cents
     * Example: 150.50 EUR -> 15050 cents
     *
     * @param float $amount Amount in major units (e.g., dollars, euros)
     * @return int Amount in cents
     */
    public static function toCents(float $amount): int
    {
        return (int) round($amount * 100);
    }

    /**
     * Convert amount in cents to major currency units
     * Example: 15050 cents -> 150.50 EUR
     *
     * @param int $cents Amount in cents
     * @return float Amount in major units
     */
    public static function fromCents(int $cents): float
    {
        return $cents / 100;
    }

    /**
     * Calculate VAT amount from base amount and VAT rate
     *
     * @param int $amountCents Base amount in cents (HT)
     * @param int $vatRatePercentage VAT rate as percentage (e.g., 20 for 20%)
     * @return float VAT amount in major units
     */
    public static function calculateVat(int $amountCents, int $vatRatePercentage): float
    {
        return ($amountCents * $vatRatePercentage) / 10000;
    }

    /**
     * Calculate total including VAT (TTC)
     *
     * @param int $amountCents Base amount in cents (HT)
     * @param int $vatRatePercentage VAT rate as percentage
     * @return float Total amount including VAT in major units
     */
    public static function calculateTtc(int $amountCents, int $vatRatePercentage): float
    {
        $ht = self::fromCents($amountCents);
        $vat = self::calculateVat($amountCents, $vatRatePercentage);
        return $ht + $vat;
    }

    /**
     * Format amount for display with currency
     *
     * @param float $amount Amount in major units
     * @param string $currency Currency symbol (default: DH)
     * @param string $decimalSeparator Decimal separator (default: ,)
     * @param string $thousandsSeparator Thousands separator (default: space)
     * @return string Formatted amount
     */
    public static function format(
        float $amount,
        string $currency = 'DH',
        string $decimalSeparator = ',',
        string $thousandsSeparator = ' '
    ): string {
        return number_format($amount, 2, $decimalSeparator, $thousandsSeparator) . ' ' . $currency;
    }
}

