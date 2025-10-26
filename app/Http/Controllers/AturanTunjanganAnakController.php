<?php

namespace App\Http\Controllers;

use App\Models\AturanTunjanganAnak;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;

class AturanTunjanganAnakController extends Controller
{
    /**
     * Tampilkan halaman 'index' yang sebenarnya adalah form edit.
     * Kita akan menggunakan firstOrCreate untuk memastikan 1 baris data selalu ada.
     */
    public function index()
    {
        // Temukan aturan, atau buat baru jika tidak ada
        $aturan = AturanTunjanganAnak::firstOrCreate(
            ['id' => 1], // Kunci pencarian
            ['nama_aturan' => 'Nilai Tunjangan per Anak', 'nilai_per_anak' => 0] // Nilai default jika dibuat
        );

        return view('aturan_anak.index', compact('aturan'));
    }

    /**
     * Update satu-satunya aturan tunjangan anak.
     */
    public function update(Request $request)
    {
        $validated = $request->validate([
            'nilai_per_anak' => 'required|numeric|min:0',
        ]);

        // Temukan aturan (seharusnya sudah ada dari method index)
        $aturan = AturanTunjanganAnak::find(1);
        if (!$aturan) {
            // Jika ada yang menghapus manual, buat lagi
            $aturan = AturanTunjanganAnak::create(
                ['id' => 1, 'nilai_per_anak' => $validated['nilai_per_anak']]
            );
        } else {
            // Update nilai
            $aturan->update($validated);
        }

        // Hapus cache agar GajiController mengambil nilai baru
        Cache::forget('aturan_tunjangan_anak_single');

        return redirect()->route('aturan-anak.index')->with('success', 'Nilai tunjangan anak berhasil diperbarui.');
    }
}
