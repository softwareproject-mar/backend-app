<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class TargetResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'ID_KS' => $this->ID_KS,
            'TGL_TGT' => $this->TGL_TGT,
            'JLH_AGT_BR' => $this->JLH_AGT_BR,
            'STR_SP' => $this->STR_SP,
            'SLD_SP' => $this->SLD_SP,
            'STR_SW' => $this->STR_SW,
            'SLD_SW' => $this->SLD_SW,
            'STR_SS' => $this->STR_SS,
            'SLD_SS' => $this->SLD_SS,
            'STR_SHR' => $this->STR_SHR,
            'SLD_SHR' => $this->SLD_SHR,
            'STR_SMD' => $this->STR_SMD,
            'SLD_SMD' => $this->SLD_SMD,
            'STR_SPD' => $this->STR_SPD,
            'SLD_SPD' => $this->SLD_SPD,
            'STR_SBJ' => $this->STR_SBJ,
            'SLD_SBJ' => $this->SLD_SBJ,
            'STR_SJP' => $this->STR_SJP,
            'SLD_SJP' => $this->SLD_SJP,
            'STR_SRY' => $this->STR_SRY,
            'SLD_SRY' => $this->SLD_SRY,
            'STR_SKA' => $this->STR_SKA,
            'SLD_SKA' => $this->SLD_SKA,
            'STR_SRI' => $this->STR_SRI,
            'SLD_SRI' => $this->SLD_SRI,
            'STR_SSD' => $this->STR_SSD,
            'SLD_SSD' => $this->SLD_SSD,
            'PCR_PJM' => $this->PCR_PJM,
            'SLD_PJM' => $this->SLD_PJM,
            'BNG_PJM' => $this->BNG_PJM,
            'SLD_BNG' => $this->SLD_BNG,
            'ASR_PKK' => $this->ASR_PKK,
            'REK_SHR' => $this->REK_SHR,
            'REK_SPD' => $this->REK_SPD,
            'REK_SMD' => $this->REK_SMD,
            'REK_SRY' => $this->REK_SRY,
            'STF_SBJ' => $this->STF_SBJ,
            'STF_SJP' => $this->STF_SJP,
            'JLH_REK' => $this->JLH_REK,
            'JLH_TAB' => $this->JLH_TAB,
            'TBN_PK' => $this->TBN_PK,
            'PRC_SHR' => $this->PRC_SHR,
            'JLH_TAR_SHR' => $this->JLH_TAR_SHR,
            'SLD_T_SHR' => $this->SLD_T_SHR,
            'PRC_SMD' => $this->PRC_SMD,
            'JLH_TAR_SMD' => $this->JLH_TAR_SMD,
            'SLD_T_SMD' => $this->SLD_T_SMD,
            'PRC_SPD' => $this->PRC_SPD,
            'JLH_TAR_SPD' => $this->JLH_TAR_SPD,
            'SLD_T_SPD' => $this->SLD_T_SPD,
            'PRC_SRY' => $this->PRC_SRY,
            'JLH_TAR_SRY' => $this->JLH_TAR_SRY,
            'SLD_T_SRY' => $this->SLD_T_SRY,
        ];
    }
}
