<?php

namespace App\Services;

use App\Models\DataLo;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

class DataLoService
{
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

        return DataLo::create($data);
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
