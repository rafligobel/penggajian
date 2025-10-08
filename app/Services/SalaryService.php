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

        $tanggal = null;
        try {
            // 1. Coba parsing dengan format BARU ('Y-m-d') terlebih dahulu
            $tanggal = Carbon::createFromFormat('Y-m-d', $bulan);
        } catch (\Carbon\Exceptions\InvalidFormatException $e) {
            // 2. Jika gagal, coba parsing dengan format LAMA ('Y-m')
            $tanggal = Carbon::createFromFormat('Y-m', $bulan);
        }

        // Sekarang kita menggunakan whereYear dan whereMonth agar lebih fleksibel
        $gajiTersimpan = Gaji::where('karyawan_id', $karyawan->id)
            ->whereYear('bulan', $tanggal->year)
            ->whereMonth('bulan', $tanggal->month)
            ->first();

        // ... Sisa kode method ini sama persis seperti sebelumnya ...
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

        $gajiPokok = optional($gajiTersimpan)->gaji_pokok ?? optional($gajiBulanLalu)->gaji_pokok ?? 0;
        $tunjJabatan = $karyawan->jabatan->tunj_jabatan ?? 0;
        $tunjAnak = optional($gajiTersimpan)->tunj_anak ?? optional($gajiBulanLalu)->tunj_anak ?? 0;
        $tunjKomunikasi = optional($gajiTersimpan)->tunj_komunikasi ?? optional($gajiBulanLalu)->tunj_komunikasi ?? 0;
        $tunjPengabdian = optional($gajiTersimpan)->tunj_pengabdian ?? optional($gajiBulanLalu)->tunj_pengabdian ?? 0;
        $tunjKinerja = optional($gajiTersimpan)->tunj_kinerja ?? optional($gajiBulanLalu)->tunj_kinerja ?? 0;
        $lembur = optional($gajiTersimpan)->lembur ?? 0;
        $potongan = optional($gajiTersimpan)->potongan ?? 0;

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

    public function calculateSimulation(Karyawan $karyawan, array $input): array
    {
        $karyawan->loadMissing('jabatan');

        // 1. Gunakan gaji terakhir sebagai template untuk komponen tetap
        $templateGaji = Gaji::where('karyawan_id', $karyawan->id)
            ->orderBy('bulan', 'desc')
            ->first();

        // Ambil tarif tunjangan kehadiran dari gaji terakhir atau default pertama
        $tunjanganKehadiranId = optional($templateGaji)->tunjangan_kehadiran_id ?? optional(TunjanganKehadiran::first())->id;
        $settingTunjangan = TunjanganKehadiran::find($tunjanganKehadiranId);
        $tarifKehadiran = $settingTunjangan->jumlah_tunjangan ?? 0; // Default ke 0 jika tidak ada

        // 2. Ambil komponen gaji tetap dari template
        $gajiPokok = $templateGaji->gaji_pokok ?? 0;
        $tunjJabatan = $karyawan->jabatan->tunj_jabatan ?? 0;
        $tunjAnak = $templateGaji->tunj_anak ?? 0;
        $tunjKomunikasi = $templateGaji->tunj_komunikasi ?? 0;
        $tunjPengabdian = $templateGaji->tunj_pengabdian ?? 0;
        $tunjKinerja = $templateGaji->tunj_kinerja ?? 0;

        // 3. Ambil komponen tidak tetap dari input form simulasi
        $jumlahHariMasuk = $input['jumlah_hari_masuk'];
        $lembur = $input['lembur'] ?? 0;
        $potongan = $input['potongan'] ?? 0;

        // 4. Hitung tunjangan kehadiran berdasarkan input
        $tunjKehadiran = $jumlahHariMasuk * $tarifKehadiran;

        // 5. Hitung total gaji bersih simulasi
        $gajiBersih = ($gajiPokok + $tunjJabatan + $tunjAnak + $tunjKomunikasi +
            $tunjPengabdian + $tunjKinerja + $tunjKehadiran + $lembur) - $potongan;

        // 6. Kembalikan dalam format yang dibutuhkan oleh view 'hasil.blade.php'
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
            'gaji_bersih' => $gajiBersih, // Tambahkan ini untuk kemudahan
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
