<?php

namespace App\Http\Controllers;

use App\Models\TandaTangan;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class TandaTanganController extends Controller
{
    public function index()
    {
        $tandaTangan = TandaTangan::where('key', 'tanda_tangan_bendahara')->first();
        return view('tanda_tangan.index', compact('tandaTangan')); // Path view baru
    }

    public function update(Request $request)
    {
        $request->validate([
            'tanda_tangan' => 'required|image|mimes:png,jpg,jpeg|max:1024',
        ]);

        $tandaTanganData = TandaTangan::firstOrNew(['key' => 'tanda_tangan_bendahara']);

        if ($tandaTanganData->value && Storage::disk('public')->exists($tandaTanganData->value)) {
            Storage::disk('public')->delete($tandaTanganData->value);
        }

        $path = $request->file('tanda_tangan')->store('tanda_tangan', 'public');
        $tandaTanganData->value = $path;
        $tandaTanganData->save();

        return redirect()->route('tanda_tangan.index')->with('success', 'Tanda tangan berhasil diperbarui.'); // Route name baru
    }
}
