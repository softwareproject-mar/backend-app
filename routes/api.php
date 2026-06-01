<?php

use App\Http\Controllers\Api\ActivityLogController;
use App\Http\Controllers\Api\Admin\TargetRealisasiController;
use App\Http\Controllers\Api\AnggotaController;
use App\Http\Controllers\Api\AnggotaImportController;
use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\DashboardCountsController;
use App\Http\Controllers\Api\DataAoController;
use App\Http\Controllers\Api\DataJlhKeluargaController;
use App\Http\Controllers\Api\DataKunjunganController;
use App\Http\Controllers\Api\DataLoController;
use App\Http\Controllers\Api\DataPengelolaController;
use App\Http\Controllers\Api\DataPenghasilanController;
use App\Http\Controllers\Api\DataTrsController;
use App\Http\Controllers\Api\DataTrsImportController;
use App\Http\Controllers\Api\KelSahController;
use App\Http\Controllers\Api\KetuaKsController;
use App\Http\Controllers\Api\MemberKelompokController;
use App\Http\Controllers\Api\SekretarisKsController;
use App\Http\Controllers\Api\SuperAdmin\DashboardController as SuperAdminDashboardController;
use App\Http\Controllers\Api\SuperAdmin\SystemActivityController;
use App\Http\Controllers\Api\SuperAdmin\UserManagementController;
use App\Http\Controllers\Api\TargetRealisasiMeController;
use App\Http\Controllers\Api\UserApprovalController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

