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
use App\Services\SalaryService; // BARU: Impor SalaryService
use App\Jobs\GenerateIndividualSlip; // <-- Tambahkan ini

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
    protected SalaryService $salaryService; // BARU: Properti untuk SalaryService

    // PERUBAHAN: Inject AbsensiService dan SalaryService
    public function __construct(AbsensiService $absensiService, SalaryService $salaryService)
    {
        $this->absensiService = $absensiService;
        $this->salaryService = $salaryService;
    }

    // ... method dashboard() dan prosesAbsensi() tidak ada perubahan ...

    public function dashboard(Request $request)
    {
        $user = Auth::user();
        $karyawan = $user->karyawan;

        // --- Logika Absensi (Sudah baik, tidak ada perubahan) ---
        $today = today();
        $sesiAbsensiHariIni = SesiAbsensi::where('tanggal', $today->format('Y-m-d'))->first();

        $isSesiDibuka = false;
        $pesanSesi = 'Sesi absensi untuk hari ini belum dibuka oleh administrator.'; // Pesan default baru

        // 2. Jika ada record sesi, baru periksa waktunya
        if ($sesiAbsensiHariIni) {
            $statusInfo = $this->absensiService->getSessionStatus($today);

            if ($statusInfo['is_active']) {
                $now = now();
                $waktuMulai = Carbon::parse($statusInfo['waktu_mulai']);
                $waktuSelesai = Carbon::parse($statusInfo['waktu_selesai']);

                if ($now->between($waktuMulai, $waktuSelesai)) {
                    $isSesiDibuka = true; // Sesi HANYA dianggap buka jika record ada DAN waktu sesuai
                    $pesanSesi = 'Sesi absensi sedang dibuka (' . $waktuMulai->format('H:i') . ' - ' . $waktuSelesai->format('H:i') . ').';
                } else {
                    $pesanSesi = 'Sesi absensi hari ini sudah ditutup.';
                }
            } else {
                // Ini terjadi jika ada record sesi, tapi is_default=false dan tanggalnya bukan hari ini
                // atau jika service menyatakan tidak aktif karena alasan lain (misal: hari libur manual)
                $pesanSesi = $statusInfo['status'];
            }
        }
        $sudahAbsen = Absensi::where('nip', $karyawan->nip)->whereDate('tanggal', $today)->exists();
        $absensiBulanIni = Absensi::where('nip', $karyawan->nip)->whereMonth('tanggal', now()->month)->count();
        $gajiTerbaru = $karyawan->gajis()->orderBy('bulan', 'desc')->first();

        // --- Logika untuk Modal Laporan Gaji (Dengan Optimasi) ---
        $tahunLaporan = $request->input('tahun', date('Y'));
        $laporanTersedia = Gaji::where('karyawan_id', $karyawan->id)
            ->whereNotNull('bulan') // <-- TAMBAHKAN BARIS INI
            ->selectRaw('YEAR(bulan) as year')
            ->distinct()->orderBy('year', 'desc')->pluck('year');

        $laporanGaji = Gaji::where('karyawan_id', $karyawan->id)
            ->whereYear('bulan', $tahunLaporan)
            ->orderBy('bulan', 'asc')
            ->with('tunjanganKehadiran', 'karyawan.jabatan') // Eager load relasi
            ->get();
        // dd($laporanTersedia, $tahunLaporan, $laporanGaji);

        // [OPTIMASI] Ambil semua data absensi untuk tahun yang dipilih dalam satu query.
        $rekapAbsensiPerBulan = Absensi::where('nip', $karyawan->nip)
            ->whereYear('tanggal', $tahunLaporan)
            ->selectRaw('DATE_FORMAT(tanggal, "%Y-%m") as bulan, COUNT(*) as jumlah_hadir')
            ->groupBy('bulan')
            ->pluck('jumlah_hadir', 'bulan');

        foreach ($laporanGaji as $gaji) {
            $tunjanganDariJabatan = $gaji->karyawan->jabatan->tunjangan_jabatan ?? 0;

            // [OPTIMASI] Gunakan data absensi yang sudah diambil, bukan query baru.
            $totalKehadiran = $rekapAbsensiPerBulan->get($gaji->bulan, 0); // Default 0 jika tidak ada data

            $tunjanganPerKehadiran = $gaji->tunjanganKehadiran->nominal_per_hari ?? 0;
            $totalTunjanganKehadiran = $totalKehadiran * $tunjanganPerKehadiran;

            $gaji->total_tunjangan = $tunjanganDariJabatan + $gaji->tunj_anak + $gaji->tunj_komunikasi + $gaji->tunj_pengabdian + $gaji->tunj_kinerja + $totalTunjanganKehadiran + $gaji->lembur;
            $gaji->total_potongan = $gaji->potongan;
        }

        // --- Logika untuk Modal Slip Gaji (Sudah baik, tidak ada perubahan) ---
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
        $karyawan = Auth::user()->karyawan;
        $now = now();
        $today = $now->copy()->startOfDay();

        // --- [PERBAIKAN TOTAL LOGIKA PROSES ABSENSI] ---

        // 1. Cek status sesi melalui service
        $statusInfo = $this->absensiService->getSessionStatus($today);
        if (!$statusInfo['is_active'] || !$now->between(Carbon::parse($statusInfo['waktu_mulai']), Carbon::parse($statusInfo['waktu_selesai']))) {
            return redirect()->route('tenaga_kerja.dashboard')->with('info', 'Sesi absensi sedang tidak dibuka saat ini.');
        }

        // 2. Cek apakah sudah absen menggunakan 'nip'
        $sudahAbsen = Absensi::where('nip', $karyawan->nip)
            ->whereDate('tanggal', $today)
            ->exists();

        if ($sudahAbsen) {
            return redirect()->route('tenaga_kerja.dashboard')
                ->with('info', 'Anda sudah melakukan absensi hari ini.');
        }

        // 3. Dapatkan Sesi Absensi ID yang aktif (wajib ada)
        $sesiAbsensi = SesiAbsensi::where('tanggal', $today->format('Y-m-d'))->first() ?? SesiAbsensi::where('is_default', true)->first();
        if (!$sesiAbsensi) {
            return redirect()->route('tenaga_kerja.dashboard')->with('info', 'Sistem tidak dapat menemukan sesi absensi yang valid.');
        }

        // 4. Simpan data absensi baru sesuai struktur tabel
        Absensi::create([
            'sesi_absensi_id' => $sesiAbsensi->id, // Kolom wajib
            'nip' => $karyawan->nip,
            'nama' => $karyawan->nama,
            'tanggal' => $now->toDateString(),
            'jam' => $now->toTimeString(),
            // Kolom 'status' tidak ada di migrasi, jadi dihapus
        ]);

        // --- [AKHIR PERBAIKAN] ---

        return redirect()->route('tenaga_kerja.dashboard')
            ->with('success', 'Absensi berhasil! Terima kasih telah melakukan absensi hari ini.');
    }

    /**
     * PERBAIKAN TOTAL: Method hitungSimulasi kini mendelegasikan
     * seluruh proses kalkulasi ke SalaryService untuk konsistensi.
     */
    public function hitungSimulasi(Request $request)
    {
        // PERBAIKAN: Validasi semua input yang dikirim dari form simulasi
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

        // Panggil service untuk melakukan kalkulasi dengan SEMUA data tervalidasi
        $hasilRincian = $this->salaryService->calculateSimulation($karyawan, $validatedData);

        // Siapkan data untuk dikirim ke view hasil.blade.php
        $hasil = [
            'karyawan'          => $karyawan,
            'jumlah_hari_masuk' => $validatedData['jumlah_hari_masuk'],
            'rincian'           => $hasilRincian,
            'gaji_bersih'       => $hasilRincian['gaji_bersih'],
        ];

        return redirect()->route('tenaga_kerja.dashboard')
            ->with('hasil_simulasi', $hasil)
            ->with('show_modal', 'hasilSimulasiModal'); // Pastikan show_modal dikirim
    }



    public function downloadSlipGaji(Request $request)
    {
        // 1. Validasi input dari form tetap Y-m
        $validated = $request->validate([
            'bulan' => 'required|date_format:Y-m',
        ]);

        try {
            $user = Auth::user();
            $karyawan = $user->karyawan;
            $tanggal = Carbon::createFromFormat('Y-m', $validated['bulan']);

            // 2. [PERBAIKAN] Cari data Gaji menggunakan whereYear dan whereMonth
            $gaji = Gaji::where('karyawan_id', $karyawan->id)
                ->whereYear('bulan', $tanggal->year)
                ->whereMonth('bulan', $tanggal->month)
                ->firstOrFail();

            // 3. LOGIKA PEMBUATAN PDF
            // Karena $gaji->bulan sekarang adalah objek Carbon, kita format ke Y-m-d
            $data = $this->salaryService->calculateDetailsForForm($gaji->karyawan, $gaji->bulan->format('Y-m-d'));

            // Siapkan gambar (logo & tanda tangan)
            $logoAlAzhar = $this->getImageAsBase64DataUri(public_path('logo/logoalazhar.png'));
            $logoYayasan = $this->getImageAsBase64DataUri(public_path('logo/logoyayasan.png'));

            // Ambil data bendahara
            $bendaharaUser = \App\Models\User::where('role', 'bendahara')->first();
            $bendaharaNama = $bendaharaUser ? $bendaharaUser->name : 'Bendahara Umum';

            // Ambil tanda tangan bendahara
            $tandaTanganBendahara = '';
            $pengaturanTtd = TandaTangan::where('key', 'tanda_tangan_bendahara')->first();
            if ($pengaturanTtd && Storage::disk('public')->exists($pengaturanTtd->value)) {
                $tandaTanganBendahara = $this->getImageAsBase64DataUri(storage_path('app/public/' . $pengaturanTtd->value));
            }

            // Generate PDF menggunakan view yang sama dengan bendahara
            $pdf = Pdf::loadView('gaji.slip_pdf', [
                'data' => $data,
                'gaji' => $gaji,
                'logoAlAzhar' => $logoAlAzhar,
                'logoYayasan' => $logoYayasan,
                'bendaharaNama' => $bendaharaNama,
                'tandaTanganBendahara' => $tandaTanganBendahara
            ]);
            $pdf->setPaper('A4', 'portrait');

            // 4. GENERATE NAMA FILE & RETURN SEBAGAI UNDUHAN LANGSUNG
            //======================================================================
            $safeFilename = str_replace(' ', '_', strtolower($gaji->karyawan->nama));
            $filename = 'slip-gaji-' . $safeFilename . '-' . $gaji->bulan->format('Y-m') . '.pdf';

            // Hentikan eksekusi Job, dan langsung kembalikan sebagai file download
            return $pdf->download($filename);
        } catch (Throwable $e) {
            // Jika terjadi error (misal: gaji tidak ditemukan, file logo hilang, dll)
            Log::error('Gagal membuat slip PDF langsung untuk karyawan: ' . $e->getMessage(), ['exception' => $e]);
            return redirect()->back()->with('error', 'Terjadi kesalahan saat membuat slip gaji. Silakan hubungi administrator.');
        }
    }


    public function cetakLaporanGaji(Gaji $gaji)
    {
        // 1. PENTING: Lakukan otorisasi untuk memastikan karyawan hanya bisa mengunduh slip gajinya sendiri.
        if ($gaji->karyawan_id !== Auth::user()->karyawan->id) {
            abort(403, 'Anda tidak diizinkan mengakses slip gaji ini.');
        }

        try {
            // 2. LOGIKA PEMBUATAN PDF (Sama persis seperti downloadSlipGaji)
            // Kalkulasi rincian gaji menggunakan service
            $data = $this->salaryService->calculateDetailsForForm($gaji->karyawan, $gaji->bulan);

            // Siapkan gambar (logo & tanda tangan)
            $logoAlAzhar = $this->getImageAsBase64DataUri(public_path('logo/logoalazhar.png'));
            $logoYayasan = $this->getImageAsBase64DataUri(public_path('logo/logoyayasan.png'));
            $bendaharaUser = \App\Models\User::where('role', 'bendahara')->first();
            $bendaharaNama = $bendaharaUser ? $bendaharaUser->name : 'Bendahara Umum';
            $tandaTanganBendahara = '';
            $pengaturanTtd = TandaTangan::where('key', 'tanda_tangan_bendahara')->first();
            if ($pengaturanTtd && Storage::disk('public')->exists($pengaturanTtd->value)) {
                $tandaTanganBendahara = $this->getImageAsBase64DataUri(storage_path('app/public/' . $pengaturanTtd->value));
            }

            // Generate PDF
            $pdf = Pdf::loadView('gaji.slip_pdf', [
                'data' => $data,
                'gaji' => $gaji,
                'logoAlAzhar' => $logoAlAzhar,
                'logoYayasan' => $logoYayasan,
                'bendaharaNama' => $bendaharaNama,
                'tandaTanganBendahara' => $tandaTanganBendahara
            ]);
            $pdf->setPaper('A4', 'portrait');

            // 3. GENERATE NAMA FILE & RETURN SEBAGAI UNDUHAN LANGSUNG
            $safeFilename = str_replace(' ', '_', strtolower($gaji->karyawan->nama));
            $filename = 'slip-gaji-' . $safeFilename . '-' . $gaji->bulan . '.pdf';
            return $pdf->download($filename);
        } catch (Throwable $e) {
            Log::error('Gagal membuat slip PDF dari Laporan Gaji: ' . $e->getMessage(), ['exception' => $e]);
            return redirect()->back()->with('error', 'Terjadi kesalahan saat membuat slip gaji. Silakan hubungi administrator.');
        }
    }
}
