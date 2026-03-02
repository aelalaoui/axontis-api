<?php

namespace App\Enums;

enum AlarmEventSeverity: string
{
    case CRITICAL = 'critical';
    case HIGH = 'high';
    case MEDIUM = 'medium';
    case INFO = 'info';

    public function label(): string
    {
        return match ($this) {
            self::CRITICAL => 'Critique',
            self::HIGH => 'Élevée',
            self::MEDIUM => 'Moyenne',
            self::INFO => 'Information',
        };
    }

    /**
     * Whether this severity should trigger an Alert creation.
     */
    public function shouldCreateAlert(): bool
    {
        return in_array($this, [self::CRITICAL, self::HIGH]);
    }

    public static function values(): array
    {
        return array_map(fn($case) => $case->value, self::cases());
    }

    public static function validationString(): string
    {
        return implode(',', self::values());
    }
}

