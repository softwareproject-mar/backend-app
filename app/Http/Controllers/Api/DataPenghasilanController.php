<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Concerns\RelaxesExportTimeouts;
use App\Http\Controllers\Controller;
use App\Http\Requests\StoreDataPenghasilanRequest;
use App\Http\Requests\UpdateDataPenghasilanRequest;
use App\Http\Resources\DataPenghasilanResource;
use App\Services\DataPenghasilanService;
use App\Services\TabularExportService;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Symfony\Component\HttpFoundation\StreamedResponse;

class DataPenghasilanController extends Controller
{
    use RelaxesExportTimeouts;

    /** @var list<string> */
    private const EXPORT_HEADERS = [
        'Nomor Anggota',
        'Penghasilan',
        'Pengeluaran',
        'Tanggal Data',
    ];

    public function __construct(
        private DataPenghasilanService $service,
        private TabularExportService $tabularExport,
    ) {}

    public function index(Request $request)
    {
        $perPage = $request->integer('per_page', 15);
        $filters = $request->only(['NO_AGT', 'search']);

        $paginator = $this->service->paginate($filters, $perPage, $request->user());

        return DataPenghasilanResource::collection($paginator);
    }

    public function store(StoreDataPenghasilanRequest $request)
    {
        $validated = $request->validated();
        $validated['created_by'] = $request->user()->id;

        $record = $this->service->create($validated, $request->user());

        return (new DataPenghasilanResource($record))
            ->response()
            ->setStatusCode(Response::HTTP_CREATED);
    }

    public function show(string $id, Request $request)
    {
        $record = $this->service->find($id, $request->user());

        return new DataPenghasilanResource($record);
    }

    public function update(UpdateDataPenghasilanRequest $request, string $id)
    {
        $record = $this->service->update($id, $request->validated(), $request->user());

        return new DataPenghasilanResource($record);
    }

    public function destroy(string $id, Request $request)
    {
        $this->service->delete($id, $request->user());

        return response()->noContent();
    }

    public function exportExcel(Request $request): StreamedResponse
    {
        $this->relaxExportRuntimeLimits();
        $filters = $request->only(['NO_AGT', 'search']);
        $rows = $this->service->listForExport($filters, $this->exportExcelLimit($request), $request->user());
        $data = $rows->map(fn ($r) => [
            $r->NO_AGT,
            $r->PENGHASILAN,
            $r->PENGELUARAN,
            $r->TGL_DATA,
        ])->all();

        return $this->tabularExport->excelResponse('DataPenghasilan', 'Tabel Data Penghasilan', self::EXPORT_HEADERS, $data);
    }

    public function exportPdf(Request $request): Response
    {
        $this->relaxExportRuntimeLimits();
        $filters = $request->only(['NO_AGT', 'search']);
        $rows = $this->service->listForExport($filters, $this->exportPdfLimit($request), $request->user());
        $data = $rows->map(fn ($r) => [
            $r->NO_AGT,
            $r->PENGHASILAN,
            $r->PENGELUARAN,
            $r->TGL_DATA,
        ])->all();

        return $this->tabularExport->pdfResponse('DataPenghasilan', 'Tabel Data Penghasilan', self::EXPORT_HEADERS, $data);
    }
}
