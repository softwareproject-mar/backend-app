<?php

namespace App\Services;

use App\Models\DataLo;
use App\Traits\LogsActivity;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

class DataLoService
{
    use LogsActivity;
    public function __construct(
        private IdGeneratorService $idGenerator
    ) {
    }
    /**
     * Paginate data_lo with optional filters.
     *
     * @param array<string, mixed> $filters
     */
    public function paginate(array $filters = [], int $perPage = 15): LengthAwarePaginator
    {
        $query = DataLo::query();

        if (isset($filters['ID_LO'])) {
            $query->where('ID_LO', $filters['ID_LO']);
        }

        if (isset($filters['NO_AGT'])) {
            $query->where('NO_AGT', $filters['NO_AGT']);
        }

        return $query->paginate($perPage);
    }

    /**
     * @param array<string, mixed> $data
     */
    public function create(array $data): DataLo
    {
        if (! isset($data['ID_LO']) || empty($data['ID_LO'])) {
            $data['ID_LO'] = $this->idGenerator->generate('data-lo');
        }

        return $this->performWithLog('create', function() use ($data) {
            return DataLo::create($data);
        }, [
            'resource_type' => 'data_lo',
            'resource_id' => $data['ID_LO'] ?? null,
            'description' => 'Menambahkan data LO: ' . ($data['ID_LO'] ?? 'Unknown'),
            'new_data' => $data,
        ]);
    }

    public function find(string $id): DataLo
    {
        return DataLo::findOrFail($id);
    }

    /**
     * @param array<string, mixed> $data
     */
    public function update(string $id, array $data): DataLo
    {
        $old = $this->find($id);
        
        return $this->performWithLog('update', function() use ($old, $data) {
            $old->update($data);
            return $old->fresh();
        }, [
            'resource_type' => 'data_lo',
            'resource_id' => $id,
            'description' => 'Mengupdate data LO: ' . $id,
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
            'resource_type' => 'data_lo',
            'resource_id' => $id,
            'description' => 'Menghapus data LO: ' . $id,
            'old_data' => $record->toArray(),
        ]);
    }
}
