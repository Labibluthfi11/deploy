<?php

use App\Http\Controllers\Admin\AbsensiAdminController;
use App\Http\Controllers\Admin\ApprovalController;
use App\Http\Controllers\ProfileController;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('welcome');
});

// ====================================================================
// DASHBOARD DEFAULT
// ====================================================================
Route::get('/dashboard', function () {
    return redirect()->route('admin.absensi.index');
})->middleware(['auth', 'verified'])->name('dashboard');

// ====================================================================
// PROFILE ROUTES
// ====================================================================
Route::middleware('auth')->group(function () {
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
});

// ====================================================================
// ADMIN ROUTES (semua butuh auth + role admin)
// ====================================================================
Route::middleware(['auth', 'admin'])->prefix('admin')->group(function () {

    // --------------------------------------------------------
    // A. ABSENSI ADMIN
    // --------------------------------------------------------
    Route::prefix('absensi')->group(function () {

        // Halaman utama & kategori absensi
        Route::get('/', [AbsensiAdminController::class, 'index'])->name('admin.absensi.index');
        Route::get('/organik', [AbsensiAdminController::class, 'indexOrganik'])->name('admin.absensi.organik');
        Route::get('/freelance', [AbsensiAdminController::class, 'indexFreelance'])->name('admin.absensi.freelance');

        // Detail absensi per user
        Route::get('/user/{user}', [AbsensiAdminController::class, 'show'])->name('admin.absensi.user');

        // 🆕 Export absensi per user (tambahan baru)
        Route::get('/user/{id}/export', [AbsensiAdminController::class, 'exportUser'])
            ->name('admin.absensi.user.export');

        // Rekap bulanan
        Route::get('/recap', [AbsensiAdminController::class, 'recap'])->name('admin.absensi.recap');

        // Export rekap bulanan ke Excel
        Route::get('/recap/export', [AbsensiAdminController::class, 'exportRecap'])->name('admin.absensi.recap.export');
    });

    // --------------------------------------------------------
    // B. APPROVAL ABSENSI (Multi-level Workflow)
    // --------------------------------------------------------
    Route::prefix('approval')->group(function () {
        Route::get('/supervisor', [ApprovalController::class, 'supervisor'])->name('admin.absensi.approval.supervisor');
        Route::get('/manager', [ApprovalController::class, 'manager'])->name('admin.absensi.approval.manager');
        Route::get('/hrga', [ApprovalController::class, 'hrga'])->name('admin.absensi.approval.hrga');

        Route::post('/{absensi}/{action}', [ApprovalController::class, 'handleAction'])
            ->name('admin.absensi.approval.action')
            ->where('action', 'approve|reject');
    });
});

require __DIR__ . '/auth.php';
