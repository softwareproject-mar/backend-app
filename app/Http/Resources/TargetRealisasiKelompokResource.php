<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @property array<string, mixed> $resource
 */
class TargetRealisasiKelompokResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $r = $this->resource;
        $fields = $r['fields'] ?? [];

        return [
            'id_kel' => $r['id_kel'] ?? null,
            'nama_kelompok' => $r['nama_kelompok'] ?? '',
            'tgl_tgt' => $r['tgl_tgt'] ?? null,
            'period_year' => isset($r['period_year']) ? (int) $r['period_year'] : null,
            'period_month' => isset($r['period_month']) ? (int) $r['period_month'] : null,
            'tgl_baris_target' => $r['tgl_baris_target'] ?? null,
            'jumlah_anggota' => (int) ($r['jumlah_anggota'] ?? 0),
            'fields' => TargetRealisasiFieldResource::collection(collect($fields)),
            'nominal_target' => $r['nominal_target'] ?? null,
            'total_realisasi' => $r['total_realisasi'] ?? '0.00',
            'persentase_pencapaian' => $r['persentase_pencapaian'] ?? null,
            'status_target' => $r['status_target'] ?? 'no_target',
        ];
    }
}
