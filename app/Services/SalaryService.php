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
use Illuminate\Support\Facades\Cache;

class SalaryService
{
    /**
     * [OPTIMASI] Mengkalkulasi detail gaji untuk form admin.
     * Menambahkan parameter $preloadedData untuk mencegah N+1 Query.
     * * @param Karyawan $karyawan
     * @param string $bulan Format Y-m
     * @param array $preloadedData Array opsional berisi 'gaji_record' dan 'jumlah_kehadiran'
     */
    public function calculateDetailsForForm(Karyawan $karyawan, string $bulan, array $preloadedData = []): array
    {
        // Cek relation, jika belum ada load (Mencegah query berulang jika sudah di-eager load)
        if (!$karyawan->relationLoaded('jabatan')) {
            $karyawan->load('jabatan');
        }

        try {
            $tanggal = Carbon::parse($bulan)->startOfMonth();
        } catch (\Exception $e) {
            $tanggal = Carbon::now()->startOfMonth();
        }

        // 1. [OPTIMASI] Cek Data Gaji (Prioritaskan Data Preloaded)
        if (array_key_exists('gaji_record', $preloadedData)) {
            $gajiTersimpan = $preloadedData['gaji_record'];
        } else {
            // Fallback: Query sendiri jika tidak disediakan (Backward Compatibility)
            $gajiTersimpan = Gaji::with(['tunjanganKehadiran', 'penilaianKinerjas'])
                ->where('karyawan_id', $karyawan->id)
                ->whereYear('bulan', $tanggal->year)
                ->whereMonth('bulan', $tanggal->month)
                ->first();
        }

        // 2. [OPTIMASI] Hitung Kehadiran (Prioritaskan Data Preloaded)
        if (array_key_exists('jumlah_kehadiran', $preloadedData)) {
            $jumlahKehadiran = $preloadedData['jumlah_kehadiran'];
        } else {
            // Fallback: Hitung query sendiri
            $jumlahKehadiran = Absensi::where('karyawan_id', $karyawan->id)
                ->whereYear('tanggal', $tanggal->year)
                ->whereMonth('tanggal', $tanggal->month)
                ->count();
        }

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

        // Ambil aturan default Tunjangan Kehadiran (Direct DB Query)
        $aturanDefaultKehadiran = TunjanganKehadiran::orderBy('id', 'asc')->first();

        if ($gajiTersimpan) {
            // --- KASUS 1: Gaji Bulan Ini SUDAH ADA ---
            $gajiId = $gajiTersimpan->id;

            // [LOGIKA PENGAMAN SEJARAH]
            // Cek apakah periode gaji ini adalah "Masa Lalu" (sebelum bulan ini).
            // Jika YA (History) -> Gunakan SNAPSHOT (Jangan ubah data lama).
            // Jika TIDAK (Current) -> Gunakan MASTER (Auto-update agar praktis).
            $isHistory = $tanggal->lt(Carbon::now()->startOfMonth());

            if ($isHistory) {
                // --- MODE HISTORY: Hormati Data Lama ---
                $gajiPokok = $gajiTersimpan->gaji_pokok;
                $tunjJabatan = $gajiTersimpan->tunj_jabatan;
                $tunjAnak = $gajiTersimpan->tunj_anak;
                $tunjPengabdian = $gajiTersimpan->tunj_pengabdian;
            } else {
                // --- MODE CURRENT: Auto-Update dari Master ---
                $gajiPokok = $karyawan->gaji_pokok ?? ($karyawan->gaji_pokok_default ?? 0);
                $tunjJabatan = $karyawan->jabatan->tunj_jabatan ?? 0;
                
                // Hitung ulang dinamis berdasarkan data master saat ini
                $tunjanganDinamis = $this->hitungTunjanganDinamisDefault($karyawan, $gajiPokok);
                $tunjAnak = $tunjanganDinamis['tunj_anak'];
                $tunjPengabdian = $tunjanganDinamis['tunj_pengabdian'];
            }
            
            // Komponen ini ambil dari snapshot (karena inputan manual/variabel)
            $tunjKomunikasi = $gajiTersimpan->tunj_komunikasi;
            $tunjKinerja = $gajiTersimpan->tunj_kinerja;
            $lembur = $gajiTersimpan->lembur;
            $potongan = $gajiTersimpan->potongan;

            // Gunakan null safe operator atau cek relasi
            $penilaianKinerja = $gajiTersimpan->penilaianKinerjas
                ? $gajiTersimpan->penilaianKinerjas->pluck('skor', 'indikator_kinerja_id')
                : collect([]);

            $tunjanganKehadiranId = $gajiTersimpan->tunjangan_kehadiran_id;

            if ($gajiTersimpan->tunjanganKehadiran) {
                $tunjanganPerKehadiran = $gajiTersimpan->tunjanganKehadiran->jumlah_tunjangan;
            } elseif ($aturanDefaultKehadiran) {
                $tunjanganPerKehadiran = $aturanDefaultKehadiran->jumlah_tunjangan;
            }

            // [FIX] Gunakan ID yang tersimpan langsung
            $tunjanganKomunikasiId = $gajiTersimpan->tunjangan_komunikasi_id;
            
        } else {
            // --- KASUS 2: Gaji Bulan Ini BELUM ADA ---

            // Cari Gaji Terakhir (bulan apa saja) untuk menyalin settingan sebelumnya
            $gajiTerakhir = Gaji::with('tunjanganKehadiran')
                ->where('karyawan_id', $karyawan->id)
                ->orderBy('bulan', 'desc')
                ->first();

            if ($gajiTerakhir) {
                // COPY dari riwayat sebelumnya untuk komponen variabel
                // TAPI untuk komponen tetap (Gaji Pokok & Jabatan), SELALU ambil dari Master Data
                // agar kenaikan gaji di master data langsung ngefek ke bulan baru.
                $gajiPokok = $karyawan->gaji_pokok ?? ($karyawan->gaji_pokok_default ?? 0);
                
                // Jika ingin copy yang lain:
                $tunjAnak = $gajiTerakhir->tunj_anak;
                $tunjPengabdian = $gajiTerakhir->tunj_pengabdian;
                $tunjanganKehadiranId = $gajiTerakhir->tunjangan_kehadiran_id;
                
                // Copy Tunjangan Komunikasi ID
                $tunjanganKomunikasiId = $gajiTerakhir->tunjangan_komunikasi_id;

                if ($gajiTerakhir->tunjanganKehadiran) {
                    $tunjanganPerKehadiran = $gajiTerakhir->tunjanganKehadiran->jumlah_tunjangan;
                } elseif ($aturanDefaultKehadiran) {
                    $tunjanganPerKehadiran = $aturanDefaultKehadiran->jumlah_tunjangan;
                }
            } else {
                // KARYAWAN BARU (Belum pernah terima gaji)
                $gajiPokok = $karyawan->gaji_pokok_default ?? 0;

                // Hitung tunjangan dinamis default
                $tunjanganDinamis = $this->hitungTunjanganDinamisDefault($karyawan, $gajiPokok);
                $tunjAnak = $tunjanganDinamis['tunj_anak'];
                $tunjPengabdian = $tunjanganDinamis['tunj_pengabdian'];

                // Ambil default tunjangan kehadiran
                if ($aturanDefaultKehadiran) {
                    $tunjanganKehadiranId = $aturanDefaultKehadiran->id;
                    $tunjanganPerKehadiran = $aturanDefaultKehadiran->jumlah_tunjangan;
                }
            }
        }

        // 3. Kalkulasi Total
        $tunjKehadiran = $jumlahKehadiran * $tunjanganPerKehadiran;

        // Hitung Total Pendapatan Kotor (Gross) agar konsisten di PDF/View
        $totalPendapatan = $gajiPokok + $tunjJabatan + $tunjKehadiran + $tunjAnak + $tunjKomunikasi + $tunjPengabdian + $tunjKinerja + $lembur;

        $gajiBersihNumeric = $totalPendapatan - $potongan;
        $gajiBersihString = 'Rp ' . number_format($gajiBersihNumeric, 0, ',', '.');

        return [
            'gaji_id' => $gajiId,
            'karyawan_id' => $karyawan->id,
            'nip' => $karyawan->nip,
            'nama' => $karyawan->nama,
            'email' => $karyawan->email,
            'jabatan' => $karyawan->jabatan->nama_jabatan ?? 'Tidak Ada Jabatan',
            'bulan' => $tanggal->format('Y-m'),

            // Data Numerik
            'gaji_pokok' => (float) $gajiPokok,
            'tunj_jabatan' => (float) $tunjJabatan,
            'tunj_anak' => (float) $tunjAnak,
            'tunj_komunikasi' => (float) $tunjKomunikasi,
            'tunj_pengabdian' => (float) $tunjPengabdian,
            'tunj_kinerja' => (float) $tunjKinerja,
            'lembur' => (float) $lembur,
            'potongan' => (float) $potongan,
            'tunj_kehadiran' => (float) $tunjKehadiran,

            // Data ID
            'tunjangan_kehadiran_id' => $tunjanganKehadiranId,
            'tunjangan_komunikasi_id' => $tunjanganKomunikasiId,

            // Data Tampilan
            'total_kehadiran' => $jumlahKehadiran,
            'jumlah_kehadiran' => $jumlahKehadiran,
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

            'penilaian_kinerja' => $penilaianKinerja,
            'tunj_kehadiran_rincian' => [
                'per_hari' => $tunjanganPerKehadiran,
                'total' => $tunjKehadiran,
                'total_string' => 'Rp ' . number_format($tunjKehadiran, 0, ',', '.'),
            ],
            
            // Total Pendapatan Kotor (Pre-calculated)
            'total_pendapatan' => $totalPendapatan,
            'total_pendapatan_string' => 'Rp ' . number_format($totalPendapatan, 0, ',', '.'),
        ];
    }

    /**
     * Fungsi helper untuk menghitung tunjangan dinamis HANYA untuk karyawan baru.
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
        ];
    }

    /**
     * Menyimpan atau update data gaji.
     */
    public function saveGaji(array $data): Gaji
    {
        $bulanCarbon = Carbon::createFromFormat('Y-m', $data['bulan'])->startOfMonth();

        $saveData = [
            'gaji_pokok' => $data['gaji_pokok'] ?? 0,
            'tunj_anak' => $data['tunj_anak'] ?? 0,
            'tunj_komunikasi' => $data['tunj_komunikasi'] ?? 0,
            'tunj_pengabdian' => $data['tunj_pengabdian'] ?? 0,
            'tunj_kinerja' => $data['tunj_kinerja'] ?? 0,
            'lembur' => $data['lembur'] ?? 0,
            'potongan' => $data['potongan'] ?? 0,
            'tunjangan_kehadiran_id' => $data['tunjangan_kehadiran_id'],
            'tunj_jabatan' => $data['tunj_jabatan'] ?? 0,
            'tunjangan_komunikasi_id' => $data['tunjangan_komunikasi_id'] ?? null,
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
     * Fungsi simulasi.
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
