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
        $r = $this->resource;

        return [
            'id' => data_get($r, 'id') ?? $this->getKey(),
            'NO_AGT' => data_get($r, 'NO_AGT'),
            'STR_SP' => data_get($r, 'STR_SP'),
            'STR_SW' => data_get($r, 'STR_SW'),
            'STR_SKA' => data_get($r, 'STR_SKA'),
            'STR_SRI' => data_get($r, 'STR_SRI'),
            'STR_SDK' => data_get($r, 'STR_SDK'),
            'STR_PJM' => data_get($r, 'STR_PJM'),
            'STR_BNG' => data_get($r, 'STR_BNG'),
            'PJM_BARU' => data_get($r, 'PJM_BARU'),
            'STR_SHR' => data_get($r, 'STR_SHR'),
            'STR_SBJ' => data_get($r, 'STR_SBJ'),
            'STR_SJP' => data_get($r, 'STR_SJP'),
            'STR_SPD' => data_get($r, 'STR_SPD'),
            'STR_SRY' => data_get($r, 'STR_SRY'),
            'STR_SMD' => data_get($r, 'STR_SMD'),
            'TGL_LAP' => data_get($r, 'TGL_LAP'),
        ];
    }
}
