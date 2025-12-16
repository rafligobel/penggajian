<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Karyawan;
use App\Models\Gaji;
use Carbon\Carbon;
use Illuminate\Support\Facades\Auth;
use App\Models\Absensi;
use App\Models\TunjanganKehadiran;
use App\Services\AbsensiService;
use App\Services\SalaryService;
use App\Models\TandaTangan;
use App\Traits\ManagesImageEncoding;
use Illuminate\Support\Facades\Storage;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB; // [PENTING] Tambahkan DB Facade
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
            ->groupBy('year') // Fix: Gunakan groupby daripada distinct
            ->orderBy('year', 'desc')->pluck('year');

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
            $tunjanganDariJabatan = $gaji->tunj_jabatan > 0 ? $gaji->tunj_jabatan : ($gaji->karyawan->jabatan->tunj_jabatan ?? 0);
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

    public function editDataSaya()
    {
        $karyawan = Auth::user()->karyawan;
        if (!$karyawan) {
            return redirect()->route('tenaga_kerja.dashboard')->with('error', 'Data kepegawaian Anda tidak ditemukan.');
        }
        return view('tenaga_kerja.edit_data_saya', compact('karyawan'));
    }

    public function updateDataSaya(Request $request)
    {
        $karyawan = Auth::user()->karyawan;
        if (!$karyawan) {
            return redirect()->back()->with('error', 'Data kepegawaian tidak ditemukan.');
        }

        $validated = $request->validate([
            'alamat' => 'nullable|string|max:500',
            'telepon' => 'nullable|string|max:15',
            'jumlah_anak' => 'nullable|integer|min:0',
            'foto' => 'nullable|image|mimes:jpeg,png,jpg,webp|max:2048',
        ]);

        $dataToUpdate = [
            'alamat' => $validated['alamat'],
            'telepon' => $validated['telepon'],
            'jumlah_anak' => $validated['jumlah_anak'] ?? $karyawan->jumlah_anak,
        ];

        if ($request->hasFile('foto')) {
            if ($karyawan->foto) {
                Storage::disk('public_uploads')->delete('foto_pegawai/' . $karyawan->foto);
            }
            $filename = time() . '_' . $request->file('foto')->getClientOriginalName();
            $request->file('foto')->storeAs('foto_pegawai', $filename, 'public_uploads');
            $dataToUpdate['foto'] = $filename;
        }

        $karyawan->update($dataToUpdate);

        return redirect()->route('tenaga_kerja.dashboard')->with('success', 'Data kepegawaian Anda berhasil diperbarui.');
    }

    public function downloadSlipGaji(Request $request)
    {
        $validated = $request->validate(['bulan' => 'required|date_format:Y-m']);
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
            Log::error('Gagal membuat slip PDF: ' . $e->getMessage());
            return redirect()->back()->with('error', 'Gagal memproses slip gaji. Pastikan data tersedia.');
        }
    }

    public function cetakLaporanGaji(Gaji $gaji)
    {
        if ($gaji->karyawan_id !== Auth::user()->karyawan->id) {
            abort(403, 'Akses ditolak.');
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
            \Illuminate\Support\Facades\Log::error('Error cetak laporan gaji: ' . $e->getMessage());
            return redirect()->back()->with('error', 'Terjadi kesalahan sistem.');
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
        return view('simulasi.hasil', compact('hasil'));
    }

    // [PERBAIKAN UTAMA: KEAMANAN DAN KONSISTENSI DATA]
    public function prosesAbsensi(Request $request)
    {
        $validated = $request->validate([
            'latitude' => 'required|numeric',
            'longitude' => 'required|numeric',
        ]);

        Log::info('Proses Absensi Request Masuk:', [
            'user_id' => Auth::id(),
            'lat' => $request->latitude,
            'lon' => $request->longitude,
            'time' => now()->toDateTimeString()
        ]);

        $userLat = (float) $request->latitude;
        $userLon = (float) $request->longitude;

        // [PERBAIKAN 1] Gunakan config(), bukan env()
        // Pastikan Anda membuat file config/absensi.php atau menambahkannya di config/services.php
        // Jika belum ada, gunakan fallback env() SEBAGAI SEMENTARA SAJA
        $officeLatitude = (float) config('absensi.office_latitude', env('OFFICE_LATITUDE', 0.0));
        $officeLongitude = (float) config('absensi.office_longitude', env('OFFICE_LONGITUDE', 0.0));
        $maxRadius = (int) config('absensi.max_radius', env('MAX_ATTENDANCE_RADIUS', 50));

        if ($officeLatitude == 0.0 || $officeLongitude == 0.0) {
            Log::error('Konfigurasi Lokasi Absensi Belum Diatur.');
            return response()->json(['status' => 'error', 'message' => 'Kesalahan Konfigurasi Server.'], 500);
        }

        // Validasi Jarak
        $distance = $this->absensiService->calculateDistance($userLat, $userLon, $officeLatitude, $officeLongitude);
        if ($distance > $maxRadius) {
            return response()->json([
                'status' => 'error',
                'message' => 'Jarak terlalu jauh: ' . round($distance) . 'm (Max: ' . $maxRadius . 'm).'
            ], 422);
        }

        // [PERBAIKAN 2] Gunakan Transaction & Locking untuk mencegah Race Condition
        try {
            return DB::transaction(function () use ($request, $userLat, $userLon, $distance) {
                $karyawan = Auth::user()->karyawan;
                $todayDate = now()->toDateString();

                // Cek Sesi (Menggunakan Service yang sudah dioptimasi)
                $statusInfo = $this->absensiService->getSessionStatus(now());

                if (!$statusInfo['is_active']) {
                    return response()->json(['status' => 'error', 'message' => 'Sesi absensi belum dibuka.'], 403);
                }

                // Validasi Jam (Double Check)
                $now = now();
                $start = Carbon::parse($statusInfo['waktu_mulai']);
                $end = Carbon::parse($statusInfo['waktu_selesai']);
                if (!$now->between($start, $end)) {
                    return response()->json(['status' => 'error', 'message' => 'Diluar jam absensi.'], 403);
                }

                // [CRITICAL] Lock baris untuk mencegah double submit dalam milidetik yang sama
                $sudahAbsen = Absensi::where('karyawan_id', $karyawan->id)
                    ->where('tanggal', $todayDate)
                    ->lockForUpdate() // KUNCI DATA
                    ->exists();

                if ($sudahAbsen) {
                    return response()->json(['status' => 'error', 'message' => 'Anda sudah absen hari ini.'], 409);
                }

                if (empty($statusInfo['sesi_id'])) {
                    return response()->json(['status' => 'error', 'message' => 'Sesi tidak valid.'], 500);
                }

                Absensi::create([
                    'sesi_absensi_id' => $statusInfo['sesi_id'],
                    'karyawan_id' => $karyawan->id,
                    'tanggal' => $todayDate,
                    'jam' => now()->toTimeString(),
                    'koordinat' => $userLat . ',' . $userLon,
                    'jarak' => round($distance)
                ]);

                return response()->json([
                    'status' => 'success',
                    'message' => 'Absensi berhasil! Jarak: ' . round($distance) . 'm.'
                ]);
            });
        } catch (\Throwable $e) {
            Log::error('Gagal Proses Absensi: ' . $e->getMessage());
            return response()->json([
                'status' => 'error', // Pesan ini akan ditangkap oleh modal
                'message' => 'Gagal menyimpan absensi: ' . $e->getMessage()
            ], 500);
        }
    }
}
