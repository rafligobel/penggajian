<?php

namespace App\Http\Controllers;

use App\Models\Jabatan; // <-- TAMBAHKAN INI
use App\Models\Karyawan;
use Illuminate\Http\Request;

class KaryawanController extends Controller
{
    public function index()
    {
        // Gunakan with('jabatan') untuk mengambil data karyawan beserta jabatannya dalam satu query
        $karyawans = Karyawan::with('jabatan')
            ->where('status_aktif', true)
            ->orderBy('nama')
            ->get();

        return view('karyawan.index', compact('karyawans'));
    }

    public function create()
    {
        // Ambil daftar jabatan untuk ditampilkan di form dropdown
        $jabatans = Jabatan::orderBy('nama_jabatan')->get();
        return view('karyawan.create', compact('jabatans'));
    }

    public function store(Request $request)
    {
        // Validasi diubah ke 'jabatan_id'
        $request->validate([
            'nama' => 'required|string|max:100',
            'nip' => 'required|unique:karyawans,nip',
            'alamat' => 'required',
            'telepon' => 'required',
            'email' => 'nullable|email|max:255|unique:karyawans,email',
            'jabatan_id' => 'required|exists:jabatans,id', // <-- DIUBAH
        ]);

        Karyawan::create($request->all());
        return redirect()->route('karyawan.index')->with('success', 'Data karyawan berhasil ditambahkan.');
    }

    public function show($id)
    {
        $karyawan = Karyawan::with('jabatan')->findOrFail($id);
        return view('karyawan.show', compact('karyawan'));
    }

    public function edit($id)
    {
        $karyawan = Karyawan::findOrFail($id);
        // Ambil juga data jabatan untuk dropdown di form edit
        $jabatans = Jabatan::orderBy('nama_jabatan')->get();
        return view('karyawan.edit', compact('karyawan', 'jabatans'));
    }

    public function update(Request $request, $id)
    {
        $karyawan = Karyawan::findOrFail($id);
        // Validasi diubah ke 'jabatan_id'
        $request->validate([
            'nama' => 'required|string|max:100',
            'nip' => 'required|unique:karyawans,nip,' . $karyawan->id,
            'alamat' => 'required',
            'telepon' => 'required',
            'email' => 'nullable|email|max:255|unique:karyawans,email,' . $karyawan->id,
            'jabatan_id' => 'required|exists:jabatans,id', // <-- DIUBAH
        ]);

        $karyawan->update($request->all());
        return redirect()->route('karyawan.index')->with('success', 'Data karyawan berhasil diperbarui.');
    }

    public function destroy($id)
    {
        $karyawan = Karyawan::findOrFail($id);
        $karyawan->delete();
        return redirect()->route('karyawan.index')->with('success', 'Data karyawan berhasil dihapus.');
    }
}
