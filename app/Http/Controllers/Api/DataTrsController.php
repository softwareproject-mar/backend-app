<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Concerns\RelaxesExportTimeouts;
use App\Http\Controllers\Controller;
use App\Http\Resources\DataTrsResource;
use App\Services\DataTrsService;
use App\Services\TabularExportService;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Symfony\Component\HttpFoundation\StreamedResponse;

class DataTrsController extends Controller
{
    use RelaxesExportTimeouts;

    /** @var list<string> */
    private const EXPORT_HEADERS = [
        'Nomor Anggota',
        'STR SP',
        'STR SW',
        'STR SKA',
        'STR SRI',
        'STR SDK',
        'STR PJM',
        'STR BNG',
        'PJM Baru',
        'STR SHR',
        'STR SBJ',
        'STR SJP',
        'STR SPD',
        'STR SRY',
        'STR SMD',
        'Tgl Laporan',
    ];

    public function __construct(
        private DataTrsService $dataTrsService,
        private TabularExportService $tabularExport,
    ) {}

    public function index(Request $request)
    {
        $perPage = max(1, min($request->integer('per_page', 15), 500));
        $filters = $request->only(['NO_AGT', 'search']);

        $paginator = $this->dataTrsService->paginate($filters, $perPage, $request->user());

        return DataTrsResource::collection($paginator);
    }

    public function exportExcel(Request $request): StreamedResponse
    {
        $this->relaxExportRuntimeLimits();
        $filters = $this->exportFilters($request);
        $rows = $this->dataTrsService->listForExport($filters, $this->exportExcelLimit($request), $request->user());

        return $this->tabularExport->excelResponse(
            'DataTransaksi',
            'Tabel Data Transaksi',
            self::EXPORT_HEADERS,
            $this->rowsToExportMatrix($rows),
        );
    }

    public function exportPdf(Request $request): Response
    {
        $this->relaxExportRuntimeLimits();
        $filters = $this->exportFilters($request);
        $rows = $this->dataTrsService->listForExport($filters, $this->exportPdfLimit($request), $request->user());

        return $this->tabularExport->pdfResponse(
            'DataTransaksi',
            'Tabel Data Transaksi',
            self::EXPORT_HEADERS,
            $this->rowsToExportMatrix($rows),
        );
    }

    /**
     * @return array<string, mixed>
     */
    private function exportFilters(Request $request): array
    {
        $filters = $request->only(['NO_AGT', 'search']);
        $out = [];
        if (isset($filters['NO_AGT']) && is_string($filters['NO_AGT']) && trim($filters['NO_AGT']) !== '') {
            $out['NO_AGT'] = trim($filters['NO_AGT']);
        }
        if (isset($filters['search']) && is_string($filters['search']) && trim($filters['search']) !== '') {
            $out['search'] = trim($filters['search']);
        }

        return $out;
    }

    /**
     * @param  \Illuminate\Support\Collection<int, \App\Models\DataTrs>  $rows
     * @return list<list<string|null>>
     */
    private function rowsToExportMatrix($rows): array
    {
        return $rows->map(static fn ($r): array => [
            $r->NO_AGT ?? '',
            $r->STR_SP ?? '',
            $r->STR_SW ?? '',
            $r->STR_SKA ?? '',
            $r->STR_SRI ?? '',
            $r->STR_SDK ?? '',
            $r->STR_PJM ?? '',
            $r->STR_BNG ?? '',
            $r->PJM_BARU ?? '',
            $r->STR_SHR ?? '',
            $r->STR_SBJ ?? '',
            $r->STR_SJP ?? '',
            $r->STR_SPD ?? '',
            $r->STR_SRY ?? '',
            $r->STR_SMD ?? '',
            $r->TGL_LAP ?? '',
        ])->all();
    }
}
