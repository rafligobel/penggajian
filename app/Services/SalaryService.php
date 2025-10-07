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
     * Menghitung dan menyusun detail gaji sesuai data yang ada di tabel.
     * Logika 'kelebihan_jam' telah dihapus sepenuhnya.
     */
    public function calculateDetailsForForm(Karyawan $karyawan, string $bulan): array
    {
        $karyawan->loadMissing('jabatan');
        $tanggal = Carbon::createFromFormat('Y-m', $bulan);

        $gajiTersimpan = Gaji::where('karyawan_id', $karyawan->id)
            ->where('bulan', $bulan)
            ->first();

        $gajiBulanLalu = null;
        if (!$gajiTersimpan) {
            $gajiBulanLalu = Gaji::where('karyawan_id', $karyawan->id)->orderBy('bulan', 'desc')->first();
        }

        $jumlahKehadiran = Absensi::where('nip', $karyawan->nip)
            ->whereYear('tanggal', $tanggal->year)
            ->whereMonth('tanggal', $tanggal->month)
            ->count();

        $tunjanganKehadiranId = optional($gajiTersimpan)->tunjangan_kehadiran_id
            ?? optional($gajiBulanLalu)->tunjangan_kehadiran_id
            ?? optional(TunjanganKehadiran::first())->id;

        $settingTunjangan = TunjanganKehadiran::find($tunjanganKehadiranId);
        $tarifKehadiran = $settingTunjangan->jumlah_tunjangan ?? 0;

        // Mengambil data mentah sesuai kolom di tabel 'gajis'
        $gajiPokok = $gajiTersimpan->gaji_pokok ?? $gajiBulanLalu->gaji_pokok ?? 0;
        $tunjJabatan = $karyawan->jabatan->tunj_jabatan ?? 0;
        $tunjAnak = $gajiTersimpan->tunj_anak ?? $gajiBulanLalu->tunj_anak ?? 0;
        $tunjKomunikasi = $gajiTersimpan->tunj_komunikasi ?? $gajiBulanLalu->tunj_komunikasi ?? 0;
        $tunjPengabdian = $gajiTersimpan->tunj_pengabdian ?? $gajiBulanLalu->tunj_pengabdian ?? 0;
        $tunjKinerja = $gajiTersimpan->tunj_kinerja ?? $gajiBulanLalu->tunj_kinerja ?? 0;
        $lembur = $gajiTersimpan->lembur ?? 0;
        $potongan = $gajiTersimpan->potongan ?? 0;

        // Kalkulasi berdasarkan data yang ada
        $tunjKehadiran = $jumlahKehadiran * $tarifKehadiran;
        $gajiBersih = ($gajiPokok + $tunjJabatan + $tunjKehadiran + $tunjAnak + $tunjKomunikasi +
            $tunjPengabdian + $tunjKinerja + $lembur) - $potongan;

        return [
            'karyawan' => $karyawan,
            'gaji' => $gajiTersimpan,
            'bulan' => $bulan,
            'gaji_pokok' => $gajiPokok,
            'tunj_jabatan' => $tunjJabatan,
            'tunj_anak' => $tunjAnak,
            'tunj_komunikasi' => $tunjKomunikasi,
            'tunj_pengabdian' => $tunjPengabdian,
            'tunj_kinerja' => $tunjKinerja,
            'lembur' => $lembur,
            'potongan' => $potongan,
            'jumlah_kehadiran' => $jumlahKehadiran,
            'tunjangan_kehadiran_id' => $tunjanganKehadiranId,
            'tunj_kehadiran' => $tunjKehadiran,
            'gaji_bersih' => $gajiBersih,
        ];
    }

    /**
     * Menyimpan data gaji sesuai kolom yang ada di tabel.
     */
    public function saveGaji(array $dataForm): Gaji
    {
        return Gaji::updateOrCreate(
            [
                'karyawan_id' => $dataForm['karyawan_id'],
                'bulan' => $dataForm['bulan']
            ],
            [
                'gaji_pokok' => $dataForm['gaji_pokok'] ?? 0,
                'tunj_anak' => $dataForm['tunj_anak'] ?? 0,
                'tunj_komunikasi' => $dataForm['tunj_komunikasi'] ?? 0,
                'tunj_pengabdian' => $dataForm['tunj_pengabdian'] ?? 0,
                'tunj_kinerja' => $dataForm['tunj_kinerja'] ?? 0,
                'lembur' => $dataForm['lembur'] ?? 0,
                'potongan' => $dataForm['potongan'] ?? 0,
                'tunjangan_kehadiran_id' => $dataForm['tunjangan_kehadiran_id'],
            ]
        );
    }
}
