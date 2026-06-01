<?php

namespace App\Models;

class DataJlhKeluarga extends FirebirdLegacyModel
{
    protected $table = 'data_jlh_keluarga';

    protected $primaryKey = 'ID';

    public $incrementing = false;

    protected $keyType = 'int';

    public $timestamps = false;

    protected $fillable = [
        'ID',
        'NO_AGT',
        'JLH_AGT_KEL',
        'TGL',
        'created_by',
    ];
}
