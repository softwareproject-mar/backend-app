<?php

namespace App\Services;

use App\Models\DataKunjungan;
use App\Traits\LogsActivity;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\ModelNotFoundException;

class DataKunjunganService
{
    use LogsActivity;
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
        return $this->performWithLog('create', function() use ($data) {
            return DataKunjungan::create($data);
        }, [
            'resource_type' => 'data_kunjungan',
            'resource_id' => null,
            'description' => 'Menambahkan data kunjungan: ' . ($data['ID_KEL_SAH'] ?? 'Unknown'),
            'new_data' => $data,
        ]);
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
        $old = $this->find($id);

        return $this->performWithLog('update', function() use ($old, $data) {
            $old->update($data);
            return $old->fresh();
        }, [
            'resource_type' => 'data_kunjungan',
            'resource_id' => (string) $id,
            'description' => 'Mengupdate data kunjungan: ' . ($old->ID_KEL_SAH ?? $id),
            'old_data' => $old->toArray(),
            'new_data' => $data,
        ]);
    }

    public function delete(int $id): void
    {
        $record = $this->find($id);

        $this->performWithLog('delete', function() use ($record) {
            $record->delete();
        }, [
            'resource_type' => 'data_kunjungan',
            'resource_id' => (string) $record->NO_URT,
            'description' => 'Menghapus data kunjungan: ' . ($record->ID_KEL_SAH ?? $record->NO_URT),
            'old_data' => $record->toArray(),
        ]);
    }
}
