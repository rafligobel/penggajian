<?php

namespace App\Http\Controllers;

use App\Models\Pengaturan;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class PengaturanController extends Controller
{
    public function index()
    {
        $tandaTangan = Pengaturan::where('key', 'tanda_tangan_bendahara')->first();
        return view('pengaturan.index', compact('tandaTangan'));
    }

    public function update(Request $request)
    {
        $request->validate([
            'tanda_tangan' => 'required|image|mimes:png,jpg,jpeg|max:1024',
        ]);

        $pengaturan = Pengaturan::firstOrNew(['key' => 'tanda_tangan_bendahara']);

        // Hapus file lama jika ada
        if ($pengaturan->value && Storage::disk('public')->exists($pengaturan->value)) {
            Storage::disk('public')->delete($pengaturan->value);
        }

        $path = $request->file('tanda_tangan')->store('tanda_tangan', 'public');
        $pengaturan->value = $path;
        $pengaturan->save();

        return redirect()->route('pengaturan.index')->with('success', 'Tanda tangan berhasil diperbarui.');
    }
}