// DEBUG ROUTE - Test Direct Insert (REMOVE after debugging)
Route::post('/debug-ketua-ks', function (Request $request) {
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
            'request_data' => $request->all(),
        ], 201);

    } catch (\Exception $e) {
        \Log::error('Error:', [
            'message' => $e->getMessage(),
            'file' => $e->getFile(),
            'line' => $e->getLine(),
        ]);

        return response()->json([
            'success' => false,
            'error' => $e->getMessage(),
            'trace' => $e->getTraceAsString(),
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
    Route::post('/auth/logout', [AuthController::class, 'logout']);
    Route::get('/auth/me', [AuthController::class, 'me']);

    Route::middleware('member_approved')->group(function () {
        Route::get('dashboard/counts', [DashboardCountsController::class, '__invoke']);

        Route::get('me/kelompok', [MemberKelompokController::class, 'show']);

        // Activity Logs (read-only for users)
        Route::get('activity-logs', [ActivityLogController::class, 'index']);
        Route::get('activity-logs/{id}', [ActivityLogController::class, 'show']);

        // Data Kunjungan (full CRUD; export sebelum {data_kunjungan})
        Route::get('data-kunjungan/export/excel', [DataKunjunganController::class, 'exportExcel']);
        Route::get('data-kunjungan/export/pdf', [DataKunjunganController::class, 'exportPdf']);
        Route::get('data-kunjungan/{id}/photo', [DataKunjunganController::class, 'photo'])->name('data-kunjungan.photo');
        Route::apiResource('data-kunjungan', DataKunjunganController::class);

        // Full CRUD resources (export routes must be registered before {anggota})
        Route::get('anggota/export/excel', [AnggotaController::class, 'exportExcel']);
        Route::get('anggota/export/pdf', [AnggotaController::class, 'exportPdf']);
        Route::apiResource('anggota', AnggotaController::class);
        Route::get('import-anggota-firebird', [AnggotaImportController::class, 'index']);
        Route::get('import-anggota-firebird/{noAgt}', [AnggotaImportController::class, 'preview']);
        Route::post('import-anggota-firebird', [AnggotaImportController::class, 'import']);

        Route::get('import-data-trs-firebird', [DataTrsImportController::class, 'index']);
        Route::get('import-data-trs-firebird/{noAgt}', [DataTrsImportController::class, 'preview']);

        // Test route for Firebird connection (REMOVE after testing)
        Route::get('test-firebird-connection', function () {
            try {
                $firebird = app(\App\Services\FirebirdService::class);
                $connected = $firebird->testConnection();

                return response()->json([
                    'message' => $connected ? 'Firebird connection successful' : 'Firebird connection failed',
                    'connected' => $connected,
                ], $connected ? 200 : 500);
            } catch (\Exception $e) {
                return response()->json([
                    'message' => 'Firebird connection error',
                    'error' => $e->getMessage(),
                ], 500);
            }
        });

        Route::get('kel-sah/export/excel', [KelSahController::class, 'exportExcel']);
        Route::get('kel-sah/export/pdf', [KelSahController::class, 'exportPdf']);
        Route::apiResource('kel-sah', KelSahController::class);

        Route::get('data-lo/export/excel', [DataLoController::class, 'exportExcel']);
        Route::get('data-lo/export/pdf', [DataLoController::class, 'exportPdf']);
        Route::apiResource('data-lo', DataLoController::class);

        Route::get('data-ao/export/excel', [DataAoController::class, 'exportExcel']);
        Route::get('data-ao/export/pdf', [DataAoController::class, 'exportPdf']);
        Route::apiResource('data-ao', DataAoController::class);

        Route::get('data-jlh-keluarga/export/excel', [DataJlhKeluargaController::class, 'exportExcel']);
        Route::get('data-jlh-keluarga/export/pdf', [DataJlhKeluargaController::class, 'exportPdf']);
        Route::apiResource('data-jlh-keluarga', DataJlhKeluargaController::class);

        Route::get('data-pengelola/export/excel', [DataPengelolaController::class, 'exportExcel']);
        Route::get('data-pengelola/export/pdf', [DataPengelolaController::class, 'exportPdf']);
        Route::apiResource('data-pengelola', DataPengelolaController::class);

        Route::get('ketua-ks/export/excel', [KetuaKsController::class, 'exportExcel']);
        Route::get('ketua-ks/export/pdf', [KetuaKsController::class, 'exportPdf']);
        Route::apiResource('ketua-ks', KetuaKsController::class);

        Route::get('sekretaris-ks/export/excel', [SekretarisKsController::class, 'exportExcel']);
        Route::get('sekretaris-ks/export/pdf', [SekretarisKsController::class, 'exportPdf']);
        Route::apiResource('sekretaris-ks', SekretarisKsController::class);

        Route::get('data-penghasilan/export/excel', [DataPenghasilanController::class, 'exportExcel']);
        Route::get('data-penghasilan/export/pdf', [DataPenghasilanController::class, 'exportPdf']);
        Route::apiResource('data-penghasilan', DataPenghasilanController::class);

        Route::get('data-trs/export/excel', [DataTrsController::class, 'exportExcel']);
        Route::get('data-trs/export/pdf', [DataTrsController::class, 'exportPdf']);
        Route::get('data-trs', [DataTrsController::class, 'index']);

        Route::get('target-realisasi/me', TargetRealisasiMeController::class);
    });

    // User Approval (admin/super_admin only)
    Route::middleware('admin')->group(function () {
        Route::get('users/approval-stats', [UserApprovalController::class, 'stats']);
        Route::get('users/registration-queue', [UserApprovalController::class, 'queue']);
        // Alias URL (kompatibel proxy/WAF atau deploy parsial)
        Route::get('users/registrations', [UserApprovalController::class, 'queue']);
        Route::get('users/pending', [UserApprovalController::class, 'index']);
        Route::get('users/member-pending', [UserApprovalController::class, 'index']);
        Route::get('users/rejected', [UserApprovalController::class, 'rejected']);
        Route::get('users/member-approved', [UserApprovalController::class, 'approved']);
        Route::post('users/{id}/approve', [UserApprovalController::class, 'approve']);
        Route::post('users/{id}/reject', [UserApprovalController::class, 'reject']);
        Route::post('users/{id}/reset-device', [UserApprovalController::class, 'resetDevice']);

        Route::get('admin/data-kunjungan/report/group-summary', [DataKunjunganController::class, 'reportGroupSummary']);
        Route::get('admin/data-kunjungan/report/by-kelompok/{id_kel_sah}/anggota-summary', [DataKunjunganController::class, 'reportAnggotaSummaryForKelompok']);

        Route::get('admin/target-realisasi/summary', [TargetRealisasiController::class, 'index']);
        Route::get('admin/target-realisasi/diagnostic', [TargetRealisasiController::class, 'diagnostic']);
        Route::get('admin/target-realisasi/export/excel', [TargetRealisasiController::class, 'exportExcel']);
        Route::get('admin/target-realisasi/export/pdf', [TargetRealisasiController::class, 'exportPdf']);
        Route::get('admin/target-realisasi/kelompok/{id_kel}', [TargetRealisasiController::class, 'show']);
        Route::put('admin/target-realisasi/kelompok/{id_kel}', [TargetRealisasiController::class, 'update']);
    });

    // Super Admin routes
    Route::prefix('super-admin')->middleware('super_admin')->group(function () {
        Route::get('dashboard/stats', [SuperAdminDashboardController::class, 'stats']);
        Route::get('dashboard/recent-activities', [SuperAdminDashboardController::class, 'recentActivities']);
        Route::get('dashboard/chart', [SuperAdminDashboardController::class, 'chartData']);
        Route::get('admins', [UserManagementController::class, 'admins']);
        Route::get('users', [UserManagementController::class, 'index']);
        Route::post('users', [UserManagementController::class, 'store']);
        Route::patch('users/{id}', [UserManagementController::class, 'update']);
        Route::delete('users/{id}', [UserManagementController::class, 'destroy']);
        Route::post('users/{id}/reset-device', [UserManagementController::class, 'resetDevice']);
        Route::get('system-activity', [SystemActivityController::class, 'index']);
    });
});
