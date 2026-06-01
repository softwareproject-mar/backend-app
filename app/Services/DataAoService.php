<?php

namespace App\Services;

use App\Models\DataAo;
use App\Support\CaseInsensitiveSearch;
use App\Support\KelSahReferenceGuard;
use App\Traits\LogsActivity;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;

class DataAoService
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
        if (isset($filters['ID_AO'])) {
            $query->where('ID_AO', $filters['ID_AO']);
        }

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

        if (! empty($filters['search'])) {
            CaseInsensitiveSearch::applyOrLikeContainsGroup(
                $query,
                ['ID_AO', 'NAMA', 'NO_AGT', 'STAT', 'TGL_STAT'],
                (string) $filters['search'],
                ['ID_AO', 'NO_AGT'],
            );
        }
    }

    /**
     * Paginate data_ao with optional filters.
     *
     * @param  array<string, mixed>  $filters
     */
    public function paginate(array $filters = [], int $perPage = 15): LengthAwarePaginator
    {
        $query = DataAo::query();
        $this->applyFilters($query, $filters);

        return $query->paginate($perPage);
    }

    /**
     * @param  array<string, mixed>  $filters
     * @return Collection<int, DataAo>
     */
    public function listForExport(array $filters, int $limit): Collection
    {
        $query = DataAo::query();
        $this->applyFilters($query, $filters);

        return $query->orderBy('ID_AO')->limit($limit)->get();
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function create(array $data): DataAo
    {
        if (! isset($data['ID_AO']) || empty($data['ID_AO'])) {
            $data['ID_AO'] = $this->idGenerator->generate('data-ao');
        }

        return $this->performWithLog('create', function () use ($data) {
            return DataAo::create($data);
        }, [
            'resource_type' => 'data_ao',
            'resource_id' => $data['ID_AO'] ?? null,
            'description' => 'Menambahkan data AO: '.ActivityLogService::anggotaLabelByNoAgt(
                $data['NO_AGT'] ?? null,
                $data['NAMA'] ?? null
            ),
            'new_data' => $data,
        ]);
    }

    public function find(string $id): DataAo
    {
        return DataAo::findOrFail($id);
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function update(string $id, array $data): DataAo
    {
        $old = $this->find($id);

        return $this->performWithLog('update', function () use ($old, $data) {
            $old->update($data);

            return $old->fresh();
        }, [
            'resource_type' => 'data_ao',
            'resource_id' => $id,
            'description' => 'Mengupdate data AO: '.ActivityLogService::anggotaLabelByNoAgt(
                $data['NO_AGT'] ?? $old->NO_AGT ?? null,
                $data['NAMA'] ?? $old->NAMA ?? null
            ),
            'old_data' => $old->toArray(),
            'new_data' => $data,
        ]);
    }

    public function delete(string $id): void
    {
        KelSahReferenceGuard::assertIdNotUsedInKelompok('ID_AO', $id);

        $record = $this->find($id);

        $this->performWithLog('delete', function () use ($record) {
            $record->delete();
        }, [
            'resource_type' => 'data_ao',
            'resource_id' => $id,
            'description' => 'Menghapus data AO: '.ActivityLogService::anggotaLabelByNoAgt(
                $record->NO_AGT ?? null,
                $record->NAMA ?? null
            ),
            'old_data' => $record->toArray(),
        ]);
    }
}
