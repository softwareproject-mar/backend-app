<?php

namespace App\Services;

use App\Models\SekretarisKs;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

class SekretarisKsService
{
    /**
     * Paginate sekre_ks with optional filters.
     *
     * @param array<string, mixed> $filters
     */
    public function paginate(array $filters = [], int $perPage = 15): LengthAwarePaginator
    {
        $query = SekretarisKs::query();

        $allowedFilters = ['ID_SEKRE', 'NO_AGT', 'NAMA', 'STAT'];

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
    public function create(array $data): SekretarisKs
    {
        return SekretarisKs::create($data);
    }

    public function find(string $id): SekretarisKs
    {
        return SekretarisKs::findOrFail($id);
    }

    /**
     * @param array<string, mixed> $data
     */
    public function update(string $id, array $data): SekretarisKs
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
