<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreTargetRequest;
use App\Http\Requests\UpdateTargetRequest;
use App\Http\Resources\TargetResource;
use App\Services\TargetService;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

class TargetController extends Controller
{
    public function __construct(private TargetService $service)
    {
    }

    public function index(Request $request)
    {
        $perPage = $request->integer('per_page', 15);
        $filters = $request->only(['ID_KS', 'TGL_TGT']);

        $paginator = $this->service->paginate($filters, $perPage);

        return TargetResource::collection($paginator);
    }

    public function store(StoreTargetRequest $request)
    {
        $record = $this->service->create($request->validated());

        return (new TargetResource($record))
            ->response()
            ->setStatusCode(Response::HTTP_CREATED);
    }

    public function show(string $idKs, string $tglTgt)
    {
        $record = $this->service->find($idKs, $tglTgt);

        return new TargetResource($record);
    }

    public function update(UpdateTargetRequest $request, string $idKs, string $tglTgt)
    {
        $record = $this->service->update($idKs, $tglTgt, $request->validated());

        return new TargetResource($record);
    }

    public function destroy(string $idKs, string $tglTgt)
    {
        $this->service->delete($idKs, $tglTgt);

        return response()->noContent();
    }
}
