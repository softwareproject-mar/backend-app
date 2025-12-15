<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreDataKunjunganRequest;
use App\Http\Requests\UpdateDataKunjunganRequest;
use App\Http\Resources\DataKunjunganResource;
use App\Services\DataKunjunganService;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

class DataKunjunganController extends Controller
{
    public function __construct(private DataKunjunganService $service)
    {
    }

    public function index(Request $request)
    {
        $perPage = $request->integer('per_page', 15);
        $filters = $request->only([
            'ID_LO',
            'NO_AGT',
            'ID_KEL_SAH',
            'TGL_KUN',
            'KEGIATAN',
            'ID_PIC',
        ]);

        $paginator = $this->service->paginate($filters, $perPage);

        return DataKunjunganResource::collection($paginator);
    }

    public function store(StoreDataKunjunganRequest $request)
    {
        $record = $this->service->create($request->validated());

        return (new DataKunjunganResource($record))
            ->response()
            ->setStatusCode(Response::HTTP_CREATED);
    }

    public function show(int $id)
    {
        $record = $this->service->find($id);

        return new DataKunjunganResource($record);
    }

    public function update(UpdateDataKunjunganRequest $request, int $id)
    {
        $record = $this->service->update($id, $request->validated());

        return new DataKunjunganResource($record);
    }

    public function destroy(int $id)
    {
        $this->service->delete($id);

        return response()->noContent();
    }
}
