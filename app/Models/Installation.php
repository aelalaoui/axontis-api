<?php

namespace App\Models;

use App\Enums\InstallationType;
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
        'country',
        'type',
        'scheduled_date',
        'scheduled_time',
    ];

    protected $casts = [
        'type' => InstallationType::class,
        'scheduled_date' => 'date',
        'scheduled_time' => 'datetime:H:i',
    ];

    protected $attributes = [
        'type' => 'first_installation',
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
