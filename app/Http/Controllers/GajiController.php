<?php
// File: app/Http/Controllers/GajiController.php

namespace App\Http\Controllers;

use App\Models\Gaji;
use App\Models\Karyawan;
use App\Models\TunjanganKehadiran;
use Illuminate\Http\Request;
use App\Services\SalaryService;
use App\Services\AbsensiService;
use Carbon\Carbon;
use Illuminate\Support\Facades\Auth;
use App\Jobs\GenerateIndividualSlip;
use App\Jobs\SendSlipToEmail;
use App\Models\AturanTunjanganAnak;
use App\Models\AturanTunjanganPengabdian;
use Illuminate\Support\Facades\Cache;
use App\Models\AturanKinerja;
use App\Models\IndikatorKinerja;
use App\Models\PenilaianKinerja;
use App\Models\TunjanganKomunikasi;
use App\Models\potongan;
use Illuminate\Support\Facades\DB;
use App\Models\Absensi; // Pastikan model Absensi di-import

class GajiController extends Controller
{
    protected SalaryService $salaryService;
    protected AbsensiService $absensiService;

    public function __construct(SalaryService $salaryService, AbsensiService $absensiService)
    {
        $this->salaryService = $salaryService;
        $this->absensiService = $absensiService;
    }

    /**
     * Fungsi helper untuk menghitung tunjangan dinamis (Anak & Pengabdian).
     * Digunakan saat menyimpan data baru.
     */
    private function hitungTunjanganDinamis(Karyawan $karyawan, float $gajiPokok): array
    {
        // Ambil aturan nilai per anak (cache 60 menit)
        $aturanAnak = Cache::remember('aturan_tunjangan_anak_single', 3600, function () {
            return AturanTunjanganAnak::first();
        });

        $nilaiPerAnak = $aturanAnak ? $aturanAnak->nilai_per_anak : 0;
        $jumlahAnak = $karyawan->jumlah_anak ?? 0;
        $tunjanganAnak = $nilaiPerAnak * $jumlahAnak;

        // Tunjangan Pengabdian
        $tunjanganPengabdian = 0;
        if ($karyawan->tanggal_masuk && $gajiPokok > 0) {
            $lamaKerjaTahun = $karyawan->tanggal_masuk->diffInYears(Carbon::now());

            $aturanPengabdian = Cache::remember('aturan_tunjangan_pengabdian_all', 3600, function () {
                return AturanTunjanganPengabdian::all();
            });

            $aturanYangBerlaku = $aturanPengabdian
                ->where('minimal_tahun_kerja', '<=', $lamaKerjaTahun)
                ->where('maksimal_tahun_kerja', '>=', $lamaKerjaTahun)
                ->first();

            if ($aturanYangBerlaku) {
                $persentase = $aturanYangBerlaku->nilai_tunjangan;
                $tunjanganPengabdian = ($persentase / 100) * $gajiPokok;
            }
        }

        return [
            'tunj_anak' => $tunjanganAnak,
            'tunj_pengabdian' => $tunjanganPengabdian,
            'tunj_komunikasi' => 0, // Default 0
        ];
    }

