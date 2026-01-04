<?php

namespace App\Models;

use App\Traits\HasUuid;
use Illuminate\Database\Eloquent\SoftDeletes;

class Installation extends Model
{
    use HasUuid, SoftDeletes;

    protected $fillable = [
        'client_uuid',
        'contract_uuid',
        'city_id',
        'address',
        'country_code',
    ];

    protected $attributes = [
        'country_code' => 'MA',
    ];

    public function client()
    {
        return $this->belongsTo(Client::class, 'client_uuid', 'uuid');
    }

    public function contract()
    {
        return $this->belongsTo(Contract::class, 'contract_uuid', 'uuid');
    }

    public function devices()
    {
        return $this->hasMany(Device::class, 'installation_uuid', 'uuid');
    }

    public function getCityArAttribute()
    {
        return City::find($this->city_id)?->name_ar;
    }

    public function getCityFrAttribute()
    {
        return City::find($this->city_id)?->name_fr;
    }

    public function getCityEnAttribute()
    {
        return City::find($this->city_id)?->name_en;
    }
}
