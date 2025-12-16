<?php

use App\Http\Controllers\Api\AnggotaController;
use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\DataAoController;
use App\Http\Controllers\Api\DataJlhKeluargaController;
use App\Http\Controllers\Api\DataKunjunganController;
use App\Http\Controllers\Api\DataLoController;
use App\Http\Controllers\Api\DataTrsController;
use App\Http\Controllers\Api\KelSahController;
use App\Http\Controllers\Api\RealisasiController;
use App\Http\Controllers\Api\TargetController;
use Illuminate\Support\Facades\Route;

// Public routes
Route::post('/auth/register', [AuthController::class, 'register']);
Route::post('/auth/login', [AuthController::class, 'login']);

// Protected routes
Route::middleware('auth:sanctum')->group(function () {
    // Auth
    Route::post('/auth/logout', [AuthController::class, 'logout']);
    Route::get('/auth/me', [AuthController::class, 'me']);

    // Data Kunjungan (existing)
    Route::apiResource('data-kunjungan', DataKunjunganController::class);

    // New resources
    Route::apiResource('anggota', AnggotaController::class)->only(['index', 'show']);
    Route::apiResource('kel-sah', KelSahController::class)->only(['index', 'show']);
    Route::apiResource('data-lo', DataLoController::class)->only(['index', 'show']);
    Route::apiResource('data-ao', DataAoController::class)->only(['index', 'show']);
    Route::apiResource('data-trs', DataTrsController::class)->only(['index', 'show']);
    Route::apiResource('data-jlh-keluarga', DataJlhKeluargaController::class)->only(['index', 'show']);

    // Composite key resources (custom routes)
    Route::get('/realisasi', [RealisasiController::class, 'index']);
    Route::get('/realisasi/{idKs}/{tglTgt}', [RealisasiController::class, 'show']);
    Route::get('/target', [TargetController::class, 'index']);
    Route::get('/target/{idKs}/{tglTgt}', [TargetController::class, 'show']);
});
