<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class DataJlhKeluarga extends Model
{
    protected $table = 'data_jlh_keluarga';

    protected $primaryKey = 'NO_AGT';

    public $incrementing = false;

    protected $keyType = 'string';

    public $timestamps = false;

    protected $fillable = [
        'NO_AGT',
        'JLH_AGT_KEL',
        'TGL',
    ];
}
