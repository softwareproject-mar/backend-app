<?php

namespace App\Services;

use App\Models\DataLo;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

class DataLoService
{
    /**
     * Paginate data_lo with optional filters.
     *
     * @param array<string, mixed> $filters
     */
    public function paginate(array $filters = [], int $perPage = 15): LengthAwarePaginator
    {
        $query = DataLo::query();

        if (isset($filters['ID_LO'])) {
            $query->where('ID_LO', $filters['ID_LO']);
        }

        if (isset($filters['NO_AGT'])) {
            $query->where('NO_AGT', $filters['NO_AGT']);
        }

        return $query->paginate($perPage);
    }

    public function find(string $id): DataLo
    {
        return DataLo::findOrFail($id);
    }
}
