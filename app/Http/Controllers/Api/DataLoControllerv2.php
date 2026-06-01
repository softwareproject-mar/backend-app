<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreDataLoRequest;
use App\Http\Requests\UpdateDataLoRequest;
use App\Http\Resources\DataLoResource;
use App\Services\DataLoService;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

class DataLoController extends Controller
{
    public function __construct(private DataLoService $service) {}

    public function index(Request $request)
    {
        $perPage = $request->integer('per_page', 15);
        $filters = $request->only(['ID_LO', 'NO_AGT']);

        $paginator = $this->service->paginate($filters, $perPage);

        return DataLoResource::collection($paginator);
    }

    public function store(StoreDataLoRequest $request)
    {
        $record = $this->service->create($request->validated());

        return (new DataLoResource($record))
            ->response()
            ->setStatusCode(Response::HTTP_CREATED);
    }

    public function show(string $id)
    {
        $record = $this->service->find($id);

        return new DataLoResource($record);
    }

    public function update(UpdateDataLoRequest $request, string $id)
    {
        $record = $this->service->update($id, $request->validated());

        return new DataLoResource($record);
    }

    public function destroy(string $id)
    {
        $this->service->delete($id);

        return response()->noContent();
    }
}
