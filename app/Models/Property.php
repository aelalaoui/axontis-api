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
            'float', 'double' => (float) $this->value,
            'boolean', 'bool' => filter_var($this->value, FILTER_VALIDATE_BOOLEAN),
            'array', 'json' => json_decode($this->value, true),
            'date' => $this->value ? \Carbon\Carbon::parse($this->value) : null,
            default => $this->value,
        };
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
            is_int($value) => 'integer',
            is_float($value) => 'float',
            is_bool($value) => 'boolean',
            is_array($value) => 'array',
            $value instanceof \Carbon\Carbon => 'date',
            default => 'string',
        };
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
