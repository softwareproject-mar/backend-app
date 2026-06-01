<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\MemberKelompokSnapshotRequest;
use App\Http\Resources\MemberKelompokSnapshotResource;
use App\Services\MemberKelompokSnapshotService;
use Symfony\Component\HttpFoundation\Response;

class MemberKelompokController extends Controller
{
    public function __construct(
        private MemberKelompokSnapshotService $snapshotService,
    ) {}

    public function show(MemberKelompokSnapshotRequest $request): MemberKelompokSnapshotResource|\Illuminate\Http\JsonResponse
    {
        $user = $request->user();
        $validated = $request->validated();
        $perPage = (int) ($validated['per_page'] ?? 15);
        $page = (int) ($validated['page'] ?? 1);

        $payload = $this->snapshotService->build($user, $perPage, $page);

        if ($payload === null) {
            return response()->json([
                'message' => 'Kelompok tidak ditemukan. Pastikan akun memiliki kelompok sahabat (disetujui admin) atau nomor anggota yang terhubung ke data kelompok.',
            ], Response::HTTP_NOT_FOUND);
        }

        return new MemberKelompokSnapshotResource($payload);
    }
}
