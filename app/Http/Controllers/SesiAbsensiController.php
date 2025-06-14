<?php

namespace App\Http\Controllers;

use App\Models\SesiAbsensi;
use Illuminate\Http\Request;

class SesiAbsensiController extends Controller
{
    // Method index() tidak berubah...
    public function index()
    {
        $sesi = SesiAbsensi::orderBy('tanggal', 'desc')->paginate(10);
        return view('sesi_absensi.index', compact('sesi'));
    }

    // Method create() tidak berubah...
    public function create()
    {
        return view('sesi_absensi.create');
    }

    // --- UBAH METHOD INI ---
    public function store(Request $request)
    {
        // Validasi untuk membuat sesi baru
        $request->validate([
            'tanggal' => 'required|date|unique:sesi_absensis,tanggal',
            'waktu_mulai' => 'required', // Dihapus aturan format agar lebih fleksibel
            'waktu_selesai' => 'required|after:waktu_mulai', // Dihapus aturan format
        ]);

        SesiAbsensi::create($request->all());

        return redirect()->route('sesi-absensi.index')->with('success', 'Sesi absensi berhasil dibuat.');
    }

    // Method edit() tidak berubah...
    public function edit(SesiAbsensi $sesiAbsensi)
    {
        return view('sesi_absensi.edit', compact('sesiAbsensi'));
    }

    // --- UBAH METHOD INI ---
    public function update(Request $request, SesiAbsensi $sesiAbsensi)
    {
        // Validasi untuk memperbarui sesi
        $request->validate([
            'tanggal' => 'required|date|unique:sesi_absensis,tanggal,' . $sesiAbsensi->id,
            'waktu_mulai' => 'required', // Dihapus aturan format
            'waktu_selesai' => 'required|after:waktu_mulai', // Dihapus aturan format
        ]);

        $data = $request->only(['tanggal', 'waktu_mulai', 'waktu_selesai']);
        $data['is_active'] = $request->has('is_active');

        $sesiAbsensi->update($data);

        return redirect()->route('sesi-absensi.index')->with('success', 'Sesi absensi berhasil diperbarui.');
    }

    // Method destroy() tidak berubah...
    public function destroy(SesiAbsensi $sesiAbsensi)
    {
        $sesiAbsensi->delete();
        return redirect()->route('sesi-absensi.index')->with('success', 'Sesi absensi berhasil dihapus.');
    }
}
