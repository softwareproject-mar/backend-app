<?php

namespace App\Services;

use App\Models\DataPengelola;
use App\Support\CaseInsensitiveSearch;
use App\Support\KelSahReferenceGuard;
use App\Traits\LogsActivity;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;

class DataPengelolaService
{
    use LogsActivity;

    public function __construct(
        private IdGeneratorService $idGenerator
    ) {}

    /**
     * Query daftar dengan LEFT JOIN ke `anggota` agar `NAMA` selalu terbaca di API
     * (relasi BelongsTo saja sering gagal jika NO_AGT berbeda format/spasi vs tabel anggota).
     *
     * @return Builder<DataPengelola>
     */
    protected function listQueryWithAnggotaNama(): Builder
    {
        return DataPengelola::query()
            ->leftJoin('anggota', function ($join): void {
                $join->whereRaw('TRIM(data_pengelola.NO_AGT) = TRIM(anggota.NO_AGT)');
            })
            ->select('data_pengelola.*')
            ->addSelect('anggota.NAMA as anggota_nama');
    }

    /**
     * @param  array<string, mixed>  $filters
     */
    protected function applyFilters(Builder $query, array $filters, string $dp = 'data_pengelola'): void
    {
        $allowedFilters = ['ID_PENG', 'NO_AGT'];

        foreach ($allowedFilters as $field) {
            if (isset($filters[$field])) {
                $query->where("{$dp}.{$field}", $filters[$field]);
            }
        }

        if (! empty($filters['search'])) {
            CaseInsensitiveSearch::applyOrLikeContainsGroup(
                $query,
                ["{$dp}.ID_PENG", "{$dp}.NO_AGT", "{$dp}.NO_SK", 'anggota.NAMA'],
                (string) $filters['search'],
                ["{$dp}.ID_PENG", "{$dp}.NO_AGT"],
            );
        }
    }

    /**
     * Paginate data_pengelola with optional filters.
     *
     * @param  array<string, mixed>  $filters
     */
    public function paginate(array $filters = [], int $perPage = 15): LengthAwarePaginator
    {
        $query = $this->listQueryWithAnggotaNama();
        $this->applyFilters($query, $filters);

        return $query->orderBy('data_pengelola.ID_PENG')->paginate($perPage);
    }

    /**
     * @param  array<string, mixed>  $filters
     * @return Collection<int, DataPengelola>
     */
    public function listForExport(array $filters, int $limit): Collection
    {
        $query = $this->listQueryWithAnggotaNama();
        $this->applyFilters($query, $filters);

        return $query->orderBy('data_pengelola.ID_PENG')->limit($limit)->get();
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function create(array $data): DataPengelola
    {
        if (! isset($data['ID_PENG']) || empty($data['ID_PENG'])) {
            $data['ID_PENG'] = $this->idGenerator->generate('data-pengelola');
        }

        return $this->performWithLog('create', function () use ($data) {
            $created = DataPengelola::create($data);

            return $this->findWithAnggotaNama($created->ID_PENG);
        }, [
            'resource_type' => 'data_pengelola',
            'resource_id' => $data['ID_PENG'] ?? null,
            'description' => 'Menambahkan data pengelola: '.ActivityLogService::anggotaLabelByNoAgt(
                $data['NO_AGT'] ?? null,
                $data['anggota_nama'] ?? null
            ),
            'new_data' => $data,
        ]);
    }

    public function find(string $id): DataPengelola
    {
        return $this->findWithAnggotaNama($id);
    }

    /**
     * Satu baris data_pengelola + kolom tambahan `anggota_nama` dari join (sama seperti index).
     */
    protected function findWithAnggotaNama(string $id): DataPengelola
    {
        return $this->listQueryWithAnggotaNama()
            ->where('data_pengelola.ID_PENG', $id)
            ->firstOrFail();
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function update(string $id, array $data): DataPengelola
    {
        $old = $this->find($id);

        return $this->performWithLog('update', function () use ($old, $data) {
            $old->update($data);

            return $this->findWithAnggotaNama($old->ID_PENG);
        }, [
            'resource_type' => 'data_pengelola',
            'resource_id' => $id,
            'description' => 'Mengupdate data pengelola: '.ActivityLogService::anggotaLabelByNoAgt(
                $data['NO_AGT'] ?? $old->NO_AGT ?? null,
                $old->anggota_nama ?? null
            ),
            'old_data' => $old->toArray(),
            'new_data' => $data,
        ]);
    }

    public function delete(string $id): void
    {
        KelSahReferenceGuard::assertIdNotUsedInKelompok('ID_PENGELOLA', $id);

        $record = $this->find($id);

        $this->performWithLog('delete', function () use ($record) {
            $record->delete();
        }, [
            'resource_type' => 'data_pengelola',
            'resource_id' => $id,
            'description' => 'Menghapus data pengelola: '.ActivityLogService::anggotaLabelByNoAgt(
                $record->NO_AGT ?? null,
                $record->anggota_nama ?? null
            ),
            'old_data' => $record->toArray(),
        ]);
    }
}
