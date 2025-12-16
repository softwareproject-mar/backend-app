<?php

namespace App\Services;

use App\Models\Realisasi;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\ModelNotFoundException;

class RealisasiService
{
    /**
     * Paginate realisasi with optional filters.
     *
     * @param array<string, mixed> $filters
     */
    public function paginate(array $filters = [], int $perPage = 15): LengthAwarePaginator
    {
        $query = Realisasi::query();

        if (isset($filters['ID_KS'])) {
            $query->where('ID_KS', $filters['ID_KS']);
        }

        if (isset($filters['TGL_TGT'])) {
            $query->where('TGL_TGT', $filters['TGL_TGT']);
        }

        return $query->paginate($perPage);
    }

    public function find(string $idKs, string $tglTgt): Realisasi
    {
        $record = Realisasi::where('ID_KS', $idKs)
            ->where('TGL_TGT', $tglTgt)
            ->first();

        if (! $record) {
            throw new ModelNotFoundException('Realisasi not found');
        }

        return $record;
    }
}