    /**
     * [OPTIMASI PERFORMA] Method index dengan Bulk Fetching.
     */
    public function index(Request $request)
    {
        $selectedMonth = $request->input('bulan', Carbon::now()->format('Y-m'));
        try {
            $bulanCarbon = Carbon::createFromFormat('Y-m', $selectedMonth);
        } catch (\Exception $e) {
            $bulanCarbon = Carbon::now();
            $selectedMonth = $bulanCarbon->format('Y-m');
        }

        // 1. Eager Load Jabatan untuk mencegah N+1 pada Karyawan
        $karyawans = Karyawan::with('jabatan')->orderBy('nama')->get();

        // 2. [OPTIMASI] Ambil SEMUA data Gaji bulan ini SEKALIGUS
        // KeyBy('karyawan_id') membuat kita bisa akses data gaji user tertentu tanpa looping query
        $gajiBulanIni = Gaji::with(['tunjanganKehadiran', 'penilaianKinerjas'])
            ->whereYear('bulan', $bulanCarbon->year)
            ->whereMonth('bulan', $bulanCarbon->month)
            ->get()
            ->keyBy('karyawan_id');

        // 3. [OPTIMASI] Hitung SEMUA Absensi bulan ini SEKALIGUS
        // Menggunakan groupBy untuk menghitung total kehadiran per karyawan dalam 1 query
        $absensiBulanIni = Absensi::selectRaw('karyawan_id, count(*) as total')
            ->whereYear('tanggal', $bulanCarbon->year)
            ->whereMonth('tanggal', $bulanCarbon->month)
            ->groupBy('karyawan_id')
            ->pluck('total', 'karyawan_id'); // Hasil: [id_karyawan => jumlah_hadir, ...]

        // Data Pendukung (Cache untuk performa)
        $tunjanganKehadirans = Cache::remember('tunjangan_kehadiran_all', 3600, fn() => TunjanganKehadiran::all());
        $tunjanganKomunikasis = Cache::remember('tunjangan_komunikasi_all', 3600, fn() => TunjanganKomunikasi::all());
        $indikatorKinerjas = Cache::remember('indikator_kinerja_all', 3600, fn() => IndikatorKinerja::all());
        $aturanKinerja = Cache::remember('aturan_kinerja_single', 3600, fn() => AturanKinerja::first());

        // Ambil data potongan global
        $potongan = potongan::first() ?? new potongan(['tarif_lembur_per_jam' => 0, 'tarif_potongan_absen' => 0]);

        // Kita tidak butuh rekap detail absensi di sini (berat), cukup total hadir dari query optimasi di atas
        // $rekapAbsensiRaw = $this->absensiService->getAttendanceRecap($bulanCarbon); <-- DIHAPUS (Terlalu Berat)

        $dataGaji = [];
        foreach ($karyawans as $karyawan) {
            // Siapkan data matang untuk disuapkan ke Service
            $preloadedData = [
                'gaji_record' => $gajiBulanIni->get($karyawan->id),
                'jumlah_kehadiran' => $absensiBulanIni->get($karyawan->id, 0), // Default 0 jika tidak ada
            ];

            // Panggil service dengan data yang sudah disiapkan (Cepat!)
            $detailGaji = $this->salaryService->calculateDetailsForForm($karyawan, $selectedMonth, $preloadedData);

            // Hitung Alpha (Hari kerja sebulan - Hadir)
            // Catatan: Untuk akurasi sempurna hari kerja, sebaiknya gunakan AbsensiService::getWorkingDaysCount
            // Tapi demi performa halaman index, kita bisa hitung manual atau estimasi jika perlu.
            // Untuk sekarang, kita gunakan logika sederhana atau ambil dari $detailGaji jika sudah ada logika di sana.

            // Hitung manual alpha untuk tampilan cepat (tanpa load service berat)
            // Jika ingin akurat 100% dengan kalender libur, gunakan AJAX nanti.
            $jumlahAlpha = 0; // Placeholder, biar UI tidak error. Logika alpha sebaiknya di handle terpisah/AJAX.

            $detailGaji['data_pendukung'] = [
                'jumlah_alpha' => $jumlahAlpha,
                'tarif_potongan_absen' => $potongan->tarif_potongan_absen,
                'tarif_lembur_per_jam' => $potongan->tarif_lembur_per_jam,
                'potongan_alpha_otomatis' => $jumlahAlpha * $potongan->tarif_potongan_absen,
            ];

            // Kalkulasi Ulang Tunjangan Dinamis jika Gaji Belum Disimpan (Belum ada ID)
            if (is_null($detailGaji['gaji_id'])) {
                $tunjanganDinamis = $this->hitungTunjanganDinamis($karyawan, $detailGaji['gaji_pokok']);

                $detailGaji['tunj_anak'] = $tunjanganDinamis['tunj_anak'];
                $detailGaji['tunj_pengabdian'] = $tunjanganDinamis['tunj_pengabdian'];

                // Update string format
                $detailGaji['tunj_anak_string'] = 'Rp ' . number_format($detailGaji['tunj_anak'], 0, ',', '.');
                $detailGaji['tunj_pengabdian_string'] = 'Rp ' . number_format($detailGaji['tunj_pengabdian'], 0, ',', '.');
            }

            $dataGaji[] = $detailGaji;
        }

        return view('gaji.index', compact(
            'dataGaji',
            'selectedMonth',
            'tunjanganKehadirans',
            'indikatorKinerjas',
            'aturanKinerja',
            'tunjanganKomunikasis',
            'potongan'
        ));
    }

