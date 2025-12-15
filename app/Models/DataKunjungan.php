<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class DataKunjungan extends Model
{
    protected $table = 'data_kunjungan';

    protected $primaryKey = 'NO_URT';

    public $incrementing = true;

    protected $keyType = 'int';

    public $timestamps = false;

    protected $fillable = [
        'ID_LO',
        'NO_AGT',
        'ID_KEL_SAH',
        'TGL_KUN',
        'KEGIATAN',
        'ID_PIC',
        'JLH_PESERTA',
    ];
}
