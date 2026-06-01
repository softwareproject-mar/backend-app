<?php

namespace App\Services;

use App\Models\DataTrs;
use App\Models\User;
use App\Support\CaseInsensitiveSearch;
use App\Support\MemberScope;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Schema;

class DataTrsService
{
    private static ?bool $hasIdColumn = null;

    /**
     * @param  array<string, mixed>  $filters
     */
    protected function applyFilters(Builder $query, array $filters): void
    {
        if (isset($filters['NO_AGT'])) {
            $noAgt = trim((string) $filters['NO_AGT']);
            if ($noAgt !== '') {
                $query->where('NO_AGT', $noAgt);
            }
        }

        if (! empty($filters['search'])) {
            CaseInsensitiveSearch::applyOrLikeContainsGroup(
                $query,
                [
                    'NO_AGT',
                    'TGL_LAP',
                    'STR_SP',
                    'STR_SW',
                    'STR_PJM',
                    'STR_BNG',
                ],
                (string) $filters['search'],
                ['NO_AGT'],
            );
        }
    }

    protected function applyDefaultOrdering(Builder $query): Builder
    {
        if ($this->hasIdColumn()) {
            return $query->orderBy('NO_AGT')->orderBy('TGL_LAP')->orderBy('ID');
        }

        return $query->orderBy('NO_AGT')->orderBy('TGL_LAP');
    }

    /**
     * @param  array<string, mixed>  $filters
     */
    public function paginate(array $filters = [], int $perPage = 15, ?User $user = null): LengthAwarePaginator
    {
        $perPage = max(1, min($perPage, 500));

        $filters = MemberScope::mergeNoAgtFilterForMemberUser($user, $filters);
        if ($filters === null) {
            return new LengthAwarePaginator([], 0, $perPage, 1);
        }

        $query = DataTrs::query();
        $this->applyFilters($query, $filters);

        return $this->applyDefaultOrdering($query)->paginate($perPage);
    }

    /**
     * @param  array<string, mixed>  $filters
     * @return Collection<int, DataTrs>
     */
    public function listForExport(array $filters, int $limit, ?User $user = null): Collection
    {
        $limit = max(1, min($limit, 10000));

        $filters = MemberScope::mergeNoAgtFilterForMemberUser($user, $filters);
        if ($filters === null) {
            return new Collection;
        }

        $query = DataTrs::query();
        $this->applyFilters($query, $filters);

        return $this->applyDefaultOrdering($query)->limit($limit)->get();
    }

    private function hasIdColumn(): bool
    {
        if (self::$hasIdColumn !== null) {
            return self::$hasIdColumn;
        }

        $model = new DataTrs;
        $connectionName = $model->getConnectionName() ?? config('database.default');
        self::$hasIdColumn = Schema::connection($connectionName)->hasColumn($model->getTable(), 'ID');

        return self::$hasIdColumn;
    }
}
