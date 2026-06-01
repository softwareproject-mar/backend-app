<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Concerns\RelaxesExportTimeouts;
use App\Http\Controllers\Controller;
use App\Http\Requests\StoreDataPengelolaRequest;
use App\Http\Requests\UpdateDataPengelolaRequest;
use App\Http\Resources\DataPengelolaResource;
use App\Services\DataPengelolaService;
use App\Services\TabularExportService;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Symfony\Component\HttpFoundation\StreamedResponse;

class DataPengelolaController extends Controller
{
    use RelaxesExportTimeouts;

    /** @var list<string> */
    private const EXPORT_HEADERS = [
        'ID Pengelola',
        'Nomor Anggota',
        'No SK',
    ];

    public function __construct(
        private DataPengelolaService $service,
        private TabularExportService $tabularExport,
    ) {}

    public function index(Request $request)
    {
        $perPage = $request->integer('per_page', 15);
        $filters = $request->only(['ID_PENG', 'NO_AGT', 'search']);

        $paginator = $this->service->paginate($filters, $perPage);

        return DataPengelolaResource::collection($paginator);
    }

    public function store(StoreDataPengelolaRequest $request)
    {
        $record = $this->service->create($request->validated());

        return (new DataPengelolaResource($record))
            ->response()
            ->setStatusCode(Response::HTTP_CREATED);
    }

    public function show(string $id)
    {
        $record = $this->service->find($id);

        return new DataPengelolaResource($record);
    }

    public function update(UpdateDataPengelolaRequest $request, string $id)
    {
        $record = $this->service->update($id, $request->validated());

        return new DataPengelolaResource($record);
    }

    public function destroy(string $id)
    {
        $this->service->delete($id);

        return response()->noContent();
    }

    public function exportExcel(Request $request): StreamedResponse
    {
        $this->relaxExportRuntimeLimits();
        $filters = $request->only(['ID_PENG', 'NO_AGT', 'search']);
        $rows = $this->service->listForExport($filters, $this->exportExcelLimit($request));
        $data = $rows->map(fn ($r) => [
            $r->ID_PENG,
            $r->NO_AGT,
            $r->NO_SK,
        ])->all();

        return $this->tabularExport->excelResponse('DataPengelola', 'Tabel Data Pengelola', self::EXPORT_HEADERS, $data);
    }

    public function exportPdf(Request $request): Response
    {
        $this->relaxExportRuntimeLimits();
        $filters = $request->only(['ID_PENG', 'NO_AGT', 'search']);
        $rows = $this->service->listForExport($filters, $this->exportPdfLimit($request));
        $data = $rows->map(fn ($r) => [
            $r->ID_PENG,
            $r->NO_AGT,
            $r->NO_SK,
        ])->all();

        return $this->tabularExport->pdfResponse('DataPengelola', 'Tabel Data Pengelola', self::EXPORT_HEADERS, $data);
    }
}
