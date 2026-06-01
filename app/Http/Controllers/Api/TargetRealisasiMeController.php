<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\TargetRealisasiMonitoringService;
use App\Support\MemberScope;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class TargetRealisasiMeController extends Controller
{
    public function __construct(
        private TargetRealisasiMonitoringService $service,
    ) {}

    public function __invoke(Request $request): JsonResponse
    {
        $idKel = MemberScope::memberKelompokId($request->user());
        if ($idKel === null || $idKel === '') {
            return response()->json([
                'message' => 'Kelompok tidak ditemukan untuk akun ini.',
            ], Response::HTTP_NOT_FOUND);
        }

        if (! $this->service->kelompokExists($idKel)) {
            return response()->json([
                'message' => 'Kelompok tidak ditemukan.',
            ], Response::HTTP_NOT_FOUND);
        }

        $payload = $this->service->getSummaryForKelompok($idKel);

        return response()->json([
            'data' => $this->service->serializeSummaryRowForApi($payload),
        ]);
    }
}
