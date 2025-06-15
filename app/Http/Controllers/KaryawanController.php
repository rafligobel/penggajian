<?php

namespace App\Http\Controllers;

use App\Models\Karyawan;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class KaryawanController extends Controller
{
    public function index(Request $request)
    {
        // PERUBAHAN: Ambil SEMUA data karyawan, bukan per halaman (paginate)
        // Data ini akan difilter secara real-time oleh JavaScript di view.
        $karyawans = Karyawan::where('status_aktif', true)->orderBy('nama')->get();

        return view('karyawan.index', compact('karyawans'));
    }

    // ... method lainnya (create, store, show, dll) tidak perlu diubah ...
    public function create()
    {
        return view('karyawan.create');
    }

    public function store(Request $request)
    {
        $request->validate([
            'nama' => 'required|string|max:100',
            'nip' => 'required|unique:karyawans,nip',
            'alamat' => 'required',
            'telepon' => 'required',
            'jabatan' => 'required',
        ]);

        Karyawan::create($request->all());
        return redirect()->route('karyawan.index')->with('success', 'Data karyawan berhasil ditambahkan.');
    }

    public function show($id)
    {
        $karyawan = Karyawan::findOrFail($id);
        return view('karyawan.show', compact('karyawan'));
    }

    public function edit($id)
    {
        $karyawan = Karyawan::findOrFail($id);
        return view('karyawan.edit', compact('karyawan'));
    }

    public function update(Request $request, $id)
    {
        $karyawan = Karyawan::findOrFail($id);
        $request->validate([
            'nama' => 'required|string|max:100',
            'nip' => 'required|unique:karyawans,nip,' . $karyawan->id,
            'alamat' => 'required',
            'telepon' => 'required',
            'jabatan' => 'required',
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
