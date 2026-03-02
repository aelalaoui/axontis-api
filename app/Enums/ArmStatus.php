<?php

namespace App\Enums;

enum ArmStatus: string
{
    case DISARMED = 'disarmed';
    case ARMED_AWAY = 'armed_away';
    case ARMED_STAY = 'armed_stay';
    case UNKNOWN = 'unknown';

    public function label(): string
    {
        return match ($this) {
            self::DISARMED => 'Désarmée',
            self::ARMED_AWAY => 'Armée totale',
            self::ARMED_STAY => 'Armée partielle',
            self::UNKNOWN => 'Inconnu',
        };
    }

    public static function values(): array
    {
        return array_map(fn($case) => $case->value, self::cases());
    }
}

