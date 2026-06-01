<?php

namespace App\Http\Resources;

use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin array{
 *     anggota_saya: \App\Models\Anggota|null,
 *     kelompok: \App\Models\KelSah,
 *     ketua: \App\Models\KetuaKs|null,
 *     sekretaris: \App\Models\SekretarisKs|null,
 *     lo: \App\Models\DataLo|null,
 *     ao: \App\Models\DataAo|null,
 *     anggota_sekelompok: LengthAwarePaginator
 * }
 */
class MemberKelompokSnapshotResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        /** @var LengthAwarePaginator $pag */
        $pag = $this->resource['anggota_sekelompok'];

        return [
            'anggota_saya' => $this->resource['anggota_saya'] !== null
                ? new AnggotaResource($this->resource['anggota_saya'])
                : null,
            'kelompok' => new KelSahResource($this->resource['kelompok']),
            'ketua' => $this->resource['ketua'] !== null
                ? new KetuaKsResource($this->resource['ketua'])
                : null,
            'sekretaris' => $this->resource['sekretaris'] !== null
                ? new SekretarisKsResource($this->resource['sekretaris'])
                : null,
            'lo' => $this->resource['lo'] !== null
                ? new DataLoResource($this->resource['lo'])
                : null,
            'ao' => $this->resource['ao'] !== null
                ? new DataAoResource($this->resource['ao'])
                : null,
            'anggota_sekelompok' => [
                'data' => AnggotaResource::collection($pag->items())->resolve(),
                'current_page' => $pag->currentPage(),
                'last_page' => $pag->lastPage(),
                'per_page' => $pag->perPage(),
                'total' => $pag->total(),
                'from' => $pag->firstItem(),
                'to' => $pag->lastItem(),
            ],
        ];
    }
}
