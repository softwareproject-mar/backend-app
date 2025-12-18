<?php

namespace App\Services;

use App\Models\DataPenghasilan;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

class DataPenghasilanService
{
    /**
     * Paginate data_penghasilan with optional filters.
     *
     * @param array<string, mixed> $filters
     */
    public function paginate(array $filters = [], int $perPage = 15): LengthAwarePaginator
    {
        $query = DataPenghasilan::query();

        if (isset($filters['NO_AGT'])) {
            $query->where('NO_AGT', $filters['NO_AGT']);
        }

        return $query->paginate($perPage);
    }

    /**
     * @param array<string, mixed> $data
     */
    public function create(array $data): DataPenghasilan
    {
        return DataPenghasilan::create($data);
    }

    public function find(string $id): DataPenghasilan
    {
        return DataPenghasilan::findOrFail($id);
    }

    /**
     * @param array<string, mixed> $data
     */
    public function update(string $id, array $data): DataPenghasilan
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
