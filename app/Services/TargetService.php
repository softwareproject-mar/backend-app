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
}
