<?php

namespace App\Traits;

/**
 * Trait FromUuid
 *
 */
trait FromUuid
{
    /**
     * Merge new searchable attributes with existing searchable attributes on the model.
     *
     * @param string $uuid
     * @return self|null
     */
    public static function fromUuid(string $uuid): ?static
    {
        return self::Query()->where('uuid', $uuid)->first();
    }
}
