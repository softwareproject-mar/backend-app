<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\ListAnggotaRequest;
use App\Services\FirebirdService;
use Exception;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Log;

class DataTrsImportController extends Controller
{
    public function __construct(
        protected FirebirdService $firebirdService
    ) {}

    /**
     * Daftar NO_AGT yang punya baris di DATA_TRS (Firebird), untuk autocomplete / pilih impor.
     */
    public function index(ListAnggotaRequest $request): JsonResponse
    {
        try {
            $filters = $request->getValidatedData();
            $result = $this->firebirdService->getDataTrsNoAgtList($filters);

            if ($filters['page'] !== null) {
                $currentPage = $filters['page'];
                $perPage = $filters['per_page'];
                $total = $result['total'];
                $lastPage = (int) max(1, ceil($total / $perPage));

                return response()->json([
                    'data' => $result['data'],
                    'links' => [
                        'first' => url()->current().'?page=1&per_page='.$perPage,
                        'last' => url()->current().'?page='.$lastPage.'&per_page='.$perPage,
                        'prev' => $currentPage > 1 ? url()->current().'?page='.($currentPage - 1).'&per_page='.$perPage : null,
                        'next' => $currentPage < $lastPage ? url()->current().'?page='.($currentPage + 1).'&per_page='.$perPage : null,
                    ],
                    'meta' => [
                        'current_page' => $currentPage,
                        'from' => (($currentPage - 1) * $perPage) + 1,
                        'last_page' => $lastPage,
                        'per_page' => $perPage,
                        'to' => min($currentPage * $perPage, $total),
                        'total' => $total,
                    ],
                ]);
            }

            $response = [
                'data' => $result['data'],
                'meta' => [
                    'total' => $result['total'],
                    'showing' => $result['showing'],
                    'has_more' => $result['has_more'],
                ],
            ];
            if ($result['has_more']) {
                $response['links'] = [
                    'next' => url()->current().'?search='.urlencode($filters['search'] ?? '').'&page='.($result['page'] + 1).'&per_page='.$result['per_page'],
                ];
            }

            return response()->json($response);
        } catch (Exception $e) {
            Log::error('DataTrs Firebird list failed: '.$e->getMessage());

            return response()->json([
                'message' => 'Gagal mengambil daftar DATA_TRS dari Firebird.',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Pratinjau baris DATA_TRS di Firebird untuk satu NO_AGT.
     */
    public function preview(string $noAgt): JsonResponse
    {
        try {
            if ($noAgt === '' || strlen($noAgt) > 15) {
                return response()->json([
                    'message' => 'Format nomor anggota tidak valid.',
                ], 422);
            }

            $rows = $this->firebirdService->fetchDataTrsRows($noAgt);
            if ($rows === []) {
                return response()->json([
                    'message' => 'Tidak ada data DATA_TRS di Firebird untuk nomor anggota ini.',
                    'data' => [
                        'firebird_rows' => [],
                    ],
                ], 404);
            }

            return response()->json([
                'message' => 'Data pratinjau berhasil diambil.',
                'data' => [
                    'firebird_rows' => $rows,
                ],
            ]);
        } catch (Exception $e) {
            Log::error('DataTrs Firebird preview failed: '.$e->getMessage(), ['no_agt' => $noAgt]);

            return response()->json([
                'message' => 'Gagal mengambil pratinjau dari Firebird.',
                'error' => $e->getMessage(),
            ], 500);
        }
    }
}
