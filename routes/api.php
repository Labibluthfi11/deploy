<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\AbsensiController;
use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\UserProfileController;
use App\Http\Controllers\Api\NotificationController;

Route::post('/register', [AuthController::class, 'register']);
Route::post('/login', [AuthController::class, 'login']);

Route::middleware('auth:sanctum')->group(function () {
    // Absensi
    Route::post('/absensi/masuk', [AbsensiController::class, 'absenMasuk']);
    Route::post('/absensi/pulang', [AbsensiController::class, 'absenPulang']);
    Route::post('/absensi/lembur', [AbsensiController::class, 'absenLembur']);
    Route::get('/absensi/me', [AbsensiController::class, 'meAbsensi']);
    Route::post('/absensi/sakit', [AbsensiController::class, 'absenSakit']);

    // Resubmit endpoints (baru)
    Route::post('/absensi/sakit/{id}/resubmit', [AbsensiController::class, 'resubmitSakit']);
    Route::post('/absensi/izin/{id}/resubmit', [AbsensiController::class, 'resubmitIzin']);
    Route::post('/absensi/lembur/{id}/resubmit', [AbsensiController::class, 'resubmitLembur']);

    // Auth
    Route::post('/logout', [AuthController::class, 'logout']);
    Route::get('/user', [AuthController::class, 'user']);

    // Profile
    Route::get('/user/profile', [UserProfileController::class, 'show']);
    Route::put('/user/profile', [UserProfileController::class, 'update']);
    Route::put('/user/update-employment-status', [AuthController::class, 'updateEmploymentStatus']);

    // Notifications
    Route::get('/notifications', [NotificationController::class, 'index']);
    Route::post('/notifications', [NotificationController::class, 'store']);
    Route::put('/notifications/{id}/read', [NotificationController::class, 'markAsRead']);
});
