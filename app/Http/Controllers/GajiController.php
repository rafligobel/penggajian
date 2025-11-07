<?php
// File: app/Http/Controllers/GajiController.php

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

// --- TAMBAHAN BARU UNTUK KINERJA ---
use App\Models\AturanKinerja;
use App\Models\IndikatorKinerja;
use App\Models\PenilaianKinerja;
// --- PERBAIKAN: Pastikan Model TunjanganKomunikasi di-use ---
use App\Models\TunjanganKomunikasi;

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
            return AturanTunjanganAnak::first();
        });

        $nilaiPerAnak = $aturanAnak ? $aturanAnak->nilai_per_anak : 0;
        $jumlahAnak = $karyawan->jumlah_anak ?? 0;
        $tunjanganAnak = $nilaiPerAnak * $jumlahAnak;

        // --- Revisi 3: Tunjangan Pengabdian ---
        $tunjanganPengabdian = 0;
        if ($karyawan->tanggal_masuk) {
            $lamaKerjaTahun = $karyawan->tanggal_masuk->diffInYears(Carbon::now());
            $aturanPengabdian = Cache::remember('aturan_tunjangan_pengabdian_all', 3600, function () {
                return AturanTunjanganPengabdian::all();
            });
            $aturanYangBerlaku = $aturanPengabdian
                ->where('minimal_tahun_kerja', '<=', $lamaKerjaTahun)
                ->where('maksimal_tahun_kerja', '>=', $lamaKerjaTahun)
                ->first();

            if ($aturanYangBerlaku) {
                $tunjanganPengabdian = $aturanYangBerlaku->nilai_tunjangan;
            }
        }

        // --- Revisi 1: Tunjangan Komunikasi ---
        $tunjanganKomunikasi = 0;

        return [
            'tunj_anak' => $tunjanganAnak,
            'tunj_pengabdian' => $tunjanganPengabdian,
            'tunj_komunikasi' => $tunjanganKomunikasi,
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
        // Ambil data Tunjangan Komunikasi untuk dropdown modal
        $tunjanganKomunikasis = TunjanganKomunikasi::all();

        // --- TAMBAHAN BARU UNTUK KINERJA ---
        // Ambil data Master Indikator
        $indikatorKinerjas = IndikatorKinerja::all();
        // Ambil aturan Tukin (nominal maksimal)
        $aturanKinerja = Cache::remember('aturan_kinerja_single', 3600, function () {
            return AturanKinerja::first();
        });
        // --- AKHIR TAMBAHAN ---


        $dataGaji = [];
        foreach ($karyawans as $karyawan) {
            // 1. Ambil data dasar (gaji_pokok, dll) dari service Anda
            //    Service ini sudah benar memuat $gajiTersimpan (data historis)
            $detailGaji = $this->salaryService->calculateDetailsForForm($karyawan, $selectedMonth);

            // --- AWAL PERBAIKAN ---
            // Hanya hitung & timpa tunjangan dinamis JIKA gaji belum diproses
            // (gaji_id masih null). Jika sudah diproses, kita gunakan
            // data historis yang sudah dimuat oleh SalaryService.
            if (is_null($detailGaji['gaji_id'])) {
                // 2. Hitung tunjangan otomatis (Anak, Pengabdian)
                $tunjanganDinamis = $this->hitungTunjanganDinamis($karyawan);

                // 3. Timpa (overwrite) nilai dari service dengan nilai kalkulasi kita
                $detailGaji['tunj_anak'] = $tunjanganDinamis['tunj_anak'];
                $detailGaji['tunj_pengabdian'] = $tunjanganDinamis['tunj_pengabdian'];

                // PERBAIKAN:
                // Jika gaji_id null, tunj_komunikasi (dari master) belum ada.
                // Kita set 0 sebagai default di tampilan, nanti diisi di modal.
                // $detailGaji['tunj_komunikasi'] = $tunjanganDinamis['tunj_komunikasi']; // Akan 0
                // Biarkan nilai dari SalaryService (yang akan 0 jika $gajiTersimpan null)
            }
            // --- AKHIR PERBAIKAN ---

            $dataGaji[] = $detailGaji;
        }

        // --- UBAH COMPACT ---
        // Pastikan $tunjanganKehadirans, $indikatorKinerjas, $aturanKinerja dikirim ke view
        return view('gaji.index', compact(
            'dataGaji',
            'selectedMonth',
            'tunjanganKehadirans',
            'indikatorKinerjas', // Kirim master indikator
            'aturanKinerja',
            'tunjanganKomunikasis'      // Kirim data master komunikasi
        ));
    }

    /**
     * [REVISI] Method saveOrUpdate di-update agar MENGHITUNG dan MENYIMPAN Tukin & skornya
     * DAN MENYIMPAN DATA SNAPSHOT KARYAWAN
     *
     * [PERBAIKAN BUG Tunjangan Komunikasi]
     */
    public function saveOrUpdate(Request $request)
    {
        // 1. Validasi input manual dari Bendahara
        $validatedData = $request->validate([
            'karyawan_id' => 'required|exists:karyawans,id',
            'bulan' => 'required|date_format:Y-m',
            'gaji_pokok' => 'required|numeric|min:0',
            'lembur' => 'required|numeric|min:0',
            'potongan' => 'required|numeric|min:0',
            'tunjangan_kehadiran_id' => 'required|exists:tunjangan_kehadirans,id',

            // --- AWAL PERBAIKAN BUG ---
            // Tambahkan validasi untuk ID tunjangan komunikasi (sesuai kode Anda)
            'tunjangan_komunikasi_id' => 'nullable|exists:tunjangan_komunikasis,id',
            // --- AKHIR PERBAIKAN BUG ---

            // Validasi untuk input skor
            'scores' => 'nullable|array',
            'scores.*' => 'required|numeric|min:0|max:100', // Skor harus 0-100
        ]);

        // --- AWAL REVISI (saveOrUpdate) ---
        // 2. Ambil model Karyawan (WAJIB Eager Load 'jabatan' untuk snapshot)
        $karyawan = Karyawan::with('jabatan')->find($validatedData['karyawan_id']);

        // 3. Panggil fungsi kalkulasi (Anak, Pengabdian)
        //    Ini akan menghasilkan 'tunj_komunikasi' => 0, TAPI tidak apa-apa
        $tunjanganDinamis = $this->hitungTunjanganDinamis($karyawan);

        // --- AWAL KALKULASI KINERJA (SESUAI PERMINTAAN BARU) ---

        // 4. Ambil Aturan Tukin (Nilai Maksimal)
        $aturanKinerja = Cache::remember('aturan_kinerja_single', 3600, function () {
            return AturanKinerja::first();
        });
        $maksimalTunjangan = $aturanKinerja ? $aturanKinerja->maksimal_tunjangan : 0;

        // 5. Hitung Skor Rata-rata dari Input Bendahara
        $scores = $request->input('scores', []);
        $totalSkor = 0;
        $jumlahIndikator = count($scores);
        $rataRataSkor = 0;

        if ($jumlahIndikator > 0) {
            foreach ($scores as $skor) {
                $totalSkor += (float)$skor;
            }
            $rataRataSkor = $totalSkor / $jumlahIndikator; // Hasilnya 0-100
        }

        // 6. Hitung Nominal Tukin
        // Rumus: (Skor Rata-rata / 100) * Tunjangan Maksimal
        $tunjanganKinerjaNominal = ($rataRataSkor / 100) * $maksimalTunjangan;

        // --- AKHIR KALKULASI KINERJA ---

        // --- AWAL PERBAIKAN BUG TUNJANGAN KOMUNIKASI ---
        // 7. Ambil nominal Tunjangan Komunikasi dari ID yang di-request
        $tunjanganKomunikasiNominal = 0;
        if (!empty($validatedData['tunjangan_komunikasi_id'])) {
            // Cari master tunjangan komunikasi berdasarkan ID dari form
            $aturanKomunikasi = TunjanganKomunikasi::find($validatedData['tunjangan_komunikasi_id']);
            if ($aturanKomunikasi) {
                // Ambil nominal 'besaran'
                $tunjanganKomunikasiNominal = $aturanKomunikasi->besaran;
            }
        }
        // --- AKHIR PERBAIKAN BUG TUNJANGAN KOMUNIKASI ---

        // --- AWAL TAMBAHAN SNAPSHOT KARYAWAN ---
        // 8. Siapkan data snapshot dari karyawan
        $snapshotData = [
            'nama_karyawan_snapshot' => $karyawan->nama,
            'nip_snapshot' => $karyawan->nip, // Asumsi NIP ada di 'nip'
            'jabatan_snapshot' => $karyawan->jabatan ? $karyawan->jabatan->nama_jabatan : null,
        ];
        // --- AKHIR TAMBAHAN SNAPSHOT KARYAWAN ---

        // 9. Gabungkan semua hasil kalkulasi ke data yang akan disimpan
        $dataUntukDisimpan = array_merge(
            $validatedData,
            $tunjanganDinamis, // Ini mengandung 'tunj_komunikasi' => 0
            ['tunj_kinerja' => $tunjanganKinerjaNominal], // Timpa/Isi tunj_kinerja
            $snapshotData // TAMBAHKAN DATA SNAPSHOT
        );

        // --- AWAL PERBAIKAN BUG: Timpa tunj_komunikasi ---
        // Timpa 'tunj_komunikasi' => 0 dari $tunjanganDinamis
        // dengan nilai nominal yang benar dari kalkulasi kita.
        $dataUntukDisimpan['tunj_komunikasi'] = $tunjanganKomunikasiNominal;
        // --- AKHIR PERBAIKAN BUG ---

        // 10. Simpan ke database menggunakan service Anda
        $gaji = $this->salaryService->saveGaji($dataUntukDisimpan);

        // 11. Simpan rincian skor ke tabel 'penilaian_kinerjas'
        //     Hapus data skor lama (jika ada)
        $gaji->penilaianKinerjas()->delete();
        //     Simpan data skor baru
        if (!empty($scores)) {
            $dataSkorBatch = [];
            foreach ($scores as $indikator_id => $skor) {
                $dataSkorBatch[] = [
                    'gaji_id' => $gaji->id,
                    'indikator_kinerja_id' => $indikator_id,
                    'skor' => (int)$skor,
                    'created_at' => now(),
                    'updated_at' => now(),
                ];
            }
            PenilaianKinerja::insert($dataSkorBatch);
        }

        // 12. Ambil data terbaru dari service untuk dikirim balik ke view
        $newData = $this->salaryService->calculateDetailsForForm($karyawan, $validatedData['bulan']);

        // --- AWAL REVISI (JSON Response) ---
        // Timpa lagi output service untuk data balikan JSON agar akurat
        // (Ini diperlukan agar data di baris tabel langsung update dengan benar)
        $newData['tunj_anak'] = $tunjanganDinamis['tunj_anak'];
        $newData['tunj_pengabdian'] = $tunjanganDinamis['tunj_pengabdian'];

        // --- AWAL PERBAIKAN BUG (JSON Response) ---
        // $newData['tunj_komunikasi'] = $tunjanganDinamis['tunj_komunikasi']; // Hapus baris ini
        $newData['tunj_komunikasi'] = $tunjanganKomunikasiNominal; // Ganti dengan nilai yg benar
        // --- AKHIR PERBAIKAN BUG (JSON Response) ---

        // Tunjangan Kinerja sudah dihitung ulang oleh service, jadi tidak perlu ditimpa
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
        // ... (Tidak berubah) ...
        GenerateIndividualSlip::dispatch($gaji->id, Auth::id());
        return response()->json(['message' => 'Permintaan diterima! Slip sedang dibuat & akan muncul di notifikasi jika siap.']);
    }

    public function sendEmail(Gaji $gaji)
    {
        // ... (Tidak berubah) ...
        if (empty($gaji->karyawan->email)) {
            return response()->json(['message' => 'Gagal. Karyawan ini tidak memiliki alamat email.'], 422);
        }

        SendSlipToEmail::dispatch([$gaji->id], Auth::id());

        return response()->json(['message' => 'Permintaan diterima! Email sedang dikirim & notifikasi akan muncul jika berhasil.']);
    }
}
