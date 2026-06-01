<?php

namespace App\Models;

class DataTrs extends FirebirdLegacyModel
{
    protected $table = 'data_trs';

    protected $primaryKey = 'ID';

    public $incrementing = false;

    protected $keyType = 'int';

    public $timestamps = false;

    protected $fillable = [
        'ID',
        'NO_AGT',
        'STR_SP',
        'STR_SW',
        'STR_SKA',
        'STR_SRI',
        'STR_SDK',
        'STR_PJM',
        'STR_BNG',
        'PJM_BARU',
        'STR_SHR',
        'STR_SBJ',
        'STR_SJP',
        'STR_SPD',
        'STR_SRY',
        'STR_SMD',
        'TGL_LAP',
        'created_by',
    ];
}
