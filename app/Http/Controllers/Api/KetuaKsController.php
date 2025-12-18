<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreKetuaKsRequest;
use App\Http\Requests\UpdateKetuaKsRequest;
use App\Http\Resources\KetuaKsResource;
use App\Services\KetuaKsService;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

class KetuaKsController extends Controller
{
    public function __construct(private KetuaKsService $service)
    {
    }

    public function index(Request $request)
    {
        $perPage = $request->integer('per_page', 15);
        $filters = $request->only(['ID_KET', 'NO_AGT', 'NAMA', 'STAT']);

        $paginator = $this->service->paginate($filters, $perPage);

        return KetuaKsResource::collection($paginator);
    }

    public function store(StoreKetuaKsRequest $request)
    {
        $record = $this->service->create($request->validated());

        return (new KetuaKsResource($record))
            ->response()
            ->setStatusCode(Response::HTTP_CREATED);
    }

    public function show(string $id)
    {
        $record = $this->service->find($id);

        return new KetuaKsResource($record);
    }

    public function update(UpdateKetuaKsRequest $request, string $id)
    {
        $record = $this->service->update($id, $request->validated());

        return new KetuaKsResource($record);
    }

    public function destroy(string $id)
    {
        $this->service->delete($id);

        return response()->noContent();
    }
}
