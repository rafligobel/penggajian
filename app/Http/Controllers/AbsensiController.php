<?php

namespace App\Http\Controllers;

use App\Models\Absensi;
use App\Models\Karyawan;
use App\Models\SesiAbsensi;
use App\Services\AbsensiService;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Exception;

class AbsensiController extends Controller
{
    protected $absensiService;

    public function __construct(AbsensiService $absensiService)
    {
        $this->absensiService = $absensiService;
    }

    public function index()
    {
        // Menggunakan today() untuk mendapatkan Carbon object hari ini (start of day)
        $today = today();
        $statusInfo = $this->absensiService->getSessionStatus($today);
        $isSesiDibuka = false;
        $sesiHariIni = null;
        $pesanSesi = $statusInfo['keterangan'] ?? $statusInfo['status'];

        if ($statusInfo['is_active']) {
            $now = now();
            // Carbon::parse() dari string waktu di DB
            $waktuMulai = Carbon::parse($statusInfo['waktu_mulai']);
            $waktuSelesai = Carbon::parse($statusInfo['waktu_selesai']);

            if ($now->between($waktuMulai, $waktuSelesai)) {
                $isSesiDibuka = true;
                $pesanSesi = 'Sesi absensi sedang dibuka.';
            } elseif ($now->isAfter($waktuSelesai)) {
                $pesanSesi = 'Sesi absensi hari ini sudah ditutup.';
            } else {
                $pesanSesi = 'Sesi absensi hari ini belum dibuka.';
            }


            $sesiHariIni = (object) [
                'waktu_mulai' => $waktuMulai->format('H:i'),
                'waktu_selesai' => $waktuSelesai->format('H:i'),
            ];
        }

        // PERHATIAN: Di controller ini, list data absensi tidak dimuat di index
        // Pastikan di view 'absensi.index' list absensi tidak ditampilkan
        return view('absensi.index', compact('sesiHariIni', 'isSesiDibuka', 'pesanSesi'));
    }

    public function store(Request $request)
    {
        // Menambahkan validasi untuk koordinat dan jarak (konsisten dengan skema DB)
        $request->validate([
            'identifier' => 'required|string',
            'koordinat' => 'nullable|string',
            'jarak' => 'nullable|numeric',
        ]);

        $karyawan = Karyawan::where('nip', $request->identifier)
            ->orWhere('nama', 'like', '%' . $request->identifier . '%')
            ->first();

        if (!$karyawan) {
            return redirect()->back()
                ->withErrors(['identifier' => 'NIP atau Nama tidak ditemukan.'])
                ->withInput();
        }

        $now = now();
        // Variabel konsisten untuk tanggal hari ini (string) untuk query
        $todayDate = $now->toDateString();

        // Menggunakan today() untuk mendapatkan Carbon object tanggal hari ini untuk service
        $today = today();
        $statusInfo = $this->absensiService->getSessionStatus($today);

        if (!$statusInfo['is_active']) {
            return redirect()->back()->with('info', 'Sesi absensi untuk hari ini tidak aktif. Keterangan: ' . ($statusInfo['keterangan'] ?? $statusInfo['status']));
        }

        $waktuMulai = Carbon::parse($statusInfo['waktu_mulai']);
        $waktuSelesai = Carbon::parse($statusInfo['waktu_selesai']);

        if (!$now->between($waktuMulai, $waktuSelesai)) {
            return redirect()->back()->with('info', 'Sesi absensi sedang ditutup. Sesi berlaku dari jam ' . $waktuMulai->format('H:i') . ' hingga ' . $waktuSelesai->format('H:i') . '.');
        }

        // Logika pengambilan ID Sesi (menggunakan variabel $todayDate yang konsisten)
        $sesiAbsensi = SesiAbsensi::where('tanggal', $todayDate)->where('is_default', false)->where('tipe', 'aktif')->first();
        if (!$sesiAbsensi) {
            $sesiAbsensi = SesiAbsensi::where('is_default', true)->first();
        }

        if (!$sesiAbsensi) {
            return redirect()->back()->with('info', 'FATAL: Sistem tidak dapat menemukan ID sesi absensi yang valid.');
        }

        // PERBAIKAN KONSISTENSI RELASI: Menggunakan karyawan_id
        $sudahAbsen = Absensi::where('karyawan_id', $karyawan->id)
            ->where('tanggal', $todayDate) // Menggunakan $todayDate yang konsisten
            ->exists();

        if ($sudahAbsen) {
            return redirect()->back()->with('info', 'Anda (' . $karyawan->nama . ') sudah melakukan absensi hari ini.');
        }

        // PERBAIKAN KONSISTENSI RELASI: Menggunakan karyawan_id untuk penyimpanan
        Absensi::create([
            'sesi_absensi_id' => $sesiAbsensi->id,
            'karyawan_id' => $karyawan->id, // MENGGANTI 'nip' dan 'nama'
            'tanggal' => $todayDate, // Menggunakan $todayDate yang konsisten
            'jam' => $now->toTimeString(),
            'koordinat' => $request->koordinat,
            'jarak' => $request->jarak ?? 0,
        ]);

        return redirect()->back()->with('success', 'Absensi untuk ' . $karyawan->nama . ' berhasil dicatat. Terima kasih!');
    }

    public function rekapPerBulan(Request $request)
    {
        $selectedMonth = $request->input('bulan', Carbon::now()->format('Y-m'));
        return view('absensi.rekap', compact('selectedMonth'));
    }

    public function fetchRekapData(Request $request)
    {
        try {
            $request->validate(['bulan' => 'required|date_format:Y-m']);
            $selectedMonth = Carbon::createFromFormat('Y-m', $request->bulan);

            $rekap = $this->absensiService->getAttendanceRecap($selectedMonth);

            return response()->json([
                'rekap' => $rekap['rekapData'],
                'nama_bulan' => $selectedMonth->translatedFormat('F Y'),
                'total_hari_kerja' => $rekap['workingDaysCount'],
            ]);
        } catch (Exception $e) {
            return response()->json([
                'error' => 'Terjadi kesalahan saat memuat rekap.',
                'message' => $e->getMessage()
            ], 500);
        }
    }
}
