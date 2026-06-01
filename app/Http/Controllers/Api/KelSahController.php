<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Concerns\RelaxesExportTimeouts;
use App\Http\Controllers\Controller;
use App\Http\Requests\StoreKelSahRequest;
use App\Http\Requests\UpdateKelSahRequest;
use App\Http\Resources\KelSahResource;
use App\Services\KelSahService;
use App\Services\TabularExportService;
use App\Support\MemberScope;
use App\Support\OwnerScope;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Symfony\Component\HttpFoundation\StreamedResponse;

class KelSahController extends Controller
{
    use RelaxesExportTimeouts;

    /** @var list<string> */
    private const EXPORT_HEADERS = [
        'ID Kelompok',
        'Nama Kelompok Sahabat',
        'ID Ketua',
        'ID Sekretaris',
        'ID LO',
        'ID AO',
        'Alamat',
        'Status',
        'Tanggal Status',
        'ID Pengelola',
    ];

    public function __construct(
        private KelSahService $service,
        private TabularExportService $tabularExport,
    ) {}

    public function index(Request $request)
    {
        $perPage = min($request->integer('per_page', 10000), 50000);
        $filters = $this->kelSahListFiltersResolved($request);
        if ($filters === null) {
            return KelSahResource::collection(MemberScope::emptyPaginator($request, $perPage));
        }
        $paginator = $this->service->paginate($filters, $perPage);

        return KelSahResource::collection($paginator);
    }

    public function store(StoreKelSahRequest $request)
    {
        abort_if(MemberScope::isRestrictedMemberUser($request->user()), 403, 'Role ini tidak dapat menambah data kelompok.');

        $record = $this->service->create($request->validated());

        return (new KelSahResource($record))
            ->response()
            ->setStatusCode(Response::HTTP_CREATED);
    }

    public function show(string $id)
    {
        $record = $this->service->find($id);
        $user = request()->user();
        if (config('obormas.strict_member_kelompok_scope') && MemberScope::isRestrictedMemberUser($user)) {
            MemberScope::assertMemberOwnsKelompok($user, (string) $record->ID_KEL);
        } else {
            OwnerScope::assertMemberUserCanAccessKelompok($user, (string) $record->ID_KEL);
        }

        return new KelSahResource($record);
    }

    public function update(UpdateKelSahRequest $request, string $id)
    {
        $record = $this->service->update($id, $request->validated());

        return new KelSahResource($record);
    }

    public function destroy(string $id)
    {
        $this->service->delete($id);

        return response()->noContent();
    }

    public function exportExcel(Request $request): StreamedResponse
    {
        $this->relaxExportRuntimeLimits();
        $resolved = $this->kelSahListFiltersResolved($request);
        $filters = $resolved ?? ['ID_KEL_IN' => []];
        $rows = $this->service->listForExport($filters, $this->exportExcelLimit($request));
        $data = $rows->map(fn ($r) => [
            $r->ID_KEL,
            $r->NAMA_KEL,
            $r->ID_KETUA,
            $r->ID_SEK,
            $r->ID_LO,
            $r->ID_AO,
            $r->ALAMAT,
            $r->STAT,
            $r->TGL_STAT,
            $r->ID_PENGELOLA,
        ])->all();

        return $this->tabularExport->excelResponse(
            'KelompokSahabat',
            'Tabel Data Kelompok Sahabat',
            self::EXPORT_HEADERS,
            $data
        );
    }

    public function exportPdf(Request $request): Response
    {
        $this->relaxExportRuntimeLimits();
        $resolved = $this->kelSahListFiltersResolved($request);
        $filters = $resolved ?? ['ID_KEL_IN' => []];
        $rows = $this->service->listForExport($filters, $this->exportPdfLimit($request));
        $data = $rows->map(fn ($r) => [
            $r->ID_KEL,
            $r->NAMA_KEL,
            $r->ID_KETUA,
            $r->ID_SEK,
            $r->ID_LO,
            $r->ID_AO,
            $r->ALAMAT,
            $r->STAT,
            $r->TGL_STAT,
            $r->ID_PENGELOLA,
        ])->all();

        return $this->tabularExport->pdfResponse(
            'KelompokSahabat',
            'Tabel Data Kelompok Sahabat',
            self::EXPORT_HEADERS,
            $data
        );
    }

    /**
     * @return array<string, mixed>
     */
    private function kelSahListFilters(Request $request): array
    {
        return $request->only(['ID_KEL', 'ID_LO', 'ID_AO', 'search']);
    }

    /**
     * @return array<string, mixed>|null null = member tanpa kelompok (index harus paginator kosong)
     */
    private function kelSahListFiltersResolved(Request $request): ?array
    {
        $filters = $this->kelSahListFilters($request);
        if (! config('obormas.strict_member_kelompok_scope')) {
            return $filters;
        }
        $user = $request->user();
        if (! MemberScope::isRestrictedMemberUser($user)) {
            return $filters;
        }
        $merged = MemberScope::mergeKelSahFilterForMemberUser($user, $filters);

        return $merged;
    }
}
