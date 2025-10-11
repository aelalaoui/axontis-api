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
        'caution_price',
        'subscription_price',
        'device_uuid',
    ];

    protected $casts = [
        'caution_price' => 'float',
        'subscription_price' => 'float',
    ];

    public $incrementing = false;
    protected $keyType = 'string';

    // Relationships
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
