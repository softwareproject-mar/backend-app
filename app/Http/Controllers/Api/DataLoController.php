<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Concerns\RelaxesExportTimeouts;
use App\Http\Controllers\Controller;
use App\Http\Requests\StoreDataLoRequest;
use App\Http\Requests\UpdateDataLoRequest;
use App\Http\Resources\DataLoResource;
use App\Services\DataLoService;
use App\Services\TabularExportService;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Symfony\Component\HttpFoundation\StreamedResponse;

class DataLoController extends Controller
{
    use RelaxesExportTimeouts;

    /** @var list<string> */
    private const EXPORT_HEADERS = [
        'ID LO',
        'Nomor Anggota',
        'ID Tipe',
        'Nama',
        'Status',
        'Tanggal Status',
    ];

    public function __construct(
        private DataLoService $service,
        private TabularExportService $tabularExport,
    ) {}

    public function index(Request $request)
    {
        $perPage = $request->integer('per_page', 15);
        $filters = $request->only(['ID_LO', 'NO_AGT', 'search']);

        $paginator = $this->service->paginate($filters, $perPage);

        return DataLoResource::collection($paginator);
    }

    public function store(StoreDataLoRequest $request)
    {
        $record = $this->service->create($request->validated());

        return (new DataLoResource($record))
            ->response()
            ->setStatusCode(Response::HTTP_CREATED);
    }

    public function show(Request $request, string $id)
    {
        $record = $this->service->find($id);

        return new DataLoResource($record);
    }

    public function update(UpdateDataLoRequest $request, string $id)
    {
        $record = $this->service->update($id, $request->validated());

        return new DataLoResource($record);
    }

    public function destroy(Request $request, string $id)
    {
        $this->service->delete($id);

        return response()->noContent();
    }

    public function exportExcel(Request $request): StreamedResponse
    {
        $this->relaxExportRuntimeLimits();
        $filters = $request->only(['ID_LO', 'NO_AGT', 'search']);
        $rows = $this->service->listForExport($filters, $this->exportExcelLimit($request));
        $data = $rows->map(fn ($r) => [
            $r->ID_LO,
            $r->NO_AGT,
            $r->ID_TP,
            $r->NAMA,
            $r->STAT,
            $r->TGL_STAT,
        ])->all();

        return $this->tabularExport->excelResponse('DataLO', 'Tabel Data LO', self::EXPORT_HEADERS, $data);
    }

    public function exportPdf(Request $request): Response
    {
        $this->relaxExportRuntimeLimits();
        $filters = $request->only(['ID_LO', 'NO_AGT', 'search']);
        $rows = $this->service->listForExport($filters, $this->exportPdfLimit($request));
        $data = $rows->map(fn ($r) => [
            $r->ID_LO,
            $r->NO_AGT,
            $r->ID_TP,
            $r->NAMA,
            $r->STAT,
            $r->TGL_STAT,
        ])->all();

        return $this->tabularExport->pdfResponse('DataLO', 'Tabel Data LO', self::EXPORT_HEADERS, $data);
    }
}
