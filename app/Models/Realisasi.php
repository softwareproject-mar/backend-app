<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Realisasi extends Model
{
    protected $table = 'realisasi';

    public $incrementing = false;

    public $timestamps = false;

    protected $fillable = [
        'ID_KS',
        'TGL_TGT',
        'JLH_AGT_BR',
        'STR_SP',
        'STR_SW',
        'STR_SS',
        'STR_SHR',
        'STR_SMD',
        'STR_SPD',
        'STR_SBJ',
        'STR_SJP',
        'STR_SRY',
        'STR_SKA',
        'STR_SRI',
        'STR_SSD',
        'PCR_PJM',
        'BNG_PJM',
        'ASR_PKK',
        'REK_SHR',
        'REK_SPD',
        'REK_SMD',
        'REK_SRY',
        'STF_SBJ',
        'STF_SJP',
    ];

    /**
     * Set the keys for a save update query.
     *
     * @param  \Illuminate\Database\Eloquent\Builder  $query
     * @return \Illuminate\Database\Eloquent\Builder
     */
    protected function setKeysForSaveQuery($query)
    {
        $query->where('ID_KS', $this->getAttribute('ID_KS'))
              ->where('TGL_TGT', $this->getAttribute('TGL_TGT'));

        return $query;
    }

    /**
     * Get the primary key for the model.
     *
     * @return string
     */
    public function getKeyName()
    {
        return ['ID_KS', 'TGL_TGT'];
    }

    /**
     * Get the value of the model's primary key.
     *
     * @return mixed
     */
    public function getKey()
    {
        return [
            'ID_KS' => $this->getAttribute('ID_KS'),
            'TGL_TGT' => $this->getAttribute('TGL_TGT'),
        ];
    }
}
