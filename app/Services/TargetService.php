<?php

namespace App\Services;

use App\Models\Target;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\ModelNotFoundException;

class TargetService
{
    /**
     * Paginate target with optional filters.
     *
     * @param array<string, mixed> $filters
     */
    public function paginate(array $filters = [], int $perPage = 15): LengthAwarePaginator
    {
        $query = Target::query();

        if (isset($filters['ID_KS'])) {
            $query->where('ID_KS', $filters['ID_KS']);
        }

        if (isset($filters['TGL_TGT'])) {
            $query->where('TGL_TGT', $filters['TGL_TGT']);
        }

        return $query->paginate($perPage);
    }

    /**
     * @param array<string, mixed> $data
     */
    public function create(array $data): Target
    {
        return Target::create($data);
    }

    public function find(string $idKs, string $tglTgt): Target
    {
        $record = Target::where('ID_KS', $idKs)
            ->where('TGL_TGT', $tglTgt)
            ->first();

        if (! $record) {
            throw new ModelNotFoundException('Target not found');
        }

        return $record;
    }

    /**
     * @param array<string, mixed> $data
     */
    public function update(string $idKs, string $tglTgt, array $data): Target
    {
        $record = $this->find($idKs, $tglTgt);
        $record->update($data);

        return $record;
    }

    public function delete(string $idKs, string $tglTgt): void
    {
        $record = $this->find($idKs, $tglTgt);
        $record->delete();
    }
}
