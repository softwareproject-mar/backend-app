<?php

namespace App\Services;

use App\Models\DataAo;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

class DataAoService
{
    /**
     * Paginate data_ao with optional filters.
     *
     * @param array<string, mixed> $filters
     */
    public function paginate(array $filters = [], int $perPage = 15): LengthAwarePaginator
    {
        $query = DataAo::query();

        if (isset($filters['ID_AO'])) {
            $query->where('ID_AO', $filters['ID_AO']);
        }

        if (isset($filters['NO_AGT'])) {
            $query->where('NO_AGT', $filters['NO_AGT']);
        }

        return $query->paginate($perPage);
    }

    public function find(string $id): DataAo
    {
        return DataAo::findOrFail($id);
    }
}
