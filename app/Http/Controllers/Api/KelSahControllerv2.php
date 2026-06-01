<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreKelSahRequest;
use App\Http\Requests\UpdateKelSahRequest;
use App\Http\Resources\KelSahResource;
use App\Services\KelSahService;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

class KelSahController extends Controller
{
    public function __construct(private KelSahService $service) {}

    public function index(Request $request)
    {
        $perPage = $request->integer('per_page', 15);
        $filters = $request->only(['ID_KEL', 'ID_LO', 'ID_AO']);

        $paginator = $this->service->paginate($filters, $perPage);

        return KelSahResource::collection($paginator);
    }

    public function store(StoreKelSahRequest $request)
    {
        $record = $this->service->create($request->validated());

        return (new KelSahResource($record))
            ->response()
            ->setStatusCode(Response::HTTP_CREATED);
    }

    public function show(string $id)
    {
        $record = $this->service->find($id);

        return new KelSahResource($record);
    }

    public function update(UpdateKelSahRequest $request, string $id)
    {
        $record = $this->service->update($id, $request->validated());

        return new KelSahResource($record);
    }

    public function destroy(string $id)
    {
        $this->service->delete($id);

        return response()->noContent();
    }
}
