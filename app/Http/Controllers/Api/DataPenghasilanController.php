<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreDataPenghasilanRequest;
use App\Http\Requests\UpdateDataPenghasilanRequest;
use App\Http\Resources\DataPenghasilanResource;
use App\Services\DataPenghasilanService;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

class DataPenghasilanController extends Controller
{
    public function __construct(private DataPenghasilanService $service)
    {
    }

    public function index(Request $request)
    {
        $perPage = $request->integer('per_page', 15);
        $filters = $request->only(['NO_AGT']);

        $paginator = $this->service->paginate($filters, $perPage);

        return DataPenghasilanResource::collection($paginator);
    }

    public function store(StoreDataPenghasilanRequest $request)
    {
        $record = $this->service->create($request->validated());

        return (new DataPenghasilanResource($record))
            ->response()
            ->setStatusCode(Response::HTTP_CREATED);
    }

    public function show(string $id)
    {
        $record = $this->service->find($id);

        return new DataPenghasilanResource($record);
    }

    public function update(UpdateDataPenghasilanRequest $request, string $id)
    {
        $record = $this->service->update($id, $request->validated());

        return new DataPenghasilanResource($record);
    }

    public function destroy(string $id)
    {
        $this->service->delete($id);

        return response()->noContent();
    }
}
