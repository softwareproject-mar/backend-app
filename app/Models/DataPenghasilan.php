<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class DataPenghasilan extends Model
{
    protected $table = 'data_penghasilan';

    protected $primaryKey = 'NO_AGT';

    public $incrementing = false;

    protected $keyType = 'string';

    public $timestamps = false;

    protected $fillable = [
        'NO_AGT',
        'PENGHASILAN',
        'PENGELUARAN',
        'TGL_DATA',
    ];
}
