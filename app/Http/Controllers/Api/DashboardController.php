<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\DashboardService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class DashboardController extends Controller
{
    public function __construct(private DashboardService $service)
    {
    }

    /**
     * Get dashboard data with joined Target and Realisasi
     */
    public function index(Request $request): JsonResponse
    {
        $filters = $request->only(['ID_KS', 'TGL_TGT']);

        $data = $this->service->getDashboardData($filters);

        return response()->json($data);
    }
}
