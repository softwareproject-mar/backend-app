<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\KelSahResource;
use App\Services\KelSahService;
use Illuminate\Http\Request;

class KelSahController extends Controller
{
    public function __construct(private KelSahService $service)
    {
    }

    public function index(Request $request)
    {
        $perPage = $request->integer('per_page', 15);
        $filters = $request->only(['ID_KEL', 'ID_LO', 'ID_AO']);

        $paginator = $this->service->paginate($filters, $perPage);

        return KelSahResource::collection($paginator);
    }

    public function show(string $id)
    {
        $record = $this->service->find($id);

        return new KelSahResource($record);
    }
}
