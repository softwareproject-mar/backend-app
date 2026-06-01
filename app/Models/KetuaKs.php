<?php

namespace App\Models;

class KetuaKs extends FirebirdLegacyModel
{
    protected $table = 'ketua_ks';

    protected $primaryKey = 'ID_KET';

    public $incrementing = false;

    protected $keyType = 'string';

    public $timestamps = false;

    protected $fillable = [
        'ID_KET',
        'NO_AGT',
        'NAMA',
        'STAT',
        'TGL_STAT',
        'NO_SK',
    ];
}
