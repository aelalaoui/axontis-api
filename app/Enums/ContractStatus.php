<?php

namespace App\Enums;

enum ContractStatus: string
{
    case SIGNED = 'signed';
    case ACTIVE = 'active';
    case PENDING = 'pending';
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
