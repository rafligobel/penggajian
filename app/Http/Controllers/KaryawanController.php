<?php

namespace App\Http\Controllers;

use App\Models\Karyawan;
use App\Models\Jabatan;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class KaryawanController extends Controller
{
    /**
     * Menampilkan daftar semua karyawan.
     */
    public function index()
    {
        // --- PERBAIKAN: Menggunakan get() bukan paginate() agar sesuai dengan JavaScript ---
        // Eager load relasi 'jabatan' untuk efisiensi
        $karyawans = Karyawan::with('jabatan')->latest()->get();

        return view('karyawan.index', compact('karyawans'));
    }

    /**
     * Menampilkan form untuk membuat karyawan baru.
     */
    public function create()
    {
        $jabatans = Jabatan::orderBy('nama_jabatan')->get();
        return view('karyawan.create', compact('jabatans'));
    }

    /**
     * Menyimpan karyawan baru ke database.
     */
    public function store(Request $request)
    {
        $validatedData = $request->validate([
            'nip' => 'required|string|max:255|unique:karyawans,nip',
            'nama' => 'required|string|max:255',
            'email' => 'nullable|email|max:255|unique:karyawans,email',
            'jabatan_id' => 'required|exists:jabatans,id',
            'telepon' => 'nullable|string|max:15',
            'alamat' => 'nullable|string',
        ]);

        Karyawan::create($validatedData);

        return redirect()->route('karyawan.index')->with('success', 'Karyawan berhasil ditambahkan.');
    }

    /**
     * Menampilkan form untuk mengedit karyawan.
     */
    public function edit(Karyawan $karyawan)
    {
        $jabatans = Jabatan::orderBy('nama_jabatan')->get();
        return view('karyawan.edit', compact('karyawan', 'jabatans'));
    }

    /**
     * Memperbarui data karyawan di database.
     */
    public function update(Request $request, Karyawan $karyawan)
    {
        $validatedData = $request->validate([
            'nip' => ['required', 'string', 'max:255', Rule::unique('karyawans')->ignore($karyawan->id)],
            'nama' => 'required|string|max:255',
            'email' => ['nullable', 'email', 'max:255', Rule::unique('karyawans')->ignore($karyawan->id)],
            'jabatan_id' => 'required|exists:jabatans,id',
            'telepon' => 'nullable|string|max:15',
            'alamat' => 'nullable|string',
        ]);

        $karyawan->update($validatedData);

        return redirect()->route('karyawan.index')->with('success', 'Data karyawan berhasil diperbarui.');
    }

    /**
     * Menghapus karyawan dari database.
     */
    public function destroy(Karyawan $karyawan)
    {
        $karyawan->delete();
        return redirect()->route('karyawan.index')->with('success', 'Data karyawan berhasil dihapus.');
    }
}
