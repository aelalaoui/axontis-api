<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class City extends Model
{
    protected $fillable = [
        'country_code',
        'postal_code',
        'city',
        'region',
        'prefecture',
    ];

    protected $hidden = [
        'created_at',
        'updated_at',
    ];
}
