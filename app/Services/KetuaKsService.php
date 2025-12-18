<?php

namespace App\Services;

use App\Models\KetuaKs;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

class KetuaKsService
{
    /**
     * Paginate ketua_ks with optional filters.
     *
     * @param array<string, mixed> $filters
     */
    public function paginate(array $filters = [], int $perPage = 15): LengthAwarePaginator
    {
        $query = KetuaKs::query();

        $allowedFilters = ['ID_KET', 'NO_AGT', 'NAMA', 'STAT'];

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
    public function create(array $data): KetuaKs
    {
        return KetuaKs::create($data);
    }

    public function find(string $id): KetuaKs
    {
        return KetuaKs::findOrFail($id);
    }

    /**
     * @param array<string, mixed> $data
     */
    public function update(string $id, array $data): KetuaKs
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
