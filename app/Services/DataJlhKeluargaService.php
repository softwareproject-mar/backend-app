<?php

namespace App\Services;

use App\Models\DataJlhKeluarga;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

class DataJlhKeluargaService
{
    /**
     * Paginate data_jlh_keluarga with optional filters.
     *
     * @param array<string, mixed> $filters
     */
    public function paginate(array $filters = [], int $perPage = 15): LengthAwarePaginator
    {
        $query = DataJlhKeluarga::query();

        if (isset($filters['NO_AGT'])) {
            $query->where('NO_AGT', $filters['NO_AGT']);
        }

        return $query->paginate($perPage);
    }

    public function find(string $id): DataJlhKeluarga
    {
        return DataJlhKeluarga::findOrFail($id);
    }
}
