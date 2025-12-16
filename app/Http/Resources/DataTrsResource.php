<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class DataTrsResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'NO_AGT' => $this->NO_AGT,
            'STR_SP' => $this->STR_SP,
            'STR_SW' => $this->STR_SW,
            'STR_SKA' => $this->STR_SKA,
            'STR_SRI' => $this->STR_SRI,
            'STR_SDK' => $this->STR_SDK,
            'STR_PJM' => $this->STR_PJM,
            'STR_BNG' => $this->STR_BNG,
            'PJM_BARU' => $this->PJM_BARU,
            'STR_SHR' => $this->STR_SHR,
            'STR_SBJ' => $this->STR_SBJ,
            'STR_SJP' => $this->STR_SJP,
            'STR_SPD' => $this->STR_SPD,
            'STR_SRY' => $this->STR_SRY,
            'STR_SMD' => $this->STR_SMD,
            'TGL_LAP' => $this->TGL_LAP,
        ];
    }
}
