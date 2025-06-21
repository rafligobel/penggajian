<?php

namespace App\Services;

use App\Models\Gaji;
use App\Models\Karyawan;
use App\Models\Absensi;
use Carbon\Carbon;

class SalaryService
{
    /**
     * Menghitung dan menyiapkan objek Gaji untuk ditampilkan atau simulasi.
     * Metode ini TIDAK menyimpan ke database.
     *
     * @param Karyawan $karyawan
     * @param string $selectedMonth Format 'Y-m'
     * @param int $tarifKehadiran
     * @param array $inputData Data tambahan (misal: untuk simulasi)
     * @return Gaji
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
        if (isset($inputData['jumlah_hari_masuk'])) {
            $jumlahKehadiran = $inputData['jumlah_hari_masuk'];
        } else {
            $date = Carbon::createFromFormat('Y-m', $selectedMonth);
            $jumlahKehadiran = Absensi::where('nip', $karyawan->nip)
                ->whereYear('tanggal', $date->year)
                ->whereMonth('tanggal', $date->month)
                ->count();
        }

        $gaji->jumlah_kehadiran = $jumlahKehadiran; // Properti non-persisted untuk tampilan
        $gaji->tunj_kehadiran = $jumlahKehadiran * $tarifKehadiran;

        // Ambil data dari input jika ada (untuk simulasi atau form edit)
        $gaji->lembur = $inputData['lembur'] ?? $gaji->lembur ?? 0;
        $gaji->potongan = $inputData['potongan'] ?? $gaji->potongan ?? 0;

        // Isi komponen lain dari input jika ada
        if (!empty($inputData)) {
            $gaji->fill($inputData);
        }

        // Hitung ulang total gaji bersih
        $gaji->gaji_bersih = $this->calculateNetSalary($gaji);

        return $gaji;
    }

    /**
     * Menyimpan atau memperbarui data gaji berdasarkan input dari form.
     * Metode ini bertanggung jawab penuh untuk kalkulasi akhir dan persistensi.
     *
     * @param array $data Data yang sudah divalidasi dari controller.
     * @return Karyawan
     */
    public function saveSalaryData(array $data): Karyawan
    {
        $karyawan = Karyawan::find($data['karyawan_id']);
        $bulan = $data['bulan'];
        $tarifKehadiran = (int) ($data['tarif_kehadiran_hidden'] ?? 10000);

        // 1. Hitung jumlah kehadiran aktual dari database
        $date = Carbon::createFromFormat('Y-m', $bulan);
        $jumlahKehadiran = Absensi::where('nip', $karyawan->nip)
            ->whereYear('tanggal', $date->year)
            ->whereMonth('tanggal', $date->month)
            ->count();

        // 2. Buat instance Gaji dari data yang di-submit form
        $gaji = new Gaji($data);

        // 3. Hitung komponen dinamis berdasarkan data aktual
        $gaji->tunj_kehadiran = $jumlahKehadiran * $tarifKehadiran;

        // 4. Hitung ulang gaji bersih final
        $gaji->gaji_bersih = $this->calculateNetSalary($gaji);

        // 5. Simpan atau perbarui data ke database menggunakan semua atribut dari objek $gaji
        Gaji::updateOrCreate(
            ['karyawan_id' => $data['karyawan_id'], 'bulan' => $bulan],
            $gaji->getAttributes()
        );

        return $karyawan;
    }

    /**
     * Menghitung gaji bersih berdasarkan komponen-komponen pada objek Gaji.
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
