<?php

namespace App\Enums;

enum InstallationType: string
{
    case FIRST_INSTALLATION = 'first_installation';
    case CURATIVE = 'curative';
    case SCHEDULED = 'scheduled';

    /**
     * Get all values as array
     */
    public static function values(): array
    {
        return array_map(fn($case) => $case->value, self::cases());
    }

    /**
     * Get validation string for rules
     */
    public static function validationString(): string
    {
        return implode(',', self::values());
    }

    /**
     * Get human-readable label
     */
    public function label(): string
    {
        return match($this) {
            self::FIRST_INSTALLATION => 'Première installation',
            self::CURATIVE => 'Maintenance corrective',
            self::SCHEDULED => 'Maintenance planifiée',
        };
    }
}

