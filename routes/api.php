<?php

use App\Http\Controllers\Api\AnggotaController;
use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\DashboardController;
use App\Http\Controllers\Api\DataAoController;
use App\Http\Controllers\Api\DataJlhKeluargaController;
use App\Http\Controllers\Api\DataKunjunganController;
use App\Http\Controllers\Api\DataPengelolaController;
use App\Http\Controllers\Api\DataLoController;
use App\Http\Controllers\Api\DataPenghasilanController;
use App\Http\Controllers\Api\DataTrsController;
use App\Http\Controllers\Api\KelSahController;
use App\Http\Controllers\Api\KetuaKsController;
use App\Http\Controllers\Api\SekretarisKsController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

// DEBUG ROUTE - Test Direct Insert (REMOVE after debugging)
Route::post('/debug-ketua-ks', function(Request $request) {
    \Log::info('=== DEBUG: Request received ===');
    \Log::info('All Data:', $request->all());
    \Log::info('Headers:', $request->headers->all());
    
    try {
        $data = [
            'ID_KET' => $request->ID_KET,
            'NO_AGT' => $request->NO_AGT,
            'NAMA' => $request->NAMA,
            'STAT' => $request->STAT,
            'TGL_STAT' => $request->TGL_STAT,
            'NO_SK' => $request->NO_SK,
        ];
        
        \Log::info('Prepared Data:', $data);
        
        $result = \App\Models\KetuaKs::create($data);
        
        \Log::info('Created Record:', $result->toArray());
        
        return response()->json([
            'success' => true,
            'message' => 'Data inserted successfully',
            'data' => $result,
            'request_data' => $request->all()
        ], 201);
        
    } catch (\Exception $e) {
        \Log::error('Error:', [
            'message' => $e->getMessage(),
            'file' => $e->getFile(),
            'line' => $e->getLine()
        ]);
        
        return response()->json([
            'success' => false,
            'error' => $e->getMessage(),
            'trace' => $e->getTraceAsString()
        ], 500);
    }
});

// Public routes
Route::post('/auth/request-otp', [AuthController::class, 'requestOtp']);
Route::post('/auth/register', [AuthController::class, 'register']);
Route::post('/auth/login', [AuthController::class, 'login']);
Route::post('/auth/forgot-password', [AuthController::class, 'forgotPassword']);
Route::post('/auth/verify-reset-otp', [AuthController::class, 'verifyResetOtp']);
Route::post('/auth/reset-password', [AuthController::class, 'resetPassword']);

// Protected routes
Route::middleware('auth:sanctum')->group(function () {
    // Auth
    Route::post('/auth/logout', [AuthController::class, 'logout']);
    Route::get('/auth/me', [AuthController::class, 'me']);

    // Data Kunjungan (full CRUD)
    Route::apiResource('data-kunjungan', DataKunjunganController::class);

    // Full CRUD resources
    Route::apiResource('anggota', AnggotaController::class);
    Route::apiResource('kel-sah', KelSahController::class);
    Route::apiResource('data-lo', DataLoController::class);
    Route::apiResource('data-ao', DataAoController::class);
    Route::apiResource('data-jlh-keluarga', DataJlhKeluargaController::class);
    Route::apiResource('data-pengelola', DataPengelolaController::class);
    Route::apiResource('ketua-ks', KetuaKsController::class);
    Route::apiResource('sekretaris-ks', SekretarisKsController::class);
    Route::apiResource('data-penghasilan', DataPenghasilanController::class);

    // Data TRS (read only)
    Route::apiResource('data-trs', DataTrsController::class)->only(['index', 'show']);
});
