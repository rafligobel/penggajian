<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Gaji;
use App\Models\Karyawan;
use App\Models\Absensi; // Pastikan ini mengarah ke App\Models\Absensi
use Carbon\Carbon;
use Barryvdh\DomPDF\Facade\Pdf;

class LaporanController extends Controller
{
    /**
     * Menampilkan halaman utama Laporan.
     * Untuk saat ini, akan langsung diarahkan ke laporan gaji bulanan.
     */
    public function index()
    {
        // Default view adalah laporan gaji bulanan
        return redirect()->route('laporan.gaji.bulanan');
    }

    /**
     * Menampilkan laporan rekapitulasi gaji bulanan.
     */
    public function gajiBulanan(Request $request)
    {
        $selectedMonth = $request->input('bulan', Carbon::now()->format('Y-m'));
        $date = Carbon::createFromFormat('Y-m', $selectedMonth);

        // Mengambil semua data gaji untuk bulan yang dipilih
        $gajis = Gaji::with('karyawan')
            ->where('bulan', $selectedMonth)
            ->get();

        // Menghitung statistik
        $statistik = [
            'total_pengeluaran' => $gajis->sum('gaji_bersih'),
            'gaji_tertinggi' => $gajis->max('gaji_bersih'),
            'gaji_terendah' => $gajis->min('gaji_bersih'),
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
     */
    public function cetakGajiBulanan(Request $request)
    {
        $selectedMonth = $request->input('bulan', Carbon::now()->format('Y-m'));
        $date = Carbon::createFromFormat('Y-m', $selectedMonth);

        $gajis = Gaji::with('karyawan')
            ->where('bulan', $selectedMonth)
            ->get();

        $karyawansData = $gajis->map(function($gaji) {
            return (object) array_merge($gaji->toArray(), ['jabatan' => $gaji->karyawan->jabatan, 'nama' => $gaji->karyawan->nama]);
        });

        $pdf = Pdf::loadView('gaji.cetak_semua', ['karyawans' => $karyawansData])->setPaper('a4', 'landscape');
        return $pdf->stream('laporan_gaji_bulanan_' . $selectedMonth . '.pdf');
    }
    
    /**
     * Menampilkan laporan riwayat gaji per karyawan.
     */
    public function perKaryawan(Request $request)
    {
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