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
     * Menyimpan atau memperbarui data gaji berdasarkan input dari form.
     */
    public function saveSalaryData(array $dataGaji): Karyawan
    {
        $karyawan = Karyawan::findOrFail($dataGaji['karyawan_id']);
        $bulan = $dataGaji['bulan'];
        $tanggal = Carbon::createFromFormat('Y-m', $bulan);

        // Ambil pengaturan tunjangan kehadiran yang dipilih
        $tunjanganKehadiranSetting = TunjanganKehadiran::find($dataGaji['tunjangan_kehadiran_id']);
        $tarifKehadiran = $tunjanganKehadiranSetting->jumlah_tunjangan ?? 0;

        // Hitung jumlah kehadiran dari tabel absensi
        $jumlahKehadiran = Absensi::where('nip', $karyawan->nip)
            ->whereMonth('tanggal', $tanggal->month)
            ->whereYear('tanggal', $tanggal->year)
            ->count();

        $totalTunjanganKehadiran = $jumlahKehadiran * $tarifKehadiran;

        // Siapkan data untuk disimpan
        $gajiData = [
            'gaji_pokok' => $dataGaji['gaji_pokok'] ?? 0,
            'tunj_anak' => $dataGaji['tunj_anak'] ?? 0,
            'tunj_pengabdian' => $dataGaji['tunj_pengabdian'] ?? 0,
            'lembur' => $dataGaji['lembur'] ?? 0,
            'potongan' => $dataGaji['potongan'] ?? 0,
            'tunj_komunikasi' => $dataGaji['tunj_komunikasi'] ?? 0,
            'tunj_kinerja' => $dataGaji['tunj_kinerja'] ?? 0,
            'kelebihan_jam' => $dataGaji['kelebihan_jam'] ?? 0,
            'tunj_jabatan' => $karyawan->jabatan->tunj_jabatan ?? 0,
            'tunj_kehadiran' => $totalTunjanganKehadiran,
            'jumlah_kehadiran' => $jumlahKehadiran,
        ];

        // Gunakan updateOrCreate untuk membuat atau memperbarui data
        $gaji = Gaji::updateOrCreate(
            ['karyawan_id' => $karyawan->id, 'bulan' => $bulan],
            $gajiData
        );

        // Hitung total gaji bersih setelah data disimpan/diperbarui
        $totalPendapatan = $gaji->gaji_pokok + $gaji->total_tunjangan + $gaji->pendapatan_lainnya;
        $gajiBersih = $totalPendapatan - $gaji->potongan;

        $gaji->gaji_bersih = $gajiBersih;
        $gaji->save();

        return $karyawan;
    }
}
