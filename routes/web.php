<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\UserController;
use App\Http\Controllers\JabatanController;
use App\Http\Controllers\TunjanganKehadiranController;
use App\Http\Controllers\KaryawanController;
use App\Http\Controllers\GajiController;
use App\Http\Controllers\LaporanController;
use App\Http\Controllers\AbsensiController;
use App\Http\Controllers\SesiAbsensiController;
use App\Http\Controllers\TandaTanganController;
use App\Http\Controllers\NotificationController;
use App\Http\Controllers\TenagaKerjaController;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
*/

// --- RUTE PUBLIK ---
Route::get('/', function () {
    return view('welcome');
});


// --- RUTE UNTUK SEMUA USER YANG SUDAH LOGIN ---
Route::middleware('auth')->group(function () {
    // Hanya satu definisi untuk route 'dashboard' utama
    Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');

    // Profile & Notifikasi
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');

    Route::get('/notifications', [NotificationController::class, 'index'])->name('notifications.index');
    Route::post('/notifications/delete-selected', [NotificationController::class, 'deleteSelected'])->name('notifications.deleteSelected');
    Route::get('/notifications/mark-all-as-read', [NotificationController::class, 'markAllAsRead'])->name('notifications.markAllAsRead');
    Route::get('/notifications/mark-as-read/{id}', [NotificationController::class, 'markAsRead'])->name('notifications.markAsRead');
});


// --- RUTE KHUSUS ADMIN & SUPERADMIN ---
Route::middleware(['auth', 'role:superadmin,admin'])->group(function () {
    Route::resource('users', UserController::class);
    Route::resource('jabatan', JabatanController::class);
    Route::resource('tunjangan-kehadiran', TunjanganKehadiranController::class)->except(['create', 'edit', 'show']);

    // Resource Karyawan sekarang aman di dalam middleware admin
    Route::resource('karyawan', KaryawanController::class);
});


// --- RUTE KHUSUS BENDAHARA ---
Route::middleware(['auth', 'role:bendahara'])->group(function () {
    // Kelola Gaji
    Route::get('gaji', [GajiController::class, 'index'])->name('gaji.index');
    Route::post('gaji/save', [GajiController::class, 'saveOrUpdate'])->name('gaji.save');
    Route::post('/gaji/{gaji}/download-slip', [GajiController::class, 'downloadSlip'])->name('gaji.download-slip');
    Route::post('/gaji/{gaji}/send-email', [GajiController::class, 'sendEmail'])->name('gaji.send-email');

    // Laporan
    Route::get('/laporan', [LaporanController::class, 'index'])->name('laporan.index');
    Route::get('/laporan/gaji-bulanan', [LaporanController::class, 'gajiBulanan'])->name('laporan.gaji.bulanan');
    Route::post('/laporan/gaji-bulanan/cetak', [LaporanController::class, 'cetakGajiBulanan'])->name('laporan.gaji.cetak');
    Route::post('/laporan/gaji/kirim-email-terpilih', [LaporanController::class, 'kirimEmailGajiTerpilih'])->name('laporan.gaji.kirim-email-terpilih');
    Route::get('/laporan/per-karyawan', [LaporanController::class, 'perKaryawan'])->name('laporan.per.karyawan');
    Route::post('/laporan/per-karyawan/cetak', [LaporanController::class, 'cetakLaporanPerKaryawan'])->name('laporan.per.karyawan.cetak');
    Route::post('/laporan/per-karyawan/kirim-email', [LaporanController::class, 'kirimEmailLaporanPerKaryawan'])->name('laporan.per.karyawan.kirim-email');
    Route::get('/laporan/absensi', [LaporanController::class, 'rekapAbsensi'])->name('laporan.absensi');
    Route::post('/laporan/absensi/cetak', [LaporanController::class, 'cetakRekapAbsensi'])->name('laporan.absensi.cetak');
    Route::post('/laporan/absensi/kirim-email', [LaporanController::class, 'kirimEmailRekapAbsensi'])->name('laporan.absensi.kirim-email');
    Route::get('/laporan/absensi/data', [AbsensiController::class, 'fetchRekapData'])->name('laporan.absensi.data');

    // Absensi
    Route::get('/rekap-absensi', [AbsensiController::class, 'rekapPerBulan'])->name('absensi.rekap');
    Route::get('/sesi-absensi', [SesiAbsensiController::class, 'index'])->name('sesi-absensi.index');
    Route::post('sesi-absensi', [SesiAbsensiController::class, 'storeOrUpdate'])->name('sesi-absensi.storeOrUpdate');
    Route::get('sesi-absensi/calendar-events', [SesiAbsensiController::class, 'getCalendarEvents'])->name('sesi-absensi.calendar-events');

    // Pengaturan Tanda Tangan
    Route::get('/tanda-tangan', [TandaTanganController::class, 'index'])->name('tanda_tangan.index');
    Route::post('/tanda-tangan', [TandaTanganController::class, 'update'])->name('tanda_tangan.update');
});


// --- RUTE KHUSUS TENAGA KERJA ---
Route::middleware(['auth', 'role:tenaga_kerja'])->prefix('tenaga-kerja')->name('tenaga_kerja.')->group(function () {
    // Cukup satu route dashboard
    Route::get('/dashboard', [TenagaKerjaController::class, 'dashboard'])->name('dashboard');
    Route::get('/absensi', [TenagaKerjaController::class, 'formAbsensi'])->name('absensi.form');
    Route::post('/absensi', [TenagaKerjaController::class, 'prosesAbsensi'])->name('absensi.store');

    // Rute untuk fungsionalitas di dashboard tenaga kerja
    Route::post('/simulasi/hitung', [TenagaKerjaController::class, 'hitungSimulasi'])->name('simulasi.hitung');
    Route::get('/laporan-gaji', [TenagaKerjaController::class, 'laporanGaji'])->name('laporan_gaji');
    // Route::post('/slip-gaji/download', [TenagaKerjaController::class, 'downloadSlipGaji'])->name('slip_gaji.download');

    // Rute untuk konten modal AJAX (INI YANG PALING PENTING UNTUK FIX ERROR)
    Route::get('/modal/laporan-gaji', [TenagaKerjaController::class, 'getModalLaporanGaji'])->name('modal.laporan_gaji');
    Route::get('/modal/slip-gaji', [TenagaKerjaController::class, 'getModalSlipGaji'])->name('modal.slip_gaji');

    Route::post('/slip-gaji/download', [TenagaKerjaController::class, 'downloadSlipGaji'])
        ->name('slip_gaji.download'); // <-- Nama route yang benar: 'tenaga_kerja.slip_gaji.download'
    Route::post('/laporan-gaji/{gaji}/cetak', [TenagaKerjaController::class, 'cetakLaporanGaji'])
        ->name('laporan_gaji.cetak');
});


// --- AUTHENTICATION ROUTES ---
require __DIR__ . '/auth.php';
