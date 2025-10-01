<?php

namespace App\Models;

use App\Traits\FromUuid;
use \Illuminate\Database\Eloquent\Model as EloquentModel;

class Model extends EloquentModel
{
    use FromUuid;

    protected $hidden = [
        'id',
    ];

    /**
     * The primary key for the model.
     *
     * @var string
     */
    protected $primaryKey = 'uuid';


    /**
     * The "type" of the primary key ID.
     *
     * @var string
     */
    protected $keyType = 'string';
}