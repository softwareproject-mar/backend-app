<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class DataTrs extends Model
{
    protected $table = 'data_trs';

    protected $primaryKey = 'NO_AGT';

    public $incrementing = false;

    protected $keyType = 'string';

    public $timestamps = false;

    protected $fillable = [
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
    ];
}
