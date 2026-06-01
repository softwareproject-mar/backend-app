<?php

namespace App\Models;

class DataLo extends FirebirdLegacyModel
{
    protected $table = 'data_lo';

    protected $primaryKey = 'ID_LO';

    public $incrementing = false;

    protected $keyType = 'string';

    public $timestamps = false;

    protected $fillable = [
        'ID_LO',
        'NO_AGT',
        'ID_TP',
        'NAMA',
        'STAT',
        'TGL_STAT',
    ];
}
