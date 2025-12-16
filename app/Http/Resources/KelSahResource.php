<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class KelSahResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'ID_KEL' => $this->ID_KEL,
            'NAMA_KEL' => $this->NAMA_KEL,
            'ID_KETUA' => $this->ID_KETUA,
            'ID_SEK' => $this->ID_SEK,
            'ID_LO' => $this->ID_LO,
            'ID_AO' => $this->ID_AO,
            'ALAMAT' => $this->ALAMAT,
            'STAT' => $this->STAT,
            'TGL_STAT' => $this->TGL_STAT,
            'ID_PENGELOLA' => $this->ID_PENGELOLA,
        ];
    }
}
