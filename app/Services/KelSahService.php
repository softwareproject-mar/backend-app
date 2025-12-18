<?php

namespace App\Services;

use App\Models\KelSah;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

class KelSahService
{
    /**
     * Paginate kel_sah with optional filters.
     *
     * @param array<string, mixed> $filters
     */
    public function paginate(array $filters = [], int $perPage = 15): LengthAwarePaginator
    {
        $query = KelSah::query();

        if (isset($filters['ID_KEL'])) {
            $query->where('ID_KEL', $filters['ID_KEL']);
        }

        if (isset($filters['ID_LO'])) {
            $query->where('ID_LO', $filters['ID_LO']);
        }

        if (isset($filters['ID_AO'])) {
            $query->where('ID_AO', $filters['ID_AO']);
        }

        return $query->paginate($perPage);
    }

    /**
     * @param array<string, mixed> $data
     */
    public function create(array $data): KelSah
    {
        return KelSah::create($data);
    }

    public function find(string $id): KelSah
    {
        return KelSah::findOrFail($id);
    }

    /**
     * @param array<string, mixed> $data
     */
    public function update(string $id, array $data): KelSah
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
