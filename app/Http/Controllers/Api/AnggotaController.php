<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\AnggotaResource;
use App\Services\AnggotaService;
use Illuminate\Http\Request;

class AnggotaController extends Controller
{
    public function __construct(private AnggotaService $service)
    {
    }

    public function index(Request $request)
    {
        $perPage = $request->integer('per_page', 15);
        $filters = $request->only(['NO_AGT', 'ID_KS', 'ID_LO']);

        $paginator = $this->service->paginate($filters, $perPage);

        return AnggotaResource::collection($paginator);
    }

    public function show(string $id)
    {
        $record = $this->service->find($id);

        return new AnggotaResource($record);
    }
}
