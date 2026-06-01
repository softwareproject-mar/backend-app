<?php

namespace App\Support;

use App\Models\Anggota;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;

final class MemberScope
{
    /**
     * Normalisasi role untuk perbandingan (trim + lowercase).
     * Menghindari mismatch DB/UI seperti "User" atau " user " yang membuat scope CRUD tidak aktif.
     */
    public static function normalizeMemberRole(?User $user): ?string
    {
        if ($user === null) {
            return null;
        }

        $role = $user->role;
        if (! is_string($role)) {
            return null;
        }

        $normalized = strtolower(trim($role));

        return $normalized !== '' ? $normalized : null;
    }

    public static function isRestrictedMemberUser(?User $user): bool
    {
        return self::normalizeMemberRole($user) === 'user';
    }

    public static function memberNoAgt(?User $user): ?string
    {
        if ($user === null) {
            return null;
        }
        $v = $user->no_agt;

        return is_string($v) && $v !== '' ? trim($v) : null;
    }

    public static function memberKelompokId(?User $user): ?string
    {
        if ($user === null) {
            return null;
        }

        // Prioritaskan sumber dari data anggota berdasarkan NO_AGT user,
        // karena ini paling akurat terhadap data operasional harian.
        $noAgt = self::memberNoAgt($user);
        if ($noAgt !== null) {
            $idKs = Anggota::query()->where('NO_AGT', $noAgt)->value('ID_KS');
            if ($idKs !== null && $idKs !== '') {
                return is_string($idKs) ? trim($idKs) : null;
            }
        }

        // Fallback ke kolom users.id_kel untuk kompatibilitas data legacy.
        $direct = $user->id_kel ?? null;
        if (is_string($direct) && trim($direct) !== '') {
            return trim($direct);
        }

        return null;
    }

    /**
     * Paksa filter NO_AGT untuk role user. Null = user tanpa no_agt (hasil kosong).
     *
     * @param  array<string, mixed>  $filters
     * @return array<string, mixed>|null
     */
    public static function mergeNoAgtFilterForMemberUser(?User $user, array $filters): ?array
    {
        if (! self::isRestrictedMemberUser($user)) {
            return $filters;
        }
        $noAgt = self::memberNoAgt($user);
        if ($noAgt === null) {
            return null;
        }
        $filters['NO_AGT'] = $noAgt;

        return $filters;
    }

    /**
     * Filter kel_sah untuk role user (satu kelompok dari anggota.ID_KS).
     *
     * @param  array<string, mixed>  $filters
     * @return array<string, mixed>|null null = tidak ada kelompok / no_agt
     */
    public static function mergeKelSahFilterForMemberUser(?User $user, array $filters): ?array
    {
        if (! self::isRestrictedMemberUser($user)) {
            return $filters;
        }
        $idKel = self::memberKelompokId($user);
        if ($idKel === null) {
            return null;
        }
        $filters['ID_KEL'] = $idKel;

        return $filters;
    }

    public static function emptyPaginator(Request $request, int $perPage): LengthAwarePaginator
    {
        $perPage = max(1, $perPage);

        return new LengthAwarePaginator([], 0, $perPage, 1, [
            'path' => $request->url(),
            'query' => $request->query(),
        ]);
    }

    public static function assertMemberOwnsNoAgt(?User $user, ?string $recordNoAgt): void
    {
        if (! self::isRestrictedMemberUser($user)) {
            return;
        }
        $expected = self::memberNoAgt($user);
        if ($expected === null) {
            abort(403, 'Akun belum ditautkan ke nomor anggota.');
        }
        $actual = $recordNoAgt !== null && $recordNoAgt !== '' ? trim((string) $recordNoAgt) : null;
        if ($actual !== $expected) {
            abort(403, 'Akses ditolak.');
        }
    }

    public static function assertMemberOwnsKelompok(?User $user, string $idKel): void
    {
        if (! self::isRestrictedMemberUser($user)) {
            return;
        }
        $expected = self::memberKelompokId($user);
        if ($expected === null) {
            abort(403, 'Akun belum ditautkan ke nomor anggota atau kelompok tidak ditemukan.');
        }
        if (trim($idKel) !== $expected) {
            abort(403, 'Akses ditolak.');
        }
    }

    /**
     * Validate ownership untuk CRUD operations. Throw 403 jika user tidak memiliki akses.
     */
    public static function validateOwnershipForCrud(?User $user, ?string $recordNoAgt, mixed $recordCreatedBy = null): void
    {
        if (! self::isRestrictedMemberUser($user)) {
            return;
        }

        // Prioritas validasi created_by jika kolom ini tersedia di tabel.
        if ($recordCreatedBy !== null && $recordCreatedBy !== '') {
            if ((int) $recordCreatedBy !== (int) $user->id) {
                abort(403, 'Anda hanya dapat mengubah data yang Anda buat sendiri.');
            }

            return;
        }

        // Fallback legacy: beberapa tabel lama belum punya created_by.
        $expectedNoAgt = self::memberNoAgt($user);
        $actualNoAgt = is_string($recordNoAgt) ? trim($recordNoAgt) : null;
        if ($expectedNoAgt === null || $actualNoAgt === null || $actualNoAgt !== $expectedNoAgt) {
            abort(403, 'Akses ditolak.');
        }
    }

    /**
     * Merge ownership filter untuk CRUD operations. Return original filters jika bukan restricted user.
     *
     * @param  array<string, mixed>  $filters
     * @return array<string, mixed>
     */
    public static function mergeOwnershipFilterForCrud(?User $user, array $filters): array
    {
        if (! self::isRestrictedMemberUser($user)) {
            return $filters;
        }

        // Scope utama CRUD untuk role user: berdasarkan pembuat data.
        $filters['created_by'] = (int) $user->id;

        return $filters;
    }

    /**
     * Inject NO_AGT untuk user role pada create/update operations.
     * Note: NO_AGT is now a regular field, no auto-injection based on user profile.
     *
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    public static function injectNoAgtForUser(?User $user, array $data): array
    {
        // NO_AGT is now a regular input field - no auto-injection for any role
        return $data;
    }
}
