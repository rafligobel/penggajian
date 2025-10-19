<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Karyawan;
use App\Models\Gaji;
use Carbon\Carbon;
use Illuminate\Support\Facades\Auth;
use App\Models\Absensi;
use App\Models\TunjanganKehadiran;
use App\Models\SesiAbsensi;
use App\Services\AbsensiService;
use App\Services\SalaryService;
use App\Jobs\GenerateIndividualSlip;

use App\Models\TandaTangan;
use App\Traits\ManagesImageEncoding;
use Illuminate\Support\Facades\Storage;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Support\Facades\Log;
use Throwable;


class TenagaKerjaController extends Controller
{
    use ManagesImageEncoding;

    protected AbsensiService $absensiService;
    protected SalaryService $salaryService;

    public function __construct(AbsensiService $absensiService, SalaryService $salaryService)
    {
        $this->absensiService = $absensiService;
        $this->salaryService = $salaryService;
    }

    public function dashboard(Request $request)
    {
        $user = Auth::user();
        $karyawan = $user->karyawan;

        // ======================================================================================
        // [PERBAIKAN 1: LOGIKA ABSENSI DASHBOARD]
        // Logika disederhanakan untuk selalu menggunakan service, yang sudah
        // menangani kasus sesi default vs. sesi spesifik hari ini.
        // ======================================================================================
        $today = today();
        $statusInfo = $this->absensiService->getSessionStatus($today);
        $isSesiDibuka = false;
        $pesanSesi = $statusInfo['status']; // Pesan default dari service

        if ($statusInfo['is_active']) {
            $now = now();
            $waktuMulai = Carbon::parse($statusInfo['waktu_mulai']);
            $waktuSelesai = Carbon::parse($statusInfo['waktu_selesai']);

            if ($now->between($waktuMulai, $waktuSelesai)) {
                $isSesiDibuka = true;
                $pesanSesi = 'Sesi absensi sedang dibuka (' . $waktuMulai->format('H:i') . ' - ' . $waktuSelesai->format('H:i') . ').';
            } else if ($now->isAfter($waktuSelesai)) {
                $pesanSesi = 'Sesi absensi hari ini sudah ditutup.';
            } else {
                $pesanSesi = 'Sesi absensi hari ini akan dibuka pada pukul ' . $waktuMulai->format('H:i') . '.';
            }
        }

        $sudahAbsen = Absensi::where('nip', $karyawan->nip)->whereDate('tanggal', $today)->exists();

        // [PERBAIKAN: Tambah whereYear untuk akurasi]
        $absensiBulanIni = Absensi::where('nip', $karyawan->nip)
            ->whereYear('tanggal', now()->year)
            ->whereMonth('tanggal', now()->month)
            ->count();

        $gajiTerbaru = $karyawan->gajis()->orderBy('bulan', 'desc')->first();

        // --- Logika untuk Modal Laporan Gaji ---
        $tahun = $request->input('tahun', date('Y'));
        $availableYears = Gaji::where('karyawan_id', $karyawan->id)
            ->whereNotNull('bulan')
            ->selectRaw('YEAR(bulan) as year')
            ->distinct()->orderBy('year', 'desc')->pluck('year');

        $gajis = Gaji::where('karyawan_id', $karyawan->id)
            ->whereYear('bulan', $tahun)
            ->orderBy('bulan', 'asc')
            ->with('tunjanganKehadiran', 'karyawan.jabatan')
            ->get();

        $rekapAbsensiPerBulan = Absensi::where('nip', $karyawan->nip)
            ->whereYear('tanggal', $tahun)
            ->selectRaw('DATE_FORMAT(tanggal, "%Y-%m") as bulan, COUNT(*) as jumlah_hadir')
            ->groupBy('bulan')
            ->pluck('jumlah_hadir', 'bulan');

        foreach ($gajis as $gaji) {
            $tunjanganDariJabatan = $gaji->karyawan->jabatan->tunjangan_jabatan ?? 0;
            $bulanKey = $gaji->bulan->format('Y-m');
            $totalKehadiran = $rekapAbsensiPerBulan->get($bulanKey, 0);
            $tunjanganPerKehadiran = $gaji->tunjanganKehadiran->jumlah_tunjangan ?? 0;
            $totalTunjanganKehadiran = $totalKehadiran * $tunjanganPerKehadiran;

            $gaji->total_tunjangan = $tunjanganDariJabatan + $gaji->tunj_anak + $gaji->tunj_komunikasi + $gaji->tunj_pengabdian + $gaji->tunj_kinerja + $totalTunjanganKehadiran + $gaji->lembur;
            $gaji->total_potongan = $gaji->potongan;
        }

        $laporanData = [
            'gajis' => $gajis
        ];

        $slipTersedia = Gaji::where('karyawan_id', $karyawan->id)
            ->orderBy('bulan', 'desc')->pluck('bulan');

        return view('tenaga_kerja.dashboard', compact(
            'karyawan',
            'gajiTerbaru',
            'absensiBulanIni',
            'isSesiDibuka',
            'sudahAbsen',
            'pesanSesi',
            'laporanData', // ▲▲▲ PERUBAHAN 2: GANTI 'gajis' MENJADI 'laporanData' ▲▲▲
            'tahun',
            'availableYears',
            'slipTersedia'
        ));
    }

