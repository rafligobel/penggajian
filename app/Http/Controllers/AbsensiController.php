<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Karyawan;
use App\Models\Absensi;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB; // Pastikan ini ada

class AbsensiController extends Controller
{
    public function index()
    {
        return view('absensi.index');
    }

    public function store(Request $request)
    {
        $request->validate([
            'nip' => 'required|exists:karyawans,nip',
        ]);

        $karyawan = Karyawan::where('nip', $request->nip)->first();

        // Cek apakah sudah absen hari ini menggunakan NIP
        $sudahAbsen = Absensi::where('nip', $karyawan->nip)
            ->where('tanggal', Carbon::now()->toDateString())
            ->exists();

        if ($sudahAbsen) {
            return redirect()->back()->with('info', 'Anda sudah absen hari ini.');
        }

        Absensi::create([
            'nip' => $karyawan->nip,
            'nama' => $karyawan->nama, // Menyimpan nama karyawan saat absen
            'tanggal' => Carbon::now()->toDateString(),
            'status' => 'Hadir', // Status default saat absen
            'jam' => Carbon::now()->toTimeString(), // Menyimpan jam absen
        ]);

        return redirect()->back()->with('success', 'Absensi berhasil dicatat untuk hari ini!');
    }

    /**
     * Menampilkan halaman rekap absensi bulanan.
     */
    public function rekapPerBulan(Request $request)
    {
        $selectedMonth = $request->input('bulan', Carbon::now()->format('Y-m'));
        return view('absensi.rekap', compact('selectedMonth'));
    }

    /**
     * Mengambil data rekap absensi untuk bulan tertentu.
     */
    public function fetchRekapData(Request $request)
    {
        $request->validate([
            'bulan' => 'required|date_format:Y-m',
        ]);

        $bulanTahun = $request->input('bulan');
        $carbonDate = Carbon::createFromFormat('Y-m', $bulanTahun);
        $tahun = $carbonDate->year;
        $bulan = $carbonDate->month;
        $jumlahHari = $carbonDate->daysInMonth;

        $karyawans = Karyawan::select('id', 'nama', 'nip')->orderBy('nama')->get();
        $rekapAbsensi = [];

        foreach ($karyawans as $karyawan) {
            $absensiKaryawan = Absensi::where('nip', $karyawan->nip)
                ->whereYear('tanggal', $tahun)
                ->whereMonth('tanggal', $bulan)
                ->orderBy('tanggal', 'asc')
                ->get()
                ->keyBy(function ($item) {
                    return Carbon::parse($item->tanggal)->format('d');
                });

            $dataHarian = [];
            $totalHadir = 0;
            for ($hari = 1; $hari <= $jumlahHari; $hari++) {
                $tanggalSebagaiKunci = str_pad($hari, 2, '0', STR_PAD_LEFT);
                if (isset($absensiKaryawan[$tanggalSebagaiKunci])) {
                    // Default ke 'H' jika status null atau kosong, atau gunakan status yang ada
                    $statusAbsen = $absensiKaryawan[$tanggalSebagaiKunci]->status;
                    $dataHarian[$hari] = !empty($statusAbsen) ? $statusAbsen : 'H';
                    if (strtoupper($dataHarian[$hari]) == 'HADIR' || strtoupper($dataHarian[$hari]) == 'H') {
                        $totalHadir++;
                    }
                } else {
                    $dataHarian[$hari] = '-'; // Tidak ada record absensi
                }
            }

            $rekapAbsensi[] = [
                'nama' => $karyawan->nama,
                'nip' => $karyawan->nip,
                'harian' => $dataHarian,
                'total_hadir' => $totalHadir,
            ];
        }

        return response()->json([
            'rekap' => $rekapAbsensi,
            'jumlah_hari' => $jumlahHari,
            'nama_bulan' => $carbonDate->translatedFormat('F Y'),
        ]);
    }
}