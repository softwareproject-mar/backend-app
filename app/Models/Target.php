<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Target extends Model
{
    protected $table = 'target';

    public $incrementing = false;

    public $timestamps = false;

    protected $fillable = [
        'ID_KS',
        'TGL_TGT',
        'JLH_AGT_BR',
        'STR_SP',
        'SLD_SP',
        'STR_SW',
        'SLD_SW',
        'STR_SS',
        'SLD_SS',
        'STR_SHR',
        'SLD_SHR',
        'STR_SMD',
        'SLD_SMD',
        'STR_SPD',
        'SLD_SPD',
        'STR_SBJ',
        'SLD_SBJ',
        'STR_SJP',
        'SLD_SJP',
        'STR_SRY',
        'SLD_SRY',
        'STR_SKA',
        'SLD_SKA',
        'STR_SRI',
        'SLD_SRI',
        'STR_SSD',
        'SLD_SSD',
        'PCR_PJM',
        'SLD_PJM',
        'BNG_PJM',
        'SLD_BNG',
        'ASR_PKK',
        'REK_SHR',
        'REK_SPD',
        'REK_SMD',
        'REK_SRY',
        'STF_SBJ',
        'STF_SJP',
        'JLH_REK',
        'JLH_TAB',
        'TBN_PK',
        'PRC_SHR',
        'JLH_TAR_SHR',
        'SLD_T_SHR',
        'PRC_SMD',
        'JLH_TAR_SMD',
        'SLD_T_SMD',
        'PRC_SPD',
        'JLH_TAR_SPD',
        'SLD_T_SPD',
        'PRC_SRY',
        'JLH_TAR_SRY',
        'SLD_T_SRY',
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
