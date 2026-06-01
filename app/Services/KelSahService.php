<?php

namespace App\Services;

use App\Models\KelSah;
use App\Support\CaseInsensitiveSearch;
use App\Traits\LogsActivity;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\QueryException;
use Illuminate\Support\Collection;
use Illuminate\Validation\ValidationException;

class KelSahService
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
        if (isset($filters['ID_KEL'])) {
            $query->where('ID_KEL', $filters['ID_KEL']);
        }

        if (! empty($filters['ID_KEL_IN']) && is_array($filters['ID_KEL_IN'])) {
            $ids = array_values(array_filter(
                $filters['ID_KEL_IN'],
                static fn ($v) => $v !== null && $v !== ''
            ));
            if ($ids === []) {
                $query->whereRaw('1 = 0');
            } else {
                $query->whereIn('ID_KEL', $ids);
            }
        }

        if (isset($filters['ID_LO'])) {
            $query->where('ID_LO', $filters['ID_LO']);
        }

        if (isset($filters['ID_AO'])) {
            $query->where('ID_AO', $filters['ID_AO']);
        }

        if (! empty($filters['search'])) {
            CaseInsensitiveSearch::applyOrLikeContainsGroup(
                $query,
                ['ID_KEL', 'NAMA_KEL', 'ID_KETUA', 'ID_SEK', 'ID_LO', 'ID_AO', 'ALAMAT', 'STAT', 'TGL_STAT', 'ID_PENGELOLA'],
                (string) $filters['search'],
                ['ID_KEL', 'ID_KETUA', 'ID_SEK', 'ID_LO', 'ID_AO', 'ID_PENGELOLA'],
            );
        }
    }

    /**
     * Paginate kel_sah with optional filters.
     *
     * @param  array<string, mixed>  $filters
     */
    public function paginate(array $filters = [], int $perPage = 15): LengthAwarePaginator
    {
        $query = KelSah::query();
        $this->applyFilters($query, $filters);

        return $query->paginate($perPage);
    }

    /**
     * @param  array<string, mixed>  $filters
     * @return Collection<int, KelSah>
     */
    public function listForExport(array $filters, int $limit): Collection
    {
        $query = KelSah::query();
        $this->applyFilters($query, $filters);

        return $query->orderBy('ID_KEL')->limit($limit)->get();
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function create(array $data): KelSah
    {
        if (! isset($data['ID_KEL']) || empty($data['ID_KEL'])) {
            $data['ID_KEL'] = $this->idGenerator->generate('kel-sah');
        }

        try {
            return $this->performWithLog('create', function () use ($data) {
                return KelSah::create($data);
            }, [
                'resource_type' => 'kel_sah',
                'resource_id' => $data['ID_KEL'] ?? null,
                'description' => 'Menambahkan kelompok sahabat: '.ActivityLogService::kelompokSahabatLabel(
                    $data['ID_KEL'] ?? null,
                    $data['NAMA_KEL'] ?? null
                ),
                'new_data' => $data,
            ]);
        } catch (QueryException $e) {
            $this->throwIfKelSahDuplicateConstraint($e);
            throw $e;
        }
    }

    public function find(string $id): KelSah
    {
        return KelSah::findOrFail($id);
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function update(string $id, array $data): KelSah
    {
        $old = $this->find($id);

        try {
            return $this->performWithLog('update', function () use ($old, $data) {
                $old->update($data);

                return $old->fresh();
            }, [
                'resource_type' => 'kel_sah',
                'resource_id' => $id,
                'description' => 'Mengupdate kelompok sahabat: '.ActivityLogService::kelompokSahabatLabel(
                    $id,
                    $data['NAMA_KEL'] ?? $old->NAMA_KEL
                ),
                'old_data' => $old->toArray(),
                'new_data' => $data,
            ]);
        } catch (QueryException $e) {
            $this->throwIfKelSahDuplicateConstraint($e);
            throw $e;
        }
    }

    /**
     * Ubah error SQL duplikat jadi validasi 422 agar frontend tidak menampilkan query mentah.
     */
    private function throwIfKelSahDuplicateConstraint(QueryException $e): void
    {
        $sql = $e->getMessage();
        $map = [
            'unq3_kel_sah' => ['ID_KETUA' => ['Ketua ini sudah dipakai di kelompok lain.']],
            'unq2_kel_sah' => ['ID_SEK' => ['Sekretaris ini sudah dipakai di kelompok lain.']],
            'unq_kel_sah_id_lo' => ['ID_LO' => ['LO ini sudah dipakai di kelompok lain.']],
            'unq_kel_sah_id_ao' => ['ID_AO' => ['AO ini sudah dipakai di kelompok lain.']],
            'unq1_kel_sah' => ['NAMA_KEL' => ['Nama kelompok sudah dipakai.']],
        ];
        foreach ($map as $key => $messages) {
            if (str_contains($sql, $key)) {
                throw ValidationException::withMessages($messages);
            }
        }

        if (str_contains($sql, 'Duplicate') && str_contains($sql, 'kel_sah')) {
            throw ValidationException::withMessages([
                'ID_KEL' => ['Ketua, sekretaris, LO, atau AO sudah dipakai di kelompok lain.'],
            ]);
        }
    }

    public function delete(string $id): void
    {
        $record = $this->find($id);

        $this->performWithLog('delete', function () use ($record) {
            $record->delete();
        }, [
            'resource_type' => 'kel_sah',
            'resource_id' => $record->ID_KEL,
            'description' => 'Menghapus kelompok sahabat: '.ActivityLogService::kelompokSahabatLabel(
                $record->ID_KEL,
                $record->NAMA_KEL
            ),
            'old_data' => $record->toArray(),
        ]);
    }
}
