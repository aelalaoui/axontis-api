<?php

namespace App\Enums;

enum ClientStatus: string
{
    case EMAIL_STEP = 'email_step';
    case PRICE_STEP = 'price_step';
    case INFO_STEP = 'info_step';
    case INSTALLATION_STEP = 'installation_step';
    case DOCUMENT_STEP = 'document_step';
    case SIGNATURE_STEP = 'signature_step';
    case SIGNED = 'signed';
    case PAYMENT_STEP = 'payment_step';
    case PAID = 'paid';
    case CREATE_PASSWORD = 'create_password';
    case ACTIVE = 'active';
    case NOT_ACTIVE_DUE_PAYMENT = 'not_active_due_payment';
    case FORMAL_NOTICE = 'formal_notice';
    case DISABLED = 'disabled';

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
