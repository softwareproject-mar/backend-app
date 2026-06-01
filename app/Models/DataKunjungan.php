<?php

namespace App\Models;

class DataKunjungan extends FirebirdLegacyModel
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
        'FOTO_PATH',
        'LATITUDE',
        'LONGITUDE',
        'created_by',
    ];
}
