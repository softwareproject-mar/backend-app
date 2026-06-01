<?php

namespace App\Services;

use App\Models\Anggota;
use App\Support\CaseInsensitiveSearch;
use App\Traits\LogsActivity;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;

class AnggotaService
{
    use LogsActivity;

    /**
     * @param  array<string, mixed>  $filters
     */
    protected function applyFilters(Builder $query, array $filters): void
    {
        if (isset($filters['NO_AGT'])) {
            $query->where('NO_AGT', $filters['NO_AGT']);
        }

        if (! empty($filters['NO_AGT_IN']) && is_array($filters['NO_AGT_IN'])) {
            $ids = array_values(array_filter(
                $filters['NO_AGT_IN'],
                static fn ($v) => $v !== null && $v !== ''
            ));
            if ($ids === []) {
                $query->whereRaw('1 = 0');
            } else {
                $query->whereIn('NO_AGT', $ids);
            }
        }

        if (isset($filters['ID_KS'])) {
            $query->where('ID_KS', $filters['ID_KS']);
        }

        if (! empty($filters['search'])) {
            CaseInsensitiveSearch::applyOrLikeContainsGroup(
                $query,
                ['NO_AGT', 'NAMA', 'ID_KS', 'ID_KS_ASL', 'TGL_MTS', 'TGL_AKTIF', 'TGL_JA'],
                (string) $filters['search'],
                ['NO_AGT', 'ID_KS', 'ID_KS_ASL'],
            );
        }
    }

    /**
     * Paginate anggota with optional filters.
     *
     * @param  array<string, mixed>  $filters
     */
    public function paginate(array $filters = [], int $perPage = 15): LengthAwarePaginator
    {
        $query = Anggota::query();
        $this->applyFilters($query, $filters);

        return $query->orderBy('NO_AGT')->paginate($perPage);
    }

    /**
     * Rows for export (same filters as index), capped for memory safety.
     *
     * @param  array<string, mixed>  $filters
     * @return Collection<int, Anggota>
     */
    public function listForExport(array $filters, int $limit): Collection
    {
        $query = Anggota::query();
        $this->applyFilters($query, $filters);

        return $query->orderBy('NO_AGT')->limit($limit)->get();
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function create(array $data): Anggota
    {
        return $this->performWithLog('create', function () use ($data) {
            return Anggota::create($data);
        }, [
            'resource_type' => 'anggota',
            'resource_id' => $data['NO_AGT'] ?? null,
            'description' => 'Menambahkan anggota: '.($data['NAMA'] ?? 'Unknown'),
            'new_data' => $data,
        ]);
    }

    public function find(string $id): Anggota
    {
        return Anggota::findOrFail($id);
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function update(string $id, array $data): Anggota
    {
        $old = $this->find($id);

        return $this->performWithLog('update', function () use ($old, $data) {
            $old->update($data);

            return $old->fresh();
        }, [
            'resource_type' => 'anggota',
            'resource_id' => $id,
            'description' => 'Mengupdate anggota: '.($old->NAMA ?? $id),
            'old_data' => $old->toArray(),
            'new_data' => $data,
        ]);
    }

    public function delete(string $id): void
    {
        $record = $this->find($id);

        $this->performWithLog('delete', function () use ($record) {
            $record->delete();
        }, [
            'resource_type' => 'anggota',
            'resource_id' => $id,
            'description' => 'Menghapus anggota: '.($record->NAMA ?? $id),
            'old_data' => $record->toArray(),
        ]);
    }
}
