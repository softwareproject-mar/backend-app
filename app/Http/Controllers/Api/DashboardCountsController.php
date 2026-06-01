<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Anggota;
use App\Models\DataAo;
use App\Models\DataKunjungan;
use App\Models\DataLo;
use App\Models\DataPengelola;
use App\Models\KelSah;
use App\Models\KetuaKs;
use App\Models\SekretarisKs;
use App\Services\TargetRealisasiMonitoringService;
use App\Support\MemberScope;
use App\Support\OwnerScope;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class DashboardCountsController extends Controller
{
    public function __construct(
        private TargetRealisasiMonitoringService $targetRealisasiMonitoring,
    ) {}

    /**
     * Return row counts for all dashboard cards in a single request.
     * Admin/super_admin: full counts. Member: scoped to their kelompok.
     */
    public function __invoke(Request $request): JsonResponse
    {
        $user = $request->user();
        $isMember = MemberScope::isRestrictedMemberUser($user);

        if ($isMember) {
            return $this->memberCounts($request, $user);
        }

        return response()->json([
            'data' => [
                'anggota' => Anggota::count(),
                'data_lo' => DataLo::count(),
                'data_ao' => DataAo::count(),
                'kelompok_sahabat' => KelSah::count(),
                'ketua_ks' => KetuaKs::count(),
                'sekretaris_ks' => SekretarisKs::count(),
                'pengelola' => DataPengelola::count(),
                'data_kunjungan' => DataKunjungan::count(),
                'target_realisasi' => $this->targetRealisasiMonitoring->collectMonitoringKelompokIds()->count(),
            ],
        ]);
    }

    private function memberCounts(Request $request, $user): JsonResponse
    {
        $idKs = MemberScope::memberKelompokId($user);

        if ($idKs === null) {
            return response()->json(['data' => array_fill_keys(
                ['anggota', 'data_lo', 'data_ao', 'kelompok_sahabat', 'ketua_ks', 'sekretaris_ks', 'pengelola', 'data_kunjungan', 'target_realisasi'],
                0
            )]);
        }

        $noAgts = OwnerScope::noAgtsFromUserOwnedRows((int) $user->id);

        $anggota = Anggota::where('ID_KS', $idKs)->count();
        $kelSah = KelSah::where('ID_KEL', $idKs)->count();

        if (empty($noAgts)) {
            $dataLo = $dataAo = $ketua = $sekretaris = $pengelola = 0;
        } else {
            $dataLo = DataLo::whereIn('NO_AGT', $noAgts)->count();
            $dataAo = DataAo::whereIn('NO_AGT', $noAgts)->count();
            $ketua = KetuaKs::whereIn('NO_AGT', $noAgts)->count();
            $sekretaris = SekretarisKs::whereIn('NO_AGT', $noAgts)->count();
            $pengelola = DataPengelola::whereIn('NO_AGT', $noAgts)->count();
        }

        return response()->json([
            'data' => [
                'anggota' => $anggota,
                'data_lo' => $dataLo,
                'data_ao' => $dataAo,
                'kelompok_sahabat' => $kelSah,
                'ketua_ks' => $ketua,
                'sekretaris_ks' => $sekretaris,
                'pengelola' => $pengelola,
                'data_kunjungan' => 0,
                'target_realisasi' => $this->memberTargetRealisasiBadgeCount($idKs),
            ],
        ]);
    }

    /** Badge anggota: 1 jika kelompoknya ada di irisan target ∩ realisasi, selain itu 0. */
    private function memberTargetRealisasiBadgeCount(string $idKs): int
    {
        $norm = strtoupper(trim($idKs));
        foreach ($this->targetRealisasiMonitoring->collectMonitoringKelompokIds() as $id) {
            if (strtoupper(trim((string) $id)) === $norm) {
                return 1;
            }
        }

        return 0;
    }
}
