<?php

namespace App\Enums;

enum ClientStep: string
{
    case EMAIL_STEP = 'email_step';
    case PRICE_STEP = 'price_step';
    case INFO_STEP = 'info_step';
    case INSTALLATION_STEP = 'installation_step';
    case DOCUMENT_STEP = 'document_step';
    case SIGNATURE_STEP = 'signature_step';
    case PAYMENT_STEP = 'payment_step';
    case PASSWORD_STEP = 'password_step';
    case SCHEDULE_STEP = 'schedule_step';
    case COMPLETED_STEPS = 'completed_steps';

    /**
     * Get all status values as array
     */
    public static function values(): array
    {
        return array_map(fn($case) => $case->value, self::cases());
    }

    /**
     * Get all status values as string for validation
     */
    public static function validationString(): string
    {
        return implode(',', self::values());
    }
}
