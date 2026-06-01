<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Concerns\RelaxesExportTimeouts;
use App\Http\Controllers\Controller;
use App\Http\Requests\StoreDataAoRequest;
use App\Http\Requests\UpdateDataAoRequest;
use App\Http\Resources\DataAoResource;
use App\Services\DataAoService;
use App\Services\TabularExportService;
use App\Support\MemberScope;
use App\Support\OwnerScope;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Symfony\Component\HttpFoundation\StreamedResponse;

class DataAoController extends Controller
{
    use RelaxesExportTimeouts;

    /** @var list<string> */
    private const EXPORT_HEADERS = [
        'ID AO',
        'Nomor Anggota',
        'Nama',
        'Status',
        'Tanggal Status',
    ];

    public function __construct(
        private DataAoService $service,
        private TabularExportService $tabularExport,
    ) {}

    public function index(Request $request)
    {
        $perPage = $request->integer('per_page', 15);
        $filters = $request->only(['ID_AO', 'NO_AGT', 'search']);
        if (MemberScope::isRestrictedMemberUser($request->user())) {
            $allowed = OwnerScope::noAgtsFromUserOwnedRows((int) $request->user()->id);
            if ($allowed === []) {
                return DataAoResource::collection(MemberScope::emptyPaginator($request, $perPage));
            }
            $reqNo = $filters['NO_AGT'] ?? null;
            if ($reqNo !== null && $reqNo !== '') {
                $n = trim((string) $reqNo);
                if (! in_array($n, $allowed, true)) {
                    return DataAoResource::collection(MemberScope::emptyPaginator($request, $perPage));
                }
            } else {
                $filters['NO_AGT_IN'] = $allowed;
            }
        }

        $paginator = $this->service->paginate($filters, $perPage);

        return DataAoResource::collection($paginator);
    }

    public function store(StoreDataAoRequest $request)
    {
        $record = $this->service->create($request->validated());

        return (new DataAoResource($record))
            ->response()
            ->setStatusCode(Response::HTTP_CREATED);
    }

    public function show(Request $request, string $id)
    {
        $record = $this->service->find($id);

        return new DataAoResource($record);
    }

    public function update(UpdateDataAoRequest $request, string $id)
    {
        $record = $this->service->update($id, $request->validated());

        return new DataAoResource($record);
    }

    public function destroy(Request $request, string $id)
    {
        $this->service->delete($id);

        return response()->noContent();
    }

    public function exportExcel(Request $request): StreamedResponse
    {
        $this->relaxExportRuntimeLimits();
        $filters = $this->buildAoExportFilters($request);
        if ($filters === null) {
            return $this->tabularExport->excelResponse('DataAO', 'Tabel Data AO', self::EXPORT_HEADERS, []);
        }
        $rows = $this->service->listForExport($filters, $this->exportExcelLimit($request));
        $data = $rows->map(fn ($r) => [
            $r->ID_AO,
            $r->NO_AGT,
            $r->NAMA,
            $r->STAT,
            $r->TGL_STAT,
        ])->all();

        return $this->tabularExport->excelResponse('DataAO', 'Tabel Data AO', self::EXPORT_HEADERS, $data);
    }

    public function exportPdf(Request $request): Response
    {
        $this->relaxExportRuntimeLimits();
        $filters = $this->buildAoExportFilters($request);
        if ($filters === null) {
            return $this->tabularExport->pdfResponse('DataAO', 'Tabel Data AO', self::EXPORT_HEADERS, []);
        }
        $rows = $this->service->listForExport($filters, $this->exportPdfLimit($request));
        $data = $rows->map(fn ($r) => [
            $r->ID_AO,
            $r->NO_AGT,
            $r->NAMA,
            $r->STAT,
            $r->TGL_STAT,
        ])->all();

        return $this->tabularExport->pdfResponse('DataAO', 'Tabel Data AO', self::EXPORT_HEADERS, $data);
    }

    /**
     * @return array<string, mixed>|null null = tidak ada baris untuk user (scope anggota kosong / filter diluar izin)
     */
    private function buildAoExportFilters(Request $request): ?array
    {
        $filters = $request->only(['ID_AO', 'NO_AGT', 'search']);
        if (! MemberScope::isRestrictedMemberUser($request->user())) {
            return $filters;
        }
        $allowed = OwnerScope::noAgtsFromUserOwnedRows((int) $request->user()->id);
        if ($allowed === []) {
            return null;
        }
        $reqNo = $filters['NO_AGT'] ?? null;
        if ($reqNo !== null && $reqNo !== '') {
            $n = trim((string) $reqNo);
            if (! in_array($n, $allowed, true)) {
                return null;
            }
        } else {
            $filters['NO_AGT_IN'] = $allowed;
        }

        return $filters;
    }
}
