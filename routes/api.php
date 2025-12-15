<?php


use App\Http\Controllers\Api\DataKunjunganController;
use Illuminate\Support\Facades\Route;

Route::middleware('api')->group(function () {
    Route::apiResource('data-kunjungan', DataKunjunganController::class);
});
