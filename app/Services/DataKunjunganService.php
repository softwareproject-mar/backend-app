<?php

namespace App\Services;

use App\Models\DataKunjungan;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\ModelNotFoundException;

class DataKunjunganService
{
    /**
     * Paginate data kunjungan with optional filters.
     *
     * @param array<string, mixed> $filters
     */
    public function paginate(array $filters = [], int $perPage = 15): LengthAwarePaginator
    {
        $query = DataKunjungan::query();

        $allowedFilters = [
            'ID_LO',
            'NO_AGT',
            'ID_KEL_SAH',
            'TGL_KUN',
            'KEGIATAN',
            'ID_PIC',
        ];

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
    public function create(array $data): DataKunjungan
    {
        return DataKunjungan::create($data);
    }

    public function find(int $id): DataKunjungan
    {
        return DataKunjungan::findOrFail($id);
    }

    /**
     * @param array<string, mixed> $data
     */
    public function update(int $id, array $data): DataKunjungan
    {
        $record = $this->find($id);

        $record->update($data);

        return $record;
    }

    public function delete(int $id): void
    {
        $record = $this->find($id);

        $record->delete();
    }
}
