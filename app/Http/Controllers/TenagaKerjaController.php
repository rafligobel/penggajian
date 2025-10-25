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
        $now = now();
        $today = $now->copy()->startOfDay();
        $todayDate = $now->toDateString();
        $currentMonth = $now->month;
        $currentYear = $now->year;

        // [PERBAIKAN: LOGIKA ABSENSI DASHBOARD MENGGUNAKAN SERVICE]
        $statusInfo = $this->absensiService->getSessionStatus($today);
        $isSesiDibuka = false;
        $pesanSesi = $statusInfo['keterangan'] ?? $statusInfo['status'];
        if ($statusInfo['is_active']) {
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

        $sudahAbsen = Absensi::where('karyawan_id', $karyawan->id)
            ->where('tanggal', $todayDate)
            ->exists();
        $absensiBulanIni = Absensi::where('karyawan_id', $karyawan->id)
            ->whereYear('tanggal', $currentYear)
            ->whereMonth('tanggal', $currentMonth)
            ->count();
        $gajiTerakhir = $karyawan->gajis()->orderBy('bulan', 'desc')->first();
        $gajiBulanIni = $karyawan->gajis()
            ->whereYear('bulan', $currentYear)
            ->whereMonth('bulan', $currentMonth)
            ->first();
        if ($gajiBulanIni) {
            $details = $this->salaryService->calculateDetailsForForm($gajiBulanIni->karyawan, $gajiBulanIni->bulan->format('Y-m-d'));
            $gajiBulanIni->gaji_bersih = $details['gaji_bersih_numeric'];
        }
        $tunjanganKehadiranDefault = TunjanganKehadiran::first();
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
        $rekapAbsensiPerBulan = Absensi::where('karyawan_id', $karyawan->id)
            ->whereYear('tanggal', $tahun)
            ->selectRaw('DATE_FORMAT(tanggal, "%Y-%m") as bulan, COUNT(*) as jumlah_hadir')
            ->groupBy('bulan')
            ->pluck('jumlah_hadir', 'bulan');
        foreach ($gajis as $gaji) {
            $tunjanganDariJabatan = $gaji->karyawan->jabatan->tunj_jabatan ?? 0;
            $bulanKey = $gaji->bulan->format('Y-m');
            $totalKehadiran = $rekapAbsensiPerBulan->get($bulanKey, 0);
            $tunjanganPerKehadiran = $gaji->tunjanganKehadiran->jumlah_tunjangan ?? 0;
            $totalTunjanganKehadiran = $totalKehadiran * $tunjanganPerKehadiran;
            $gaji->total_tunjangan = $tunjanganDariJabatan + $gaji->tunj_anak + $gaji->tunj_komunikasi + $gaji->tunj_pengabdian + $gaji->tunj_kinerja + $totalTunjanganKehadiran + $gaji->lembur;
            $gaji->total_potongan = $gaji->potongan;
            $gaji->gaji_bersih = ($gaji->gaji_pokok ?? 0) + $gaji->total_tunjangan - $gaji->total_potongan;
        }
        $laporanData = ['gajis' => $gajis];
        $slipTersedia = Gaji::where('karyawan_id', $karyawan->id)
            ->orderBy('bulan', 'desc')->pluck('bulan');

        return view('tenaga_kerja.dashboard', compact(
            'karyawan',
            'gajiTerakhir',
            'gajiBulanIni',
            'absensiBulanIni',
            'isSesiDibuka',
            'sudahAbsen',
            'pesanSesi',
            'laporanData',
            'tahun',
            'availableYears',
            'slipTersedia',
            'tunjanganKehadiranDefault'
        ));
    }

    // FUNGSI INI DIBUAT SEMPURNA UNTUK MENGGANTIKAN FUNGSI LAMA
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
            return redirect()->back()->with('error', 'Gagal memproses slip gaji: ' . $e->getMessage() . '. Pastikan data gaji tersedia dan file logo ada.');
        }
    }

    // FUNGSI INI DIBUAT SEMPURNA UNTUK MENGGANTIKAN FUNGSI LAMA
    public function cetakLaporanGaji(Gaji $gaji)
    {
        if ($gaji->karyawan_id !== Auth::user()->karyawan->id) {
            abort(403, 'Anda tidak diizinkan mengakses slip gaji ini.');
        }
        try {
            $data = $this->salaryService->calculateDetailsForForm($gaji->karyawan, $gaji->bulan->format('Y-m-d'));
            $logoAlAzhar = $this->getImageAsBase64DataUri(public_path('logo/logoalazhar.png'));
            $logoYayasan = $this->getImageAsBase64DataUri(public_path('logo/logoyayasan.png'));
            $bendaharaUser = \App\Models\User::where('role', 'bendahara')->first();
            $bendaharaNama = $bendaharaUser ? $bendaharaUser->name : 'Bendahara Umum';
            $tandaTanganBendahara = '';
            $pengaturanTtd = \App\Models\TandaTangan::where('key', 'tanda_tangan_bendahara')->first();
            if ($pengaturanTtd && \Illuminate\Support\Facades\Storage::disk('public')->exists($pengaturanTtd->value)) {
                $tandaTanganBendahara = $this->getImageAsBase64DataUri(storage_path('app/public/' . $pengaturanTtd->value));
            }
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
    public function hitungSimulasi(Request $request)
    {
        $validated = $request->validate([
            'jumlah_hari_masuk' => 'required|integer|min:0|max:31',
            'tunj_anak'         => 'required|numeric|min:0',
            'tunj_komunikasi'   => 'required|numeric|min:0',
            'tunj_pengabdian'   => 'required|numeric|min:0',
            'tunj_kinerja'      => 'required|numeric|min:0',
            'lembur'            => 'required|numeric|min:0',
            'potongan'          => 'required|numeric|min:0',
            'tunjangan_kehadiran_id' => 'required|exists:tunjangan_kehadirans,id',
        ]);
        $karyawan = Auth::user()->karyawan->loadMissing('jabatan');
        $gajiTerakhir = $karyawan->gajis()->orderBy('bulan', 'desc')->first();
        $data = $validated;
        $data['jumlah_kehadiran'] = $validated['jumlah_hari_masuk'];
        $data['gaji_pokok'] = $gajiTerakhir->gaji_pokok ?? $karyawan->gaji_pokok_default ?? 0;
        $data['tunj_jabatan'] = $karyawan->jabatan->tunj_jabatan ?? 0;
        $hasil = $this->salaryService->calculateSimulasi($karyawan, $data);
        return view('tenaga_kerja.modals.hasil', compact('hasil'));
    }


    public function prosesAbsensi(Request $request)
    {
        $validated = $request->validate([
            'latitude' => 'required|numeric|between:-90,90',
            'longitude' => 'required|numeric|between:-180,180',
        ]);

        $userLat = (float) $validated['latitude'];
        $userLon = (float) $validated['longitude'];

        if ($userLat == 0 && $userLon == 0) {
            return response()->json([
                'status' => 'error',
                'message' => 'Gagal mendapatkan lokasi GPS. Pastikan izin lokasi (GPS) di perangkat Anda sudah aktif dan coba lagi.'
            ], 422); // Unprocessable Entity
        }

        $officeLatitude = (float) env('OFFICE_LATITUDE', 0.0);
        $officeLongitude = (float) env('OFFICE_LONGITUDE', 0.0);
        $maxRadius = (int) env('MAX_ATTENDANCE_RADIUS', 50);

        if ($officeLatitude == 0.0 || $officeLongitude == 0.0) {
            Log::error('Kesalahan Konfigurasi: OFFICE_LATITUDE atau OFFICE_LONGITUDE tidak diatur dengan benar atau bernilai nol. Periksa file .env Anda.');
            return response()->json([
                'status' => 'error',
                'message' => 'Kesalahan Konfigurasi Server: Lokasi kantor tidak dapat dibaca. Harap hubungi administrator.'
            ], 500); // Server Error
        }

        // [PERBAIKAN] Gunakan service untuk menghitung jarak
        $distance = $this->absensiService->calculateDistance($userLat, $userLon, $officeLatitude, $officeLongitude);

        if ($distance > $maxRadius) {
            $errorMessage = 'Anda berada di luar area sekolah! Jarak Anda ' . round($distance) . ' meter. Maksimal ' . $maxRadius . ' meter diizinkan.';
            return response()->json([
                'status' => 'error',
                'message' => $errorMessage
            ], 422);
        }

        $karyawan = Auth::user()->karyawan;
        $now = now();
        $today = $now->copy()->startOfDay();
        $todayDate = $now->toDateString();

        // [PERBAIKAN] Gunakan service untuk status sesi
        $statusInfo = $this->absensiService->getSessionStatus($today);
        if (!$statusInfo['is_active'] || !$now->between(Carbon::parse($statusInfo['waktu_mulai']), Carbon::parse($statusInfo['waktu_selesai']))) {
            return response()->json(['status' => 'error', 'message' => 'Sesi absensi sedang tidak dibuka saat ini. Keterangan: ' . ($statusInfo['keterangan'] ?? $statusInfo['status'])], 403);
        }

        $sudahAbsen = Absensi::where('karyawan_id', $karyawan->id)
            ->where('tanggal', $todayDate)
            ->exists();

        if ($sudahAbsen) {
            return response()->json(['status' => 'error', 'message' => 'Anda sudah melakukan absensi hari ini.'], 409);
        }

        // [PERBAIKAN] Gunakan sesi_id dari service
        if (empty($statusInfo['sesi_id'])) {
            Log::error('FATAL: Tidak ada sesi absensi (default atau khusus) yang ditemukan di database.', ['statusInfo' => $statusInfo]);
            return response()->json(['status' => 'error', 'message' => 'Sistem tidak dapat menemukan sesi absensi yang valid.'], 500);
        }

        Absensi::create([
            'sesi_absensi_id' => $statusInfo['sesi_id'],
            'karyawan_id' => $karyawan->id,
            'tanggal' => $todayDate,
            'jam' => $now->toTimeString(),
            'koordinat' => $userLat . ',' . $userLon, // Simpan sebagai string
            'jarak' => round($distance)
        ]);

        return response()->json([
            'status' => 'success',
            'message' => 'Absensi berhasil! Jarak Anda: ' . round($distance) . ' meter. Terima kasih.'
        ]);
    }
}
