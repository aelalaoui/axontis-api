<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class Property extends Model
{
    use HasFactory;

    protected $table = 'properties';

    protected $fillable = [
        'extendable_type',
        'extendable_id',
        'property',
        'value',
        'type',
    ];

    protected $casts = [
        'value' => 'string',
    ];

    /**
     * Get the parent extendable model.
     */
    public function extendable(): MorphTo
    {
        return $this->morphTo();
    }

    /**
     * Get the typed value based on the type column
     */
    public function getTypedValueAttribute()
    {
        return match ($this->type) {
            'integer', 'int' => (int) $this->value,
            'float', 'double' => (float) str_replace(',', '.', $this->value), // Support virgule française
            'boolean', 'bool' => $this->convertToBoolean($this->value),
            'array', 'json' => json_decode($this->value, true),
            'date' => $this->value ? \Carbon\Carbon::parse($this->value) : null,
            default => $this->value,
        };
    }

    /**
     * Convert value to boolean, supporting French and English terms
     */
    private function convertToBoolean($value): bool
    {
        if (is_bool($value)) {
            return $value;
        }

        $lowercaseValue = strtolower(trim((string) $value));

        // Valeurs considérées comme true
        $trueValues = ['true', 'vrai', 'oui', 'yes', '1', 'on', 'enabled', 'actif'];

        // Valeurs considérées comme false
        $falseValues = ['false', 'faux', 'non', 'no', '0', 'off', 'disabled', 'inactif'];

        if (in_array($lowercaseValue, $trueValues)) {
            return true;
        }

        if (in_array($lowercaseValue, $falseValues)) {
            return false;
        }

        // Fallback sur la fonction PHP standard
        return filter_var($value, FILTER_VALIDATE_BOOLEAN);
    }

    /**
     * Set the value with automatic type detection
     */
    public function setTypedValue($value, ?string $type = null): void
    {
        if ($type === null) {
            $type = $this->detectType($value);
        }

        $this->type = $type;
        $this->value = $this->convertValueToString($value);
    }

    /**
     * Detect the type of a value
     */
    private function detectType($value): string
    {
        return match (true) {
            // Types PHP natifs
            is_int($value) => 'integer',
            is_float($value) => 'float',
            is_bool($value) => 'boolean',
            is_array($value) => 'array',
            $value instanceof \Carbon\Carbon => 'date',

            // Détection intelligente pour les chaînes
            is_string($value) => $this->detectStringType($value),

            default => 'string',
        };
    }

    /**
     * Detect the type of a string value by analyzing its content
     */
    private function detectStringType(string $value): string
    {
        // Valeur vide ou null
        if (empty($value) || $value === 'null') {
            return 'string';
        }

        // Booléens en français et anglais
        $lowercaseValue = strtolower(trim($value));
        if (in_array($lowercaseValue, ['true', 'false', 'vrai', 'faux', 'oui', 'non', 'yes', 'no', '1', '0'])) {
            return 'boolean';
        }

        // Nombres entiers (y compris négatifs)
        if (preg_match('/^-?\d+$/', $value)) {
            return 'integer';
        }

        // Nombres décimaux (avec point ou virgule)
        if (preg_match('/^-?\d+[.,]\d+$/', $value)) {
            return 'float';
        }

        // Dates ISO (format basique)
        if (preg_match('/^\d{4}-\d{2}-\d{2}/', $value)) {
            return 'date';
        }

        // JSON arrays ou objects
        if ((str_starts_with($value, '[') && str_ends_with($value, ']')) ||
            (str_starts_with($value, '{') && str_ends_with($value, '}'))) {
            $decoded = json_decode($value, true);
            if (json_last_error() === JSON_ERROR_NONE) {
                return 'array';
            }
        }

        // Par défaut, c'est une chaîne
        return 'string';
    }

    /**
     * Convert value to string for storage
     */
    private function convertValueToString($value): string
    {
        return match (true) {
            is_array($value) => json_encode($value),
            is_bool($value) => $value ? '1' : '0',
            $value instanceof \Carbon\Carbon => $value->toISOString(),
            default => (string) $value,
        };
    }
}
