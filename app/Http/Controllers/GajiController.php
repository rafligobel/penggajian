<?php

namespace App\Http\Controllers;

use App\Models\Gaji;
use App\Models\Karyawan;
use App\Models\TunjanganKehadiran;
use Illuminate\Http\Request;
use App\Services\SalaryService;
use Carbon\Carbon;
use Illuminate\Support\Facades\Auth;
use App\Jobs\GenerateIndividualSlip;
use App\Jobs\SendSlipToEmail;

// --- REVISI: Tambahkan Model & Cache ---
use App\Models\AturanTunjanganAnak;
use App\Models\AturanTunjanganPengabdian;
use Illuminate\Support\Facades\Cache;

class GajiController extends Controller
{
    protected SalaryService $salaryService;

    public function __construct(SalaryService $salaryService)
    {
        $this->salaryService = $salaryService;
    }

    /**
     * [REVISI] Fungsi INTI untuk menghitung tunjangan dinamis
     * @param Karyawan $karyawan
     * @return array
     */
    private function hitungTunjanganDinamis(Karyawan $karyawan): array
    {
        // --- Revisi 2: Tunjangan Anak ---
        // Ambil aturan nilai per anak (kita cache 60 menit)
        $aturanAnak = Cache::remember('aturan_tunjangan_anak_single', 3600, function () {
            return AturanTunjanganAnak::first(); // Asumsi hanya ada 1 baris
        });

        $nilaiPerAnak = $aturanAnak ? $aturanAnak->nilai_per_anak : 0;
        $jumlahAnak = $karyawan->jumlah_anak ?? 0;
        $tunjanganAnak = $nilaiPerAnak * $jumlahAnak;

        // --- Revisi 3: Tunjangan Pengabdian ---
        $tunjanganPengabdian = 0;
        if ($karyawan->tanggal_masuk) {
            $lamaKerjaTahun = $karyawan->tanggal_masuk->diffInYears(Carbon::now());

            // Ambil SEMUA aturan pengabdian (cache 60 menit)
            $aturanPengabdian = Cache::remember('aturan_tunjangan_pengabdian_all', 3600, function () {
                return AturanTunjanganPengabdian::all();
            });

            // Cari aturan yang cocok
            $aturanYangBerlaku = $aturanPengabdian
                ->where('minimal_tahun_kerja', '<=', $lamaKerjaTahun)
                ->where('maksimal_tahun_kerja', '>=', $lamaKerjaTahun)
                ->first();

            if ($aturanYangBerlaku) {
                $tunjanganPengabdian = $aturanYangBerlaku->nilai_tunjangan;
            }
        }

        // --- Revisi 1: Tunjangan Komunikasi ---
        // Sesuai permintaan, 'tunj_komunikasi' tidak dipakai lagi.
        // Kita set 0 agar tidak error di service.
        $tunjanganKomunikasi = 0;


        // Kembalikan dalam array yang sesuai dengan nama kolom di tabel 'gajis'
        return [
            'tunj_anak' => $tunjanganAnak,
            'tunj_pengabdian' => $tunjanganPengabdian,
            'tunj_komunikasi' => $tunjanganKomunikasi, // Set 0
        ];
    }


    /**
     * [REVISI] Method index di-update agar menampilkan tunjangan yang sudah dihitung
     * dan memuat data Tunjangan Kehadiran untuk modal.
     */
    public function index(Request $request)
    {
        $selectedMonth = $request->input('bulan', Carbon::now()->format('Y-m'));

        $karyawans = Karyawan::with('jabatan')->orderBy('nama')->get();

        // Revisi 1: Ambil data Tunjangan Kehadiran untuk dropdown modal
        $tunjanganKehadirans = TunjanganKehadiran::all();

        $dataGaji = [];
        foreach ($karyawans as $karyawan) {
            // 1. Ambil data dasar (gaji_pokok, dll) dari service Anda
            $detailGaji = $this->salaryService->calculateDetailsForForm($karyawan, $selectedMonth);

            // --- AWAL REVISI (index) ---
            // 2. Hitung tunjangan otomatis (Anak, Pengabdian)
            $tunjanganDinamis = $this->hitungTunjanganDinamis($karyawan);

            // 3. Timpa (overwrite) nilai dari service dengan nilai kalkulasi kita
            $detailGaji['tunj_anak'] = $tunjanganDinamis['tunj_anak'];
            $detailGaji['tunj_pengabdian'] = $tunjanganDinamis['tunj_pengabdian'];
            $detailGaji['tunj_komunikasi'] = $tunjanganDinamis['tunj_komunikasi']; // Akan 0
            // --- AKHIR REVISI (index) ---

            $dataGaji[] = $detailGaji;
        }

        // Pastikan $tunjanganKehadirans dikirim ke view untuk modal
        return view('gaji.index', compact('dataGaji', 'selectedMonth', 'tunjanganKehadirans'));
    }

    /**
     * [REVISI] Method saveOrUpdate di-update agar MENYIMPAN tunjangan yang sudah dihitung
     */
    public function saveOrUpdate(Request $request)
    {
        // 1. Validasi input manual dari Bendahara
        $validatedData = $request->validate([
            'karyawan_id' => 'required|exists:karyawans,id',
            'bulan' => 'required|date_format:Y-m',
            'gaji_pokok' => 'required|numeric|min:0',
            'tunj_kinerja' => 'required|numeric|min:0',
            'lembur' => 'required|numeric|min:0',
            'potongan' => 'required|numeric|min:0',
            // Revisi 1: 'tunjangan_kehadiran_id' adalah input manual (dropdown)
            'tunjangan_kehadiran_id' => 'required|exists:tunjangan_kehadirans,id',

            // 'tunj_anak', 'tunj_komunikasi', 'tunj_pengabdian' DIHAPUS dari validasi
            // karena akan dihitung otomatis
        ]);

        // --- AWAL REVISI (saveOrUpdate) ---
        // 2. Ambil model Karyawan
        $karyawan = Karyawan::find($validatedData['karyawan_id']);

        // 3. Panggil fungsi kalkulasi baru kita (Anak, Pengabdian)
        $tunjanganDinamis = $this->hitungTunjanganDinamis($karyawan);

        // 4. Gabungkan hasil kalkulasi ke data yang akan disimpan
        $dataUntukDisimpan = array_merge($validatedData, $tunjanganDinamis);
        // --- AKHIR REVISI (saveOrUpdate) ---

        // 5. Simpan ke database menggunakan service Anda
        //    Service Anda akan menyimpan semua data di $dataUntukDisimpan
        $this->salaryService->saveGaji($dataUntukDisimpan);

        // 6. Ambil data terbaru dari service untuk dikirim balik ke view
        $newData = $this->salaryService->calculateDetailsForForm($karyawan, $validatedData['bulan']);

        // --- AWAL REVISI (JSON Response) ---
        // Timpa lagi output service untuk data balikan JSON agar akurat
        $newData['tunj_anak'] = $tunjanganDinamis['tunj_anak'];
        $newData['tunj_pengabdian'] = $tunjanganDinamis['tunj_pengabdian'];
        $newData['tunj_komunikasi'] = $tunjanganDinamis['tunj_komunikasi']; // Akan 0
        // --- AKHIR REVISI (JSON Response) ---

        return response()->json([
            'success' => true,
            'message' => 'Data gaji berhasil disimpan.',
            'newData' => $newData
        ]);
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
