<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreAnggotaRequest;
use App\Http\Requests\UpdateAnggotaRequest;
use App\Http\Resources\AnggotaResource;
use App\Services\AnggotaService;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

class AnggotaController extends Controller
{
    public function __construct(private AnggotaService $service) {}

    public function index(Request $request)
    {
        $perPage = $request->integer('per_page', 15);
        $filters = $request->only(['NO_AGT', 'ID_KS']);

        $paginator = $this->service->paginate($filters, $perPage);

        return AnggotaResource::collection($paginator);
    }

    public function store(StoreAnggotaRequest $request)
    {
        $record = $this->service->create($request->validated());

        return (new AnggotaResource($record))
            ->response()
            ->setStatusCode(Response::HTTP_CREATED);
    }

    public function show(string $id)
    {
        $record = $this->service->find($id);

        return new AnggotaResource($record);
    }

    public function update(UpdateAnggotaRequest $request, string $id)
    {
        $record = $this->service->update($id, $request->validated());

        return new AnggotaResource($record);
    }

    public function destroy(string $id)
    {
        $this->service->delete($id);

        return response()->noContent();
    }
}
