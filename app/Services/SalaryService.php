<?php
// File: app/Services/SalaryService.php

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
     */
    public function calculateDetailsForForm(Karyawan $karyawan, string $bulan): array
    {
        $karyawan->loadMissing('jabatan');

        try {
            $tanggal = Carbon::parse($bulan)->startOfMonth();
        } catch (\Exception $e) {
            $tanggal = Carbon::now()->startOfMonth();
        }

        $gajiTersimpan = Gaji::with(['tunjanganKehadiran', 'penilaianKinerjas'])
            ->where('karyawan_id', $karyawan->id)
            ->whereYear('bulan', $tanggal->year)
            ->whereMonth('bulan', $tanggal->month)
            ->first();

        $jumlahKehadiran = Absensi::where('karyawan_id', $karyawan->id)
            ->whereYear('tanggal', $tanggal->year)
            ->whereMonth('tanggal', $tanggal->month)
            ->count();

        $tunjanganPerKehadiran = 0;
        $tunjanganKehadiranId = null;

        // --- AWAL PERBAIKAN ERROR 'is_default' ---
        if ($gajiTersimpan && $gajiTersimpan->tunjanganKehadiran) {
            // 1. Jika gaji sudah ada, pakai ID dari data tersimpan
            $tunjanganPerKehadiran = $gajiTersimpan->tunjanganKehadiran->jumlah_tunjangan;
            $tunjanganKehadiranId = $gajiTersimpan->tunjangan_kehadiran_id;
        } else {
            // 2. Jika gaji BELUM ada, ambil aturan pertama sebagai default
            //    (Mengganti logika `where('is_default', true)`)
            $aturanDefault = TunjanganKehadiran::orderBy('id', 'asc')->first();

            if ($aturanDefault) {
                $tunjanganPerKehadiran = $aturanDefault->jumlah_tunjangan;
                $tunjanganKehadiranId = $aturanDefault->id;
            }
        }
        // --- AKHIR PERBAIKAN ERROR 'is_default' ---

        $gajiPokok = $gajiTersimpan->gaji_pokok ?? $karyawan->gaji_pokok_default ?? 0;
        $tunjJabatan = $karyawan->jabatan->tunj_jabatan ?? 0;

        $tunjAnak = $gajiTersimpan->tunj_anak ?? 0;
        $tunjKomunikasi = $gajiTersimpan->tunj_komunikasi ?? 0;
        $tunjPengabdian = $gajiTersimpan->tunj_pengabdian ?? 0;
        $tunjKinerja = $gajiTersimpan->tunj_kinerja ?? 0;
        $lembur = $gajiTersimpan->lembur ?? 0;
        $potongan = $gajiTersimpan->potongan ?? 0;

        $tunjKehadiran = $jumlahKehadiran * $tunjanganPerKehadiran;

        $gajiBersihNumeric = ($gajiPokok + $tunjJabatan + $tunjKehadiran + $tunjAnak + $tunjKomunikasi + $tunjPengabdian + $tunjKinerja + $lembur) - $potongan;
        $gajiBersihString = 'Rp ' . number_format($gajiBersihNumeric, 0, ',', '.');

        $result = [
            'gaji_id' => $gajiTersimpan->id ?? null,
            'karyawan_id' => $karyawan->id,
            'nip' => $karyawan->nip,
            'nama' => $karyawan->nama,
            'email' => $karyawan->email,
            'jabatan' => $karyawan->jabatan->nama_jabatan ?? 'Tidak Ada Jabatan',
            'bulan' => $tanggal->format('Y-m'),

            'gaji_pokok' => (float) $gajiPokok,
            'tunj_jabatan' => (float) $tunjJabatan,
            'tunj_anak' => (float) $tunjAnak,
            'tunj_komunikasi' => (float) $tunjKomunikasi,
            'tunj_pengabdian' => (float) $tunjPengabdian,
            'tunj_kinerja' => (float) $tunjKinerja,
            'lembur' => (float) $lembur,
            'potongan' => (float) $potongan,
            'jumlah_kehadiran' => $jumlahKehadiran,
            'tunj_kehadiran' => (float) $tunjKehadiran,
            'gaji_bersih' => $gajiBersihString,
            'total_kehadiran' => $jumlahKehadiran,
            'tunjangan_kehadiran_id' => $tunjanganKehadiranId,
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

            'penilaian_kinerja' => $gajiTersimpan ? $gajiTersimpan->penilaianKinerjas->pluck('skor', 'indikator_kinerja_id') : [],

            'tunj_kehadiran_rincian' => [
                'per_hari' => $tunjanganPerKehadiran,
                'total' => $tunjKehadiran,
                'total_string' => 'Rp ' . number_format($tunjKehadiran, 0, ',', '.'),
            ],
        ];

        return $result;
    }

    /**
     * [PERBAIKAN] Menyimpan atau update data gaji.
     * Termasuk menyimpan data snapshot
     */
    public function saveGaji(array $data): Gaji
    {
        $bulanCarbon = Carbon::createFromFormat('Y-m', $data['bulan'])->startOfMonth();

        return Gaji::updateOrCreate(
            [
                'karyawan_id' => $data['karyawan_id'],
                'bulan' => $bulanCarbon,
            ],
            [
                // Data Snapshot (dari GajiController)
                'nama_karyawan_snapshot' => $data['nama_karyawan_snapshot'] ?? null,
                'nip_snapshot' => $data['nip_snapshot'] ?? null,
                'jabatan_snapshot' => $data['jabatan_snapshot'] ?? null,

                // Data Finansial
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
