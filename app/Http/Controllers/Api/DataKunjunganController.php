<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Concerns\RelaxesExportTimeouts;
use App\Http\Controllers\Controller;
use App\Http\Requests\StoreDataKunjunganRequest;
use App\Http\Requests\UpdateDataKunjunganRequest;
use App\Http\Resources\DataKunjunganResource;
use App\Services\DataKunjunganService;
use App\Services\TabularExportService;
use App\Support\MemberScope;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Symfony\Component\HttpFoundation\StreamedResponse;

class DataKunjunganController extends Controller
{
    use RelaxesExportTimeouts;

    /** @var list<string> */
    private const EXPORT_HEADERS = [
        'No Urut',
        'Nomor Anggota',
        'ID Kelompok',
        'Tanggal Kunjungan',
        'Kegiatan',
        'ID PIC',
        'Jumlah Peserta',
        'URL Foto',
        'Latitude',
        'Longitude',
    ];

    public function __construct(
        private DataKunjunganService $service,
        private TabularExportService $tabularExport,
    ) {}

    /**
     * Hanya anggota (restricted user) yang boleh mutasi data kunjungan.
     */
    private function denyKunjunganMutationUnlessMemberUser(Request $request): void
    {
        if (! MemberScope::isRestrictedMemberUser($request->user())) {
            abort(403, 'Hanya anggota yang dapat menambah, mengubah, atau menghapus data kunjungan.');
        }
    }

    public function reportGroupSummary(Request $request): JsonResponse
    {
        $filters = $this->kunjunganAdminReportFilters($request);
        $rows = $this->service->reportGroupSummaryRows($filters);

        return response()->json([
            'data' => $rows->all(),
        ]);
    }

    public function reportAnggotaSummaryForKelompok(Request $request, string $id_kel_sah): JsonResponse
    {
        $id = trim($id_kel_sah);
        if ($id === '') {
            return response()->json(['data' => []]);
        }

        $filters = $this->kunjunganAdminReportFilters($request);
        $rows = $this->service->reportAnggotaSummaryForKelompok($id, $filters);

        return response()->json([
            'data' => $rows->all(),
        ]);
    }

    public function index(Request $request)
    {
        $perPage = min($request->integer('per_page', 10000), 50000);
        $filters = $this->kunjunganListFilters($request);

        $paginator = $this->service->paginate($filters, $perPage, $request->user());

        return DataKunjunganResource::collection($paginator);
    }

    public function store(StoreDataKunjunganRequest $request)
    {
        $this->denyKunjunganMutationUnlessMemberUser($request);
        $validated = $request->validated();
        $validated['created_by'] = $request->user()->id;
        $photoFile = $request->file('photo');

        $record = $this->service->create($validated, $photoFile, $request->user());

        return (new DataKunjunganResource($record))
            ->response()
            ->setStatusCode(Response::HTTP_CREATED);
    }

    public function show(int $id, Request $request)
    {
        $record = $this->service->find($id, $request->user());

        return new DataKunjunganResource($record);
    }

    public function photo(int $id, Request $request): BinaryFileResponse|\Illuminate\Http\JsonResponse
    {
        $record = $this->service->find($id, $request->user());
        $fotoPath = trim((string) ($record->FOTO_PATH ?? ''));
        if ($fotoPath === '') {
            return response()->json(['message' => 'Foto tidak ditemukan.'], 404);
        }

        $disk = Storage::disk('public');
        if (! $disk->exists($fotoPath)) {
            return response()->json(['message' => 'File foto tidak ditemukan di storage.'], 404);
        }

        return response()->file($disk->path($fotoPath));
    }

    public function update(UpdateDataKunjunganRequest $request, int $id)
    {
        $this->denyKunjunganMutationUnlessMemberUser($request);
        $validated = $request->validated();
        $photoFile = $request->file('photo');

        $record = $this->service->update($id, $validated, $photoFile, $request->user());

        return new DataKunjunganResource($record);
    }

    public function destroy(int $id, Request $request)
    {
        $this->denyKunjunganMutationUnlessMemberUser($request);
        $this->service->delete($id, $request->user());

        return response()->noContent();
    }

    public function exportExcel(Request $request): StreamedResponse
    {
        $this->relaxExportRuntimeLimits();
        $filters = $this->kunjunganListFilters($request);
        $rows = $this->service->listForExport($filters, $this->exportExcelLimit($request), $request->user());
        $data = $rows->map(fn ($r) => [
            $r->NO_URT,
            $r->NO_AGT,
            $r->ID_KEL_SAH,
            $r->TGL_KUN,
            $r->KEGIATAN,
            $r->ID_PIC,
            $r->JLH_PESERTA,
            $this->fotoUrlForExport($r->FOTO_PATH),
            $r->LATITUDE,
            $r->LONGITUDE,
        ])->all();

        return $this->tabularExport->excelResponse('DataKunjungan', 'Tabel Data Kunjungan', self::EXPORT_HEADERS, $data);
    }

    public function exportPdf(Request $request): Response
    {
        $this->relaxExportRuntimeLimits();
        $filters = $this->kunjunganListFilters($request);
        $rows = $this->service->listForExport($filters, $this->exportPdfLimit($request), $request->user());
        $data = $rows->map(fn ($r) => [
            $r->NO_URT,
            $r->NO_AGT,
            $r->ID_KEL_SAH,
            $r->TGL_KUN,
            $r->KEGIATAN,
            $r->ID_PIC,
            $r->JLH_PESERTA,
            $this->fotoUrlForExport($r->FOTO_PATH),
            $r->LATITUDE,
            $r->LONGITUDE,
        ])->all();

        return $this->tabularExport->pdfResponse('DataKunjungan', 'Tabel Data Kunjungan', self::EXPORT_HEADERS, $data);
    }

    /**
     * URL publik untuk export Excel/PDF. Prioritas: config obormas.export_storage_base
     * (default hardcode demo), lalu fallback Storage::disk('public')->url().
     */
    private function fotoUrlForExport(?string $fotoPath): string
    {
        if ($fotoPath === null || $fotoPath === '') {
            return '';
        }

        $trimmed = trim($fotoPath);
        if (str_starts_with($trimmed, 'http://') || str_starts_with($trimmed, 'https://')) {
            return $trimmed;
        }

        $base = trim((string) config('obormas.export_storage_base', ''));
        if ($base !== '') {
            return rtrim($base, '/').'/'.ltrim($trimmed, '/');
        }

        return Storage::disk('public')->url($trimmed);
    }

    /**
     * @return array<string, mixed>
     */
    private function kunjunganListFilters(Request $request): array
    {
        return $request->only([
            'ID_LO',
            'NO_AGT',
            'ID_KEL_SAH',
            'TGL_KUN',
            'KEGIATAN',
            'ID_PIC',
            'search',
        ]);
    }

    /**
     * Query opsional laporan admin (nama kelompok / anggota).
     *
     * @return array{search?: string}
     */
    private function kunjunganAdminReportFilters(Request $request): array
    {
        /** @var array{search?: string} */
        $validated = $request->validate([
            'search' => ['nullable', 'string', 'max:200'],
        ]);

        return $validated;
    }
}
