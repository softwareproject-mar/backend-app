<?php

namespace App\Models;

class DataAo extends FirebirdLegacyModel
{
    protected $table = 'data_ao';

    protected $primaryKey = 'ID_AO';

    public $incrementing = false;

    protected $keyType = 'string';

    public $timestamps = false;

    protected $fillable = [
        'ID_AO',
        'NO_AGT',
        'NAMA',
        'STAT',
        'TGL_STAT',
    ];
}
