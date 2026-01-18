<?php

namespace App\Enums;

enum ContractStatus: string
{
    case CREATED = 'created';
    case SIGNED = 'signed';
    case PAID = 'paid';
    case PENDING = 'pending';
    case SCHEDULED = 'scheduled';
    case ACTIVE = 'active';
    case SUSPENDED = 'suspended';
    case TERMINATED = 'terminated';

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
