<?php

namespace App\Http\Controllers;

use App\Models\AturanTunjanganPengabdian;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;

class AturanTunjanganPengabdianController extends Controller
{
    /**
     * Tampilkan daftar aturan.
     */
    public function index()
    {
        $aturans = AturanTunjanganPengabdian::orderBy('minimal_tahun_kerja')->get();
        return view('aturan_pengabdian.index', compact('aturans'));
    }

    /**
     * Simpan aturan baru dari modal.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'nama_aturan' => 'required|string|max:255',
            'minimal_tahun_kerja' => 'required|integer|min:0',
            'maksimal_tahun_kerja' => 'required|integer|gte:minimal_tahun_kerja',
            'nilai_tunjangan' => 'required|numeric|min:0',
        ]);

        AturanTunjanganPengabdian::create($validated);

        Cache::forget('aturan_tunjangan_pengabdian_all');

        return redirect()->route('aturan-pengabdian.index')->with('success', 'Aturan baru berhasil ditambahkan.');
    }

    // Method show(), create(), edit() sengaja dihilangkan

    /**
     * Update aturan yang ada dari modal.
     */
    public function update(Request $request, AturanTunjanganPengabdian $aturan_pengabdian)
    {
        $validated = $request->validate([
            'nama_aturan' => 'required|string|max:255',
            'minimal_tahun_kerja' => 'required|integer|min:0',
            'maksimal_tahun_kerja' => 'required|integer|gte:minimal_tahun_kerja',
            'nilai_tunjangan' => 'required|numeric|min:0',
        ]);

        $aturan_pengabdian->update($validated);

        Cache::forget('aturan_tunjangan_pengabdian_all');

        return redirect()->route('aturan-pengabdian.index')->with('success', 'Aturan berhasil diperbarui.');
    }

    /**
     * Hapus aturan.
     */
    public function destroy(AturanTunjanganPengabdian $aturan_pengabdian)
    {
        $aturan_pengabdian->delete();

        Cache::forget('aturan_tunjangan_pengabdian_all');

        return redirect()->route('aturan-pengabdian.index')->with('success', 'Aturan berhasil dihapus.');
    }
}
