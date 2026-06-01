<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class DataPengelolaResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'ID_PENG' => $this->ID_PENG,
            'NO_AGT' => $this->NO_AGT,
            'NO_SK' => $this->NO_SK,
            'NAMA' => $this->anggota_nama ?? $this->anggota?->NAMA,
        ];
    }
}
