<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class DataKunjunganResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'NO_URT' => $this->NO_URT,
            'ID_LO' => $this->ID_LO,
            'NO_AGT' => $this->NO_AGT,
            'ID_KEL_SAH' => $this->ID_KEL_SAH,
            'TGL_KUN' => $this->TGL_KUN,
            'KEGIATAN' => $this->KEGIATAN,
            'ID_PIC' => $this->ID_PIC,
            'JLH_PESERTA' => $this->JLH_PESERTA,
        ];
    }
}
