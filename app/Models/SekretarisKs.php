<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SekretarisKs extends Model
{
    protected $table = 'sekre_ks';

    protected $primaryKey = 'ID_SEKRE';

    public $incrementing = false;

    protected $keyType = 'string';

    public $timestamps = false;

    protected $fillable = [
        'ID_SEKRE',
        'NO_AGT',
        'NAMA',
        'STAT',
        'TGL_STAT',
        'NO_SK',
    ];
}
