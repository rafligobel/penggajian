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

        try {
            // [PERBAIKAN] Pastikan $bulan diparsing dengan benar
            $tanggal = Carbon::parse($bulan)->startOfMonth();
        } catch (\Exception $e) {
            $tanggal = Carbon::now()->startOfMonth();
        }

        $gajiTersimpan = Gaji::where('karyawan_id', $karyawan->id)
            ->whereYear('bulan', $tanggal->year)
            ->whereMonth('bulan', $tanggal->month)
            ->first();

        $jumlahKehadiran = Absensi::where('nip', $karyawan->nip)
            ->whereYear('tanggal', $tanggal->year)
            ->whereMonth('tanggal', $tanggal->month)
            ->count();

        $tunjanganPerKehadiran = 0;
        $tunjanganKehadiranId = null;

        if ($gajiTersimpan && $gajiTersimpan->tunjanganKehadiran) {
            $tunjanganPerKehadiran = $gajiTersimpan->tunjanganKehadiran->jumlah_tunjangan;
            $tunjanganKehadiranId = $gajiTersimpan->tunjangan_kehadiran_id;
        } else {
            // Fallback jika gaji belum diset, gunakan tunjangan default
            $defaultTunjangan = TunjanganKehadiran::first();
            if ($defaultTunjangan) {
                $tunjanganKehadiranId = $defaultTunjangan->id;
                // [FIX] Logika fallback tunjangan per kehadiran (jika perlu)
                // $tunjanganPerKehadiran = $defaultTunjangan->jumlah_tunjangan; 
            }
        }
        $totalTunjanganKehadiran = $jumlahKehadiran * $tunjanganPerKehadiran;


        $gajiPokok = $gajiTersimpan->gaji_pokok ?? $karyawan->gaji_pokok_default ?? 0;
        $tunjanganJabatan = $karyawan->jabatan->tunj_jabatan ?? 0;
        $tunjAnak = $gajiTersimpan->tunj_anak ?? 0;
        $tunjKomunikasi = $gajiTersimpan->tunj_komunikasi ?? 0;
        $tunjPengabdian = $gajiTersimpan->tunj_pengabdian ?? 0;
        $tunjKinerja = $gajiTersimpan->tunj_kinerja ?? 0;
        $lembur = $gajiTersimpan->lembur ?? 0;
        $potongan = $gajiTersimpan->potongan ?? 0;

        $totalTunjangan = $tunjanganJabatan + $tunjAnak + $tunjKomunikasi + $tunjPengabdian + $tunjKinerja + $totalTunjanganKehadiran + $lembur;
        $gajiKotor = $gajiPokok + $totalTunjangan;
        $gajiBersih = $gajiKotor - $potongan;

        // [PERBAIKAN UTAMA DIMULAI DI SINI]
        return [
            // Data Info
            'karyawan_id' => $karyawan->id,
            'nama' => $karyawan->nama,
            'jabatan' => $karyawan->jabatan->nama_jabatan ?? 'N/A',
            'bulan' => $tanggal->format('Y-m'),
            'gaji_id' => $gajiTersimpan->id ?? null,
            'nip' => $karyawan->nip,
            'email' => $karyawan->email,

            // Data Teks (untuk form admin)
            'gaji_pokok_string' => 'Rp ' . number_format($gajiPokok, 0, ',', '.'),
            'total_kehadiran' => $jumlahKehadiran,
            'tunjangan_per_kehadiran_string' => 'Rp ' . number_format($tunjanganPerKehadiran, 0, ',', '.'),
            'total_tunjangan_kehadiran_string' => 'Rp ' . number_format($totalTunjanganKehadiran, 0, ',', '.'),
            'total_tunjangan_string' => 'Rp ' . number_format($totalTunjangan, 0, ',', '.'),
            'total_potongan_string' => 'Rp ' . number_format($potongan, 0, ',', '.'),
            'gaji_kotor_string' => 'Rp ' . number_format($gajiKotor, 0, ',', '.'),
            'gaji_bersih_string' => 'Rp ' . number_format($gajiBersih, 0, ',', '.'),

            // Data Numeric (untuk input form admin)
            'gaji_pokok_numeric' => $gajiPokok,
            'gaji_bersih_numeric' => $gajiBersih,
            'tunj_jabatan' => $tunjanganJabatan,
            'tunj_anak' => $tunjAnak,
            'tunj_komunikasi' => $tunjKomunikasi,
            'tunj_pengabdian' => $tunjPengabdian,
            'tunj_kinerja' => $tunjKinerja,
            'lembur' => $lembur,
            'potongan' => $potongan,
            'tunjangan_kehadiran_id' => $tunjanganKehadiranId,

            // =================================================================
            // [PERBAIKAN WAJIB] Key untuk slip_pdf.blade.php
            // =================================================================
            'gaji_pokok' => $gajiPokok,
            'jumlah_kehadiran' => $jumlahKehadiran,
            'tunj_kehadiran' => $totalTunjanganKehadiran,
            // 'tunj_jabatan' -> sudah ada di atas
            // 'tunj_anak' -> sudah ada di atas
            // 'tunj_komunikasi' -> sudah ada di atas
            // 'tunj_pengabdian' -> sudah ada di atas
            // 'tunj_kinerja' -> sudah ada di atas
            // 'lembur' -> sudah ada di atas
            // 'potongan' -> sudah ada di atas
            'gaji_bersih' => $gajiBersih,
        ];
    }

    /**
     * [PERBAIKAN] Menyimpan atau update data gaji.
     * Menggunakan format Y-m untuk input $data['bulan']
     */
    public function saveGaji(array $data): Gaji
    {
        $bulanCarbon = Carbon::createFromFormat('Y-m', $data['bulan'])->startOfMonth();

        return Gaji::updateOrCreate(
            [
                'karyawan_id' => $data['karyawan_id'],
                'bulan' => $bulanCarbon, // Simpan sebagai objek Carbon YYYY-MM-01
            ],
            [
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
