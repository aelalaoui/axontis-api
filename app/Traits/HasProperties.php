<?php

namespace App\Traits;

use App\Models\Property;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Support\Collection;

trait HasProperties
{
    /**
     * Get all extended properties for this model.
     */
    public function properties(): MorphMany
    {
        return $this->morphMany(Property::class, 'extendable', 'extendable_type', 'extendable_id', 'uuid');
    }

    /**
     * Set an extended property
     */
    public function setProperty(string $property, $value, ?string $type = null): Property
    {
        $property = $this->properties()
            ->where('property', $property)
            ->first();

        if (!$property) {
            $property = new Property([
                'property' => $property,
                'extendable_type' => $this->getMorphClass(),
                'extendable_id' => $this->uuid,
            ]);
        }

        $property->setTypedValue($value, $type);
        $property->save();

        return $property;
    }

    /**
     * Get an extended property value
     */
    public function getProperty(string $property, $default = null)
    {
        $property = $this->properties()
            ->where('property', $property)
            ->first();

        return $property ? $property->typed_value : $default;
    }

    /**
     * Get an extended property raw value (as string)
     */
    public function getPropertyRaw(string $property, $default = null): ?string
    {
        $property = $this->properties()
            ->where('property', $property)
            ->first();

        return $property ? $property->value : $default;
    }

    /**
     * Get an extended property type
     */
    public function getPropertyType(string $property): ?string
    {
        $property = $this->properties()
            ->where('property', $property)
            ->first();

        return $property?->type;
    }

    /**
     * Check if an extended property exists
     */
    public function hasProperty(string $property): bool
    {
        return $this->properties()
            ->where('property', $property)
            ->exists();
    }

    /**
     * Remove an extended property
     */
    public function removeProperty(string $property): bool
    {
        return $this->properties()
            ->where('property', $property)
            ->delete() > 0;
    }

    /**
     * Get all extended properties as a key-value array
     */
    public function getAllProperties(): array
    {
        return $this->properties()
            ->get()
            ->pluck('typed_value', 'property')
            ->toArray();
    }

    /**
     * Get all extended properties with their types
     */
    public function getAllPropertiesWithTypes(): array
    {
        return $this->properties()
            ->get()
            ->mapWithKeys(function ($property) {
                return [
                    $property->property => [
                        'value' => $property->typed_value,
                        'type' => $property->type,
                        'raw_value' => $property->value,
                    ]
                ];
            })
            ->toArray();
    }

    /**
     * Set multiple extended properties at once
     */
    public function setProperties(array $properties): Collection
    {
        $results = collect();

        foreach ($properties as $property => $value) {
            $type = null;

            // Allow passing type with value: ['property' => ['value' => 'val', 'type' => 'string']]
            if (is_array($value) && isset($value['value'])) {
                $type = $value['type'] ?? null;
                $value = $value['value'];
            }

            $results->push($this->setProperty($property, $value, $type));
        }

        return $results;
    }

    /**
     * Get extended properties by type
     */
    public function getPropertiesByType(string $type): Collection
    {
        return $this->properties()
            ->where('type', $type)
            ->get()
            ->pluck('typed_value', 'property');
    }

    /**
     * Search extended properties by property name pattern
     */
    public function searchProperties(string $pattern): Collection
    {
        return $this->properties()
            ->where('property', 'like', $pattern)
            ->get()
            ->pluck('typed_value', 'property');
    }

    /**
     * Clear all extended properties
     */
    public function clearProperties(): int
    {
        return $this->properties()->delete();
    }
}
