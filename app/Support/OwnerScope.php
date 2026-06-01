<?php

namespace App\Support;

use App\Models\DataJlhKeluarga;
use App\Models\DataKunjungan;
use App\Models\DataPenghasilan;
use App\Models\DataTrs;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;

final class OwnerScope
{
    /**
     * @param  array<string, mixed>  $filters
     * @return array<string, mixed>
     */
    public static function mergeCreatedByFilterForMemberUser(?User $user, array $filters): array
    {
        if (! MemberScope::isRestrictedMemberUser($user)) {
            return $filters;
        }
        $filters['created_by'] = (int) $user->id;

        return $filters;
    }

    public static function assertMemberOwnsCreatedBy(?User $user, mixed $recordCreatedBy): void
    {
        if (! MemberScope::isRestrictedMemberUser($user)) {
            return;
        }
        $ownerId = $recordCreatedBy !== null && $recordCreatedBy !== '' ? (int) $recordCreatedBy : null;
        if ($ownerId === null || $ownerId !== (int) $user->id) {
            abort(403, 'Akses ditolak.');
        }
    }

    /**
     * @return list<string>
     */
    public static function noAgtsFromUserOwnedRows(int $userId): array
    {
        return collect()
            ->merge(DataPenghasilan::query()->where('created_by', $userId)->pluck('NO_AGT'))
            ->merge(DataJlhKeluarga::query()->where('created_by', $userId)->pluck('NO_AGT'))
            ->merge(DataTrs::query()->where('created_by', $userId)->pluck('NO_AGT'))
            ->merge(DataKunjungan::query()->where('created_by', $userId)->pluck('NO_AGT'))
            ->map(fn ($v) => $v !== null && $v !== '' ? trim((string) $v) : null)
            ->filter()
            ->unique()
            ->values()
            ->all();
    }

    /**
     * @return list<string>
     */
    public static function kelompokIdsFromUserKunjungan(int $userId): array
    {
        return DataKunjungan::query()
            ->where('created_by', $userId)
            ->whereNotNull('ID_KEL_SAH')
            ->where('ID_KEL_SAH', '!=', '')
            ->distinct()
            ->pluck('ID_KEL_SAH')
            ->map(fn ($v) => is_string($v) ? trim($v) : (string) $v)
            ->filter()
            ->unique()
            ->values()
            ->all();
    }

    /**
     * @return list<string>
     */
    public static function loIdsFromUserKunjungan(int $userId): array
    {
        return DataKunjungan::query()
            ->where('created_by', $userId)
            ->whereNotNull('ID_LO')
            ->where('ID_LO', '!=', '')
            ->distinct()
            ->pluck('ID_LO')
            ->map(fn ($v) => is_string($v) ? trim($v) : (string) $v)
            ->filter()
            ->unique()
            ->values()
            ->all();
    }

    public static function assertMemberUserCanAccessKelompok(?User $user, string $idKel): void
    {
        if (! MemberScope::isRestrictedMemberUser($user)) {
            return;
        }
        $allowed = self::kelompokIdsFromUserKunjungan((int) $user->id);
        $needle = trim($idKel);
        if ($needle === '' || ! in_array($needle, $allowed, true)) {
            abort(403, 'Akses ditolak.');
        }
    }

    public static function applyCreatedByMemberFilter(Builder $query, ?User $user): void
    {
        if (MemberScope::isRestrictedMemberUser($user)) {
            $query->where('created_by', (int) $user->id);
        }
    }
}
