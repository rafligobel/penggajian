<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Karyawan;
use App\Models\Gaji;

class SimulasiGajiController extends Controller
{
    /**
     * Menampilkan form simulasi.
     * Tidak perlu lagi mengirim data karyawan ke view ini.
     */
    public function index()
    {
        return view('simulasi.index');
    }

    /**
     * Menghitung dan menampilkan hasil simulasi gaji berdasarkan PENCARIAN.
     */
    public function hitung(Request $request)
    {
        $validated = $request->validate([
            // Validasi diubah untuk menerima query pencarian
            'karyawan_query' => 'required|string|min:3',
            'jumlah_hari_masuk' => 'required|integer|min:0|max:31',
            'lembur' => 'nullable|numeric|min:0',
            'potongan' => 'nullable|numeric|min:0',
        ]);

        $query = $validated['karyawan_query'];

        // Cari karyawan berdasarkan NIP (prioritas) atau nama
        $karyawan = Karyawan::where('nip', $query)
            ->orWhere('nama', 'LIKE', "%{$query}%")
            ->get();

        // Penanganan jika karyawan tidak ditemukan atau ambigu
        if ($karyawan->count() === 0) {
            return redirect()->back()->withInput()->with('error', "Karyawan dengan nama atau NIP '{$query}' tidak ditemukan.");
        }

        if ($karyawan->count() > 1) {
            return redirect()->back()->withInput()->with('error', "Ditemukan lebih dari satu karyawan. Mohon gunakan NIP atau nama yang lebih spesifik.");
        }

        // Jika hanya 1 karyawan ditemukan, lanjutkan
        $selectedKaryawan = $karyawan->first();

        $templateGaji = Gaji::where('karyawan_id', $selectedKaryawan->id)->orderBy('bulan', 'desc')->first();

        if (!$templateGaji) {
            return redirect()->back()->withInput()->with('error', 'Data gaji sebelumnya untuk karyawan ini tidak ditemukan. Simulasi tidak dapat dilanjutkan.');
        }

        // Logika perhitungan tetap sama
        $rincian = $templateGaji->toArray();
        $tarif_kehadiran = 10000;
        $rincian['tunj_kehadiran'] = $validated['jumlah_hari_masuk'] * $tarif_kehadiran;
        $rincian['lembur'] = $validated['lembur'] ?? 0;
        $rincian['potongan'] = $validated['potongan'] ?? 0;

        $gaji_bersih = ($rincian['gaji_pokok'] ?? 0)
            + ($rincian['tunj_kehadiran'])
            + ($rincian['tunj_anak'] ?? 0)
            + ($rincian['tunj_komunikasi'] ?? 0)
            + ($rincian['tunj_pengabdian'] ?? 0)
            + ($rincian['tunj_jabatan'] ?? 0)
            + ($rincian['tunj_kinerja'] ?? 0)
            + ($rincian['lembur'])
            + ($rincian['kelebihan_jam'] ?? 0)
            - ($rincian['potongan']);

        $hasil = [
            'karyawan' => $selectedKaryawan,
            'jumlah_hari_masuk' => $validated['jumlah_hari_masuk'],
            'rincian' => $rincian,
            'gaji_bersih' => $gaji_bersih,
        ];

        return view('simulasi.hasil', compact('hasil'));
    }
}
