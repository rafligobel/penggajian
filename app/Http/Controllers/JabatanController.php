<?php

namespace App\Http\Controllers;

use App\Models\Jabatan;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class JabatanController extends Controller
{
    /**
     * Menampilkan daftar jabatan.
     */
    public function index()
    {
        // Mengurutkan berdasarkan nama jabatan untuk tampilan yang lebih rapi
        $jabatans = Jabatan::orderBy('nama_jabatan')->paginate(10);
        return view('jabatan.index', compact('jabatans'));
    }

    /**
     * Menyimpan jabatan baru.
     */
    public function store(Request $request)
    {
        // Validasi dan penyimpanan disesuaikan untuk Tunjangan Jabatan
        $validatedData = $request->validate([
            'nama_jabatan' => 'required|string|max:255|unique:jabatans,nama_jabatan',
            'tunj_jabatan' => 'required|numeric|min:0', // Validasi untuk tunj_jabatan
        ]);

        Jabatan::create($validatedData);

        return redirect()->route('jabatan.index')
            ->with('success', 'Jabatan berhasil ditambahkan.');
    }

    /**
     * Memperbarui data jabatan.
     */
    public function update(Request $request, Jabatan $jabatan)
    {
        // Validasi dan pembaruan disesuaikan untuk Tunjangan Jabatan
        $validatedData = $request->validate([
            'nama_jabatan' => [
                'required',
                'string',
                'max:255',
                Rule::unique('jabatans')->ignore($jabatan->id),
            ],
            'tunj_jabatan' => 'required|numeric|min:0', // Validasi untuk tunj_jabatan
        ]);

        $jabatan->update($validatedData);

        return redirect()->route('jabatan.index')
            ->with('success', 'Jabatan berhasil diperbarui.');
    }

    /**
     * Menghapus data jabatan.
     */
    public function destroy(Jabatan $jabatan)
    {
        // Sebelum menghapus, pastikan tidak ada karyawan yang terikat dengan jabatan ini
        if ($jabatan->karyawans()->exists()) {
            return redirect()->route('jabatan.index')
                ->with('error', 'Gagal! Jabatan tidak dapat dihapus karena masih digunakan oleh karyawan.');
        }

        $jabatan->delete();

        return redirect()->route('jabatan.index')
            ->with('success', 'Jabatan berhasil dihapus.');
    }
}
