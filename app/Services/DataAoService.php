<?php

namespace App\Services;

use App\Models\DataAo;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

class DataAoService
{
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

        return DataAo::create($data);
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
