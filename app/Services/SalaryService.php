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
     * Menghitung dan menyusun detail gaji.
     * SEMUA OPERASI TANGGAL KINI MENGGUNAKAN OBJEK CARBON YANG KONSISTEN.
     */
    public function calculateDetailsForForm(Karyawan $karyawan, string $bulan): array
    {
        $karyawan->loadMissing('jabatan');

        // PASTIKAN $bulan SELALU DIANGGAP SEBAGAI TANGGAL 1
        $tanggal = Carbon::parse($bulan)->startOfMonth();

        // [PERBAIKAN] Query yang lebih spesifik dan aman menggunakan objek tanggal
        $gajiTersimpan = Gaji::where('karyawan_id', $karyawan->id)
            ->where('bulan', $tanggal->toDateString())
            ->first();

        $gajiBulanLalu = null;
        if (!$gajiTersimpan) {
            // Ambil gaji terakhir sebelum bulan yang dipilih
            $gajiBulanLalu = Gaji::where('karyawan_id', $karyawan->id)
                ->where('bulan', '<', $tanggal->toDateString())
                ->orderBy('bulan', 'desc')
                ->first();
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

        // Komponen gaji diambil dari data tersimpan, atau fallback ke bulan lalu, atau 0
        $gajiPokok = $gajiTersimpan->gaji_pokok ?? optional($gajiBulanLalu)->gaji_pokok ?? 0;
        $tunjJabatan = $karyawan->jabatan->tunj_jabatan ?? 0; // Tunjangan jabatan selalu dari relasi
        $tunjAnak = $gajiTersimpan->tunj_anak ?? optional($gajiBulanLalu)->tunj_anak ?? 0;
        $tunjKomunikasi = $gajiTersimpan->tunj_komunikasi ?? optional($gajiBulanLalu)->tunj_komunikasi ?? 0;
        $tunjPengabdian = $gajiTersimpan->tunj_pengabdian ?? optional($gajiBulanLalu)->tunj_pengabdian ?? 0;
        $tunjKinerja = $gajiTersimpan->tunj_kinerja ?? optional($gajiBulanLalu)->tunj_kinerja ?? 0;
        $lembur = optional($gajiTersimpan)->lembur ?? 0; // Jika bulan ini belum ada gaji, lembur dianggap 0
        $potongan = optional($gajiTersimpan)->potongan ?? 0; // Sama seperti lembur

        $tunjKehadiran = $jumlahKehadiran * $tarifKehadiran;

        $totalPendapatan = $gajiPokok + $tunjJabatan + $tunjKehadiran + $tunjAnak + $tunjKomunikasi +
            $tunjPengabdian + $tunjKinerja + $lembur;

        $gajiBersih = $totalPendapatan - $potongan;

        return [
            'karyawan' => $karyawan,
            'gaji' => $gajiTersimpan,
            'bulan' => $tanggal->format('Y-m'), // Kirim balik format Y-m untuk view
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

    // Method calculateSimulation sudah terlihat baik, tidak perlu perubahan signifikan.
    public function calculateSimulation(Karyawan $karyawan, array $input): array
    {
        $karyawan->loadMissing('jabatan');
        $templateGaji = Gaji::where('karyawan_id', $karyawan->id)
            ->orderBy('bulan', 'desc')
            ->first();
        $tunjanganKehadiranId = optional($templateGaji)->tunjangan_kehadiran_id ?? optional(TunjanganKehadiran::first())->id;
        $settingTunjangan = TunjanganKehadiran::find($tunjanganKehadiranId);
        $tarifKehadiran = $settingTunjangan->jumlah_tunjangan ?? 0;
        $gajiPokok = $templateGaji->gaji_pokok ?? 0;
        $tunjJabatan = $karyawan->jabatan->tunj_jabatan ?? 0;
        $tunjAnak = $templateGaji->tunj_anak ?? 0;
        $tunjKomunikasi = $templateGaji->tunj_komunikasi ?? 0;
        $tunjPengabdian = $templateGaji->tunj_pengabdian ?? 0;
        $tunjKinerja = $templateGaji->tunj_kinerja ?? 0;
        $jumlahHariMasuk = $input['jumlah_hari_masuk'];
        $lembur = $input['lembur'] ?? 0;
        $potongan = $input['potongan'] ?? 0;
        $tunjKehadiran = $jumlahHariMasuk * $tarifKehadiran;
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

    /**
     * Menyimpan data gaji. Format 'bulan' dipastikan Y-m-d.
     */
    public function saveGaji(array $dataForm): Gaji
    {
        // [PERBAIKAN] Pastikan format tanggal sudah benar sebelum disimpan
        $bulan = Carbon::parse($dataForm['bulan'])->startOfMonth()->toDateString();

        return Gaji::updateOrCreate(
            [
                'karyawan_id' => $dataForm['karyawan_id'],
                'bulan' => $bulan,
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
