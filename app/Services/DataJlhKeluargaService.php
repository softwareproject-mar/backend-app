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

    /**
     * @param array<string, mixed> $data
     */
    public function create(array $data): DataJlhKeluarga
    {
        return DataJlhKeluarga::create($data);
    }

    public function find(string $id): DataJlhKeluarga
    {
        return DataJlhKeluarga::findOrFail($id);
    }

    /**
     * @param array<string, mixed> $data
     */
    public function update(string $id, array $data): DataJlhKeluarga
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
