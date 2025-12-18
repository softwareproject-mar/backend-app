<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class KetuaKsResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'ID_KET' => $this->ID_KET,
            'NO_AGT' => $this->NO_AGT,
            'NAMA' => $this->NAMA,
            'STAT' => $this->STAT,
            'TGL_STAT' => $this->TGL_STAT,
            'NO_SK' => $this->NO_SK,
        ];
    }
}
