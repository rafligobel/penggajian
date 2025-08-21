<?php

use App\Http\Controllers\ProfileController;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\KaryawanController;
use App\Http\Controllers\GajiController;
use App\Http\Controllers\AbsensiController;
use App\Http\Controllers\SesiAbsensiController;
use App\Http\Controllers\LaporanController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\SimulasiGajiController;
use App\Http\Controllers\UserController;
use App\Http\Controllers\NotificationController;
use App\Http\Controllers\TandaTanganController; // Pastikan controller ini ada

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for your application. These
| routes are loaded by the RouteServiceProvider and all of them will
| be assigned to the "web" middleware group. Make something great!
|
*/

Route::get('/', function () {
    return view('welcome');
});

// Rute untuk menampilkan form absensi (publik)
Route::get('/absensi', [AbsensiController::class, 'showAbsensiForm'])->name('absensi.form');
Route::post('/absensi', [AbsensiController::class, 'storeAbsensi'])->name('absensi.store');


Route::get('/dashboard', [DashboardController::class, 'index'])->middleware(['auth', 'verified'])->name('dashboard');

Route::middleware('auth')->group(function () {
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
});

Route::middleware(['auth', 'role:superadmin,admin'])->group(function () {
    Route::resource('karyawan', KaryawanController::class);
    Route::resource('users', UserController::class);
});

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

    // Rute yang hilang sebelumnya, sekarang dikembalikan
    Route::post('/laporan/gaji/kirim-email-terpilih', [LaporanController::class, 'kirimEmailGajiTerpilih'])->name('laporan.gaji.kirim-email-terpilih');

    Route::get('/laporan/absensi', [AbsensiController::class, 'rekapPerBulan'])->name('laporan.absensi.index');
    Route::get('/laporan/absensi/data', [AbsensiController::class, 'fetchRekapData'])->name('laporan.absensi.data');
    Route::resource('sesi-absensi', SesiAbsensiController::class)->except(['show']);

    Route::get('/notifications', [NotificationController::class, 'index'])->name('notifications.index');
    Route::get('/notifications/{id}/mark-as-read', [NotificationController::class, 'markAsRead'])->name('notifications.markAsRead');
    Route::post('/notifications/mark-all-as-read', [NotificationController::class, 'markAllAsRead'])->name('notifications.markAllAsRead');

    // Rute Tanda Tangan yang hilang sebelumnya, sekarang dikembalikan
    Route::get('/tanda-tangan', [TandaTanganController::class, 'index'])->name('tanda_tangan.index');
    Route::post('/tanda-tangan', [TandaTanganController::class, 'update'])->name('tanda_tangan.update');
});

// Rute Simulasi Gaji (publik)
Route::get('/simulasi-gaji', [SimulasiGajiController::class, 'index'])->name('simulasi.index');
Route::post('/simulasi-gaji/hitung', [SimulasiGajiController::class, 'hitung'])->name('simulasi.hitung');

require __DIR__ . '/auth.php';
