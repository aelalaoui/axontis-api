<?php

namespace App\Models;

use App\Traits\HasUuid;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Installation extends Model
{
    use HasUuid, SoftDeletes;

    protected $fillable = [
        'client_uuid',
        'contract_uuid',
        'city',
        'address',
        'zip_code',
        'country',
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
}
