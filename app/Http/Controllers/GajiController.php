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
     * Fungsi INTI untuk menghitung tunjangan dinamis
     * @param Karyawan $karyawan
     * @param float $gajiPokok Gaji pokok wajib ada untuk hitung tunjangan pengabdian
     * @return array
     */
    private function hitungTunjanganDinamis(Karyawan $karyawan, float $gajiPokok): array
    {
        // Ambil aturan nilai per anak (kita cache 60 menit)
        $aturanAnak = Cache::remember('aturan_tunjangan_anak_single', 3600, function () {
            return AturanTunjanganAnak::first();
        });

        $nilaiPerAnak = $aturanAnak ? $aturanAnak->nilai_per_anak : 0;
        $jumlahAnak = $karyawan->jumlah_anak ?? 0;
        $tunjanganAnak = $nilaiPerAnak * $jumlahAnak;

        // Tunjangan Pengabdian  ---
        $tunjanganPengabdian = 0;
        // Cek apakah karyawan punya tanggal masuk DAN gaji pokok lebih dari 0
        if ($karyawan->tanggal_masuk && $gajiPokok > 0) {
            $lamaKerjaTahun = $karyawan->tanggal_masuk->diffInYears(Carbon::now());

            // Ambil semua aturan dari cache
            $aturanPengabdian = Cache::remember('aturan_tunjangan_pengabdian_all', 3600, function () {
                return AturanTunjanganPengabdian::all();
            });

            // Cari aturan yang sesuai
            $aturanYangBerlaku = $aturanPengabdian
                ->where('minimal_tahun_kerja', '<=', $lamaKerjaTahun)
                ->where('maksimal_tahun_kerja', '>=', $lamaKerjaTahun)
                ->first();

            if ($aturanYangBerlaku) {
                // Asumsi: nilai_tunjangan di DB adalah persentase (e.g., 5, 10, 15)
                $persentase = $aturanYangBerlaku->nilai_tunjangan;
                // Hitung tunjangan berdasarkan persentase Gaji Pokok
                $tunjanganPengabdian = ($persentase / 100) * $gajiPokok;
            }
        }

        // Tunjangan Komunikasi ---
        $tunjanganKomunikasi = 0; // Default 0, akan diisi dari modal

        return [
            'tunj_anak' => $tunjanganAnak,
            'tunj_pengabdian' => $tunjanganPengabdian,
            'tunj_komunikasi' => $tunjanganKomunikasi,
        ];
    }


    /**
     * Method index di-update agar menampilkan tunjangan yang sudah dihitung
     * dan memuat data Tunjangan Kehadiran untuk modal.
     */
    public function index(Request $request)
    {
        $selectedMonth = $request->input('bulan', Carbon::now()->format('Y-m'));
        $bulanCarbon = Carbon::createFromFormat('Y-m', $selectedMonth);
        $karyawans = Karyawan::with('jabatan')->orderBy('nama')->get();

        // Ambil data Tunjangan Kehadiran untuk dropdown modal
        $tunjanganKehadirans = TunjanganKehadiran::all();
        // Ambil data Tunjangan Komunikasi untuk dropdown modal
        $tunjanganKomunikasis = TunjanganKomunikasi::all();

        // Ambil data Master Indikator
        $indikatorKinerjas = IndikatorKinerja::all();
        // Ambil aturan Tukin (nominal maksimal)
        $aturanKinerja = Cache::remember('aturan_kinerja_single', 3600, fn() => AturanKinerja::first());
        $potongan = potongan::first() ?? new potongan(['tarif_lembur_per_jam' => 0, 'tarif_potongan_absen' => 0]);
        $rekapAbsensiRaw = $this->absensiService->getAttendanceRecap($bulanCarbon);
        $rekapAbsensi = $rekapAbsensiRaw['rekapData']->keyBy('id');

        $dataGaji = [];
        foreach ($karyawans as $karyawan) {
            // 1. Ambil data dasar (gaji_pokok, dll) dari service Anda
            //    Service ini sudah benar memuat $gajiTersimpan (data historis)
            //    DAN SUDAH MEMUAT Gaji Pokok dari master jika data baru
            $detailGaji = $this->salaryService->calculateDetailsForForm($karyawan, $selectedMonth);
            $dataAbsenKaryawan = $rekapAbsensi->get($karyawan->id);
            $jumlahAlpha = $dataAbsenKaryawan ? $dataAbsenKaryawan['summary']['total_alpha'] : 0;
            $detailGaji['data_pendukung'] = [
                'jumlah_alpha' => $jumlahAlpha,
                'tarif_potongan_absen' => $potongan->tarif_potongan_absen,
                'tarif_lembur_per_jam' => $potongan->tarif_lembur_per_jam,
                // Hitung potongan otomatis (hanya saran, nanti JS yang eksekusi real-time)
                'potongan_alpha_otomatis' => $jumlahAlpha * $potongan->tarif_potongan_absen,
            ];
            // Hanya hitung & timpa tunjangan dinamis JIKA gaji belum diproses
            // (gaji_id masih null). Jika sudah diproses, kita gunakan
            // data historis yang sudah dimuat oleh SalaryService.
            if (is_null($detailGaji['gaji_id'])) {
                // 2. Hitung tunjangan otomatis (Anak, Pengabdian)
                $tunjanganDinamis = $this->hitungTunjanganDinamis($karyawan, $detailGaji['gaji_pokok']);

                // 3. Timpa (overwrite) nilai dari service dengan nilai kalkulasi kita
                $detailGaji['tunj_anak'] = $tunjanganDinamis['tunj_anak'];
                $detailGaji['tunj_pengabdian'] = $tunjanganDinamis['tunj_pengabdian'];
                $detailGaji['potongan'] = $detailGaji['data_pendukung']['potongan_alpha_otomatis'];
                // Update string-nya juga
                $detailGaji['tunj_anak_string'] = 'Rp ' . number_format($detailGaji['tunj_anak'], 0, ',', '.');
                $detailGaji['tunj_pengabdian_string'] = 'Rp ' . number_format($detailGaji['tunj_pengabdian'], 0, ',', '.');
            }

            $dataGaji[] = $detailGaji;
        }

        // Pastikan $tunjanganKehadirans, $indikatorKinerjas, $aturanKinerja dikirim ke view
        return view('gaji.index', compact(
            'dataGaji',
            'selectedMonth',
            'tunjanganKehadirans',
            'indikatorKinerjas', // Kirim master indikator
            'aturanKinerja',
            'tunjanganKomunikasis',
            'potongan'   // Kirim data master komunikasi
        ));
    }

    /**
     * Method saveOrUpdate di-update agar MENGHITUNG dan MENYIMPAN Tukin & skornya
     * DAN MENYIMPAN DATA SNAPSHOT KARYAWAN
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

        // [FIX 3] Gunakan Database Transaction
        return DB::transaction(function () use ($request, $validated) {

            $karyawan = Karyawan::with('jabatan')->find($validated['karyawan_id']);

            // 1. Hitung Tunjangan Statis (Anak, Pengabdian)
            $tunjanganDinamis = $this->hitungTunjanganDinamis($karyawan, $validated['gaji_pokok']);

            // 2. Hitung Tunjangan Kinerja (Tukin)
            $aturanKinerja = Cache::remember('aturan_kinerja_single', 3600, fn() => AturanKinerja::first());
            $maxTukin = $aturanKinerja ? $aturanKinerja->maksimal_tunjangan : 0;

            $scores = $request->input('scores', []);
            $rataSkor = count($scores) > 0 ? array_sum($scores) / count($scores) : 0;
            $tukinNominal = ($rataSkor / 100) * $maxTukin;

            // 3. Hitung Tunjangan Komunikasi
            $tunjKomunikasiNominal = 0;
            if (!empty($validated['tunjangan_komunikasi_id'])) {
                $kom = TunjanganKomunikasi::find($validated['tunjangan_komunikasi_id']);
                $tunjKomunikasiNominal = $kom ? $kom->besaran : 0;
            }

            // 4. [CRITICAL] Snapshot Tunjangan Jabatan
            // Kita ambil nilai saat ini dari master jabatan, dan simpan mati ke tabel gaji
            $tunjJabatanSnapshot = $karyawan->jabatan->tunj_jabatan ?? 0;
            $lemburFinal = $validated['lembur_nominal_manual'];

            // 5. Prepare Data Save
            $dataSave = array_merge($validated, [
                'karyawan_id' => $validated['karyawan_id'],
                'bulan' => $validated['bulan'],
                'gaji_pokok' => $validated['gaji_pokok'],
                'lembur' => $lemburFinal, // Simpan Nominal
                'potongan' => $validated['potongan'],
                'tunjangan_kehadiran_id' => $validated['tunjangan_kehadiran_id'],
                'tunjangan_komunikasi_id' => $validated['tunjangan_komunikasi_id'],

                'tunj_anak' => $tunjanganDinamis['tunj_anak'],
                'tunj_pengabdian' => $tunjanganDinamis['tunj_pengabdian'],
                'tunj_komunikasi' => $tunjKomunikasiNominal,
                'tunj_kinerja' => round($tukinNominal), // Round agar jadi integer rapi
                'tunj_jabatan' => $tunjJabatanSnapshot, // Simpan Snapshot Jabatan!

                // Snapshot Identitas (Model Gaji::booting sudah menangani sebagian, tapi jabatan_snapshot perlu update)
                'nama_karyawan_snapshot' => $karyawan->nama,
                'nip_snapshot' => $karyawan->nip,
                'jabatan_snapshot' => $karyawan->jabatan->nama_jabatan ?? 'Tanpa Jabatan',
            ]);

            // 6. Simpan Gaji
            // Karena $salaryService->saveGaji menggunakan updateOrCreate, kita perlu sesuaikan 
            // Pastikan Service Anda menerima 'tunj_jabatan' di fillable
            $gaji = $this->salaryService->saveGaji($dataSave);

            // 7. Simpan Skor Kinerja
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

            // Return respons sukses
            $newData = $this->salaryService->calculateDetailsForForm($karyawan, $validated['bulan']);
            // Paksa update nilai display dengan yang baru dihitung agar UI sinkron
            $newData['tunj_anak'] = $tunjanganDinamis['tunj_anak'];
            $newData['tunj_pengabdian'] = $tunjanganDinamis['tunj_pengabdian'];
            $newData['tunj_komunikasi'] = $tunjKomunikasiNominal;
            $newData['tunj_jabatan'] = $tunjJabatanSnapshot; // Update UI

            return response()->json([
                'success' => true,
                'message' => 'Data gaji tersimpan aman & terverifikasi.',
                'newData' => $newData
            ]);
        }); // End Transaction
    }

    // Fungsi lain (downloadSlip, sendEmail) tidak berubah
    public function downloadSlip(Gaji $gaji)
    {
        GenerateIndividualSlip::dispatch($gaji->id, Auth::id());
        return response()->json(['message' => 'Permintaan diterima! Slip sedang dibuat & akan muncul di notifikasi jika siap.']);
    }

    public function sendEmail(Gaji $gaji)
    {
        if (empty($gaji->karyawan->email)) {
            return response()->json(['message' => 'Gagal. Karyawan ini tidak memiliki alamat email.'], 422);
        }

        SendSlipToEmail::dispatch([$gaji->id], Auth::id());

        return response()->json(['message' => 'Permintaan diterima! Email sedang dikirim & notifikasi akan muncul jika berhasil.']);
    }
}
