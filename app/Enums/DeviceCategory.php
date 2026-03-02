<?php

namespace App\Enums;

enum DeviceCategory: string
{
    case CAMERA = 'camera';
    case NVR = 'nvr';
    case SWITCH = 'switch';
    case CABLE = 'cable';
    case ACCESSORY = 'accessory';
    case ALARM_PANEL = 'alarm_panel';
    case SENSOR = 'sensor';
    case OTHER = 'other';

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
        return match ($this) {
            self::CAMERA => 'Caméra',
            self::NVR => 'NVR / DVR',
            self::SWITCH => 'Switch réseau',
            self::CABLE => 'Câble',
            self::ACCESSORY => 'Accessoire',
            self::ALARM_PANEL => 'Centrale d\'alarme',
            self::SENSOR => 'Détecteur / Capteur',
            self::OTHER => 'Autre',
        };
    }
}

