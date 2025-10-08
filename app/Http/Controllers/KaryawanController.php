<?php

namespace App\Http\Controllers;

use App\Models\Karyawan;
use App\Models\Jabatan;
use App\Models\User; // <-- Tambahkan ini
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash; // <-- Tambahkan ini
use Illuminate\Validation\Rule; // <-- Tambahkan ini

class KaryawanController extends Controller
{
    public function index()
    {
        $karyawans = Karyawan::with(['jabatan', 'user'])->latest()->get(); // Eager load user juga
        return view('karyawan.index', compact('karyawans'));
    }

    public function create()
    {
        $karyawan = new Karyawan(); // Kirim model kosong ke view
        $jabatans = Jabatan::orderBy('nama_jabatan')->get();
        // Variabel $tombol disesuaikan dengan form Anda
        return view('karyawan.create', compact('karyawan', 'jabatans'))->with('tombol', 'Simpan');
    }

    public function store(Request $request)
    {
        // --- AWAL PERUBAHAN ---
        $validated = $request->validate([
            'nama' => 'required|string|max:255',
            'nip' => 'required|string|max:255|unique:karyawans,nip',
            'alamat' => 'nullable|string',
            'telepon' => 'nullable|string|max:15',
            'jabatan_id' => 'nullable|exists:jabatans,id',
            'status_aktif' => 'sometimes|boolean',
            'user_email' => 'required|email|max:255|unique:users,email',
            'password' => 'required|string|min:8',
        ]);

        // 1. Buat Akun User terlebih dahulu
        $user = User::create([
            'name' => $validated['nama'],
            'email' => $validated['user_email'],
            'password' => Hash::make($validated['password']),
            'role' => 'tenaga_kerja',
        ]);

        // 2. Buat Karyawan dan hubungkan user_id
        Karyawan::create([
            'user_id' => $user->id, // Hubungkan ID dari user yang baru dibuat
            'nama' => $validated['nama'],
            'nip' => $validated['nip'],
            'alamat' => $validated['alamat'],
            'telepon' => $validated['telepon'],
            'jabatan_id' => $validated['jabatan_id'],
            'status_aktif' => $request->boolean('status_aktif'),
            // 'email' di tabel karyawan bisa kita samakan atau biarkan (sesuai kebutuhan)
            'email' => $validated['user_email'],
        ]);

        return redirect()->route('karyawan.index')->with('success', 'Data karyawan dan akun login berhasil dibuat.');
        // --- AKHIR PERUBAHAN ---
    }

    public function show(Karyawan $karyawan)
    {
        return view('karyawan.show', compact('karyawan'));
    }

    public function edit(Karyawan $karyawan)
    {
        $jabatans = Jabatan::orderBy('nama_jabatan')->get();
        // Variabel $tombol disesuaikan dengan form Anda
        return view('karyawan.edit', compact('karyawan', 'jabatans'))->with('tombol', 'Perbarui');
    }

    public function update(Request $request, Karyawan $karyawan)
    {
        // --- AWAL PERUBAHAN ---
        $validated = $request->validate([
            'nama' => 'required|string|max:255',
            'nip' => ['required', 'string', 'max:255', Rule::unique('karyawans')->ignore($karyawan->id)],
            'alamat' => 'nullable|string',
            'telepon' => 'nullable|string|max:15',
            'jabatan_id' => 'nullable|exists:jabatans,id',
            'status_aktif' => 'sometimes|boolean',
            'user_email' => ['required', 'email', 'max:255', Rule::unique('users', 'email')->ignore(optional($karyawan->user)->id)],
            'password' => 'nullable|string|min:8',
        ]);

        // 1. Update data karyawan
        $karyawan->update([
            'nama' => $validated['nama'],
            'nip' => $validated['nip'],
            'alamat' => $validated['alamat'],
            'telepon' => $validated['telepon'],
            'jabatan_id' => $validated['jabatan_id'],
            'status_aktif' => $request->boolean('status_aktif'),
            'email' => $validated['user_email'],
        ]);

        // 2. Update data user yang terhubung
        if ($karyawan->user) {
            $karyawan->user->name = $validated['nama'];
            $karyawan->user->email = $validated['user_email'];
            if ($request->filled('password')) {
                $karyawan->user->password = Hash::make($validated['password']);
            }
            $karyawan->user->save();
        } else {
            // Jika karena suatu hal user tidak ada, buatkan user baru
            $user = User::create([
                'name' => $validated['nama'],
                'email' => $validated['user_email'],
                'password' => Hash::make($request->password ?: 'password'), // default password jika kosong
                'role' => 'tenaga_kerja',
            ]);
            $karyawan->user_id = $user->id;
            $karyawan->save();
        }

        return redirect()->route('karyawan.index')->with('success', 'Data karyawan dan akun login berhasil diperbarui.');
        // --- AKHIR PERUBAHAN ---
    }

    public function destroy(Karyawan $karyawan)
    {
        // --- AWAL PERUBAHAN ---
        // Hapus juga user yang terhubung untuk menjaga kebersihan data
        if ($karyawan->user) {
            $karyawan->user->delete();
        }
        $karyawan->delete();
        // --- AKHIR PERUBAHAN ---

        return redirect()->route('karyawan.index')->with('success', 'Data karyawan berhasil dihapus.');
    }
}
