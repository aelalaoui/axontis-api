<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphMany;

class Product extends Model
{
    use HasFactory, HasUuids;

    protected $fillable = [
        'id_parent',
        'name',
        'property_name',
        'default_value',
        'caution_price_cents',
        'subscription_price_cents',
        'device_uuid',
    ];

    protected $casts = [
        'caution_price_cents' => 'integer',
        'subscription_price_cents' => 'integer',
    ];

    /**
     * Append computed attributes for convenience.
     */
    protected $appends = [
        'caution_price',
        'subscription_price',
    ];

    public $incrementing = false;
    protected $keyType = 'string';

    // -----------------------------------------------
    // Accessors: expose prices in MAD (divide by 100)
    // -----------------------------------------------

    public function getCautionPriceAttribute(): ?float
    {
        return $this->caution_price_cents !== null
            ? $this->caution_price_cents / 100
            : null;
    }

    public function getSubscriptionPriceAttribute(): ?float
    {
        return $this->subscription_price_cents !== null
            ? $this->subscription_price_cents / 100
            : null;
    }

    // -----------------------------------------------
    // Relationships
    // -----------------------------------------------

    public function contracts(): HasMany
    {
        return $this->hasMany(Contract::class, 'product_uuid', 'id');
    }

    public function device(): BelongsTo
    {
        return $this->belongsTo(Device::class, 'device_uuid', 'uuid');
    }

    public function parent(): BelongsTo
    {
        return $this->belongsTo(Product::class, 'id_parent', 'id');
    }

    public function children(): HasMany
    {
        return $this->hasMany(Product::class, 'id_parent', 'id');
    }

    public function files(): MorphMany
    {
        return $this->morphMany(File::class, 'fileable');
    }

    public function documents(): MorphMany
    {
        return $this->morphMany(File::class, 'fileable')->where('type', 'document');
    }
}
