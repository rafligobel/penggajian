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
     * Menghitung dan mengambil detail gaji untuk seorang karyawan pada bulan tertentu.
     * Method ini TIDAK menyimpan ke database, hanya melakukan kalkulasi untuk ditampilkan di form.
     *
     * @param Karyawan $karyawan
     * @param string $bulan Format 'Y-m'
     * @return array
     */
    public function calculateDetailsForForm(Karyawan $karyawan, string $bulan): array
    {
        $tanggal = Carbon::createFromFormat('Y-m', $bulan);
        $settingTunjangan = TunjanganKehadiran::first();
        $tarifKehadiran = $settingTunjangan->jumlah_tunjangan ?? 0;

        $gajiTersimpan = Gaji::where('karyawan_id', $karyawan->id)
            ->where('bulan', $bulan)
            ->first();

        $jumlahKehadiran = Absensi::where('nip', $karyawan->nip)
            ->whereMonth('tanggal', $tanggal->month)
            ->whereYear('tanggal', $tanggal->year)
            ->count();

        $totalTunjanganKehadiran = $jumlahKehadiran * $tarifKehadiran;

        // --- PERBAIKAN UTAMA DI SINI ---
        // Selalu siapkan array data yang lengkap dengan nilai default
        $data = [
            'karyawan' => $karyawan,
            'gaji' => $gajiTersimpan,
            'bulan' => $bulan,
            'jumlah_kehadiran' => $jumlahKehadiran,
            'tunjangan_kehadiran_id' => $settingTunjangan->id ?? null,
            'gaji_pokok' => $gajiTersimpan->gaji_pokok ?? $karyawan->gaji_pokok ?? 0,
            'tunj_jabatan' => $gajiTersimpan->tunj_jabatan ?? $karyawan->jabatan->tunj_jabatan ?? 0,
            'tunj_kehadiran' => $gajiTersimpan->tunj_kehadiran ?? $totalTunjanganKehadiran,
            'tunj_anak' => $gajiTersimpan->tunj_anak ?? 0,
            'tunj_komunikasi' => $gajiTersimpan->tunj_komunikasi ?? 0,
            'tunj_pengabdian' => $gajiTersimpan->tunj_pengabdian ?? 0,
            'tunj_kinerja' => $gajiTersimpan->tunj_kinerja ?? 0,
            'lembur' => $gajiTersimpan->lembur ?? 0,
            'kelebihan_jam' => $gajiTersimpan->kelebihan_jam ?? 0,
            'potongan' => $gajiTersimpan->potongan ?? 0,
        ];

        // Hitung total pendapatan dan gaji bersih berdasarkan data yang ada
        $totalPendapatan =
            $data['gaji_pokok'] + $data['tunj_jabatan'] + $data['tunj_kehadiran'] +
            $data['tunj_anak'] + $data['tunj_komunikasi'] + $data['tunj_pengabdian'] +
            $data['tunj_kinerja'] + $data['lembur'] + $data['kelebihan_jam'];

        $gajiBersih = $totalPendapatan - $data['potongan'];

        // Pastikan semua key yang dibutuhkan view ada di dalam array
        $data['total_tunjangan'] = $totalPendapatan - $data['gaji_pokok'];
        $data['pendapatan_lainnya'] = $data['lembur'] + $data['kelebihan_jam'];
        $data['gaji_bersih'] = $gajiTersimpan->gaji_bersih ?? $gajiBersih; // Prioritaskan yg tersimpan

        return $data;
    }

    /**
     * Menyimpan atau memperbarui data gaji berdasarkan input dari form.
     * Method ini telah diperbaiki untuk melakukan kalkulasi yang benar dan lengkap.
     */
    public function saveOrUpdateSalary(array $dataForm): Gaji
    {
        $karyawan = Karyawan::findOrFail($dataForm['karyawan_id']);
        $bulan = $dataForm['bulan'];
        $tanggal = Carbon::createFromFormat('Y-m', $bulan);

        $tunjanganKehadiranSetting = TunjanganKehadiran::find($dataForm['tunjangan_kehadiran_id']);
        $tarifKehadiran = $tunjanganKehadiranSetting->jumlah_tunjangan ?? 0;

        $jumlahKehadiran = Absensi::where('nip', $karyawan->nip)
            ->whereMonth('tanggal', $tanggal->month)
            ->whereYear('tanggal', $tanggal->year)
            ->count();

        $totalTunjanganKehadiran = $jumlahKehadiran * $tarifKehadiran;

        // Siapkan semua data yang akan disimpan dari form
        $saveData = [
            'gaji_pokok' => $dataForm['gaji_pokok'] ?? 0,
            'tunj_anak' => $dataForm['tunj_anak'] ?? 0,
            'tunj_pengabdian' => $dataForm['tunj_pengabdian'] ?? 0,
            'lembur' => $dataForm['lembur'] ?? 0,
            'potongan' => $dataForm['potongan'] ?? 0,
            'tunj_komunikasi' => $dataForm['tunj_komunikasi'] ?? 0,
            'tunj_kinerja' => $dataForm['tunj_kinerja'] ?? 0,
            'kelebihan_jam' => $dataForm['kelebihan_jam'] ?? 0,
            'tunj_jabatan' => $dataForm['tunj_jabatan'] ?? 0,
            'jumlah_kehadiran' => $jumlahKehadiran,
            'tunj_kehadiran' => $totalTunjanganKehadiran,
        ];

        // Kalkulasi total pendapatan berdasarkan SEMUA komponen yang ada
        $totalPendapatan =
            ($saveData['gaji_pokok']) +
            ($saveData['tunj_anak']) +
            ($saveData['tunj_pengabdian']) +
            ($saveData['lembur']) +
            ($saveData['tunj_komunikasi']) +
            ($saveData['tunj_kinerja']) +
            ($saveData['kelebihan_jam']) +
            ($saveData['tunj_jabatan']) +
            ($saveData['tunj_kehadiran']);

        // Hitung gaji bersih final
        $gajiBersih = $totalPendapatan - ($saveData['potongan']);
        $saveData['gaji_bersih'] = $gajiBersih;

        // Gunakan updateOrCreate untuk menyimpan atau memperbarui data gaji
        return Gaji::updateOrCreate(
            ['karyawan_id' => $karyawan->id, 'bulan' => $bulan],
            $saveData
        );
    }
}
