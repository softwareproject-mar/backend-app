<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreDataPengelolaRequest;
use App\Http\Requests\UpdateDataPengelolaRequest;
use App\Http\Resources\DataPengelolaResource;
use App\Services\DataPengelolaService;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

class DataPengelolaController extends Controller
{
    public function __construct(private DataPengelolaService $service) {}

    public function index(Request $request)
    {
        $perPage = $request->integer('per_page', 15);
        $filters = $request->only(['ID_PENG', 'NO_AGT']);

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
}
