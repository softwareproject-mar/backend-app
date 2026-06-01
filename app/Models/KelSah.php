<?php

namespace App\Models;

class KelSah extends FirebirdLegacyModel
{
    protected $table = 'kel_sah';

    protected $primaryKey = 'ID_KEL';

    public $incrementing = false;

    protected $keyType = 'string';

    public $timestamps = false;

    protected $fillable = [
        'ID_KEL',
        'NAMA_KEL',
        'ID_KETUA',
        'ID_SEK',
        'ID_LO',
        'ID_AO',
        'ALAMAT',
        'STAT',
        'TGL_STAT',
        'ID_PENGELOLA',
    ];
}
