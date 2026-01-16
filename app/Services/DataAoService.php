<?php

namespace App\Services;

use App\Models\DataAo;
use App\Traits\LogsActivity;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

class DataAoService
{
    use LogsActivity;
    public function __construct(
        private IdGeneratorService $idGenerator
    ) {
    }
    /**
     * Paginate data_ao with optional filters.
     *
     * @param array<string, mixed> $filters
     */
    public function paginate(array $filters = [], int $perPage = 15): LengthAwarePaginator
    {
        $query = DataAo::query();

        if (isset($filters['ID_AO'])) {
            $query->where('ID_AO', $filters['ID_AO']);
        }

        if (isset($filters['NO_AGT'])) {
            $query->where('NO_AGT', $filters['NO_AGT']);
        }

        return $query->paginate($perPage);
    }

    /**
     * @param array<string, mixed> $data
     */
    public function create(array $data): DataAo
    {
        if (! isset($data['ID_AO']) || empty($data['ID_AO'])) {
            $data['ID_AO'] = $this->idGenerator->generate('data-ao');
        }

        return $this->performWithLog('create', function() use ($data) {
            return DataAo::create($data);
        }, [
            'resource_type' => 'data_ao',
            'resource_id' => $data['ID_AO'] ?? null,
            'description' => 'Menambahkan data AO: ' . ($data['ID_AO'] ?? 'Unknown'),
            'new_data' => $data,
        ]);
    }

    public function find(string $id): DataAo
    {
        return DataAo::findOrFail($id);
    }

    /**
     * @param array<string, mixed> $data
     */
    public function update(string $id, array $data): DataAo
    {
        $old = $this->find($id);
        
        return $this->performWithLog('update', function() use ($old, $data) {
            $old->update($data);
            return $old->fresh();
        }, [
            'resource_type' => 'data_ao',
            'resource_id' => $id,
            'description' => 'Mengupdate data AO: ' . $id,
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
            'resource_type' => 'data_ao',
            'resource_id' => $id,
            'description' => 'Menghapus data AO: ' . $id,
            'old_data' => $record->toArray(),
        ]);
    }
}
