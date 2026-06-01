<?php

namespace App\Models;

class DataPenghasilan extends FirebirdLegacyModel
{
    protected $table = 'data_penghasilan';

    protected $primaryKey = 'ID';

    public $incrementing = false;

    protected $keyType = 'int';

    public $timestamps = false;

    protected $fillable = [
        'ID',
        'NO_AGT',
        'PENGHASILAN',
        'PENGELUARAN',
        'TGL_DATA',
        'created_by',
    ];
}
