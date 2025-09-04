<?php

namespace App\Services;

use App\Models\Gaji;
use App\Models\Karyawan;
use App\Models\Absensi;
use Carbon\Carbon;

class SalaryService
{
    /**
     * Menyiapkan data Gaji untuk ditampilkan.
     * Logika ini memiliki "memori" untuk menggunakan data bulan sebelumnya sebagai template.
     *
     * @param Karyawan $karyawan
     * @param string $selectedMonth Format 'Y-m'
     * @param int $tarifKehadiran
     * @return Gaji
     */
    public function calculateSalary(Karyawan $karyawan, string $selectedMonth, int $tarifKehadiran): Gaji
    {
        // 1. Cek apakah data gaji untuk bulan ini sudah ada di database.
        $gajiBulanIni = Gaji::where('karyawan_id', $karyawan->id)
            ->where('bulan', $selectedMonth)
            ->first();

        // 2. Jika sudah ada, langsung gunakan data tersebut.
        if ($gajiBulanIni) {
            $gajiBulanIni->jumlah_kehadiran = $this->getActualAttendance($karyawan->nip, $selectedMonth);
            $gajiBulanIni->setRelation('karyawan', $karyawan);
            return $gajiBulanIni;
        }

        // --- LOGIKA JIKA DATA GAJI BULAN INI BELUM ADA ---

        // 3. Cari data gaji terakhir dari bulan sebelumnya untuk dijadikan template.
        $templateGaji = Gaji::where('karyawan_id', $karyawan->id)
            ->orderBy('bulan', 'desc')
            ->first();

        // 4. Buat objek Gaji baru untuk bulan ini.
        $gaji = new Gaji();

        // 5. Isi Gaji Pokok dan Tunjangan individu lainnya dari template (jika ada).
        // Ini adalah "memori" dari bulan sebelumnya.
        $gaji->gaji_pokok      = $templateGaji->gaji_pokok ?? 0;
        $gaji->tunj_anak       = $templateGaji->tunj_anak ?? 0;
        $gaji->tunj_komunikasi = $templateGaji->tunj_komunikasi ?? 0;
        $gaji->tunj_pengabdian = $templateGaji->tunj_pengabdian ?? 0;
        $gaji->tunj_kinerja    = $templateGaji->tunj_kinerja ?? 0;
        $gaji->lembur          = 0; // Selalu reset nilai dinamis
        $gaji->kelebihan_jam   = 0;
        $gaji->potongan        = 0;

        // 6. Tunjangan Jabatan SELALU diambil dari data master Jabatan.
        $gaji->tunj_jabatan = $karyawan->jabatan->tunj_jabatan ?? 0;

        // 7. Hitung Tunjangan Kehadiran berdasarkan absensi bulan ini.
        $jumlahKehadiran = $this->getActualAttendance($karyawan->nip, $selectedMonth);
        $gaji->tunj_kehadiran = $jumlahKehadiran * $tarifKehadiran;

        // 8. Hitung ulang Gaji Bersih.
        $gaji->gaji_bersih = $this->calculateNetSalary($gaji);

        // 9. Tambahkan properti non-database untuk keperluan tampilan.
        $gaji->jumlah_kehadiran = $jumlahKehadiran;
        $gaji->setRelation('karyawan', $karyawan);

        return $gaji;
    }

    /**
     * Menyimpan atau memperbarui data gaji dari form edit.
     *
     * @param array $data Data yang sudah divalidasi
     * @return Karyawan
     */
    public function saveSalaryData(array $data): Karyawan
    {
        $karyawan = Karyawan::with('jabatan')->find($data['karyawan_id']);

        // Ambil Tunjangan Jabatan dari relasi untuk memastikan konsistensi saat menyimpan.
        $data['tunj_jabatan'] = $karyawan->jabatan->tunj_jabatan ?? 0;

        // Cari atau buat instance Gaji baru
        $gaji = Gaji::firstOrNew([
            'karyawan_id' => $karyawan->id,
            'bulan' => $data['bulan']
        ]);

        // Isi semua data dari form ke objek Gaji
        $gaji->fill($data);

        // Hitung ulang komponen dinamis sebelum menyimpan
        $tarifKehadiran = (int) ($data['tarif_kehadiran_hidden'] ?? 0);
        $jumlahKehadiran = $this->getActualAttendance($karyawan->nip, $data['bulan']);
        $gaji->tunj_kehadiran = $jumlahKehadiran * $tarifKehadiran;

        // Hitung ulang gaji bersih final
        $gaji->gaji_bersih = $this->calculateNetSalary($gaji);

        // Simpan ke database
        $gaji->save();

        return $karyawan;
    }

    /**
     * Kalkulator Gaji Bersih.
     *
     * @param Gaji $gaji
     * @return int
     */
    private function calculateNetSalary(Gaji $gaji): int
    {
        return ($gaji->gaji_pokok ?? 0) +
            ($gaji->tunj_jabatan ?? 0) +
            ($gaji->tunj_anak ?? 0) +
            ($gaji->tunj_komunikasi ?? 0) +
            ($gaji->tunj_pengabdian ?? 0) +
            ($gaji->tunj_kinerja ?? 0) +
            ($gaji->tunj_kehadiran ?? 0) +
            ($gaji->lembur ?? 0) +
            ($gaji->kelebihan_jam ?? 0) -
            ($gaji->potongan ?? 0);
    }

    /**
     * Helper untuk mengambil jumlah kehadiran aktual dari database.
     *
     * @param string $nip
     * @param string $selectedMonth
     * @return int
     */
    private function getActualAttendance(string $nip, string $selectedMonth): int
    {
        $date = Carbon::createFromFormat('Y-m', $selectedMonth);
        return Absensi::where('nip', $nip)
            ->whereYear('tanggal', $date->year)
            ->whereMonth('tanggal', $date->month)
            ->count();
    }
}
