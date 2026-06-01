<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreSekretarisKsRequest;
use App\Http\Requests\UpdateSekretarisKsRequest;
use App\Http\Resources\SekretarisKsResource;
use App\Services\SekretarisKsService;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

class SekretarisKsController extends Controller
{
    public function __construct(private SekretarisKsService $service) {}

    public function index(Request $request)
    {
        $perPage = $request->integer('per_page', 15);
        $filters = $request->only(['ID_SEKRE', 'NO_AGT', 'NAMA', 'STAT']);

        $paginator = $this->service->paginate($filters, $perPage);

        return SekretarisKsResource::collection($paginator);
    }

    public function store(StoreSekretarisKsRequest $request)
    {
        $record = $this->service->create($request->validated());

        return (new SekretarisKsResource($record))
            ->response()
            ->setStatusCode(Response::HTTP_CREATED);
    }

    public function show(string $id)
    {
        $record = $this->service->find($id);

        return new SekretarisKsResource($record);
    }

    public function update(UpdateSekretarisKsRequest $request, string $id)
    {
        $record = $this->service->update($id, $request->validated());

        return new SekretarisKsResource($record);
    }

    public function destroy(string $id)
    {
        $this->service->delete($id);

        return response()->noContent();
    }
}
