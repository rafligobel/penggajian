<?php

namespace App\Services;

use App\Models\Karyawan;
use App\Models\Gaji;
use App\Models\Absensi;
use App\Models\TunjanganKehadiran;
use Carbon\Carbon;

class SalaryService
{
    /**
     * [PERBAIKAN] Mengkalkulasi detail gaji untuk form admin atau slip.
     * Dibuat lebih aman (robust) untuk menangani input bulan yang bervariasi.
     */
    public function calculateDetailsForForm(Karyawan $karyawan, string $bulan): array
    {
        $karyawan->loadMissing('jabatan');

        // [PERBAIKAN] Standarisasi input bulan.
        // Baik '2025-10' (dari filter) atau '2025-10-01' (dari $gaji->bulan)
        // akan diubah menjadi objek Carbon tanggal 1.
        try {
            $tanggal = Carbon::parse($bulan)->startOfMonth();
        } catch (\Exception $e) {
            // Fallback jika format bulan tidak valid
            $tanggal = Carbon::now()->startOfMonth();
        }

        // Ambil data gaji yang sudah tersimpan di database
        $gajiTersimpan = Gaji::where('karyawan_id', $karyawan->id)
            ->whereYear('bulan', $tanggal->year)
            ->whereMonth('bulan', $tanggal->month)
            ->first();

        // Hitung total kehadiran
        $jumlahKehadiran = Absensi::where('nip', $karyawan->nip)
            ->whereYear('tanggal', $tanggal->year)
            ->whereMonth('tanggal', $tanggal->month)
            ->count();

        // [PERBAIKAN] Logika aman untuk mengambil tunjangan per kehadiran
        $tunjanganPerKehadiran = 0;
        $tunjanganKehadiranId = null;

        if ($gajiTersimpan && $gajiTersimpan->tunjanganKehadiran) {
            // 1. Ambil dari data Gaji yang tersimpan
            $tunjanganPerKehadiran = $gajiTersimpan->tunjanganKehadiran->jumlah_tunjangan;
            $tunjanganKehadiranId = $gajiTersimpan->tunjangan_kehadiran_id;
        } else {
            // 2. Jika Gaji belum ada, ambil tunjangan default (baris pertama)
            $defaultTunjangan = TunjanganKehadiran::first();
            if ($defaultTunjangan) {
                // Jangan hitung totalnya dulu, biarkan 0, tapi set ID-nya
                // $tunjanganPerKehadiran = $defaultTunjangan->jumlah_tunjangan; // (Biarkan 0 agar tidak membingungkan)
                $tunjanganKehadiranId = $defaultTunjangan->id;
            }
        }
        // Jika $gajiTersimpan ada, tunjanganPerKehadiran akan berisi nilai dari DB
        // Jika $gajiTersimpan tidak ada, tunjanganPerKehadiran akan 0
        $totalTunjanganKehadiran = $jumlahKehadiran * $tunjanganPerKehadiran;


        // Ambil data dari tabel Gaji jika ada, jika tidak, ambil default dari Karyawan/Jabatan
        // (Ganti 'gaji_pokok_default' jika nama kolom di tabel karyawan Anda berbeda)
        $gajiPokok = $gajiTersimpan->gaji_pokok ?? $karyawan->gaji_pokok_default ?? 0;
        $tunjanganJabatan = $karyawan->jabatan->tunjangan_jabatan ?? 0;

        // Ambil data tunjangan lain dari Gaji jika ada, jika tidak, 0
        $tunjAnak = $gajiTersimpan->tunj_anak ?? 0;
        $tunjKomunikasi = $gajiTersimpan->tunj_komunikasi ?? 0;
        $tunjPengabdian = $gajiTersimpan->tunj_pengabdian ?? 0;
        $tunjKinerja = $gajiTersimpan->tunj_kinerja ?? 0;
        $lembur = $gajiTersimpan->lembur ?? 0;
        $potongan = $gajiTersimpan->potongan ?? 0;

        // Kalkulasi
        $totalTunjangan = $tunjanganJabatan + $tunjAnak + $tunjKomunikasi + $tunjPengabdian + $tunjKinerja + $totalTunjanganKehadiran + $lembur;
        $gajiKotor = $gajiPokok + $totalTunjangan;
        $gajiBersih = $gajiKotor - $potongan;

        // [PERBAIKAN] Kembalikan SEMUA data yang dibutuhkan oleh UI (form input & teks)
        return [
            'karyawan_id' => $karyawan->id,
            'nama' => $karyawan->nama,
            'jabatan' => $karyawan->jabatan->nama_jabatan ?? 'N/A',
            'bulan' => $tanggal->format('Y-m'),
            'gaji_id' => $gajiTersimpan->id ?? null,

            // Data Teks (untuk ditampilkan)
            'gaji_pokok_string' => 'Rp ' . number_format($gajiPokok, 0, ',', '.'),
            'total_kehadiran' => $jumlahKehadiran,
            'tunjangan_per_kehadiran_string' => 'Rp ' . number_format($tunjanganPerKehadiran, 0, ',', '.'),
            'total_tunjangan_kehadiran_string' => 'Rp ' . number_format($totalTunjanganKehadiran, 0, ',', '.'),
            'total_tunjangan_string' => 'Rp ' . number_format($totalTunjangan, 0, ',', '.'),
            'total_potongan_string' => 'Rp ' . number_format($potongan, 0, ',', '.'),
            'gaji_kotor_string' => 'Rp ' . number_format($gajiKotor, 0, ',', '.'),
            'gaji_bersih_string' => 'Rp ' . number_format($gajiBersih, 0, ',', '.'),

            // Data Numeric (untuk nilai input form)
            'gaji_pokok_numeric' => $gajiPokok,
            'tunj_anak' => $tunjAnak,
            'tunj_komunikasi' => $tunjKomunikasi,
            'tunj_pengabdian' => $tunjPengabdian,
            'tunj_kinerja' => $tunjKinerja,
            'lembur' => $lembur,
            'potongan' => $potongan,
            'tunjangan_kehadiran_id' => $tunjanganKehadiranId,
        ];
    }

