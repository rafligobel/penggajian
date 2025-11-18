<?php

namespace App\Http\Controllers;

use App\Models\Potongan;
use Illuminate\Http\Request;

class PotonganController extends Controller
{
    /**
     * Menampilkan satu halaman index yang berisi form pengaturan tarif.
     */
    public function index()
    {
        // Ambil data pengaturan pertama.
        // Jika belum ada (pertama kali install), otomatis buat data default 0.
        $potongan = Potongan::firstOrCreate(
            ['id' => 1],
            [
                'tarif_lembur_per_jam' => 0,
                'tarif_potongan_absen' => 0
            ]
        );

        // Return ke view 'potongan.index' dengan membawa data tersebut
        return view('potongan.index', compact('potongan'));
    }

    /**
     * Menyimpan perubahan tarif (Lembur & Potongan) sekaligus.
     */
    public function update(Request $request)
    {
        $validated = $request->validate([
            'tarif_lembur_per_jam' => 'required|numeric|min:0',
            'tarif_potongan_absen' => 'required|numeric|min:0',
        ]);

        // Update data pada ID 1 (Single Record)
        $potongan = Potongan::first();
        $potongan->update($validated);

        return redirect()->route('potongan.index')
            ->with('success', 'Tarif default (Lembur & Potongan) berhasil diperbarui!');
    }
}
