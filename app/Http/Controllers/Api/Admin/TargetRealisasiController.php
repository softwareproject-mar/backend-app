<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Concerns\RelaxesExportTimeouts;
use App\Http\Controllers\Controller;
use App\Http\Requests\UpsertTargetKelompokRequest;
use App\Services\TargetRealisasiExportService;
use App\Services\TargetRealisasiMonitoringService;
use App\Support\TargetPeriod;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Symfony\Component\HttpFoundation\StreamedResponse;

class TargetRealisasiController extends Controller
{
    use RelaxesExportTimeouts;

    public function __construct(
        private TargetRealisasiMonitoringService $service,
        private TargetRealisasiExportService $exportService,
    ) {}

    public function index(): JsonResponse
    {
        $rows = $this->service->listSummariesForAdmin();
        $data = array_map(
            fn (array $row): array => $this->service->serializeSummaryRowForApi($row),
            $rows
        );

        return response()->json([
            'data' => $data,
        ]);
    }

    public function diagnostic(): JsonResponse
    {
        return response()->json([
            'data' => $this->service->diagnosticsForAdmin(),
        ]);
    }

    public function show(Request $request, string $id_kel): JsonResponse
    {
        $tgl = $request->query('tgl_tgt');
        $explicit = is_string($tgl) && trim($tgl) !== '';
        if ($explicit && ! TargetPeriod::isEndOfMonth(trim((string) $tgl))) {
            return response()->json([
                'message' => 'Tanggal target harus akhir bulan (periode bulanan).',
                'errors' => [
                    'tgl_tgt' => ['Tanggal target harus akhir bulan (periode bulanan).'],
                ],
            ], Response::HTTP_UNPROCESSABLE_ENTITY);
        }

        $payload = $this->service->getKelompokDetail(
            $id_kel,
            $explicit ? trim((string) $tgl) : null,
            false,
            false,
        );

        return response()->json([
            'data' => $this->service->serializeKelompokDetailForApi($payload),
        ]);
    }

    public function update(UpsertTargetKelompokRequest $request, string $id_kel): JsonResponse
    {
        $tglRaw = $request->validated('tgl_tgt');
        $normalized = is_string($tglRaw) && trim($tglRaw) !== ''
            ? $this->service->assertEndOfMonthOrFail(trim($tglRaw))
            : null;
        $this->service->upsertTargetsForKelompok($id_kel, $request->targetsForService(), $normalized);
        $payload = $this->service->getKelompokDetail(
            $id_kel,
            $normalized ?? TargetPeriod::currentPeriodEnd(),
            false,
            false,
        );

        return response()->json([
            'data' => $this->service->serializeKelompokDetailForApi($payload),
        ], Response::HTTP_OK);
    }

    public function exportExcel(Request $request): StreamedResponse
    {
        $this->relaxExportRuntimeLimits();
        $blocks = $this->service->buildStructuredExportBlocks($this->exportExcelLimit($request));

        return $this->exportService->excelResponse($blocks);
    }

    public function exportPdf(Request $request): Response
    {
        $this->relaxExportRuntimeLimits();
        $blocks = $this->service->buildStructuredExportBlocks($this->exportPdfLimit($request));

        return $this->exportService->pdfResponse($blocks);
    }
}
