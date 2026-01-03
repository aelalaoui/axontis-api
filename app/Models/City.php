<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * App\Models\City
 *
 * @property int $id
 * @property int $region_id
 * @property string $name_ar
 * @property string $name_en
 * @property string $name_fr
 * @property string $country_code
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read \App\Models\Region $region
 */
class City extends Model
{
    protected $fillable = [
        'region_id',
        'name_ar',
        'name_en',
        'name_fr',
        'country_code'
    ];

    protected $hidden = [
        'created_at',
        'updated_at',
    ];

    public function region(): BelongsTo
    {
        return $this->belongsTo(Region::class);
    }
}
