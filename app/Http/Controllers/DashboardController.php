<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Karyawan;
use App\Models\Gaji;
use App\Models\Jabatan;
use App\Models\SesiAbsensi;
use App\Models\User;
use Carbon\Carbon;
use App\Services\SalaryService; // Menggunakan SalaryService untuk konsistensi
use Illuminate\Container\Attributes\DB;

class DashboardController extends Controller
{
    protected $salaryService;

    public function __construct(SalaryService $salaryService)
    {
        $this->salaryService = $salaryService;
    }

    public function index()
    {
        $semuaGaji = Gaji::all();

        // --- 1. Kalkulasi untuk Kartu Gaji ---
        $totalGajiDibayarkan = $semuaGaji->sum(fn($gaji) => $gaji->gaji_pokok + $gaji->total_tunjangan - $gaji->total_potongan);
        $totalGajiBulanIni = $semuaGaji->whereBetween('bulan', [now()->startOfMonth(), now()->endOfMonth()])->sum(fn($gaji) => $gaji->gaji_pokok + $gaji->total_tunjangan - $gaji->total_potongan);
        $totalGajiBulanLalu = $semuaGaji->whereBetween('bulan', [now()->subMonth()->startOfMonth(), now()->subMonth()->endOfMonth()])->sum(fn($gaji) => $gaji->gaji_pokok + $gaji->total_tunjangan - $gaji->total_potongan);

        if ($totalGajiBulanLalu > 0) {
            $perbandinganGaji = (($totalGajiBulanIni - $totalGajiBulanLalu) / $totalGajiBulanLalu) * 100;
        } else {
            $perbandinganGaji = $totalGajiBulanIni > 0 ? 100 : 0;
        }

        // --- 2. Jumlah Entitas & Statistik Karyawan ---
        $jumlahKaryawan = Karyawan::count();
        $jumlahJabatan = Jabatan::count();
        $jumlahPengguna = User::count();
        $jumlahSlipGaji = $semuaGaji->count();

        // [PERBAIKAN FINAL] Menghitung karyawan baru bulan ini
        $karyawanBaruBulanIni = Karyawan::whereBetween('created_at', [now()->startOfMonth(), now()->endOfMonth()])->count();
        $gajiDiproses = Gaji::with('karyawan.jabatan')->latest()->take(5)->get();

        // --- 3. Data untuk Grafik (Chart) ---
        $gajiPerBulan = $semuaGaji->groupBy(fn($gaji) => Carbon::parse($gaji->tanggal)->format('Y-m'))
            ->map(fn($gajiBulanan) => $gajiBulanan->sum(fn($gaji) => $gaji->gaji_pokok + $gaji->total_tunjangan - $gaji->total_potongan))
            ->sortKeys();

        $labels = $gajiPerBulan->keys()->map(fn($bulan) => Carbon::createFromFormat('Y-m', $bulan)->format('M Y'));
        $data = $gajiPerBulan->values();

        // --- 4. Kirim Semua Data ke View ---
        return view('dashboard.index', compact(
            'totalGajiDibayarkan',
            'totalGajiBulanIni',
            'perbandinganGaji',
            'jumlahKaryawan',
            'karyawanBaruBulanIni', // Variabel yang hilang kini ditambahkan
            'jumlahJabatan',
            'jumlahPengguna',
            'jumlahSlipGaji',
            'gajiDiproses',
            'labels',
            'data'
        ));
    }
}
