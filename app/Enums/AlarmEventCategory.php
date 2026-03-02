<?php

namespace App\Enums;

enum AlarmEventCategory: string
{
    case INTRUSION = 'intrusion';
    case FIRE = 'fire';
    case FLOOD = 'flood';
    case PANIC = 'panic';
    case ARMING = 'arming';
    case SYSTEM = 'system';

    public function label(): string
    {
        return match ($this) {
            self::INTRUSION => 'Intrusion',
            self::FIRE => 'Incendie',
            self::FLOOD => 'Inondation',
            self::PANIC => 'Panique',
            self::ARMING => 'Armement / Désarmement',
            self::SYSTEM => 'Système',
        };
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

