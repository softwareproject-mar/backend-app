<?php

namespace App\Services;

use App\Models\Anggota;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

class AnggotaService
{
    /**
     * Paginate anggota with optional filters.
     *
     * @param array<string, mixed> $filters
     */
    public function paginate(array $filters = [], int $perPage = 15): LengthAwarePaginator
    {
        $query = Anggota::query();

        if (isset($filters['NO_AGT'])) {
            $query->where('NO_AGT', $filters['NO_AGT']);
        }

        if (isset($filters['ID_KS'])) {
            $query->where('ID_KS', $filters['ID_KS']);
        }

        if (isset($filters['ID_LO'])) {
            $query->where('ID_LO', $filters['ID_LO']);
        }

        return $query->paginate($perPage);
    }

    /**
     * @param array<string, mixed> $data
     */
    public function create(array $data): Anggota
    {
        return Anggota::create($data);
    }

    public function find(string $id): Anggota
    {
        return Anggota::findOrFail($id);
    }

    /**
     * @param array<string, mixed> $data
     */
    public function update(string $id, array $data): Anggota
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