    /**
     * [PERBAIKAN] Menyimpan atau update data gaji.
     * Menggunakan format Y-m untuk input $data['bulan']
     */
    public function saveGaji(array $data): Gaji
    {
        // [PERBAIKAN] Pastikan bulan disimpan sebagai tanggal 1
        // Ini mengubah input '2025-10' menjadi '2025-10-01'
        $bulanCarbon = Carbon::createFromFormat('Y-m', $data['bulan'])->startOfMonth();

        return Gaji::updateOrCreate(
            [
                'karyawan_id' => $data['karyawan_id'],
                'bulan' => $bulanCarbon, // Simpan sebagai objek Carbon YYYY-MM-01
            ],
            [
                // Gunakan ?? 0 untuk memastikan tidak ada nilai null
                'gaji_pokok' => $data['gaji_pokok'] ?? 0,
                'tunj_anak' => $data['tunj_anak'] ?? 0,
                'tunj_komunikasi' => $data['tunj_komunikasi'] ?? 0,
                'tunj_pengabdian' => $data['tunj_pengabdian'] ?? 0,
                'tunj_kinerja' => $data['tunj_kinerja'] ?? 0,
                'lembur' => $data['lembur'] ?? 0,
                'potongan' => $data['potongan'] ?? 0,
                'tunjangan_kehadiran_id' => $data['tunjangan_kehadiran_id'],
            ]
        );
    }

    /**
     * Fungsi simulasi (sudah ada di file Anda, tidak diubah)
     */
    public function calculateSimulasi(array $data): array
    {
        $gajiPokok = (float) $data['gaji_pokok'];
        $tunjJabatan = (float) $data['tunj_jabatan'];
        $tunjAnak = (float) $data['tunj_anak'];
        $tunjKomunikasi = (float) $data['tunj_komunikasi'];
        $tunjPengabdian = (float) $data['tunj_pengabdian'];
        $tunjKinerja = (float) $data['tunj_kinerja'];
        $lembur = (float) $data['lembur'];
        $potongan = (float) $data['potongan'];
        $jumlahKehadiran = (int) $data['jumlah_kehadiran'];

        $tunjanganKehadiranModel = TunjanganKehadiran::find($data['tunjangan_kehadiran_id']);
        $tunjanganPerKehadiran = $tunjanganKehadiranModel ? $tunjanganKehadiranModel->jumlah_tunjangan : 0;

        $tunjKehadiran = $jumlahKehadiran * $tunjanganPerKehadiran;

        $gajiBersih = ($gajiPokok + $tunjJabatan + $tunjAnak + $tunjKomunikasi +
            $tunjPengabdian + $tunjKinerja + $tunjKehadiran + $lembur) - $potongan;

        return [
            'gaji_pokok' => $gajiPokok,
            'tunj_jabatan' => $tunjJabatan,
            'tunj_anak' => $tunjAnak,
            'tunj_komunikasi' => $tunjKomunikasi,
            'tunj_pengabdian' => $tunjPengabdian,
            'tunj_kinerja' => $tunjKinerja,
            'tunj_kehadiran' => $tunjKehadiran,
            'lembur' => $lembur,
            'potongan' => $potongan,
            'gaji_bersih' => $gajiBersih,
        ];
    }
}
