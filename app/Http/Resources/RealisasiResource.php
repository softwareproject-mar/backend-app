<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class RealisasiResource extends JsonResource
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
            'STR_SW' => $this->STR_SW,
            'STR_SS' => $this->STR_SS,
            'STR_SHR' => $this->STR_SHR,
            'STR_SMD' => $this->STR_SMD,
            'STR_SPD' => $this->STR_SPD,
            'STR_SBJ' => $this->STR_SBJ,
            'STR_SJP' => $this->STR_SJP,
            'STR_SRY' => $this->STR_SRY,
            'STR_SKA' => $this->STR_SKA,
            'STR_SRI' => $this->STR_SRI,
            'STR_SSD' => $this->STR_SSD,
            'PCR_PJM' => $this->PCR_PJM,
            'BNG_PJM' => $this->BNG_PJM,
            'ASR_PKK' => $this->ASR_PKK,
            'REK_SHR' => $this->REK_SHR,
            'REK_SPD' => $this->REK_SPD,
            'REK_SMD' => $this->REK_SMD,
            'REK_SRY' => $this->REK_SRY,
            'STF_SBJ' => $this->STF_SBJ,
            'STF_SJP' => $this->STF_SJP,
        ];
    }
}
