<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Concerns\RelaxesExportTimeouts;
use App\Http\Controllers\Controller;
use App\Http\Requests\StoreSekretarisKsRequest;
use App\Http\Requests\UpdateSekretarisKsRequest;
use App\Http\Resources\SekretarisKsResource;
use App\Services\SekretarisKsService;
use App\Services\TabularExportService;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Symfony\Component\HttpFoundation\StreamedResponse;

class SekretarisKsController extends Controller
{
    use RelaxesExportTimeouts;

    /** @var list<string> */
    private const EXPORT_HEADERS = [
        'ID Sekretaris',
        'Nomor Anggota',
        'Nama',
        'Status',
        'Tanggal Status',
        'No SK',
    ];

    public function __construct(
        private SekretarisKsService $service,
        private TabularExportService $tabularExport,
    ) {}

    public function index(Request $request)
    {
        $perPage = $request->integer('per_page', 15);
        $filters = $request->only(['ID_SEKRE', 'NO_AGT', 'NAMA', 'STAT', 'search']);

        $paginator = $this->service->paginate($filters, $perPage);

        return SekretarisKsResource::collection($paginator);
    }

    public function store(StoreSekretarisKsRequest $request)
    {
        $record = $this->service->create($request->validated());

        return (new SekretarisKsResource($record))
            ->response()
            ->setStatusCode(Response::HTTP_CREATED);
    }

    public function show(string $id)
    {
        $record = $this->service->find($id);

        return new SekretarisKsResource($record);
    }

    public function update(UpdateSekretarisKsRequest $request, string $id)
    {
        $record = $this->service->update($id, $request->validated());

        return new SekretarisKsResource($record);
    }

    public function destroy(string $id)
    {
        $this->service->delete($id);

        return response()->noContent();
    }

    public function exportExcel(Request $request): StreamedResponse
    {
        $this->relaxExportRuntimeLimits();
        $filters = $request->only(['ID_SEKRE', 'NO_AGT', 'NAMA', 'STAT', 'search']);
        $rows = $this->service->listForExport($filters, $this->exportExcelLimit($request));
        $data = $rows->map(fn ($r) => [
            $r->ID_SEKRE,
            $r->NO_AGT,
            $r->NAMA,
            $r->STAT,
            $r->TGL_STAT,
            $r->NO_SK,
        ])->all();

        return $this->tabularExport->excelResponse('SekretarisKS', 'Tabel Data Sekretaris KS', self::EXPORT_HEADERS, $data);
    }

    public function exportPdf(Request $request): Response
    {
        $this->relaxExportRuntimeLimits();
        $filters = $request->only(['ID_SEKRE', 'NO_AGT', 'NAMA', 'STAT', 'search']);
        $rows = $this->service->listForExport($filters, $this->exportPdfLimit($request));
        $data = $rows->map(fn ($r) => [
            $r->ID_SEKRE,
            $r->NO_AGT,
            $r->NAMA,
            $r->STAT,
            $r->TGL_STAT,
            $r->NO_SK,
        ])->all();

        return $this->tabularExport->pdfResponse('SekretarisKS', 'Tabel Data Sekretaris KS', self::EXPORT_HEADERS, $data);
    }
}
