<?php

use App\Http\Controllers\ProfileController;
use Illuminate\Support\Facades\Route;
use App\Http\Middleware\RoleMiddleware;
use App\Http\Controllers\KaryawanController;
use App\Http\Controllers\GajiController;
use App\Http\Controllers\SimulasiGajiController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\AbsensiController;
use App\Http\Controllers\LaporanController;
use App\Http\Controllers\SesiAbsensiController;
use App\Http\Controllers\NotificationController;
use App\Models\User;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
*/

Route::get('/', function () {
    return view('welcome');
});

Route::get('/dashboard', [DashboardController::class, 'index'])->middleware(['auth', 'verified'])->name('dashboard');


Route::middleware('auth')->group(function () {
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');

    // Bendahara specific routes
    Route::middleware(['role:bendahara'])->group(function () {
        Route::get('gaji', [GajiController::class, 'index'])->name('gaji.index');
        Route::post('gaji/save', [GajiController::class, 'saveOrUpdate'])->name('gaji.save');

        // RUTE BARU UNTUK PROSES BACKGROUND
        Route::post('/gaji/{gaji}/download', [GajiController::class, 'downloadSlip'])->name('gaji.download');
        Route::post('/gaji/{gaji}/send-email', [GajiController::class, 'sendEmail'])->name('gaji.send-email');

        Route::get('/laporan', [LaporanController::class, 'index'])->name('laporan.index');
        Route::get('/laporan/gaji-bulanan', [LaporanController::class, 'gajiBulanan'])->name('laporan.gaji.bulanan');
        Route::post('/laporan/gaji-bulanan/cetak', [LaporanController::class, 'cetakGajiBulanan'])->name('laporan.gaji.cetak');
        Route::get('/laporan/per-karyawan', [LaporanController::class, 'perKaryawan'])->name('laporan.per.karyawan');

        Route::get('/laporan/absensi', [AbsensiController::class, 'rekapPerBulan'])->name('laporan.absensi.index');
        Route::get('/laporan/absensi/data', [AbsensiController::class, 'fetchRekapData'])->name('laporan.absensi.data');
        Route::resource('sesi-absensi', SesiAbsensiController::class)->except(['show']);
    });

    // Admin specific routes for Karyawan Management (CRUD except index and show)
    Route::middleware(['role:admin'])->group(function () {
        Route::resource('/karyawan', KaryawanController::class)->except(['index', 'show']);
        Route::resource('/users', ProfileController::class);
    });


    // Admin and Bendahara can view Karyawan list and details
    Route::middleware(['role:admin,bendahara'])->group(function () {
        Route::get('/karyawan', [KaryawanController::class, 'index'])->name('karyawan.index');
        Route::get('/karyawan/{karyawan}', [KaryawanController::class, 'show'])->name('karyawan.show');

        Route::get('/notifications', [NotificationController::class, 'index'])->name('notifications.index');
        Route::get('/notifications/{id}/mark-as-read', [NotificationController::class, 'markAsRead'])->name('notifications.markAsRead');
        Route::post('/notifications/mark-all-as-read', [NotificationController::class, 'markAllAsRead'])->name('notifications.markAllAsRead');
        Route::post('/notifications/delete-selected', [NotificationController::class, 'deleteSelected'])->name('notifications.deleteSelected');
        Route::post('/notifications/delete-all', [NotificationController::class, 'deleteAll'])->name('notifications.deleteAll');
        // Rute yang bisa diakses Bendahara & Admin (sebagai pengawas)
        Route::get('gaji', [GajiController::class, 'index'])->name('gaji.index');
        Route::post('gaji/save', [GajiController::class, 'saveOrUpdate'])->name('gaji.save');
        Route::post('/gaji/{gaji}/download', [GajiController::class, 'downloadSlip'])->name('gaji.download');
        Route::post('/gaji/{gaji}/send-email', [GajiController::class, 'sendEmail'])->name('gaji.send-email');

        Route::get('/laporan', [LaporanController::class, 'index'])->name('laporan.index');
        Route::get('/laporan/gaji-bulanan', [LaporanController::class, 'gajiBulanan'])->name('laporan.gaji.bulanan');
        Route::post('/laporan/gaji-bulanan/cetak', [LaporanController::class, 'cetakGajiBulanan'])->name('laporan.gaji.cetak');
        Route::get('/laporan/per-karyawan', [LaporanController::class, 'perKaryawan'])->name('laporan.per.karyawan');

        Route::get('/laporan/absensi', [AbsensiController::class, 'rekapPerBulan'])->name('laporan.absensi.index');
        Route::get('/laporan/absensi/data', [AbsensiController::class, 'fetchRekapData'])->name('laporan.absensi.data');
        Route::resource('sesi-absensi', SesiAbsensiController::class)->except(['show']);
    });
});


// Guest routes
Route::get('/simulasi', [SimulasiGajiController::class, 'index'])->name('simulasi.index');
Route::post('/simulasi/hitung', [SimulasiGajiController::class, 'hitung'])->name('simulasi.hitung');
Route::get('/absensi', [AbsensiController::class, 'index'])->name('absensi.form');
Route::post('/absensi', [AbsensiController::class, 'store'])->name('absensi.store');


require __DIR__ . '/auth.php';
