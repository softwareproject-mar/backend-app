<?php

namespace App\Services;

use App\Models\DataPenghasilan;
use App\Models\User;
use App\Support\CaseInsensitiveSearch;
use App\Support\MemberScope;
use App\Support\OwnerScope;
use App\Traits\LogsActivity;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Schema;

class DataPenghasilanService
{
    use LogsActivity;

    private static ?bool $hasIdColumn = null;

    /**
     * @param  array<string, mixed>  $filters
     */
    protected function applyFilters(Builder $query, array $filters): void
    {
        if (isset($filters['NO_AGT'])) {
            $query->where('NO_AGT', $filters['NO_AGT']);
        }

        if (isset($filters['created_by'])) {
            $query->where('created_by', (int) $filters['created_by']);
        }

        if (! empty($filters['search'])) {
            CaseInsensitiveSearch::applyOrLikeContainsGroup(
                $query,
                ['NO_AGT', 'PENGHASILAN', 'PENGELUARAN', 'TGL_DATA'],
                (string) $filters['search'],
                ['NO_AGT'],
            );
        }
    }

    /**
     * Paginate data_penghasilan with optional filters and ownership validation.
     *
     * @param  array<string, mixed>  $filters
     */
    public function paginate(array $filters = [], int $perPage = 15, ?User $user = null): LengthAwarePaginator
    {
        // Apply ownership filtering for user role
        $filters = MemberScope::mergeOwnershipFilterForCrud($user, $filters);

        $query = DataPenghasilan::query();
        $this->applyFilters($query, $filters);
        OwnerScope::applyCreatedByMemberFilter($query, $user);

        return $query->orderBy($this->defaultSortColumn(), 'desc')->paginate($perPage);
    }

    /**
     * @param  array<string, mixed>  $filters
     * @return Collection<int, DataPenghasilan>
     */
    public function listForExport(array $filters, int $limit, ?User $user = null): Collection
    {
        // Apply ownership filtering for user role
        $filters = MemberScope::mergeOwnershipFilterForCrud($user, $filters);

        $query = DataPenghasilan::query();
        $this->applyFilters($query, $filters);
        OwnerScope::applyCreatedByMemberFilter($query, $user);

        return $query->orderBy($this->defaultSortColumn(), 'desc')->limit($limit)->get();
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function create(array $data, ?User $user = null): DataPenghasilan
    {
        // Auto-inject NO_AGT for user role
        $data = MemberScope::injectNoAgtForUser($user, $data);
        if (MemberScope::isRestrictedMemberUser($user)) {
            $data['created_by'] = (int) $user->id;
        }

        /*
         * Skema Firebird: PK_DATA_PENGHASILAN memakai NO_AGT (satu baris per anggota).
         * "Tambah" untuk NO_AGT yang sudah ada harus meng-update baris yang sama, bukan INSERT baru.
         */
        $noAgt = isset($data['NO_AGT']) ? trim((string) $data['NO_AGT']) : '';
        if ($noAgt !== '') {
            $existing = DataPenghasilan::query()->where('NO_AGT', $noAgt)->first();
            if ($existing !== null) {
                MemberScope::validateOwnershipForCrud($user, $existing->NO_AGT, $existing->created_by);

                return $this->update((string) $existing->getKey(), $data, $user);
            }
        }

        if (! isset($data['ID'])) {
            $nextId = ((int) DataPenghasilan::query()->max('ID')) + 1;
            $data['ID'] = $nextId;
        }

        return $this->performWithLog('create', function () use ($data) {
            return DataPenghasilan::create($data);
        }, [
            'resource_type' => 'data_penghasilan',
            'description' => 'Menambahkan data penghasilan: '.ActivityLogService::anggotaLabelByNoAgt($data['NO_AGT'] ?? null),
            'new_data' => $data,
        ]);
    }

    public function find(string $id, ?User $user = null): DataPenghasilan
    {
        $record = DataPenghasilan::findOrFail($id);

        // Validate ownership for user role (NO_AGT atau created_by)
        MemberScope::validateOwnershipForCrud($user, $record->NO_AGT, $record->created_by);

        return $record;
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function update(string $id, array $data, ?User $user = null): DataPenghasilan
    {
        $old = $this->find($id, $user); // This already validates ownership

        // Ensure NO_AGT cannot be changed by user role
        if (MemberScope::isRestrictedMemberUser($user)) {
            unset($data['NO_AGT']); // Remove NO_AGT from update data for security
        }

        return $this->performWithLog('update', function () use ($old, $data) {
            $old->update($data);

            return $this->reloadModelAfterUpdate($old);
        }, [
            'resource_type' => 'data_penghasilan',
            'resource_id' => $id,
            'description' => 'Mengupdate data penghasilan: '.ActivityLogService::anggotaLabelByNoAgt(
                $old->NO_AGT ?? null
            ),
            'old_data' => $old->toArray(),
            'new_data' => $data,
        ]);
    }

    public function delete(string $id, ?User $user = null): void
    {
        $record = $this->find($id, $user); // This already validates ownership

        $this->performWithLog('delete', function () use ($record) {
            $record->delete();
        }, [
            'resource_type' => 'data_penghasilan',
            'resource_id' => $id,
            'description' => 'Menghapus data penghasilan: '.ActivityLogService::anggotaLabelByNoAgt(
                $record->NO_AGT ?? null
            ),
            'old_data' => $record->toArray(),
        ]);
    }

    private function defaultSortColumn(): string
    {
        if (self::$hasIdColumn !== null) {
            return self::$hasIdColumn ? 'ID' : 'NO_AGT';
        }

        $model = new DataPenghasilan;
        $connectionName = $model->getConnectionName() ?? config('database.default');
        self::$hasIdColumn = Schema::connection($connectionName)->hasColumn($model->getTable(), 'ID');

        return self::$hasIdColumn ? 'ID' : 'NO_AGT';
    }
}