    /**
     * Menyimpan data gaji dengan Transaction & Snapshot.
     */
    public function saveOrUpdate(Request $request)
    {
        $validated = $request->validate([
            'karyawan_id' => 'required|exists:karyawans,id',
            'bulan' => 'required|date_format:Y-m',
            'gaji_pokok' => 'required|numeric|min:0',
            'jam_lembur' => 'nullable|numeric|min:0',
            'lembur_nominal_manual' => 'required|numeric|min:0',
            'potongan' => 'required|numeric|min:0',
            'tunjangan_kehadiran_id' => 'required|exists:tunjangan_kehadirans,id',
            'tunjangan_komunikasi_id' => 'nullable|exists:tunjangan_komunikasis,id',
            'scores' => 'nullable|array',
            'scores.*' => 'numeric|min:0|max:100',
        ]);

        return DB::transaction(function () use ($request, $validated) {
            $karyawan = Karyawan::with('jabatan')->find($validated['karyawan_id']);

            // 1. Hitung Tunjangan Statis
            $tunjanganDinamis = $this->hitungTunjanganDinamis($karyawan, $validated['gaji_pokok']);

            // 2. Hitung Tukin
            $aturanKinerja = Cache::remember('aturan_kinerja_single', 3600, fn() => AturanKinerja::first());
            $maxTukin = $aturanKinerja ? $aturanKinerja->maksimal_tunjangan : 0;

            $scores = $request->input('scores', []);
            $rataSkor = count($scores) > 0 ? array_sum($scores) / count($scores) : 0;
            $tukinNominal = ($rataSkor / 100) * $maxTukin;

            // 3. Hitung Komunikasi
            $tunjKomunikasiNominal = 0;
            if (!empty($validated['tunjangan_komunikasi_id'])) {
                $kom = TunjanganKomunikasi::find($validated['tunjangan_komunikasi_id']);
                $tunjKomunikasiNominal = $kom ? $kom->besaran : 0;
            }

            // 4. Snapshot Data
            $tunjJabatanSnapshot = $karyawan->jabatan->tunj_jabatan ?? 0;
            $lemburFinal = $validated['lembur_nominal_manual'];

            $dataSave = array_merge($validated, [
                'karyawan_id' => $validated['karyawan_id'],
                'bulan' => $validated['bulan'],
                'gaji_pokok' => $validated['gaji_pokok'],
                'lembur' => $lemburFinal,
                'potongan' => $validated['potongan'],
                'tunjangan_kehadiran_id' => $validated['tunjangan_kehadiran_id'],
                'tunjangan_komunikasi_id' => $validated['tunjangan_komunikasi_id'],
                'tunj_anak' => $tunjanganDinamis['tunj_anak'],
                'tunj_pengabdian' => $tunjanganDinamis['tunj_pengabdian'],
                'tunj_komunikasi' => $tunjKomunikasiNominal,
                'tunj_kinerja' => round($tukinNominal),
                'tunj_jabatan' => $tunjJabatanSnapshot,
                'nama_karyawan_snapshot' => $karyawan->nama,
                'nip_snapshot' => $karyawan->nip,
                'jabatan_snapshot' => $karyawan->jabatan->nama_jabatan ?? 'Tanpa Jabatan',
            ]);

            // 5. Simpan Gaji
            $gaji = $this->salaryService->saveGaji($dataSave);

            // 6. Simpan Skor
            $gaji->penilaianKinerjas()->delete();
            if (!empty($scores)) {
                $batchSkor = [];
                foreach ($scores as $id_indikator => $skor) {
                    $batchSkor[] = [
                        'gaji_id' => $gaji->id,
                        'indikator_kinerja_id' => $id_indikator,
                        'skor' => $skor,
                        'created_at' => now(),
                        'updated_at' => now(),
                    ];
                }
                PenilaianKinerja::insert($batchSkor);
            }

            // Return data baru untuk update UI
            // Di sini kita tidak perlu preloaded data karena hanya untuk 1 karyawan (respons AJAX)
            $newData = $this->salaryService->calculateDetailsForForm($karyawan, $validated['bulan']);

            // Paksa update nilai display agar sinkron dengan yang baru disimpan
            $newData['tunj_anak'] = $tunjanganDinamis['tunj_anak'];
            $newData['tunj_pengabdian'] = $tunjanganDinamis['tunj_pengabdian'];
            $newData['tunj_komunikasi'] = $tunjKomunikasiNominal;
            $newData['tunj_jabatan'] = $tunjJabatanSnapshot;

            return response()->json([
                'success' => true,
                'message' => 'Data gaji tersimpan.',
                'newData' => $newData
            ]);
        });
    }

    public function downloadSlip(Gaji $gaji)
    {
        GenerateIndividualSlip::dispatch($gaji->id, Auth::id());
        return response()->json(['message' => 'Permintaan diterima! Slip sedang dibuat & akan muncul di notifikasi jika siap.']);
    }

    public function sendEmail(Gaji $gaji)
    {
        if (empty($gaji->karyawan->email)) {
            return response()->json(['message' => 'Gagal. Email karyawan tidak tersedia.'], 422);
        }
        SendSlipToEmail::dispatch([$gaji->id], Auth::id());
        return response()->json(['message' => 'Permintaan diterima! Email sedang dikirim.']);
    }
}
