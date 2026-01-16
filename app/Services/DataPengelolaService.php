<?php

namespace App\Services;

use App\Models\DataPengelola;
use App\Traits\LogsActivity;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

class DataPengelolaService
{
    use LogsActivity;
    public function __construct(
        private IdGeneratorService $idGenerator
    ) {
    }
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
        if (! isset($data['ID_PENG']) || empty($data['ID_PENG'])) {
            $data['ID_PENG'] = $this->idGenerator->generate('data-pengelola');
        }

        return $this->performWithLog('create', function() use ($data) {
            return DataPengelola::create($data);
        }, [
            'resource_type' => 'data_pengelola',
            'resource_id' => $data['ID_PENG'] ?? null,
            'description' => 'Menambahkan data pengelola: ' . ($data['NAMA'] ?? 'Unknown'),
            'new_data' => $data,
        ]);
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
        $old = $this->find($id);
        
        return $this->performWithLog('update', function() use ($old, $data) {
            $old->update($data);
            return $old->fresh();
        }, [
            'resource_type' => 'data_pengelola',
            'resource_id' => $id,
            'description' => 'Mengupdate data pengelola: ' . ($old->NAMA ?? $id),
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
            'resource_type' => 'data_pengelola',
            'resource_id' => $id,
            'description' => 'Menghapus data pengelola: ' . ($record->NAMA ?? $id),
            'old_data' => $record->toArray(),
        ]);
    }
}
