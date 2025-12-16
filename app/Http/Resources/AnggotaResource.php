<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class AnggotaResource extends JsonResource
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
            'NAMA' => $this->NAMA,
            'ID_KS' => $this->ID_KS,
            'ID_LO' => $this->ID_LO,
            'ID_AO' => $this->ID_AO,
            'ID_KS_ASL' => $this->ID_KS_ASL,
            'TGL_MTS' => $this->TGL_MTS,
            'TGL_AKTIF' => $this->TGL_AKTIF,
            'TGL_JA' => $this->TGL_JA,
        ];
    }
}
