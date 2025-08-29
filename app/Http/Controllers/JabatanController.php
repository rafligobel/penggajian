<?php

namespace App\Http\Controllers;

use App\Models\Jabatan;
use Illuminate\Http\Request;

class JabatanController extends Controller
{
    /**
     * Menampilkan daftar jabatan.
     */
    public function index()
    {
        $jabatans = Jabatan::latest()->paginate(10);
        return view('jabatan.index', compact('jabatans'));
    }

    /**
     * Menyimpan jabatan baru.
     */
    public function store(Request $request)
    {
        $request->validate([
            'nama_jabatan' => 'required|string|max:255|unique:jabatans,nama_jabatan',
            'gaji_pokok' => 'required|numeric|min:0', // Nama input dari form
        ]);

        Jabatan::create([
            'nama_jabatan' => $request->nama_jabatan,
            'gaji_pokok' => $request->gaji_pokok,
        ]);

        return redirect()->route('jabatan.index')
            ->with('success', 'Jabatan berhasil ditambahkan.');
    }

    /**
     * Memperbarui data jabatan.
     */
    public function update(Request $request, Jabatan $jabatan)
    {
        $request->validate([
            'nama_jabatan' => 'required|string|max:255|unique:jabatans,nama_jabatan,' . $jabatan->id,
            'gaji_pokok' => 'required|numeric|min:0',
        ]);

        $jabatan->update([
            'nama_jabatan' => $request->nama_jabatan,
            'gaji_pokok' => $request->gaji_pokok,
        ]);

        return redirect()->route('jabatan.index')
            ->with('success', 'Jabatan berhasil diperbarui.');
    }

    /**
     * Menghapus data jabatan.
     */
    public function destroy(Jabatan $jabatan)
    {
        $jabatan->delete();

        return redirect()->route('jabatan.index')
            ->with('success', 'Jabatan berhasil dihapus.');
    }
}