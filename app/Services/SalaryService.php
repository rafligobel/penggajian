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
     * Menghitung dan menyusun SEMUA detail gaji untuk ditampilkan di view atau slip.
     * Ini adalah SATU-SATUNYA sumber kebenaran untuk kalkulasi gaji.
     */
    public function calculateDetailsForForm(Karyawan $karyawan, string $bulan): array
    {
        // Pastikan relasi jabatan sudah ter-load untuk efisiensi
        $karyawan->loadMissing('jabatan');
        $tanggal = Carbon::createFromFormat('Y-m', $bulan);

        // 1. Dapatkan data mentah dari database untuk bulan ini
        $gajiTersimpan = Gaji::where('karyawan_id', $karyawan->id)
            ->where('bulan', $bulan)
            ->first();

        // 2. Jika tidak ada data, gunakan data bulan terakhir sebagai template
        $gajiBulanLalu = null;
        if (!$gajiTersimpan) {
            $gajiBulanLalu = Gaji::where('karyawan_id', $karyawan->id)->orderBy('bulan', 'desc')->first();
        }

        // 3. Ambil data absensi aktual dari bulan ini
        $jumlahKehadiran = Absensi::where('nip', $karyawan->nip)
            ->whereYear('tanggal', $tanggal->year)
            ->whereMonth('tanggal', $tanggal->month)
            ->count();

        // 4. Tentukan tunjangan kehadiran yang akan digunakan
        // Prioritas: Gaji tersimpan -> Gaji bulan lalu -> Tunjangan pertama sebagai default
        $tunjanganKehadiranId = $gajiTersimpan->tunjangan_kehadiran_id
            ?? $gajiBulanLalu->tunjangan_kehadiran_id
            ?? optional(TunjanganKehadiran::first())->id ?? 1;

        $settingTunjangan = TunjanganKehadiran::find($tunjanganKehadiranId);
        $tarifKehadiran = $settingTunjangan->jumlah_tunjangan ?? 0;

        // 5. Tentukan semua komponen pendapatan dan potongan
        $gajiPokok = $gajiTersimpan->gaji_pokok ?? $gajiBulanLalu->gaji_pokok ?? 0;
        $tunjJabatan = $karyawan->jabatan->tunj_jabatan ?? 0; // Selalu dari data jabatan terbaru
        $tunjKehadiran = $jumlahKehadiran * $tarifKehadiran; // Selalu dihitung ulang
        $tunjAnak = $gajiTersimpan->tunj_anak ?? $gajiBulanLalu->tunj_anak ?? 0;
        $tunjKomunikasi = $gajiTersimpan->tunj_komunikasi ?? $gajiBulanLalu->tunj_komunikasi ?? 0;
        $tunjPengabdian = $gajiTersimpan->tunj_pengabdian ?? $gajiBulanLalu->tunj_pengabdian ?? 0;
        $tunjKinerja = $gajiTersimpan->tunj_kinerja ?? $gajiBulanLalu->tunj_kinerja ?? 0;
        $lembur = $gajiTersimpan->lembur ?? 0; // Diambil dari data tersimpan, bisa di-nol-kan jika perlu
        $potongan = $gajiTersimpan->potongan ?? 0;

        // 6. Kalkulasi Gaji Bersih
        $gajiBersih = ($gajiPokok + $tunjJabatan + $tunjKehadiran + $tunjAnak + $tunjKomunikasi +
            $tunjPengabdian + $tunjKinerja + $lembur) - $potongan;

        // 7. Kembalikan array yang lengkap dan terstruktur untuk digunakan di mana saja
        return [
            'karyawan' => $karyawan,
            'gaji' => $gajiTersimpan, // Bisa null, untuk menandakan status "Template"
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
            'tunj_kehadiran' => $tunjKehadiran, // Hasil kalkulasi
            'gaji_bersih' => $gajiBersih, // Hasil kalkulasi
        ];
    }

    /**
     * Menyimpan atau memperbarui data mentah gaji.
     */
    public function saveGaji(array $dataForm): Gaji
    {
        // Data yang disimpan HANYA inputan manual, bukan hasil kalkulasi
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
                'kelebihan_jam' => $dataForm['kelebihan_jam'] ?? 0, // Jika ada
                'potongan' => $dataForm['potongan'] ?? 0,
                'tunjangan_kehadiran_id' => $dataForm['tunjangan_kehadiran_id'],
            ]
        );
    }
}
