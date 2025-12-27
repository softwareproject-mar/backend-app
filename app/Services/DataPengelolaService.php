<?php

namespace App\Services;

use App\Models\DataPengelola;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

class DataPengelolaService
{
    /**
     * Paginate data_pengelola with optional filters.
     *
     * @param array<string, mixed> $filters
     */
    public function paginate(array $filters = [], int $perPage = 15): LengthAwarePaginator
    {
        $query = DataPengelola::query();

        $allowedFilters = ['ID_PENG', 'NO_AGT'];

        foreach ($allowedFilters as $field) {
            if (isset($filters[$field])) {
                $query->where($field, $filters[$field]);
            }
        }

        return $query->paginate($perPage);
    }

    /**
     * @param array<string, mixed> $data
     */
    public function create(array $data): DataPengelola
    {
        return DataPengelola::create($data);
    }

    public function find(string $id): DataPengelola
    {
        return DataPengelola::findOrFail($id);
    }

    /**
     * @param array<string, mixed> $data
     */
    public function update(string $id, array $data): DataPengelola
    {
        $record = $this->find($id);
        $record->update($data);

        return $record;
    }

    public function delete(string $id): void
    {
        $record = $this->find($id);
        $record->delete();
    }
}
