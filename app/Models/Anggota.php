<?php

namespace App\Models;

class Anggota extends FirebirdLegacyModel
{
    protected $table = 'anggota';

    protected $primaryKey = 'NO_AGT';

    public $incrementing = false;

    protected $keyType = 'string';

    public $timestamps = false;

    protected $fillable = [
        'NO_AGT',
        'NAMA',
        'ID_KS',
        'ID_LO',
        'ID_AO',
        'ID_KS_ASL',
        'TGL_MTS',
        'TGL_AKTIF',
        'TGL_JA',
    ];
}
