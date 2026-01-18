<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\ListAnggotaRequest;
use App\Models\Anggota;
use App\Services\FirebirdService;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Exception;

class AnggotaImportController extends Controller
{
    protected FirebirdService $firebirdService;

    public function __construct(FirebirdService $firebirdService)
    {
        $this->firebirdService = $firebirdService;
    }

    /**
     * Get list of anggota from Firebird for dropdown
     */
    public function index(ListAnggotaRequest $request)
    {
        try {
            $filters = $request->getValidatedData();
            $result = $this->firebirdService->getAnggotaList($filters);

            // If pagination requested, return paginated response
            if ($filters['page'] !== null) {
                $currentPage = $filters['page'];
                $perPage = $filters['per_page'];
                $total = $result['total'];
                $lastPage = (int) ceil($total / $perPage);

                return response()->json([
                    'data' => $result['data'],
                    'links' => [
                        'first' => url()->current() . '?page=1&per_page=' . $perPage,
                        'last' => url()->current() . '?page=' . $lastPage . '&per_page=' . $perPage,
                        'prev' => $currentPage > 1 ? url()->current() . '?page=' . ($currentPage - 1) . '&per_page=' . $perPage : null,
                        'next' => $currentPage < $lastPage ? url()->current() . '?page=' . ($currentPage + 1) . '&per_page=' . $perPage : null,
                    ],
                    'meta' => [
                        'current_page' => $currentPage,
                        'from' => (($currentPage - 1) * $perPage) + 1,
                        'last_page' => $lastPage,
                        'per_page' => $perPage,
                        'to' => min($currentPage * $perPage, $total),
                        'total' => $total,
                    ]
                ]);
            }

            // Simple list response with metadata
            $response = [
                'data' => $result['data'],
                'meta' => [
                    'total' => $result['total'],
                    'showing' => $result['showing'],
                    'has_more' => $result['has_more'],
                ]
            ];

            // Add pagination link if has more data
            if ($result['has_more']) {
                $response['links'] = [
                    'next' => url()->current() . '?search=' . urlencode($filters['search'] ?? '') . '&page=' . ($result['page'] + 1) . '&per_page=' . $result['per_page']
                ];
            }

            return response()->json($response);

        } catch (Exception $e) {
            Log::error('Failed to fetch anggota list from Firebird: ' . $e->getMessage(), [
                'filters' => $filters ?? [],
                'error' => $e->getMessage()
            ]);

            return response()->json([
                'message' => 'Failed to fetch data from Firebird.',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Preview anggota data from Firebird before import
     */
    public function preview(string $noAgt): JsonResponse
    {
        try {
            // Validate NO_AGT format
            if (empty($noAgt) || strlen($noAgt) > 15) {
                return response()->json([
                    'message' => 'Invalid NO_AGT format.',
                    'errors' => ['no_agt' => 'NO_AGT must be 1-15 characters.']
                ], 422);
            }

            // Check if already exists in MySQL
            $existing = Anggota::where('NO_AGT', $noAgt)->first();
            if ($existing) {
                return response()->json([
                    'message' => 'Anggota already exists in system.',
                    'data' => [
                        'exists_in_mysql' => true,
                        'mysql_data' => $existing,
                        'firebird_data' => null
                    ]
                ], 200);
            }

            // Fetch from Firebird
            $firebirdData = $this->firebirdService->fetchAnggota($noAgt);

            if (!$firebirdData) {
                return response()->json([
                    'message' => 'Anggota not found in Firebird database.',
                    'data' => [
                        'exists_in_mysql' => false,
                        'mysql_data' => null,
                        'firebird_data' => null
                    ]
                ], 404);
            }

            return response()->json([
                'message' => 'Preview data retrieved successfully.',
                'data' => [
                    'exists_in_mysql' => false,
                    'mysql_data' => null,
                    'firebird_data' => $firebirdData
                ]
            ], 200);

        } catch (Exception $e) {
            Log::error('Preview failed: ' . $e->getMessage(), [
                'no_agt' => $noAgt,
                'error' => $e->getMessage()
            ]);

            return response()->json([
                'message' => 'Failed to retrieve preview data.',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Import anggota from Firebird to MySQL
     */
    public function import(ImportAnggotaRequest $request): JsonResponse
    {
        $noAgt = $request->input('no_agt');

        try {
            // Check if already exists in MySQL
            $existing = Anggota::where('NO_AGT', $noAgt)->first();
            if ($existing) {
                return response()->json([
                    'message' => 'Anggota already exists in system.',
                    'data' => $existing
                ], 422);
            }

            // Fetch from Firebird
            $firebirdData = $this->firebirdService->fetchAnggota($noAgt);

            if (!$firebirdData) {
                return response()->json([
                    'message' => 'Anggota not found in Firebird database.'
                ], 404);
            }

            // Begin transaction
            DB::beginTransaction();

            try {
                // Create in MySQL
                $anggota = Anggota::create($firebirdData);

                DB::commit();

                Log::info('Anggota imported successfully', [
                    'no_agt' => $noAgt,
                    'imported_by' => auth()->id() ?? 'system'
                ]);

                return response()->json([
                    'message' => 'Anggota imported successfully.',
                    'data' => $anggota
                ], 201);

            } catch (Exception $e) {
                DB::rollBack();
                throw $e;
            }

        } catch (Exception $e) {
            Log::error('Import failed: ' . $e->getMessage(), [
                'no_agt' => $noAgt,
                'error' => $e->getMessage()
            ]);

            return response()->json([
                'message' => 'Failed to import anggota.',
                'error' => $e->getMessage()
            ], 500);
        }
    }
}