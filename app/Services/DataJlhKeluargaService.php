<?php

namespace App\Services;

use App\Models\DataJlhKeluarga;
use App\Traits\LogsActivity;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

class DataJlhKeluargaService
{
    use LogsActivity;
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
        return $this->performWithLog('create', function() use ($data) {
            return DataJlhKeluarga::create($data);
        }, [
            'resource_type' => 'data_jlh_keluarga',
            'resource_id' => $data['NO_AGT'] ?? null,
            'description' => 'Menambahkan data jumlah keluarga: ' . ($data['NO_AGT'] ?? 'Unknown'),
            'new_data' => $data,
        ]);
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
        $old = $this->find($id);
        
        return $this->performWithLog('update', function() use ($old, $data) {
            $old->update($data);
            return $old->fresh();
        }, [
            'resource_type' => 'data_jlh_keluarga',
            'resource_id' => $id,
            'description' => 'Mengupdate data jumlah keluarga: ' . $id,
            'old_data' => $old->toArray(),
            'new_data' => $data,
        ]);
    }

    public function delete(string $id): void
    {
        $record = $this->find($id);
        
        $this->performWithLog('delete', function() use ($record) {
            $record->delete();
        }, [
            'resource_type' => 'data_jlh_keluarga',
            'resource_id' => $id,
            'description' => 'Menghapus data jumlah keluarga: ' . $id,
            'old_data' => $record->toArray(),
        ]);
    }
}
