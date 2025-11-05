<?php

namespace App\Http\Controllers;

use App\Models\AturanKinerja;
use App\Models\IndikatorKinerja;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;

class PengaturanKinerjaController extends Controller
{
    /**
     * Menampilkan halaman pengaturan terpadu.
     */
    public function index()
    {
        // 1. Ambil Aturan Nilai Maksimal
        $aturan = AturanKinerja::firstOrCreate(
            ['id' => 1], // Selalu gunakan ID 1
            ['maksimal_tunjangan' => 0] // Default jika baru dibuat
        );

        // 2. Ambil Daftar Indikator
        $indikators = IndikatorKinerja::orderBy('nama_indikator')->get();

        return view('pengaturan_kinerja.index', compact('aturan', 'indikators'));
    }

    /**
     * Update Aturan Nilai Maksimal Tunjangan Kinerja.
     */
    public function updateAturan(Request $request)
    {
        $validated = $request->validate([
            'maksimal_tunjangan' => 'required|numeric|min:0',
        ]);

        $aturan = AturanKinerja::find(1);
        if ($aturan) {
            $aturan->update($validated);
        }

        // Hapus cache agar data baru bisa diambil
        Cache::forget('aturan_kinerja_single');

        return redirect()->route('pengaturan-kinerja.index')
            ->with('success', 'Aturan Tunjangan Kinerja berhasil diperbarui.');
    }

    /**
     * Simpan Indikator Kinerja baru.
     */
    public function storeIndikator(Request $request)
    {
        $request->validate(['nama_indikator' => 'required|string|max:255|unique:indikator_kinerjas']);

        IndikatorKinerja::create($request->all());

        return redirect()->route('pengaturan-kinerja.index')
            ->with('success', 'Indikator baru berhasil ditambahkan.');
    }

    /**
     * Update Indikator Kinerja yang ada.
     */
    public function updateIndikator(Request $request, IndikatorKinerja $indikator)
    {
        $request->validate(['nama_indikator' => 'required|string|max:255|unique:indikator_kinerjas,nama_indikator,' . $indikator->id]);

        $indikator->update($request->all());

        return redirect()->route('pengaturan-kinerja.index')
            ->with('success', 'Indikator berhasil diperbarui.');
    }

    /**
     * Hapus Indikator Kinerja.
     */
    public function destroyIndikator(IndikatorKinerja $indikator)
    {
        try {
            $indikator->delete();
            return redirect()->route('pengaturan-kinerja.index')
                ->with('success', 'Indikator berhasil dihapus.');
        } catch (\Exception $e) {
            // Gagal jika indikator terikat ke data gaji
            return redirect()->route('pengaturan-kinerja.index')
                ->with('error', 'Gagal menghapus. Indikator mungkin sedang digunakan di data gaji.');
        }
    }
}
