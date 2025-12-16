<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class DataLoResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'ID_LO' => $this->ID_LO,
            'NO_AGT' => $this->NO_AGT,
            'ID_TP' => $this->ID_TP,
            'NAMA' => $this->NAMA,
            'STAT' => $this->STAT,
            'TGL_STAT' => $this->TGL_STAT,
        ];
    }
}
