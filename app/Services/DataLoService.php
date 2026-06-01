<?php

namespace App\Services;

use App\Models\DataLo;
use App\Support\CaseInsensitiveSearch;
use App\Support\KelSahReferenceGuard;
use App\Traits\LogsActivity;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;

class DataLoService
{
    use LogsActivity;

    public function __construct(
        private IdGeneratorService $idGenerator
    ) {}

    /**
     * @param  array<string, mixed>  $filters
     */
    protected function applyFilters(Builder $query, array $filters): void
    {
        if (isset($filters['ID_LO'])) {
            $query->where('ID_LO', $filters['ID_LO']);
        }

        if (isset($filters['NO_AGT'])) {
            $query->where('NO_AGT', $filters['NO_AGT']);
        }

        if (! empty($filters['ID_LO_IN']) && is_array($filters['ID_LO_IN'])) {
            $ids = array_values(array_filter(
                $filters['ID_LO_IN'],
                static fn ($v) => $v !== null && $v !== ''
            ));
            if ($ids === []) {
                $query->whereRaw('1 = 0');
            } else {
                $query->whereIn('ID_LO', $ids);
            }
        }

        if (! empty($filters['search'])) {
            CaseInsensitiveSearch::applyOrLikeContainsGroup(
                $query,
                ['ID_LO', 'NAMA', 'NO_AGT', 'ID_TP', 'STAT', 'TGL_STAT'],
                (string) $filters['search'],
                ['ID_LO', 'NO_AGT'],
            );
        }
    }

    /**
     * Paginate data_lo with optional filters.
     *
     * @param  array<string, mixed>  $filters
     */
    public function paginate(array $filters = [], int $perPage = 15): LengthAwarePaginator
    {
        $query = DataLo::query();
        $this->applyFilters($query, $filters);

        return $query->paginate($perPage);
    }

    /**
     * @param  array<string, mixed>  $filters
     * @return Collection<int, DataLo>
     */
    public function listForExport(array $filters, int $limit): Collection
    {
        $query = DataLo::query();
        $this->applyFilters($query, $filters);

        return $query->orderBy('ID_LO')->limit($limit)->get();
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function create(array $data): DataLo
    {
        if (! isset($data['ID_LO']) || empty($data['ID_LO'])) {
            $data['ID_LO'] = $this->idGenerator->generate('data-lo');
        }

        return $this->performWithLog('create', function () use ($data) {
            return DataLo::create($data);
        }, [
            'resource_type' => 'data_lo',
            'resource_id' => $data['ID_LO'] ?? null,
            'description' => 'Menambahkan data LO: '.ActivityLogService::anggotaLabelByNoAgt(
                $data['NO_AGT'] ?? null,
                $data['NAMA'] ?? null
            ),
            'new_data' => $data,
        ]);
    }

    public function find(string $id): DataLo
    {
        return DataLo::findOrFail($id);
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function update(string $id, array $data): DataLo
    {
        $old = $this->find($id);

        return $this->performWithLog('update', function () use ($old, $data) {
            $old->update($data);

            return $old->fresh();
        }, [
            'resource_type' => 'data_lo',
            'resource_id' => $id,
            'description' => 'Mengupdate data LO: '.ActivityLogService::anggotaLabelByNoAgt(
                $data['NO_AGT'] ?? $old->NO_AGT ?? null,
                $data['NAMA'] ?? $old->NAMA ?? null
            ),
            'old_data' => $old->toArray(),
            'new_data' => $data,
        ]);
    }

    public function delete(string $id): void
    {
        KelSahReferenceGuard::assertIdNotUsedInKelompok('ID_LO', $id);

        $record = $this->find($id);

        $this->performWithLog('delete', function () use ($record) {
            $record->delete();
        }, [
            'resource_type' => 'data_lo',
            'resource_id' => $id,
            'description' => 'Menghapus data LO: '.ActivityLogService::anggotaLabelByNoAgt(
                $record->NO_AGT ?? null,
                $record->NAMA ?? null
            ),
            'old_data' => $record->toArray(),
        ]);
    }
}
