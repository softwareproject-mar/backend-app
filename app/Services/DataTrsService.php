<?php

namespace App\Services;

use App\Models\DataTrs;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

class DataTrsService
{
    /**
     * Paginate data_trs with optional filters.
     *
     * @param array<string, mixed> $filters
     */
    public function paginate(array $filters = [], int $perPage = 15): LengthAwarePaginator
    {
        $query = DataTrs::query();

        if (isset($filters['NO_AGT'])) {
            $query->where('NO_AGT', $filters['NO_AGT']);
        }

        return $query->paginate($perPage);
    }

    public function find(string $id): DataTrs
    {
        return DataTrs::findOrFail($id);
    }
}
