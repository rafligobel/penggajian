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
        // [PERBAIKAN 1: LOGIKA ABSENSI]
        // Logika sebelumnya salah karena hanya memeriksa SesiAbsensi spesifik untuk hari ini
        // dan mengabaikan sesi default. Logika baru ini selalu menggunakan AbsensiService
        // yang sudah dirancang untuk menangani kedua kasus (spesifik dan default) dengan benar.
        // ======================================================================================
        $today = today();
        $statusInfo = $this->absensiService->getSessionStatus($today); // Langsung gunakan service

        $isSesiDibuka = false;
        $pesanSesi = $statusInfo['status']; // Ambil pesan default dari service

        // Cek apakah sesi aktif DAN waktunya sesuai
        if ($statusInfo['is_active']) {
            $now = now();
            $waktuMulai = Carbon::parse($statusInfo['waktu_mulai']);
            $waktuSelesai = Carbon::parse($statusInfo['waktu_selesai']);

            if ($now->between($waktuMulai, $waktuSelesai)) {
                $isSesiDibuka = true;
                $pesanSesi = 'Sesi absensi sedang dibuka (' . $waktuMulai->format('H:i') . ' - ' . $waktuSelesai->format('H:i') . ').';
            } else if ($now->isAfter($waktuSelesai)) {
                $pesanSesi = 'Sesi absensi hari ini sudah ditutup.';
            } else { // $now->isBefore($waktuMulai)
                $pesanSesi = 'Sesi absensi hari ini akan dibuka pada pukul ' . $waktuMulai->format('H:i') . '.';
            }
        }

        $sudahAbsen = Absensi::where('nip', $karyawan->nip)->whereDate('tanggal', $today)->exists();

        // ======================================================================================
        // [PERBAIKAN 2: KEHADIRAN BULAN INI]
        // Menambahkan filter `whereYear()` untuk memastikan hanya kehadiran di tahun ini yang dihitung,
        // mencegah data dari tahun sebelumnya ikut terhitung.
        // ======================================================================================
        $absensiBulanIni = Absensi::where('nip', $karyawan->nip)
            ->whereYear('tanggal', now()->year)
            ->whereMonth('tanggal', now()->month)
            ->count();

        // [INFO] Logika Gaji Terakhir Diterima sudah benar, tidak perlu diubah.
        $gajiTerbaru = $karyawan->gajis()->orderBy('bulan', 'desc')->first();

        // --- Logika untuk Modal Laporan Gaji ---
        $tahunLaporan = $request->input('tahun', date('Y'));
        $laporanTersedia = Gaji::where('karyawan_id', $karyawan->id)
            ->whereNotNull('bulan')
            ->selectRaw('YEAR(bulan) as year')
            ->distinct()->orderBy('year', 'desc')->pluck('year');

        $laporanGaji = Gaji::where('karyawan_id', $karyawan->id)
            ->whereYear('bulan', $tahunLaporan)
            ->orderBy('bulan', 'asc')
            ->with('tunjanganKehadiran', 'karyawan.jabatan')
            ->get();

        $rekapAbsensiPerBulan = Absensi::where('nip', $karyawan->nip)
            ->whereYear('tanggal', $tahunLaporan)
            ->selectRaw('DATE_FORMAT(tanggal, "%Y-%m") as bulan, COUNT(*) as jumlah_hadir')
            ->groupBy('bulan')
            ->pluck('jumlah_hadir', 'bulan');

        foreach ($laporanGaji as $gaji) {
            $tunjanganDariJabatan = $gaji->karyawan->jabatan->tunjangan_jabatan ?? 0;
            $bulanKey = $gaji->bulan->format('Y-m');
            $totalKehadiran = $rekapAbsensiPerBulan->get($bulanKey, 0);
            $tunjanganPerKehadiran = $gaji->tunjanganKehadiran->nominal_per_hari ?? 0;
            $totalTunjanganKehadiran = $totalKehadiran * $tunjanganPerKehadiran;

            $gaji->total_tunjangan = $tunjanganDariJabatan + $gaji->tunj_anak + $gaji->tunj_komunikasi + $gaji->tunj_pengabdian + $gaji->tunj_kinerja + $totalTunjanganKehadiran + $gaji->lembur;
            $gaji->total_potongan = $gaji->potongan;
        }

        $slipTersedia = Gaji::where('karyawan_id', $karyawan->id)
            ->orderBy('bulan', 'desc')->pluck('bulan');

        return view('tenaga_kerja.dashboard', compact(
            'karyawan',
            'gajiTerbaru',
            'absensiBulanIni',
            'isSesiDibuka',
            'sudahAbsen',
            'pesanSesi',
            'laporanGaji',
            'tahunLaporan',
            'laporanTersedia',
            'slipTersedia'
        ));
    }

    public function prosesAbsensi(Request $request)
    {
        // 1. Validasi input dari frontend, sekarang harus ada koordinat
        $request->validate([
            'latitude' => 'required|numeric',
            'longitude' => 'required|numeric',
        ]);

        // 2. Logika validasi radius
        $officeLatitude = (float) env('OFFICE_LATITUDE');
        $officeLongitude = (float) env('OFFICE_LONGITUDE');
        $maxRadius = (int) env('MAX_ATTENDANCE_RADIUS');

        $distance = $this->hitungJarak($request->latitude, $request->longitude, $officeLatitude, $officeLongitude);

        if ($distance > $maxRadius) {
            return response()->json([
                'status' => 'error',
                'message' => 'Anda berada di luar area sekolah Al-Azhar! Jarak Anda ' . round($distance) . ' meter.'
            ], 422); // Unprocessable Entity
        }

        // 3. Logika absensi yang sudah ada (sesi, cek duplikat)
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

        // 4. Simpan absensi ke database DENGAN koordinat
        Absensi::create([
            'sesi_absensi_id' => $sesiAbsensi->id,
            'nip' => $karyawan->nip,
            'nama' => $karyawan->nama,
            'tanggal' => $now->toDateString(),
            'jam' => $now->toTimeString(),
            'koordinat' => $request->latitude . ',' . $request->longitude, // Simpan koordinat
        ]);

        return response()->json([
            'status' => 'success',
            'message' => 'Absensi berhasil! Terima kasih telah melakukan absensi hari ini.'
        ]);
    }

    /**
     * Fungsi helper untuk menghitung jarak.
     * Cukup salin fungsi ini ke bagian bawah controller Anda.
     */
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

    public function hitungSimulasi(Request $request)
    {
        $validatedData = $request->validate([
            'jumlah_hari_masuk' => 'required|integer|min:0',
            'lembur'            => 'nullable|numeric|min:0',
            'potongan'          => 'nullable|numeric|min:0',
            'tunj_anak'         => 'nullable|numeric|min:0',
            'tunj_komunikasi'   => 'nullable|numeric|min:0',
            'tunj_pengabdian'   => 'nullable|numeric|min:0',
            'tunj_kinerja'      => 'nullable|numeric|min:0',
        ]);

        $karyawan = Auth::user()->karyawan;
        $hasilRincian = $this->salaryService->calculateSimulation($karyawan, $validatedData);

        $hasil = [
            'karyawan'          => $karyawan,
            'jumlah_hari_masuk' => $validatedData['jumlah_hari_masuk'],
            'rincian'           => $hasilRincian,
            'gaji_bersih'       => $hasilRincian['gaji_bersih'],
        ];

        return redirect()->route('tenaga_kerja.dashboard')
            ->with('hasil_simulasi', $hasil)
            ->with('show_modal', 'hasilSimulasiModal');
    }

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

            return $pdf->download($filename);
        } catch (Throwable $e) {
            Log::error('Gagal membuat slip PDF langsung untuk karyawan: ' . $e->getMessage(), ['exception' => $e]);
            return redirect()->back()->with('error', 'Terjadi kesalahan saat membuat slip gaji. Silakan hubungi administrator.');
        }
    }

    public function cetakLaporanGaji(Gaji $gaji)
    {
        // Autorisasi: Pastikan karyawan hanya bisa mengakses slip gajinya sendiri
        if ($gaji->karyawan_id !== Auth::user()->karyawan->id) {
            abort(403, 'Anda tidak diizinkan mengakses slip gaji ini.');
        }

        try {
            // [PERBAIKAN 1]
            // Mengirim 'bulan' sebagai string dengan format 'Y-m-d' yang diharapkan oleh SalaryService.
            // Sebelumnya, objek Carbon lengkap dikirim, yang bisa menyebabkan kesalahan parsing.
            $data = $this->salaryService->calculateDetailsForForm($gaji->karyawan, $gaji->bulan->format('Y-m-d'));

            // [PERBAIKAN 2]
            // Menghapus baris dd($data); yang menghentikan eksekusi skrip.
            // Ini adalah penyebab utama mengapa PDF tidak pernah dibuat.

            // Mempersiapkan data untuk PDF (logo, nama bendahara, tanda tangan)
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

            // Membuat nama file yang aman dan deskriptif
            $safeFilename = str_replace(' ', '_', strtolower($gaji->karyawan->nama));
            $filename = 'slip-gaji-' . $safeFilename . '-' . $gaji->bulan->format('Y-m') . '.pdf';

            // Mengirim file PDF ke browser untuk diunduh
            return $pdf->download($filename);
        } catch (\Throwable $e) {
            // Jika terjadi error, catat log dan kembalikan ke halaman sebelumnya dengan pesan error
            \Illuminate\Support\Facades\Log::error('Gagal membuat slip PDF dari Laporan Gaji: ' . $e->getMessage(), ['exception' => $e]);
            return redirect()->back()->with('error', 'Terjadi kesalahan saat membuat slip gaji. Silakan hubungi administrator.');
        }
    }
}
