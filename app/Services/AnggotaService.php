<?php

namespace App\Services;

use App\Models\Anggota;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

class AnggotaService
{
    /**
     * Paginate anggota with optional filters.
     *
     * @param array<string, mixed> $filters
     */
    public function paginate(array $filters = [], int $perPage = 15): LengthAwarePaginator
    {
        $query = Anggota::query();

        if (isset($filters['NO_AGT'])) {
            $query->where('NO_AGT', $filters['NO_AGT']);
        }

        if (isset($filters['ID_KS'])) {
            $query->where('ID_KS', $filters['ID_KS']);
        }

        if (isset($filters['ID_LO'])) {
            $query->where('ID_LO', $filters['ID_LO']);
        }

        return $query->paginate($perPage);
    }

    public function find(string $id): Anggota
    {
        return Anggota::findOrFail($id);
    }
}
