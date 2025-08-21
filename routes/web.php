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
use App\Http\Controllers\UserController;
use App\Http\Controllers\TandaTanganController;

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


    
    Route::middleware(['role:bendahara'])->group(function () {
        Route::get('gaji', [GajiController::class, 'index'])->name('gaji.index');
        Route::post('gaji/save', [GajiController::class, 'saveOrUpdate'])->name('gaji.save');
        Route::post('/gaji/{gaji}/download', [GajiController::class, 'downloadSlip'])->name('gaji.download');
        Route::post('/gaji/{gaji}/send-email', [GajiController::class, 'sendEmail'])->name('gaji.send-email');

        // --- RUTE LAPORAN YANG SUDAH DIRAPIKAN ---
        
        // Menampilkan halaman laporan
        Route::get('/laporan/gaji-bulanan', [LaporanController::class, 'gajiBulanan'])->name('laporan.gaji.bulanan');
        Route::get('/laporan/per-karyawan', [LaporanController::class, 'perKaryawan'])->name('laporan.per.karyawan');
        
        // Rute untuk Laporan Absensi (diperbaiki)
        Route::get('/laporan/absensi', [LaporanController::class, 'rekapAbsensi'])->name('laporan.absensi.index');

        // Aksi spesifik untuk Laporan Gaji Bulanan (Cetak/Kirim yang Terpilih)
        Route::post('/laporan/gaji-bulanan/cetak-terpilih', [LaporanController::class, 'cetakGajiBulanan'])->name('laporan.gaji.cetak');
        Route::post('/laporan/gaji-bulanan/kirim-email-terpilih', [LaporanController::class, 'kirimEmailGajiTerpilih'])->name('laporan.gaji.kirim-email-terpilih');

        // Rute terpusat untuk mencetak atau mengirim laporan KESELURUHAN
        Route::post('/laporan/cetak', [LaporanController::class, 'cetakLaporanPdf'])->name('laporan.cetak');
        Route::post('/laporan/kirim-email', [LaporanController::class, 'kirimLaporanEmail'])->name('laporan.kirim-email');
        
        // Rute lainnya
        Route::resource('sesi-absensi', SesiAbsensiController::class)->except(['show']);
        Route::get('/notifications', [NotificationController::class, 'index'])->name('notifications.index');
        Route::get('/notifications/{id}/mark-as-read', [NotificationController::class, 'markAsRead'])->name('notifications.markAsRead');
        Route::post('/notifications/mark-all-as-read', [NotificationController::class, 'markAllAsRead'])->name('notifications.markAllAsRead');
        Route::get('/tanda-tangan', [TandaTanganController::class, 'index'])->name('tanda_tangan.index');
        Route::post('/tanda-tangan', [TandaTanganController::class, 'update'])->name('tanda_tangan.update');
    });

    // Admin specific routes for Karyawan Management (CRUD except index and show)
    Route::middleware(['role:admin,superadmin'])->group(function () {
        Route::resource('/karyawan', KaryawanController::class);
        Route::resource('users', UserController::class);
    });

    // Admin and Bendahara can view Karyawan list and details
    Route::middleware(['role:admin,bendahara'])->group(function () {
        Route::get('/karyawan', [KaryawanController::class, 'index'])->name('karyawan.index');
        Route::get('/karyawan/{karyawan}', [KaryawanController::class, 'show'])->name('karyawan.show');
    });
});


// Guest routes
Route::get('/simulasi', [SimulasiGajiController::class, 'index'])->name('simulasi.index');
Route::post('/simulasi/hitung', [SimulasiGajiController::class, 'hitung'])->name('simulasi.hitung');
Route::get('/absensi', [AbsensiController::class, 'index'])->name('absensi.form');
Route::post('/absensi', [AbsensiController::class, 'store'])->name('absensi.store');


require __DIR__ . '/auth.php';
