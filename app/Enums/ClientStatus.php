<?php

namespace App\Enums;

enum ClientStatus: string
{
    case CREATED = 'created';
    case SIGNED = 'signed';
    case REFUSED = 'refused';
    case PAID = 'paid';
    case ACTIVE = 'active';
    case FORMAL_NOTICE = 'formal_notice';
    case DISABLED = 'disabled';
    case CLOSED = 'closed';

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
