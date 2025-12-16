<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Anggota extends Model
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
