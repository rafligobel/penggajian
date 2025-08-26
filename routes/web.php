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
use App\Http\Controllers\TandaTanganController;
use App\Http\Controllers\JabatanController;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
*/

Route::get('/', function () {
    return view('welcome');
});

// Rute untuk menampilkan form absensi (publik)

Route::get('/dashboard', [DashboardController::class, 'index'])->middleware(['auth', 'verified'])->name('dashboard');

Route::middleware('auth')->group(function () {
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');

    Route::get('/notifications', [NotificationController::class, 'index'])->name('notifications.index');
    Route::get('/notifications/{id}/read', [NotificationController::class, 'markAsRead'])->name('notifications.markAsRead');
    Route::delete('/notifications/delete-selected', [NotificationController::class, 'deleteSelected'])->name('notifications.deleteSelected');
    Route::delete('/notifications/delete-all', [NotificationController::class, 'deleteAll'])->name('notifications.deleteAll');

    Route::get('/notifications/mark-all-as-read', [NotificationController::class, 'markAllAsRead'])->name('notifications.markAllAsRead');
    Route::get('/notifications/mark-as-read/{id}', [NotificationController::class, 'markAsRead'])->name('notifications.markAsRead');
});

Route::middleware(['auth', 'role:superadmin,admin'])->group(function () {
    // <-- Rute untuk admin
    Route::resource('users', UserController::class);
    Route::resource('jabatan', JabatanController::class);
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

    // Route::get('karyawan', [KaryawanController::class, 'index'])->name('karyawan.index');
    Route::get('karyawan/{karyawan}', [KaryawanController::class, 'show'])->name('karyawan.show');

    Route::get('/laporan/per-karyawan', [LaporanController::class, 'perKaryawan'])->name('laporan.per.karyawan');
    Route::post('/laporan/per-karyawan/cetak', [LaporanController::class, 'cetakLaporanPerKaryawan'])->name('laporan.per.karyawan.cetak');
    Route::post('/laporan/per-karyawan/kirim-email', [LaporanController::class, 'kirimEmailLaporanPerKaryawan'])->name('laporan.per.karyawan.kirim-email');

    // Rute yang hilang sebelumnya, sekarang dikembalikan
    Route::post('/laporan/gaji/kirim-email-terpilih', [LaporanController::class, 'kirimEmailGajiTerpilih'])->name('laporan.gaji.kirim-email-terpilih');

    Route::get('/rekap-absensi', [AbsensiController::class, 'rekapPerBulan'])->name('absensi.rekap');
    Route::get('/laporan/absensi/data', [AbsensiController::class, 'fetchRekapData'])->name('laporan.absensi.data');
    Route::resource('sesi-absensi', SesiAbsensiController::class)->except(['show']);

    //laporan absensi
    Route::get('/laporan/absensi', [LaporanController::class, 'rekapAbsensi'])->name('laporan.absensi');
    Route::post('/laporan/absensi/cetak', [LaporanController::class, 'cetakRekapAbsensi'])->name('laporan.absensi.cetak');
    Route::post('/laporan/absensi/kirim-email', [LaporanController::class, 'kirimEmailRekapAbsensi'])->name('laporan.absensi.kirim-email');


    // Rute Tanda Tangan yang hilang sebelumnya, sekarang dikembalikan
    Route::get('/tanda-tangan', [TandaTanganController::class, 'index'])->name('tanda_tangan.index');
    Route::post('/tanda-tangan', [TandaTanganController::class, 'update'])->name('tanda_tangan.update');
});

Route::resource('karyawan', KaryawanController::class);
Route::get('karyawan/{karyawan}', [KaryawanController::class, 'create'])->name('karyawan.create');




// Rute Simulasi Gaji (publik)
Route::get('/simulasi-gaji', [SimulasiGajiController::class, 'index'])->name('simulasi.index');
Route::post('/simulasi-gaji/hitung', [SimulasiGajiController::class, 'hitung'])->name('simulasi.hitung');

Route::get('/absensi', [AbsensiController::class, 'index'])->name('absensi.form');
Route::post('/absensi', [AbsensiController::class, 'store'])->name('absensi.store');


require __DIR__ . '/auth.php';
