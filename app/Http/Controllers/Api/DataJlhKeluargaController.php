<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\DataJlhKeluargaResource;
use App\Services\DataJlhKeluargaService;
use Illuminate\Http\Request;

class DataJlhKeluargaController extends Controller
{
    public function __construct(private DataJlhKeluargaService $service)
    {
    }

    public function index(Request $request)
    {
        $perPage = $request->integer('per_page', 15);
        $filters = $request->only(['NO_AGT']);

        $paginator = $this->service->paginate($filters, $perPage);

        return DataJlhKeluargaResource::collection($paginator);
    }

    public function show(string $id)
    {
        $record = $this->service->find($id);

        return new DataJlhKeluargaResource($record);
    }
}