    public function prosesAbsensi(Request $request)
    {
        $request->validate([
            'latitude' => 'required|numeric|between:-90,90',
            'longitude' => 'required|numeric|between:-180,180',
        ]);

        // ======================================================================================
        // [PERBAIKAN 2: BUG LOKASI (0,0)]
        // Menambahkan validasi untuk menolak koordinat (0,0) jika GPS gagal.
        // ======================================================================================
        $userLat = (float) $request->latitude;
        $userLon = (float) $request->longitude;

        if ($userLat == 0 && $userLon == 0) {
            return response()->json([
                'status' => 'error',
                'message' => 'Gagal mendapatkan lokasi GPS. Pastikan izin lokasi (GPS) di perangkat Anda sudah aktif dan coba lagi.'
            ], 422); // Unprocessable Entity
        }
        // --- PERBAIKAN SELESAI ---

        $officeLatitude = (float) env('OFFICE_LATITUDE');
        $officeLongitude = (float) env('OFFICE_LONGITUDE');
        $maxRadius = (int) env('MAX_ATTENDANCE_RADIUS', 50);

        $distance = $this->hitungJarak($userLat, $userLon, $officeLatitude, $officeLongitude);

        if ($distance > $maxRadius) {
            $errorMessage = 'Anda berada di luar area sekolah! Jarak Anda ' . round($distance) . ' meter dari sekolah Alazhar.';
            return response()->json([
                'status' => 'error',
                'message' => $errorMessage
            ], 422);
        }

        $karyawan = Auth::user()->karyawan;
        $now = now();
        $today = $now->copy()->startOfDay();

        $statusInfo = $this->absensiService->getSessionStatus($today);
        if (!$statusInfo['is_active'] || !$now->between(Carbon::parse($statusInfo['waktu_mulai']), Carbon::parse($statusInfo['waktu_selesai']))) {
            return response()->json(['status' => 'error', 'message' => 'Sesi absensi sedang tidak dibuka saat ini.'], 403);
        }

        $sudahAbsen = Absensi::where('nip', $karyawan->nip)->whereDate('tanggal', $today)->exists();
        if ($sudahAbsen) {
            return response()->json(['status' => 'error', 'message' => 'Anda sudah melakukan absensi hari ini.'], 409);
        }

        $sesiAbsensi = SesiAbsensi::where('tanggal', $today->format('Y-m-d'))->first() ?? SesiAbsensi::where('is_default', true)->first();
        if (!$sesiAbsensi) {
            return response()->json(['status' => 'error', 'message' => 'Sistem tidak dapat menemukan sesi absensi yang valid.'], 500);
        }

        Absensi::create([
            'sesi_absensi_id' => $sesiAbsensi->id,
            'nip' => $karyawan->nip,
            'nama' => $karyawan->nama,
            'tanggal' => $now->toDateString(),
            'jam' => $now->toTimeString(),
            'koordinat' => $userLat . ',' . $userLon,
            'jarak' => round($distance)
        ]);

        return response()->json([
            'status' => 'success',
            'message' => 'Absensi berhasil! Terima kasih.'
        ]);
    }

    private function hitungJarak($lat1, $lon1, $lat2, $lon2)
    {
        $earthRadius = 6371000;
        $latFrom = deg2rad($lat1);
        $lonFrom = deg2rad($lon1);
        $latTo = deg2rad($lat2);
        $lonTo = deg2rad($lon2);
        $latDelta = $latTo - $latFrom;
        $lonDelta = $lonTo - $lonFrom;
        $angle = 2 * asin(sqrt(pow(sin($latDelta / 2), 2) + cos($latFrom) * cos($latTo) * pow(sin($lonDelta / 2), 2)));
        return $angle * $earthRadius;
    }

    // ======================================================================================
    // [PERBAIKAN: FITUR SIMULASI]
    // Fungsi hitungSimulasi() yang lama (menggunakan redirect) dihapus.
    // Logika tersebut sekarang ditangani oleh SimulasiGajiController,
    // yang akan di-rute-kan ke 'tenaga_kerja.simulasi.hitung'.
    // ======================================================================================

