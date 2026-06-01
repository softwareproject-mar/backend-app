<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;

class DataPengelola extends FirebirdLegacyModel
{
    protected $table = 'data_pengelola';

    protected $primaryKey = 'ID_PENG';

    public $incrementing = false;

    protected $keyType = 'string';

    public $timestamps = false;

    protected $fillable = [
        'ID_PENG',
        'NO_AGT',
        'NO_SK',
    ];

    public function anggota(): BelongsTo
    {
        return $this->belongsTo(Anggota::class, 'NO_AGT', 'NO_AGT');
    }
}
