<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\DataLoResource;
use App\Services\DataLoService;
use Illuminate\Http\Request;

class DataLoController extends Controller
{
    public function __construct(private DataLoService $service)
    {
    }

    public function index(Request $request)
    {
        $perPage = $request->integer('per_page', 15);
        $filters = $request->only(['ID_LO', 'NO_AGT']);

        $paginator = $this->service->paginate($filters, $perPage);

        return DataLoResource::collection($paginator);
    }

    public function show(string $id)
    {
        $record = $this->service->find($id);

        return new DataLoResource($record);
    }
}
