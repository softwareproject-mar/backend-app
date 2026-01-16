<?php

namespace App\Services;

use App\Models\DataPenghasilan;
use App\Traits\LogsActivity;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

class DataPenghasilanService
{
    use LogsActivity;
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
        return $this->performWithLog('create', function() use ($data) {
            return DataPenghasilan::create($data);
        }, [
            'resource_type' => 'data_penghasilan',
            'resource_id' => $data['NO_AGT'] ?? null,
            'description' => 'Menambahkan data penghasilan: ' . ($data['NO_AGT'] ?? 'Unknown'),
            'new_data' => $data,
        ]);
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
        $old = $this->find($id);
        
        return $this->performWithLog('update', function() use ($old, $data) {
            $old->update($data);
            return $old->fresh();
        }, [
            'resource_type' => 'data_penghasilan',
            'resource_id' => $id,
            'description' => 'Mengupdate data penghasilan: ' . $id,
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
            'resource_type' => 'data_penghasilan',
            'resource_id' => $id,
            'description' => 'Menghapus data penghasilan: ' . $id,
            'old_data' => $record->toArray(),
        ]);
    }
}
