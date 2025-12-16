<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\RealisasiResource;
use App\Services\RealisasiService;
use Illuminate\Http\Request;

class RealisasiController extends Controller
{
    public function __construct(private RealisasiService $service)
    {
    }

    public function index(Request $request)
    {
        $perPage = $request->integer('per_page', 15);
        $filters = $request->only(['ID_KS', 'TGL_TGT']);

        $paginator = $this->service->paginate($filters, $perPage);

        return RealisasiResource::collection($paginator);
    }

    public function show(string $idKs, string $tglTgt)
    {
        $record = $this->service->find($idKs, $tglTgt);

        return new RealisasiResource($record);
    }
}
