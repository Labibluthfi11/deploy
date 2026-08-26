<?php

use App\Http\Controllers\Admin\AbsensiAdminController;
use App\Http\Controllers\Admin\ApprovalController;
use App\Http\Controllers\ProfileController;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('welcome');
});


Route::get('/dashboard', function () {
    return redirect()->route('admin.absensi.index');
})->middleware(['auth', 'verified'])->name('dashboard');

Route::middleware('auth')->group(function () {
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
});

Route::middleware(['auth', 'admin', \App\Http\Middleware\ActivityLogMiddleware::class])->prefix('admin')->group(function () {

    Route::prefix('absensi')->group(function () {

        // ✅ Dashboard & List
        Route::get('/', [AbsensiAdminController::class, 'index'])->name('admin.absensi.index');
        Route::get('/freelance', [AbsensiAdminController::class, 'indexFreelance'])->name('admin.absensi.freelance');
        
        // 🛠️ EDIT JAM MASUK (Tadi salah nama jadi edit-checkin)
        Route::post('/{id}/update-checkin', [AbsensiAdminController::class, 'updateCheckIn'])
            ->name('admin.absensi.updateCheckIn');
        Route::post('/{id}/update-checkout', [AbsensiAdminController::class, 'updateCheckOut'])
            ->name('admin.absensi.updateCheckOut');

        // Detail absensi per user
        Route::get('/user/{user}', [AbsensiAdminController::class, 'show'])->name('admin.absensi.user')->middleware('block.pkl');

        // Export data
        Route::get('/user/{id}/export', [AbsensiAdminController::class, 'exportUser'])->name('admin.absensi.user.export')->middleware('block.pkl');
        Route::get('/recap', [AbsensiAdminController::class, 'recap'])->name('admin.absensi.recap');
        Route::get('/recap/export', [AbsensiAdminController::class, 'exportRecap'])->name('admin.absensi.recap.export')->middleware('block.pkl');
        
        Route::post('/bulk-export-detail', [AbsensiAdminController::class, 'bulkExportDetail'])->name('admin.absensi.bulk-export-detail')->middleware('block.pkl');
        Route::post('/bulk-export-simple', [AbsensiAdminController::class, 'bulkExportSimple'])->name('admin.absensi.bulk-export-simple');
        Route::get('/bulk-export-all-simple', [AbsensiAdminController::class, 'bulkExportAllSimple'])->name('admin.absensi.bulk-export-all-simple')->middleware('block.pkl');

        // 🔒 Khusus Super Admin
        Route::middleware('super_admin')->group(function () {
            Route::get('/organik', [AbsensiAdminController::class, 'indexOrganik'])->name('admin.absensi.organik');
            Route::get('/user/{user}/export-slip', [AbsensiAdminController::class, 'exportSlipGaji'])->name('admin.absensi.user.export-slip')->middleware('block.pkl');
            Route::get('/user/{id}/export-slip-pdf', [AbsensiAdminController::class, 'exportSlipGajiPdf'])->name('admin.absensi.user.export-slip-pdf')->middleware('block.pkl');
            Route::post('/bulk-export-pdf', [AbsensiAdminController::class, 'bulkExportPdf'])->name('admin.absensi.bulk-export-pdf')->middleware('block.pkl');
            Route::get('/activity-logs', [\App\Http\Controllers\Admin\ActivityLogController::class, 'index'])->name('admin.activity_logs.index');
        });
    });

    // HARI LIBUR
    Route::prefix('holidays')->group(function () {
        Route::get('/', [\App\Http\Controllers\Admin\HolidayController::class, 'index'])->name('admin.holidays.index');
        Route::get('/create', [\App\Http\Controllers\Admin\HolidayController::class, 'create'])->name('admin.holidays.create');
        Route::post('/', [\App\Http\Controllers\Admin\HolidayController::class, 'store'])->name('admin.holidays.store');
        Route::delete('/{id}', [\App\Http\Controllers\Admin\HolidayController::class, 'destroy'])->name('admin.holidays.destroy');
        Route::post('/sync', [\App\Http\Controllers\Admin\HolidayController::class, 'sync'])->name('admin.holidays.sync');
    });

    // IZIN KELUAR
    Route::prefix('izin-keluar')->group(function () {
        Route::get('/', [\App\Http\Controllers\Admin\IzinKeluarAdminController::class, 'index'])->name('admin.izin-keluar.index');
    });

    // APPROVAL ABSENSI
    Route::prefix('approval')->group(function () {
        Route::get('/supervisor', [ApprovalController::class, 'supervisor'])->name('admin.absensi.approval.supervisor');
        Route::get('/manager', [ApprovalController::class, 'manager'])->name('admin.absensi.approval.manager');
        Route::get('/hrga', [ApprovalController::class, 'hrga'])->name('admin.absensi.approval.hrga');

        Route::post('/{absensi}/{action}', [ApprovalController::class, 'handleAction'])
            ->name('admin.absensi.approval.action')
            ->where('action', 'approve|reject');

        Route::post('/bulk-approve', [ApprovalController::class, 'handleBulkAction'])->name('admin.absensi.approval.bulk-action');

        Route::post('/scheduled-lembur/{id}/{action}', [ApprovalController::class, 'handleScheduledLemburAction'])
            ->name('admin.absensi.approval.scheduled-lembur.action')
            ->where('action', 'approve|reject');
        Route::post('/izin-keluar/{id}/{action}', [ApprovalController::class, 'handleIzinKeluarAction'])
            ->name('admin.absensi.approval.izin-keluar.action')
            ->where('action', 'approve|reject');
    });

    // --- ABSENSI MANAGEMENT (Adjustment Gaji) ---
    Route::post('/absensi/{absensi}/adjustment', [AbsensiAdminController::class, 'updateAdjustment'])
        ->name('admin.absensi.update-adjustment');
        
    Route::post('/absensi/manual-entry', [AbsensiAdminController::class, 'storeManual'])
        ->name('admin.absensi.store-manual');

    Route::get('/absensi/koreksi', [AbsensiAdminController::class, 'showManualEntryPage'])
        ->name('admin.absensi.koreksi');
});

require __DIR__ . '/auth.php';