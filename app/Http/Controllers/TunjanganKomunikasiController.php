<?php

namespace App\Http\Controllers;

use App\Models\TunjanganKomunikasi; // Ganti Model
use Illuminate\Http\Request;

class TunjanganKomunikasiController extends Controller
{
    public function index()
    {
        $tunjanganKomunikasis = TunjanganKomunikasi::latest()->paginate(10); // Ganti variabel
        return view('tunjangan_komunikasi.index', compact('tunjanganKomunikasis')); // Ganti view
    }

    public function store(Request $request)
    {
        $validatedData = $request->validate([
            'nama_level' => 'required|string|max:255', // Ganti field
            'besaran' => 'required|numeric|min:0', // Ganti field
        ]);

        TunjanganKomunikasi::create($validatedData); // Ganti Model

        return redirect()->route('tunjangan-komunikasi.index') // Ganti route
            ->with('success', 'Tunjangan komunikasi berhasil ditambahkan.');
    }

    public function update(Request $request, TunjanganKomunikasi $tunjanganKomunikasi) // Ganti Type-hint
    {
        $request->validate([
            'nama_level' => 'required|string|max:255', // Ganti field
            'besaran' => 'required|numeric|min:0', // Ganti field
        ]);

        $tunjanganKomunikasi->update($request->all());

        return redirect()->route('tunjangan-komunikasi.index') // Ganti route
            ->with('success', 'Tunjangan komunikasi berhasil diperbarui.');
    }

    public function destroy(TunjanganKomunikasi $tunjanganKomunikasi) // Ganti Type-hint
    {
        $tunjanganKomunikasi->delete();

        return redirect()->route('tunjangan-komunikasi.index') // Ganti route
            ->with('success', 'Tunjangan komunikasi berhasil dihapus.');
    }
}