    public function downloadSlipGaji(Request $request)
    {
        $validated = $request->validate([
            'bulan' => 'required|date_format:Y-m',
        ]);

        try {
            $user = Auth::user();
            $karyawan = $user->karyawan;
            $tanggal = Carbon::createFromFormat('Y-m', $validated['bulan']);

            $gaji = Gaji::where('karyawan_id', $karyawan->id)
                ->whereYear('bulan', $tanggal->year)
                ->whereMonth('bulan', $tanggal->month)
                ->firstOrFail();

            $data = $this->salaryService->calculateDetailsForForm($gaji->karyawan, $gaji->bulan->format('Y-m-d'));

            $logoAlAzhar = $this->getImageAsBase64DataUri(public_path('logo/logoalazhar.png'));
            $logoYayasan = $this->getImageAsBase64DataUri(public_path('logo/logoyayasan.png'));

            $bendaharaUser = \App\Models\User::where('role', 'bendahara')->first();
            $bendaharaNama = $bendaharaUser ? $bendaharaUser->name : 'Bendahara Umum';

            $tandaTanganBendahara = '';
            $pengaturanTtd = TandaTangan::where('key', 'tanda_tangan_bendahara')->first();
            if ($pengaturanTtd && Storage::disk('public')->exists($pengaturanTtd->value)) {
                $tandaTanganBendahara = $this->getImageAsBase64DataUri(storage_path('app/public/' . $pengaturanTtd->value));
            }

            $pdf = Pdf::loadView('gaji.slip_pdf', [
                'data' => $data,
                'gaji' => $gaji,
                'logoAlAzhar' => $logoAlAzhar,
                'logoYayasan' => $logoYayasan,
                'bendaharaNama' => $bendaharaNama,
                'tandaTanganBendahara' => $tandaTanganBendahara
            ]);
            $pdf->setPaper('A4', 'portrait');

            $safeFilename = str_replace(' ', '_', strtolower($gaji->karyawan->nama));
            $filename = 'slip-gaji-' . $safeFilename . '-' . $gaji->bulan->format('Y-m') . '.pdf';

            return $pdf->stream($filename);
        } catch (Throwable $e) {
            Log::error('Gagal membuat slip PDF langsung untuk karyawan: ' . $e->getMessage(), ['exception' => $e]);
            return redirect()->back()->with('error', 'Terjadi kesalahan saat membuat slip gaji. Silakan hubungi administrator.');
        }
    }

    public function cetakLaporanGaji(Gaji $gaji)
    {
        if ($gaji->karyawan_id !== Auth::user()->karyawan->id) {
            abort(403, 'Anda tidak diizinkan mengakses slip gaji ini.');
        }

        try {
            // ======================================================================================
            // [PERBAIKAN 3: BUG CETAK LAPORAN]
            // 1. Mengirim 'bulan' sebagai string 'Y-m-d' yang diharapkan SalaryService.
            // 2. Menghapus 'dd($data);' yang menghentikan eksekusi.
            // ======================================================================================
            $data = $this->salaryService->calculateDetailsForForm($gaji->karyawan, $gaji->bulan->format('Y-m-d'));

            // Mempersiapkan data untuk PDF
            $logoAlAzhar = $this->getImageAsBase64DataUri(public_path('logo/logoalazhar.png'));
            $logoYayasan = $this->getImageAsBase64DataUri(public_path('logo/logoyayasan.png'));

            $bendaharaUser = \App\Models\User::where('role', 'bendahara')->first();
            $bendaharaNama = $bendaharaUser ? $bendaharaUser->name : 'Bendahara Umum';

            $tandaTanganBendahara = '';
            $pengaturanTtd = \App\Models\TandaTangan::where('key', 'tanda_tangan_bendahara')->first();
            if ($pengaturanTtd && \Illuminate\Support\Facades\Storage::disk('public')->exists($pengaturanTtd->value)) {
                $tandaTanganBendahara = $this->getImageAsBase64DataUri(storage_path('app/public/' . $pengaturanTtd->value));
            }

            // Membuat PDF
            $pdf = \Barryvdh\DomPDF\Facade\Pdf::loadView('gaji.slip_pdf', [
                'data' => $data,
                'gaji' => $gaji,
                'logoAlAzhar' => $logoAlAzhar,
                'logoYayasan' => $logoYayasan,
                'bendaharaNama' => $bendaharaNama,
                'tandaTanganBendahara' => $tandaTanganBendahara
            ]);
            $pdf->setPaper('A4', 'portrait');

            $safeFilename = str_replace(' ', '_', strtolower($gaji->karyawan->nama));
            $filename = 'slip-gaji-' . $safeFilename . '-' . $gaji->bulan->format('Y-m') . '.pdf';

            return $pdf->stream($filename);
        } catch (\Throwable $e) {
            \Illuminate\Support\Facades\Log::error('Gagal membuat slip PDF dari Laporan Gaji: ' . $e->getMessage(), ['exception' => $e]);
            return redirect()->back()->with('error', 'Terjadi kesalahan saat membuat slip gaji. Silakan hubungi administrator.');
        }
    }
}
