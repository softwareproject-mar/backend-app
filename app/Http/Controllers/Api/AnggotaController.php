<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Concerns\RelaxesExportTimeouts;
use App\Http\Controllers\Controller;
use App\Http\Requests\StoreAnggotaRequest;
use App\Http\Requests\UpdateAnggotaRequest;
use App\Http\Resources\AnggotaResource;
use App\Services\AnggotaExportService;
use App\Services\AnggotaService;
use App\Support\MemberScope;
use Illuminate\Database\QueryException;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpFoundation\StreamedResponse;

class AnggotaController extends Controller
{
    use RelaxesExportTimeouts;

    public function __construct(
        private AnggotaService $service,
        private AnggotaExportService $exportService,
    ) {}

    public function index(Request $request)
    {
        $perPage = $request->integer('per_page', 15);
        $filters = $request->only(['NO_AGT', 'ID_KS', 'search']);
        if (MemberScope::isRestrictedMemberUser($request->user())) {
            $idKs = MemberScope::memberKelompokId($request->user());
            if ($idKs !== null) {
                $filters['ID_KS'] = $idKs;
            } else {
                // Fallback aman: jika mapping kelompok user belum sinkron,
                // tetap tampilkan minimal anggota milik user sendiri.
                $noAgt = MemberScope::memberNoAgt($request->user());
                if ($noAgt === null) {
                    return AnggotaResource::collection(MemberScope::emptyPaginator($request, $perPage));
                }
                $filters['NO_AGT'] = $noAgt;
            }
        }

        $paginator = $this->service->paginate($filters, $perPage);

        return AnggotaResource::collection($paginator);
    }

    public function store(StoreAnggotaRequest $request)
    {
        abort_if(
            config('obormas.strict_member_kelompok_scope') && MemberScope::isRestrictedMemberUser($request->user()),
            403,
            'Akun anggota tidak dapat menambah data master anggota.'
        );

        try {
            $record = $this->service->create($request->validated());
        } catch (QueryException $e) {
            // Fallback jika race / constraint DB (1062 = duplicate entry MySQL)
            $sqlState = $e->errorInfo[0] ?? '';
            $driverCode = (int) ($e->errorInfo[1] ?? 0);
            if ($sqlState === '23000' && ($driverCode === 1062 || str_contains(strtolower($e->getMessage()), 'duplicate'))) {
                throw ValidationException::withMessages([
                    'NO_AGT' => ['Nomor anggota sudah ada di sistem. Gunakan nomor lain atau ubah data yang sudah ada.'],
                ]);
            }
            throw $e;
        }

        return (new AnggotaResource($record))
            ->response()
            ->setStatusCode(Response::HTTP_CREATED);
    }

    public function show(Request $request, string $id)
    {
        $record = $this->service->find($id);
        $this->assertMemberCanAccessAnggota($request, $record);

        return new AnggotaResource($record);
    }

    public function update(UpdateAnggotaRequest $request, string $id)
    {
        abort_if(
            config('obormas.strict_member_kelompok_scope') && MemberScope::isRestrictedMemberUser($request->user()),
            403,
            'Akun anggota tidak dapat mengubah data master anggota.'
        );

        $existing = $this->service->find($id);
        $this->assertMemberCanAccessAnggota($request, $existing);

        $record = $this->service->update($id, $request->validated());

        return new AnggotaResource($record);
    }

    public function destroy(Request $request, string $id)
    {
        abort_if(
            config('obormas.strict_member_kelompok_scope') && MemberScope::isRestrictedMemberUser($request->user()),
            403,
            'Akun anggota tidak dapat menghapus data master anggota.'
        );

        $existing = $this->service->find($id);
        $this->assertMemberCanAccessAnggota($request, $existing);

        $this->service->delete($id);

        return response()->noContent();
    }

    public function exportExcel(Request $request): StreamedResponse
    {
        abort_if(
            config('obormas.strict_member_kelompok_scope') && MemberScope::isRestrictedMemberUser($request->user()),
            403,
            'Akun anggota tidak dapat mengekspor data master anggota.'
        );

        $this->relaxExportRuntimeLimits();
        $filters = $request->only(['NO_AGT', 'ID_KS', 'search']);
        $rows = $this->service->listForExport($filters, $this->exportExcelLimit($request));

        return $this->exportService->excelResponse($rows);
    }

    public function exportPdf(Request $request): Response
    {
        abort_if(
            config('obormas.strict_member_kelompok_scope') && MemberScope::isRestrictedMemberUser($request->user()),
            403,
            'Akun anggota tidak dapat mengekspor data master anggota.'
        );

        $this->relaxExportRuntimeLimits();
        $filters = $request->only(['NO_AGT', 'ID_KS', 'search']);
        $rows = $this->service->listForExport($filters, $this->exportPdfLimit($request));

        return $this->exportService->pdfResponse($rows);
    }

    private function assertMemberCanAccessAnggota(Request $request, \App\Models\Anggota $record): void
    {
        if (! MemberScope::isRestrictedMemberUser($request->user())) {
            return;
        }
        $expected = MemberScope::memberKelompokId($request->user());
        if ($expected === null) {
            abort(403, 'Akun belum ditautkan ke nomor anggota atau kelompok tidak ditemukan.');
        }
        $actual = $record->ID_KS !== null && $record->ID_KS !== '' ? trim((string) $record->ID_KS) : null;
        if ($actual !== $expected) {
            abort(403, 'Akses ditolak.');
        }
    }
}
