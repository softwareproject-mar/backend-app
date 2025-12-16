<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\DataAoResource;
use App\Services\DataAoService;
use Illuminate\Http\Request;

class DataAoController extends Controller
{
    public function __construct(private DataAoService $service)
    {
    }

    public function index(Request $request)
    {
        $perPage = $request->integer('per_page', 15);
        $filters = $request->only(['ID_AO', 'NO_AGT']);

        $paginator = $this->service->paginate($filters, $perPage);

        return DataAoResource::collection($paginator);
    }

    public function show(string $id)
    {
        $record = $this->service->find($id);

        return new DataAoResource($record);
    }
}
