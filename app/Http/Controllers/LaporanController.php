<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Gaji;
use App\Models\Karyawan;
use App\Models\Absensi;
use Carbon\Carbon;
use Barryvdh\DomPDF\Facade\Pdf;
use App\Traits\ManagesImageEncoding; // <-- Import Trait
use App\Jobs\GenerateMonthlySalaryReport; // Import Job
use Illuminate\Support\Facades\Auth;

class LaporanController extends Controller
{
    use ManagesImageEncoding; // <-- Gunakan Trait

    public function index()
    {
        return redirect()->route('laporan.gaji.bulanan');
    }

    public function gajiBulanan(Request $request)
    {
        $selectedMonth = $request->input('bulan', Carbon::now()->format('Y-m'));

        $gajis = Gaji::with('karyawan')
            ->where('bulan', $selectedMonth)
            ->get();

        $statistik = [
            'total_pengeluaran' => $gajis->sum('gaji_bersih'),
            'gaji_tertinggi' => $gajis->max('gaji_bersih'),
            'gaji_rata_rata' => $gajis->avg('gaji_bersih'),
            'jumlah_penerima' => $gajis->count(),
        ];

        return view('laporan.gaji_bulanan', [
            'gajis' => $gajis,
            'selectedMonth' => $selectedMonth,
            'statistik' => $statistik
        ]);
    }

    /**
     * Mencetak laporan rekapitulasi gaji bulanan dalam format PDF.
     * Metode ini telah disempurnakan.
     */
    public function cetakGajiBulanan(Request $request)
    {
        $selectedMonth = $request->input('bulan', now()->format('Y-m'));
        $user = Auth::user();

        // Mengirim tugas ke antrian. Ini adalah satu-satunya tugasnya!
        GenerateMonthlySalaryReport::dispatch($selectedMonth, $user);

        // Redirect kembali dengan pesan sukses
        return redirect()->route('laporan.gaji.bulanan', ['bulan' => $selectedMonth])
            ->with('success', 'Permintaan laporan diterima! Laporan sedang diproses dan akan muncul di notifikasi jika sudah siap.');
    }

    // method perKaryawan tidak berubah
    public function perKaryawan(Request $request)
    {
        // ... (kode tetap sama)
        $karyawans = Karyawan::where('status_aktif', true)->orderBy('nama')->get();
        $selectedKaryawanId = $request->input('karyawan_id');
        $tanggalMulai = $request->input('tanggal_mulai', Carbon::now()->subMonths(5)->format('Y-m'));
        $tanggalSelesai = $request->input('tanggal_selesai', Carbon::now()->format('Y-m'));
        $laporanData = [];
        $selectedKaryawan = null;

        if ($selectedKaryawanId) {
            $selectedKaryawan = Karyawan::find($selectedKaryawanId);
            $gajis = Gaji::where('karyawan_id', $selectedKaryawanId)
                ->whereBetween('bulan', [$tanggalMulai, $tanggalSelesai])
                ->orderBy('bulan', 'asc')
                ->get();

            $startOfMonth = Carbon::createFromFormat('Y-m', $tanggalMulai)->startOfMonth();
            $endOfMonth = Carbon::createFromFormat('Y-m', $tanggalSelesai)->endOfMonth();

            $absensi = Absensi::where('nip', $selectedKaryawan->nip)
                ->whereBetween('tanggal', [$startOfMonth, $endOfMonth])
                ->get();

            $totalHariKerja = 0;
            for ($date = $startOfMonth->copy(); $date->lte($endOfMonth); $date->addDay()) {
                if ($date->isWeekday() || $date->isSaturday()) {
                    $totalHariKerja++;
                }
            }

            $laporanData = [
                'gajis' => $gajis,
                'absensi_summary' => [
                    'hadir' => $absensi->count(),
                    'alpha' => $totalHariKerja - $absensi->count(),
                ],
            ];
        }

        return view('laporan.per_karyawan', [
            'karyawans' => $karyawans,
            'selectedKaryawanId' => $selectedKaryawanId,
            'tanggalMulai' => $tanggalMulai,
            'tanggalSelesai' => $tanggalSelesai,
            'laporanData' => $laporanData,
            'selectedKaryawan' => $selectedKaryawan,
        ]);
    }
}
