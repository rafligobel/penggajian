<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Karyawan;
use App\Models\Absensi;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

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
                    $statusAbsen = $absensiKaryawan[$tanggalSebagaiKunci]->status;
                    $jamAbsen = $absensiKaryawan[$tanggalSebagaiKunci]->jam ? Carbon::parse($absensiKaryawan[$tanggalSebagaiKunci]->jam)->format('H:i') : ''; // Format jam
                    $dataHarian[$hari] = [
                        'status' => !empty($statusAbsen) ? $statusAbsen : 'H',
                        'jam' => $jamAbsen,
                    ];
                    if (strtoupper($dataHarian[$hari]['status']) == 'HADIR' || strtoupper($dataHarian[$hari]['status']) == 'H') {
                        $totalHadir++;
                    }
                } else {
                    $dataHarian[$hari] = ['status' => '-', 'jam' => '']; // Tidak ada record absensi
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
