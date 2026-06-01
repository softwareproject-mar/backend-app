<?php

namespace App\Services;

use App\Models\Anggota;
use App\Models\DataAo;
use App\Models\DataLo;
use App\Models\KelSah;
use App\Models\KetuaKs;
use App\Models\SekretarisKs;
use App\Models\User;
use App\Support\MemberScope;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

class MemberKelompokSnapshotService
{
    /**
     * @return array{
     *     anggota_saya: Anggota|null,
     *     kelompok: KelSah,
     *     ketua: KetuaKs|null,
     *     sekretaris: SekretarisKs|null,
     *     lo: DataLo|null,
     *     ao: DataAo|null,
     *     anggota_sekelompok: LengthAwarePaginator
     * }|null
     */
    public function build(User $user, int $perPage, int $page = 1): ?array
    {
        $idKel = MemberScope::memberKelompokId($user);
        if ($idKel === null) {
            return null;
        }

        $kelompok = KelSah::query()->where('ID_KEL', $idKel)->first();
        if ($kelompok === null) {
            return null;
        }

        $anggotaSaya = null;
        $noAgt = MemberScope::memberNoAgt($user);
        if ($noAgt !== null) {
            $anggotaSaya = Anggota::query()->where('NO_AGT', $noAgt)->first();
        }

        $ketua = $kelompok->ID_KETUA !== null && $kelompok->ID_KETUA !== ''
            ? KetuaKs::query()->where('ID_KET', trim((string) $kelompok->ID_KETUA))->first()
            : null;

        $sekretaris = $kelompok->ID_SEK !== null && $kelompok->ID_SEK !== ''
            ? SekretarisKs::query()->where('ID_SEKRE', trim((string) $kelompok->ID_SEK))->first()
            : null;

        $lo = $kelompok->ID_LO !== null && $kelompok->ID_LO !== ''
            ? DataLo::query()->where('ID_LO', trim((string) $kelompok->ID_LO))->first()
            : null;

        $ao = $kelompok->ID_AO !== null && $kelompok->ID_AO !== ''
            ? DataAo::query()->where('ID_AO', trim((string) $kelompok->ID_AO))->first()
            : null;

        $perPage = max(1, min(500, $perPage));
        $page = max(1, $page);

        $paginator = Anggota::query()
            ->where('ID_KS', $idKel)
            ->orderBy('NO_AGT')
            ->paginate($perPage, ['*'], 'page', $page);

        return [
            'anggota_saya' => $anggotaSaya,
            'kelompok' => $kelompok,
            'ketua' => $ketua,
            'sekretaris' => $sekretaris,
            'lo' => $lo,
            'ao' => $ao,
            'anggota_sekelompok' => $paginator,
        ];
    }
}
