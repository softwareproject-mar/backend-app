<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreDataAoRequest;
use App\Http\Requests\UpdateDataAoRequest;
use App\Http\Resources\DataAoResource;
use App\Services\DataAoService;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

class DataAoController extends Controller
{
    public function __construct(private DataAoService $service) {}

    public function index(Request $request)
    {
        $perPage = $request->integer('per_page', 15);
        $filters = $request->only(['ID_AO', 'NO_AGT']);

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

    public function show(string $id)
    {
        $record = $this->service->find($id);

        return new DataAoResource($record);
    }

    public function update(UpdateDataAoRequest $request, string $id)
    {
        $record = $this->service->update($id, $request->validated());

        return new DataAoResource($record);
    }

    public function destroy(string $id)
    {
        $this->service->delete($id);

        return response()->noContent();
    }
}
