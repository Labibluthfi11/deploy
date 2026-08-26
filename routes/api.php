<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\AbsensiController;
use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\UserProfileController;
use App\Http\Controllers\Api\NotificationController;
use App\Http\Controllers\Api\ForgotPasswordController;
use App\Http\Controllers\Api\ScheduledLemburController;
use App\Http\Controllers\Api\KpiIntegrationController;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
| Seluruh endpoint API di sini telah diproteksi dengan Rate Limiting
| untuk mencegah serangan Brute Force dan DDoS.
*/

Route::middleware('throttle:api')->prefix('v1')->group(function () {
    
    // ==========================================
    // KPI INTEGRATION ROUTE (Protected by API Key)
    // ==========================================
    Route::middleware(['api.kpi'])->group(function () {
        Route::get('/kpi-attendance-summary', [KpiIntegrationController::class, 'getAttendanceSummary']);
        Route::get('/kpi-active-employees', [KpiIntegrationController::class, 'getActiveEmployees']);
    });

    // ==========================================
    // PUBLIC ROUTES (Tanpa Auth)
    // ==========================================

    // ✅ REGISTER - Rate limit: 10 attempts per menit
    Route::post('/register', [AuthController::class, 'register'])
        ->middleware('throttle:10,1');

    // ✅ LOGIN - Rate limit: 5 attempts per menit (anti brute force)
    Route::post('/login', [AuthController::class, 'login'])
        ->middleware('throttle:5,1');

    // ✅ FORGOT PASSWORD - Rate limit ketat untuk prevent spam
    Route::prefix('forgot-password')->group(function () {
        Route::post('/send-otp', [ForgotPasswordController::class, 'sendOtp'])
            ->middleware('throttle:3,1');  // Max 3x/menit
        
        Route::post('/verify-otp', [ForgotPasswordController::class, 'verifyOtp'])
            ->middleware('throttle:5,1');  // Max 5x/menit
        
        Route::post('/reset-password', [ForgotPasswordController::class, 'resetPassword'])
            ->middleware('throttle:3,1');  // Max 3x/menit
    });

    // ==========================================
    // PROTECTED ROUTES (Harus Login)
    // ==========================================

    Route::middleware('auth:sanctum')->group(function () {
        
        // ========== AUTH & PROFILE ==========
        Route::post('/logout', [AuthController::class, 'logout']);
        Route::get('/user', [AuthController::class, 'user']);
        
        // Profile routes
        Route::get('/user/profile', [UserProfileController::class, 'show']);
        Route::put('/user/profile', [UserProfileController::class, 'update'])
            ->middleware('throttle:10,1');  // ✅ Max 10x/menit untuk update profile
        
        Route::put('/user/update-employment-status', [AuthController::class, 'updateEmploymentStatus'])
            ->middleware('throttle:5,1');  // ✅ Max 5x/menit untuk update status
        
        // ========== ABSENSI - POST ENDPOINTS (dengan rate limit) ==========
        Route::middleware('throttle:10,1')->group(function () {
            Route::post('/absensi/masuk', [AbsensiController::class, 'absenMasuk']);
            Route::post('/absensi/pulang', [AbsensiController::class, 'absenPulang']);
            Route::post('/absensi/lembur', [AbsensiController::class, 'absenLembur']);
            Route::post('/absensi/submit-lembur', [AbsensiController::class, 'submitLembur']);
            Route::post('/absensi/sakit', [AbsensiController::class, 'absenSakit']);
            Route::post('/absensi/izin', [AbsensiController::class, 'absenIzin']);
            Route::post('/absensi/telat', [AbsensiController::class, 'pengajuanTelat']);
            Route::post('/absensi/scheduled-lembur', [ScheduledLemburController::class, 'store']);
            
            // IZIN KELUAR API
            Route::post('/izin-keluar/start', [\App\Http\Controllers\Api\IzinKeluarApiController::class, 'startIzin']);
            Route::post('/izin-keluar/end', [\App\Http\Controllers\Api\IzinKeluarApiController::class, 'endIzin']);
        });
        
        Route::get('/absensi/scheduled-lembur', [ScheduledLemburController::class, 'index']);
        Route::put('/absensi/scheduled-lembur/{id}/status', [ScheduledLemburController::class, 'updateStatus'])
            ->middleware('throttle:10,1');
        
        // ========== ABSENSI - RESUBMIT ENDPOINTS (rate limit lebih ketat) ==========
        Route::middleware('throttle:5,1')->group(function () {
            Route::post('/absensi/sakit/{id}/resubmit', [AbsensiController::class, 'resubmitSakit']);
            Route::post('/absensi/izin/{id}/resubmit', [AbsensiController::class, 'resubmitIzin']);
            Route::post('/absensi/lembur/{id}/resubmit', [AbsensiController::class, 'resubmitLembur']);
            Route::post('/absensi/resubmit/{id}', [AbsensiController::class, 'resubmit']);
        });
        
        // ========== ABSENSI - GET ENDPOINTS ==========
        Route::get('/absensi/me', [AbsensiController::class, 'meAbsensi']);
        Route::get('/absensi/detail/{id}', [AbsensiController::class, 'getDetailAbsensi']);
        
        // ========== NOTIFICATIONS ==========
        Route::get('/notifications', [NotificationController::class, 'index']);
        Route::put('/notifications/{id}/read', [NotificationController::class, 'markAsRead'])
            ->middleware('throttle:30,1');
        
        // ✅ Notification store - KHUSUS ADMIN!
        Route::post('/notifications', [NotificationController::class, 'store'])
            ->middleware('throttle:20,1');
    });

});
