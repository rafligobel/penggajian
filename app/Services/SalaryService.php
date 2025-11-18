<?php

namespace App\Services;

use App\Models\Karyawan;
use App\Models\Gaji;
use App\Models\Absensi;
use App\Models\TunjanganKehadiran;
use App\Models\TunjanganKomunikasi;
use Carbon\Carbon;

use App\Models\AturanTunjanganAnak;
use App\Models\AturanTunjanganPengabdian;
use App\Models\AturanKinerja;
use Illuminate\Support\Facades\Cache;


class SalaryService
{
    /**
     * [REVISI BESAR] Mengkalkulasi detail gaji untuk form admin.
     * Jika gaji bulan ini belum ada, ambil data Gaji Pokok dari master Karyawan.
     */
    public function calculateDetailsForForm(Karyawan $karyawan, string $bulan): array
    {
        $karyawan->loadMissing('jabatan');

        try {
            $tanggal = Carbon::parse($bulan)->startOfMonth();
        } catch (\Exception $e) {
            $tanggal = Carbon::now()->startOfMonth();
        }

        // 1. Cek Gaji Bulan Ini
        $gajiTersimpan = Gaji::with(['tunjanganKehadiran', 'penilaianKinerjas'])
            ->where('karyawan_id', $karyawan->id)
            ->whereYear('bulan', $tanggal->year)
            ->whereMonth('bulan', $tanggal->month)
            ->first();

        // 2. Hitung Kehadiran (Selalu real-time)
        $jumlahKehadiran = Absensi::where('karyawan_id', $karyawan->id)
            ->whereYear('tanggal', $tanggal->year)
            ->whereMonth('tanggal', $tanggal->month)
            ->count();

        // Variabel default
        $gajiPokok = 0;
        $tunjJabatan = $karyawan->jabatan->tunj_jabatan ?? 0;
        $tunjAnak = 0;
        $tunjKomunikasi = 0;
        $tunjPengabdian = 0;
        $tunjKinerja = 0;
        $lembur = 0;
        $potongan = 0;
        $tunjanganKehadiranId = null;
        $tunjanganPerKehadiran = 0;
        $tunjanganKomunikasiId = null;
        $penilaianKinerja = [];
        $gajiId = null;

        // Ambil aturan default Tunjangan Kehadiran (fallback)
        $aturanDefaultKehadiran = TunjanganKehadiran::orderBy('id', 'asc')->first();

        if ($gajiTersimpan) {
            // --- KASUS 1: Gaji Bulan Ini SUDAH ADA ---
            $gajiId = $gajiTersimpan->id;
            $gajiPokok = $gajiTersimpan->gaji_pokok;
            $tunjAnak = $gajiTersimpan->tunj_anak;
            $tunjKomunikasi = $gajiTersimpan->tunj_komunikasi;
            $tunjPengabdian = $gajiTersimpan->tunj_pengabdian;
            $tunjKinerja = $gajiTersimpan->tunj_kinerja;
            $lembur = $gajiTersimpan->lembur;
            $potongan = $gajiTersimpan->potongan;
            $penilaianKinerja = $gajiTersimpan->penilaianKinerjas->pluck('skor', 'indikator_kinerja_id');
            $tunjanganKehadiranId = $gajiTersimpan->tunjangan_kehadiran_id;

            if ($gajiTersimpan->tunjanganKehadiran) {
                $tunjanganPerKehadiran = $gajiTersimpan->tunjanganKehadiran->jumlah_tunjangan;
            } elseif ($aturanDefaultKehadiran) {
                $tunjanganPerKehadiran = $aturanDefaultKehadiran->jumlah_tunjangan;
            }

            // Logika tunjangan komunikasi ID
            if ($gajiTersimpan->tunj_komunikasi > 0) {
                $aturanKomunikasi = TunjanganKomunikasi::where('besaran', $gajiTersimpan->tunj_komunikasi)->first();
                if ($aturanKomunikasi) {
                    $tunjanganKomunikasiId = $aturanKomunikasi->id;
                }
            }
        } else {
            // Gaji Bulan Ini BELUM ADA (Logika Baru) ---

            // Cari Gaji Terakhir (bulan apa saja)
            $gajiTerakhir = Gaji::with('tunjanganKehadiran') // Eager load relasinya
                ->where('karyawan_id', $karyawan->id)
                ->orderBy('bulan', 'desc')
                ->first();

            if ($gajiTerakhir) {
                // JIKA ADA RIWAYAT GAJI: Ambil data dari riwayat
                $gajiPokok = $gajiTerakhir->gaji_pokok;
                $tunjAnak = $gajiTerakhir->tunj_anak;
                $tunjPengabdian = $gajiTerakhir->tunj_pengabdian;
                $tunjanganKehadiranId = $gajiTerakhir->tunjangan_kehadiran_id;

                if ($gajiTerakhir->tunjanganKehadiran) {
                    $tunjanganPerKehadiran = $gajiTerakhir->tunjanganKehadiran->jumlah_tunjangan;
                } elseif ($aturanDefaultKehadiran) {
                    $tunjanganPerKehadiran = $aturanDefaultKehadiran->jumlah_tunjangan;
                }

                // Tunj Komunikasi, Kinerja, Lembur, Potongan default 0
                // (Ini diisi manual oleh Bendahara)

            } else {
                $gajiPokok = $karyawan->gaji_pokok_default ?? 0;

                // Hitung tunjangan dinamis berdasarkan Gaji Pokok master
                $tunjanganDinamis = $this->hitungTunjanganDinamisDefault($karyawan, $gajiPokok); // <-- PERBAIKAN
                $tunjAnak = $tunjanganDinamis['tunj_anak'];
                $tunjPengabdian = $tunjanganDinamis['tunj_pengabdian'];

                // Ambil default tunjangan kehadiran
                if ($aturanDefaultKehadiran) {
                    $tunjanganKehadiranId = $aturanDefaultKehadiran->id;
                    $tunjanganPerKehadiran = $aturanDefaultKehadiran->jumlah_tunjangan;
                }
                // Tunj Komunikasi, Kinerja, Lembur, Potongan default 0
                // (Ini diisi manual oleh Bendahara saat form)
            }
        }

        // 3. Kalkulasi Total (Selalu real-time)
        $tunjKehadiran = $jumlahKehadiran * $tunjanganPerKehadiran;

        $gajiBersihNumeric = ($gajiPokok + $tunjJabatan + $tunjKehadiran + $tunjAnak + $tunjKomunikasi + $tunjPengabdian + $tunjKinerja + $lembur) - $potongan;
        $gajiBersihString = 'Rp ' . number_format($gajiBersihNumeric, 0, ',', '.');

        // 4. Susun Array Hasil
        $result = [
            'gaji_id' => $gajiId,
            'karyawan_id' => $karyawan->id,
            'nip' => $karyawan->nip,
            'nama' => $karyawan->nama,
            'email' => $karyawan->email,
            'jabatan' => $karyawan->jabatan->nama_jabatan ?? 'Tidak Ada Jabatan',
            'bulan' => $tanggal->format('Y-m'),

            // Data Numerik untuk Form
            'gaji_pokok' => (float) $gajiPokok,
            'tunj_jabatan' => (float) $tunjJabatan,
            'tunj_anak' => (float) $tunjAnak,
            'tunj_komunikasi' => (float) $tunjKomunikasi,
            'tunj_pengabdian' => (float) $tunjPengabdian,
            'tunj_kinerja' => (float) $tunjKinerja,
            'lembur' => (float) $lembur,
            'potongan' => (float) $potongan,
            'tunj_kehadiran' => (float) $tunjKehadiran,

            // Data ID untuk Dropdown
            'tunjangan_kehadiran_id' => $tunjanganKehadiranId,
            'tunjangan_komunikasi_id' => $tunjanganKomunikasiId,

            // Data Tampilan
            'total_kehadiran' => $jumlahKehadiran, // Nama alias
            'jumlah_kehadiran' => $jumlahKehadiran, // Nama asli
            'gaji_bersih_numeric' => $gajiBersihNumeric,

            'gaji_pokok_string' => 'Rp ' . number_format($gajiPokok, 0, ',', '.'),
            'tunj_jabatan_string' => 'Rp ' . number_format($tunjJabatan, 0, ',', '.'),
            'tunj_anak_string' => 'Rp ' . number_format($tunjAnak, 0, ',', '.'),
            'tunj_komunikasi_string' => 'Rp ' . number_format($tunjKomunikasi, 0, ',', '.'),
            'tunj_pengabdian_string' => 'Rp ' . number_format($tunjPengabdian, 0, ',', '.'),
            'tunj_kinerja_string' => 'Rp ' . number_format($tunjKinerja, 0, ',', '.'),
            'lembur_string' => 'Rp ' . number_format($lembur, 0, ',', '.'),
            'potongan_string' => 'Rp ' . number_format($potongan, 0, ',', '.'),
            'total_tunjangan_kehadiran_string' => 'Rp ' . number_format($tunjKehadiran, 0, ',', '.'),
            'gaji_bersih_string' => $gajiBersihString,

            // Data Rincian Modal
            'penilaian_kinerja' => $penilaianKinerja,
            'tunj_kehadiran_rincian' => [
                'per_hari' => $tunjanganPerKehadiran,
                'total' => $tunjKehadiran,
                'total_string' => 'Rp ' . number_format($tunjKehadiran, 0, ',', '.'),
            ],
        ];

        return $result;
    }

