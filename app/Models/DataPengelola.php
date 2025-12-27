<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class DataPengelola extends Model
{
    protected $table = 'data_pengelola';

    protected $primaryKey = 'ID_PENG';

    public $incrementing = false;

    protected $keyType = 'string';

    public $timestamps = false;

    protected $fillable = [
        'ID_PENG',
        'NO_AGT',
        'NO_SK',
    ];
}
