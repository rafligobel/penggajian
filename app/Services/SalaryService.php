<?php

namespace App\Services;

use App\Models\Gaji;
use App\Models\Karyawan;
use App\Models\Absensi;
use Carbon\Carbon;

class SalaryService
{
    /**
     * Menghitung detail gaji untuk seorang karyawan pada bulan tertentu.
     *
     * @param Karyawan $karyawan
     * @param string $selectedMonth Format 'Y-m'
     * @param int $tarifKehadiran
     * @param array $inputData Data tambahan untuk simulasi (jumlah_hari_masuk, lembur, potongan)
     * @return Gaji Objek Gaji yang sudah dihitung (tidak disimpan ke database)
     */
    public function calculateSalary(Karyawan $karyawan, string $selectedMonth, int $tarifKehadiran, array $inputData = []): Gaji
    {
        // Cari data gaji yang sudah tersimpan untuk bulan ini
        $gajiBulanIni = Gaji::where('karyawan_id', $karyawan->id)
            ->where('bulan', $selectedMonth)
            ->first();

        // Jika tidak ada, gunakan template dari bulan terakhir atau buat objek baru
        $templateGaji = $gajiBulanIni ?? Gaji::where('karyawan_id', $karyawan->id)
            ->orderBy('bulan', 'desc')
            ->first();

        $gaji = new Gaji();
        if ($templateGaji) {
            $gaji->fill($templateGaji->getAttributes());
        }

        // Atur properti dasar
        $gaji->id = $gajiBulanIni->id ?? null;
        $gaji->created_at = $gajiBulanIni->created_at ?? null;
        $gaji->updated_at = $gajiBulanIni->updated_at ?? null;
        $gaji->karyawan_id = $karyawan->id;
        $gaji->bulan = $selectedMonth;
        $gaji->karyawan = $karyawan;

        // Hitung komponen dinamis
        // Jika dari simulasi, gunakan jumlah hari dari input, jika tidak, hitung dari database
        if (isset($inputData['jumlah_hari_masuk'])) {
            $jumlahKehadiran = $inputData['jumlah_hari_masuk'];
        } else {
            $date = Carbon::createFromFormat('Y-m', $selectedMonth);
            $jumlahKehadiran = Absensi::where('nip', $karyawan->nip)
                ->whereYear('tanggal', $date->year)
                ->whereMonth('tanggal', $date->month)
                ->count();
        }

        $gaji->jumlah_kehadiran = $jumlahKehadiran;
        $gaji->tunj_kehadiran = $jumlahKehadiran * $tarifKehadiran;

        // Ambil data lembur dan potongan dari input simulasi jika ada
        $gaji->lembur = $inputData['lembur'] ?? $gaji->lembur ?? 0;
        $gaji->potongan = $inputData['potongan'] ?? $gaji->potongan ?? 0;

        // Hitung ulang total gaji bersih
        $gaji->gaji_bersih = $this->calculateNetSalary($gaji);

        return $gaji;
    }

    /**
     * Menghitung gaji bersih berdasarkan komponen-komponennya.
     *
     * @param Gaji $gaji
     * @return int
     */
    public function calculateNetSalary(Gaji $gaji): int
    {
        return ($gaji->gaji_pokok ?? 0) +
            ($gaji->tunj_kehadiran ?? 0) +
            ($gaji->tunj_jabatan ?? 0) +
            ($gaji->tunj_anak ?? 0) +
            ($gaji->tunj_komunikasi ?? 0) +
            ($gaji->tunj_pengabdian ?? 0) +
            ($gaji->tunj_kinerja ?? 0) +
            ($gaji->lembur ?? 0) +
            ($gaji->kelebihan_jam ?? 0) -
            ($gaji->potongan ?? 0);
    }
}
