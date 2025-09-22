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
     * Method ini TIDAK menyimpan ke database, hanya melakukan kalkulasi.
     *
     * @param Karyawan $karyawan
     * @param string $bulan Format 'Y-m'
     * @param int|null $tarifKehadiran Kustom (opsional)
     * @return array
     */
    public function calculateAndFetchDetails(Karyawan $karyawan, string $bulan, ?int $tarifKehadiran = null): array
    {
        $tanggal = Carbon::createFromFormat('Y-m', $bulan);

        // Jika tarif tidak diberikan, ambil dari default setting
        if ($tarifKehadiran === null) {
            $setting = TunjanganKehadiran::first();
            $tarifKehadiran = $setting ? $setting->jumlah_tunjangan : 0;
        }

        // Coba cari data gaji yang sudah ada di database untuk bulan ini
        $gajiTersimpan = Gaji::where('karyawan_id', $karyawan->id)
            ->where('bulan', $bulan)
            ->first();

        // Hitung jumlah kehadiran dari tabel absensi
        $jumlahKehadiran = Absensi::where('nip', $karyawan->nip)
            ->whereMonth('tanggal', $tanggal->month)
            ->whereYear('tanggal', $tanggal->year)
            ->count();

        $totalTunjanganKehadiran = $jumlahKehadiran * $tarifKehadiran;

        // Prioritaskan data dari database jika ada, jika tidak, gunakan data default dari karyawan/jabatan
        $gajiPokok = $gajiTersimpan->gaji_pokok ?? $karyawan->jabatan->gaji_pokok ?? 0;
        $tunjJabatan = $gajiTersimpan->tunj_jabatan ?? $karyawan->jabatan->tunj_jabatan ?? 0;

        // Kumpulkan semua data, baik yang sudah tersimpan maupun yang baru dihitung
        $data = [
            'karyawan' => $karyawan,
            'gaji' => $gajiTersimpan, // Kirim juga data gaji yang ada (bisa null)
            'bulan' => $bulan,
            'jumlah_kehadiran' => $jumlahKehadiran,
            'gaji_pokok' => $gajiPokok,
            'tunj_jabatan' => $tunjJabatan,
            'tunj_kehadiran' => $gajiTersimpan->tunj_kehadiran ?? $totalTunjanganKehadiran,
            'tunj_anak' => $gajiTersimpan->tunj_anak ?? 0,
            'tunj_komunikasi' => $gajiTersimpan->tunj_komunikasi ?? 0,
            'tunj_pengabdian' => $gajiTersimpan->tunj_pengabdian ?? 0,
            'tunj_kinerja' => $gajiTersimpan->tunj_kinerja ?? 0,
            'lembur' => $gajiTersimpan->lembur ?? 0,
            'kelebihan_jam' => $gajiTersimpan->kelebihan_jam ?? 0,
            'potongan' => $gajiTersimpan->potongan ?? 0,
        ];

        // Hitung total pendapatan dan gaji bersih
        $totalTunjangan = $data['tunj_jabatan'] + $data['tunj_kehadiran'] + $data['tunj_anak'] + $data['tunj_komunikasi'] + $data['tunj_pengabdian'] + $data['tunj_kinerja'];
        $pendapatanLain = $data['lembur'] + $data['kelebihan_jam'];
        $totalPendapatan = $data['gaji_pokok'] + $totalTunjangan + $pendapatanLain;
        $gajiBersih = $totalPendapatan - $data['potongan'];

        $data['total_tunjangan'] = $totalTunjangan;
        $data['pendapatan_lainnya'] = $pendapatanLain;
        $data['gaji_bersih'] = $gajiBersih;

        return $data;
    }

    /**
     * Menyimpan atau memperbarui data gaji berdasarkan input dari form.
     */
    public function saveSalaryData(array $dataGaji): Karyawan
    {
        $karyawan = Karyawan::findOrFail($dataGaji['karyawan_id']);
        $bulan = $dataGaji['bulan'];

        // Ambil pengaturan tunjangan kehadiran yang dipilih
        $tunjanganKehadiranSetting = TunjanganKehadiran::find($dataGaji['tunjangan_kehadiran_id']);
        $tarifKehadiran = $tunjanganKehadiranSetting->jumlah_tunjangan ?? 0;

        // Hitung ulang detail gaji berdasarkan data yang di-submit
        $detailGaji = $this->calculateAndFetchDetails($karyawan, $bulan, $tarifKehadiran);

        // Siapkan data untuk disimpan (ambil dari inputan form)
        $gajiData = [
            'gaji_pokok' => $dataGaji['gaji_pokok'] ?? 0,
            'tunj_anak' => $dataGaji['tunj_anak'] ?? 0,
            'tunj_pengabdian' => $dataGaji['tunj_pengabdian'] ?? 0,
            'lembur' => $dataGaji['lembur'] ?? 0,
            'potongan' => $dataGaji['potongan'] ?? 0,
            'tunj_komunikasi' => $dataGaji['tunj_komunikasi'] ?? 0,
            'tunj_kinerja' => $dataGaji['tunj_kinerja'] ?? 0,
            'kelebihan_jam' => $dataGaji['kelebihan_jam'] ?? 0,
            'tunj_jabatan' => $dataGaji['tunj_jabatan'] ?? 0,
            'tunj_kehadiran' => $detailGaji['tunj_kehadiran'], // Ambil hasil perhitungan tunjangan kehadiran
            'jumlah_kehadiran' => $detailGaji['jumlah_kehadiran'],
            'gaji_bersih' => $detailGaji['gaji_bersih'], // Ambil hasil perhitungan gaji bersih
        ];

        // Gunakan updateOrCreate untuk membuat atau memperbarui data
        Gaji::updateOrCreate(
            ['karyawan_id' => $karyawan->id, 'bulan' => $bulan],
            $gajiData
        );

        return $karyawan;
    }
}
