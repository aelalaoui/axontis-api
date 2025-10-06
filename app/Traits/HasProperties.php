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
    public function setProperty(string $propertyName, $value, ?string $type = null): Property
    {
        $existingProperty = $this->properties()
            ->where('property', $propertyName)
            ->first();

        if (!$existingProperty) {
            $existingProperty = new Property([
                'property' => $propertyName,
                'extendable_type' => $this->getMorphClass(),
                'extendable_id' => $this->uuid,
            ]);
        }

        $existingProperty->setTypedValue($value, $type);
        $existingProperty->save();

        return $existingProperty;
    }

    /**
     * Get an extended property value
     */
    public function getProperty(string $propertyName, $default = null)
    {
        $property = $this->properties()
            ->where('property', $propertyName)
            ->first();

        return $property ? $property->typed_value : $default;
    }

    /**
     * Get an extended property raw value (as string)
     */
    public function getPropertyRaw(string $propertyName, $default = null): ?string
    {
        $property = $this->properties()
            ->where('property', $propertyName)
            ->first();

        return $property ? $property->value : $default;
    }

    /**
     * Get an extended property type
     */
    public function getPropertyType(string $propertyName): ?string
    {
        $property = $this->properties()
            ->where('property', $propertyName)
            ->first();

        return $property?->type;
    }

    /**
     * Check if an extended property exists
     */
    public function hasProperty(string $propertyName): bool
    {
        return $this->properties()
            ->where('property', $propertyName)
            ->exists();
    }

    /**
     * Remove an extended property
     */
    public function removeProperty(string $propertyName): bool
    {
        return $this->properties()
            ->where('property', $propertyName)
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

        foreach ($properties as $propertyName => $value) {
            $type = null;

            // Allow passing type with value: ['property' => ['value' => 'val', 'type' => 'string']]
            if (is_array($value) && isset($value['value'])) {
                $type = $value['type'] ?? null;
                $value = $value['value'];
            }

            $results->push($this->setProperty($propertyName, $value, $type));
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
