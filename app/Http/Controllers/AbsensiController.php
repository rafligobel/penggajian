<?php

namespace App\Http\Controllers;

use App\Models\Absensi;
use App\Models\Karyawan;
use App\Models\SesiAbsensi;
use App\Services\AbsensiService; // Pastikan AbsensiService di-import
use Carbon\Carbon;
use Illuminate\Http\Request;
use Exception; // Import Exception untuk error handling
// [PERBAIKAN] Tambahkan Log jika diperlukan untuk error
use Illuminate\Support\Facades\Log;

class AbsensiController extends Controller
{
    protected $absensiService;

    // Inject AbsensiService melalui constructor
    public function __construct(AbsensiService $absensiService)
    {
        $this->absensiService = $absensiService;
    }

    /**
     * Menampilkan halaman utama absensi.
     * (Tidak ada perubahan, sudah benar)
     */
    public function index()
    {
        $today = today();
        $statusInfo = $this->absensiService->getSessionStatus($today);
        $isSesiDibuka = false;
        $sesiHariIni = null;
        $pesanSesi = $statusInfo['keterangan'] ?? $statusInfo['status'];

        if ($statusInfo['is_active']) {
            $now = now();
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

        return view('absensi.index', compact('sesiHariIni', 'isSesiDibuka', 'pesanSesi'));
    }

    /**
     * Menyimpan data absensi baru.
     * [PERBAIKAN] Menghapus logika pencarian sesi manual dan menggunakan sesi_id dari service.
     */
    public function store(Request $request)
    {
        $request->validate([
            'identifier' => 'required|string',
            'koordinat' => 'required|string|regex:/^[-]?\d+(\.\d+)?,[-]?\d+(\.\d+)?$/',
        ]);

        $karyawan = Karyawan::where('nip', $request->identifier)
            ->orWhere('nama', 'like', '%' . $request->identifier . '%')
            ->first();

        if (!$karyawan) {
            return redirect()->back()
                ->withErrors(['identifier' => 'NIP atau Nama tidak ditemukan.'])
                ->withInput();
        }

        // --- Validasi Jarak ---
        $office_lat = env('OFFICE_LATITUDE');
        $office_lon = env('OFFICE_LONGITUDE');
        $max_radius = (float) env('MAX_ATTENDANCE_RADIUS', 50);

        if (!$office_lat || !$office_lon) {
            return redirect()->back()->with('info', 'Konfigurasi lokasi kantor belum lengkap. Silakan hubungi administrator.');
        }

        $koordinat_parts = explode(',', $request->koordinat);
        $lat_karyawan = isset($koordinat_parts[0]) ? (float) trim($koordinat_parts[0]) : null;
        $lon_karyawan = isset($koordinat_parts[1]) ? (float) trim($koordinat_parts[1]) : null;

        $jarak = 0;
        if ($lat_karyawan !== null && $lon_karyawan !== null) {
            try {
                $jarak = $this->absensiService->calculateDistance(
                    $lat_karyawan,
                    $lon_karyawan,
                    (float) $office_lat,
                    (float) $office_lon
                );
            } catch (Exception $e) {
                return redirect()->back()->with('info', 'Terjadi kesalahan saat menghitung jarak: ' . $e->getMessage());
            }
        } else {
            return redirect()->back()->with('info', 'Format koordinat tidak valid. Pastikan izin lokasi aktif dan coba lagi.');
        }

        if ($jarak > $max_radius) {
            return redirect()->back()->with('info', "Anda berada di luar area kantor! Jarak Anda sekitar " . round($jarak) . " meter dari kantor. Maksimal jarak yang diizinkan adalah {$max_radius} meter.");
        }
        // --- Akhir Validasi Jarak ---

        $now = now();
        $todayDate = $now->toDateString();
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

        // [PERBAIKAN] Menggunakan sesi_id dari service, hapus pencarian manual
        if (empty($statusInfo['sesi_id'])) {
            // Ini seharusnya tidak terjadi jika is_active true, tapi sebagai pengaman
            Log::warning('AbsensiController@store: Sesi aktif tetapi sesi_id tidak ditemukan.', ['statusInfo' => $statusInfo]);
            return redirect()->back()->with('info', 'Tidak ditemukan ID sesi absensi yang valid untuk hari ini.');
        }

        $sesiAbsensiId = $statusInfo['sesi_id'];

        // Cek apakah karyawan sudah melakukan absensi pada tanggal ini
        $sudahAbsen = Absensi::where('karyawan_id', $karyawan->id)
            ->where('tanggal', $todayDate)
            ->exists();

        if ($sudahAbsen) {
            return redirect()->back()->with('info', 'Anda (' . $karyawan->nama . ') sudah melakukan absensi hari ini.');
        }

        Absensi::create([
            'sesi_absensi_id' => $sesiAbsensiId, // [PERBAIKAN] Menggunakan ID dari service
            'karyawan_id' => $karyawan->id,
            'tanggal' => $todayDate,
            'jam' => $now->toTimeString(),
            'koordinat' => $request->koordinat,
            'jarak' => round($jarak),
        ]);

        return redirect()->back()->with('success', 'Absensi untuk ' . $karyawan->nama . ' berhasil dicatat. Jarak Anda: ' . round($jarak) . ' meter. Terima kasih!');
    }

    /**
     * Menampilkan halaman rekap absensi per bulan.
     * (Tidak ada perubahan)
     */
    public function rekapPerBulan(Request $request)
    {
        $selectedMonth = $request->input('bulan', Carbon::now()->format('Y-m'));
        return view('absensi.rekap', compact('selectedMonth'));
    }

    /**
     * Mengambil data rekap absensi untuk bulan tertentu (API endpoint untuk AJAX).
     * (Tidak ada perubahan)
     */
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
                'days_in_month' => $rekap['daysInMonth'],
            ]);
        } catch (Exception $e) {
            // Log::error("Error fetching rekap data: " . $e->getMessage());
            return response()->json([
                'error' => 'Terjadi kesalahan saat memuat data rekap absensi.',
                'message' => $e->getMessage()
            ], 500);
        }
    }
}
