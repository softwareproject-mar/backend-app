<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @property array<string, mixed> $resource
 */
class TargetRealisasiFieldResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $r = $this->resource;

        return [
            'key' => $r['key'] ?? null,
            'label' => $r['label'] ?? '',
            'target' => $r['target'] ?? null,
            'realisasi' => $r['realisasi'] ?? '0.00',
            'persentase' => $r['persentase'] ?? null,
            'status' => $r['status'] ?? 'no_target',
        ];
    }
}
