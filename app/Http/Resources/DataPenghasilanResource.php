<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class DataPenghasilanResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->getKey(),
            'NO_AGT' => $this->NO_AGT,
            'PENGHASILAN' => $this->PENGHASILAN,
            'PENGELUARAN' => $this->PENGELUARAN,
            'TGL_DATA' => $this->TGL_DATA,
        ];
    }
}
