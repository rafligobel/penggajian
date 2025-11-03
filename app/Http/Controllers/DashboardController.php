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
// use Illuminate\Container\Attributes\DB; // DIHAPUS KARENA TYPO DAN TIDAK DIGUNAKAN

class DashboardController extends Controller
{
    protected $salaryService;

    public function __construct(SalaryService $salaryService)
    {
        $this->salaryService = $salaryService;
    }

    public function index()
    {
        $semuaGaji = Gaji::with('karyawan.jabatan')->get();

        $kalkulasiGajiBersih = function (Gaji $gaji) {
            if (!$gaji->karyawan) {
                return 0;
            }

            $tunjanganJabatan = $gaji->karyawan->jabatan->tunj_jabatan ?? 0;


            $detailGaji = $this->salaryService->calculateDetailsForForm($gaji->karyawan, $gaji->bulan);
            $tunjanganKehadiran = $detailGaji['tunj_kehadiran'] ?? 0;

            $totalTunjangan = $tunjanganKehadiran +
                $tunjanganJabatan +
                $gaji->tunj_anak +
                $gaji->tunj_komunikasi +
                $gaji->tunj_pengabdian +
                $gaji->tunj_kinerja +
                $gaji->lembur;

            return $gaji->gaji_pokok + $totalTunjangan - $gaji->potongan;
        };


        $totalGajiDibayarkan = $semuaGaji->sum($kalkulasiGajiBersih);

        $totalGajiBulanIni = $semuaGaji->whereBetween('bulan', [now()->startOfMonth(), now()->endOfMonth()])
            ->sum($kalkulasiGajiBersih); 

        $totalGajiBulanLalu = $semuaGaji->whereBetween('bulan', [now()->subMonth()->startOfMonth(), now()->subMonth()->endOfMonth()])
            ->sum($kalkulasiGajiBersih); 

        if ($totalGajiBulanLalu > 0) {
            $perbandinganGaji = (($totalGajiBulanIni - $totalGajiBulanLalu) / $totalGajiBulanLalu) * 100;
        } else {
            $perbandinganGaji = $totalGajiBulanIni > 0 ? 100 : 0;
        }

        $jumlahKaryawan = Karyawan::count();
        $jumlahJabatan = Jabatan::count();
        $jumlahPengguna = User::count();
        $jumlahSlipGaji = $semuaGaji->count();
        $karyawanBaruBulanIni = Karyawan::whereBetween('created_at', [now()->startOfMonth(), now()->endOfMonth()])->count();
        $gajiDiproses = Gaji::whereBetween('bulan', [now()->startOfMonth(), now()->endOfMonth()])->count();

        $gajiPerBulan = $semuaGaji->groupBy(fn($gaji) => Carbon::parse($gaji->bulan)->format('Y-m'))
            ->map(fn($gajiBulanan) => $gajiBulanan->sum($kalkulasiGajiBersih)) // PERBAIKAN
            ->sortKeys();

        $labels = $gajiPerBulan->keys()->map(fn($bulan) => Carbon::createFromFormat('Y-m', $bulan)->format('M Y'));
        $data = $gajiPerBulan->values();

        return view('dashboard.index', compact(
            'totalGajiDibayarkan',
            'totalGajiBulanIni',
            'totalGajiBulanLalu',
            'perbandinganGaji',
            'jumlahKaryawan',
            'karyawanBaruBulanIni',
            'jumlahJabatan',
            'jumlahPengguna',
            'jumlahSlipGaji',
            'gajiDiproses',
            'labels',
            'data'
        ));
    }
}

// PERBAIKAN 3: Kurung kurawal '}' ekstra di akhir file telah DIHAPUS.