    /**
     * [REVISI] Fungsi helper untuk menghitung tunjangan dinamis HANYA untuk karyawan baru.
     * (Dipindah dari GajiController)
     * @param Karyawan $karyawan
     * @param float $gajiPokok Wajib ada untuk hitung Tunjangan Pengabdian
     * @return array
     */
    private function hitungTunjanganDinamisDefault(Karyawan $karyawan, float $gajiPokok): array
    {
        // --- Tunjangan Anak ---
        $aturanAnak = Cache::remember('aturan_tunjangan_anak_single', 3600, function () {
            return AturanTunjanganAnak::first();
        });
        $nilaiPerAnak = $aturanAnak ? $aturanAnak->nilai_per_anak : 0;
        $jumlahAnak = $karyawan->jumlah_anak ?? 0;
        $tunjanganAnak = $nilaiPerAnak * $jumlahAnak;

        // --- Tunjangan Pengabdian 
        $tunjanganPengabdian = 0;
        // Cek apakah karyawan punya tanggal masuk DAN gaji pokok lebih dari 0
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
                // Asumsi: nilai_tunjangan di DB adalah persentase (e.g., 5, 10, 15)
                $persentase = $aturanYangBerlaku->nilai_tunjangan;
                // Hitung tunjangan berdasarkan persentase Gaji Pokok
                $tunjanganPengabdian = ($persentase / 100) * $gajiPokok;
            }
        }

        return [
            'tunj_anak' => $tunjanganAnak,
            'tunj_pengabdian' => $tunjanganPengabdian,
        ];
    }


    /**
     * [PERBAIKAN] Menyimpan atau update data gaji.
     * (Tidak ada perubahan di sini, sudah benar)
     */
    public function saveGaji(array $data): Gaji
    {
        $bulanCarbon = Carbon::createFromFormat('Y-m', $data['bulan'])->startOfMonth();

        // Ambil data untuk disimpan, pastikan semua key ada
        $saveData = [
            // Data Finansial
            'gaji_pokok' => $data['gaji_pokok'] ?? 0,
            'tunj_anak' => $data['tunj_anak'] ?? 0,
            'tunj_komunikasi' => $data['tunj_komunikasi'] ?? 0,
            'tunj_pengabdian' => $data['tunj_pengabdian'] ?? 0,
            'tunj_kinerja' => $data['tunj_kinerja'] ?? 0,
            'lembur' => $data['lembur'] ?? 0,
            'potongan' => $data['potongan'] ?? 0,
            'tunjangan_kehadiran_id' => $data['tunjangan_kehadiran_id'],
            'tunj_jabatan' => $data['tunj_jabatan'] ?? 0,

            // PERBAIKAN: Pastikan ID tunjangan komunikasi juga tersimpan
            // Ini akan diambil dari GajiController jika ada
            'tunjangan_komunikasi_id' => $data['tunjangan_komunikasi_id'] ?? null,

            // Data Snapshot (dari GajiController)
            'nama_karyawan_snapshot' => $data['nama_karyawan_snapshot'] ?? null,
            'nip_snapshot' => $data['nip_snapshot'] ?? null,
            'jabatan_snapshot' => $data['jabatan_snapshot'] ?? null,
        ];

        return Gaji::updateOrCreate(
            [
                'karyawan_id' => $data['karyawan_id'],
                'bulan' => $bulanCarbon,
            ],
            $saveData
        );
    }

    /**
     * Fungsi simulasi (tidak diubah)
     */
    public function calculateSimulasi(Karyawan $karyawan, array $data): array
    {
        $gajiPokok = (float) ($data['gaji_pokok'] ?? 0);
        $tunjJabatan = (float) ($data['tunj_jabatan'] ?? 0);
        $tunjAnak = (float) ($data['tunj_anak'] ?? 0);
        $tunjKomunikasi = (float) ($data['tunj_komunikasi'] ?? 0);
        $tunjPengabdian = (float) ($data['tunj_pengabdian'] ?? 0);
        $tunjKinerja = (float) ($data['tunj_kinerja'] ?? 0);
        $lembur = (float) ($data['lembur'] ?? 0);
        $potongan = (float) ($data['potongan'] ?? 0);
        $jumlahKehadiran = (int) ($data['jumlah_kehadiran'] ?? 0);

        $tunjanganKehadiranModel = TunjanganKehadiran::find($data['tunjangan_kehadiran_id']);
        $tunjanganPerKehadiran = $tunjanganKehadiranModel ? $tunjanganKehadiranModel->jumlah_tunjangan : 0;
        $tunjKehadiran = $jumlahKehadiran * $tunjanganPerKehadiran;

        $gajiBersih = ($gajiPokok + $tunjJabatan + $tunjAnak + $tunjKomunikasi +
            $tunjPengabdian + $tunjKinerja + $tunjKehadiran + $lembur) - $potongan;

        return [
            'karyawan' => $karyawan,
            'jumlah_hari_masuk' => $jumlahKehadiran,
            'gaji_bersih' => $gajiBersih,
            'rincian' => [

                'gaji_pokok' => $gajiPokok,
                'tunj_jabatan' => $tunjJabatan,
                'tunj_anak' => $tunjAnak,
                'tunj_komunikasi' => $tunjKomunikasi,
                'tunj_pengabdian' => $tunjPengabdian,
                'tunj_kinerja' => $tunjKinerja,
                'tunj_kehadiran' => $tunjKehadiran,
                'lembur' => $lembur,
                'potongan' => $potongan,
            ]
        ];
    }
}
