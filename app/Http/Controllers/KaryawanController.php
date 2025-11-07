<?php

namespace App\Http\Controllers;

use App\Models\Karyawan;
use App\Models\Jabatan;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;
use Illuminate\Support\Facades\Storage; // <-- TAMBAHAN: Import Storage facade

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
        $validated = $request->validate([
            'nama' => 'required|string|max:255',
            'nip' => 'required|string|max:255|unique:karyawans,nip',
            'alamat' => 'nullable|string',
            'telepon' => 'nullable|string|max:15',
            'jabatan_id' => 'nullable|exists:jabatans,id',
            'user_email' => 'required|email|max:255|unique:users,email',
            'password' => 'required|string|min:8',
            'tanggal_masuk' => 'nullable|date',
            'jumlah_anak' => 'nullable|integer|min:0',
            'foto' => 'nullable|image|mimes:jpeg,png,jpg,webp|max:2048', // <-- TAMBAHAN: Validasi foto
        ]);

        // 1. Buat Akun User terlebih dahulu
        $user = User::create([
            'name' => $validated['nama'],
            'email' => $validated['user_email'],
            'password' => Hash::make($validated['password']),
            'role' => 'tenaga_kerja', // Sesuai file Anda
        ]);

        // 2. Siapkan Data Karyawan
        $karyawanData = [
            'user_id' => $user->id,
            'nama' => $validated['nama'],
            'nip' => $validated['nip'],
            'alamat' => $validated['alamat'],
            'telepon' => $validated['telepon'],
            'jabatan_id' => $validated['jabatan_id'],
            'email' => $validated['user_email'],
            'tanggal_masuk' => $validated['tanggal_masuk'] ?? null,
            'jumlah_anak' => $validated['jumlah_anak'] ?? 0,
            'foto' => null, // Default
        ];

        // 3. TAMBAHAN: Logika Upload Foto
        if ($request->hasFile('foto')) {
            $filename = time() . '_' . $request->file('foto')->getClientOriginalName();
            // Simpan file ke storage/app/public/foto_pegawai
            $request->file('foto')->storeAs('public/foto_pegawai', $filename);
            $karyawanData['foto'] = $filename; // Simpan nama file ke database
        }

        // 4. Buat Karyawan
        Karyawan::create($karyawanData);

        return redirect()->route('karyawan.index')->with('success', 'Data karyawan dan akun login berhasil dibuat.');
    }

    public function show(Karyawan $karyawan)
    {
        // Tampilkan foto di halaman show jika diperlukan (opsional)
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
        $validated = $request->validate([
            'nama' => 'required|string|max:255',
            'nip' => ['required', 'string', 'max:255', Rule::unique('karyawans')->ignore($karyawan->id)],
            'alamat' => 'nullable|string',
            'telepon' => 'nullable|string|max:15',
            'jabatan_id' => 'nullable|exists:jabatans,id',
            'user_email' => ['required', 'email', 'max:255', Rule::unique('users', 'email')->ignore(optional($karyawan->user)->id)],
            'password' => 'nullable|string|min:8',
            'tanggal_masuk' => 'nullable|date',
            'jumlah_anak' => 'nullable|integer|min:0',
            'foto' => 'nullable|image|mimes:jpeg,png,jpg,webp|max:2048', // <-- TAMBAHAN: Validasi foto
        ]);

        // 1. Siapkan data update karyawan
        $dataToUpdate = [
            'nama' => $validated['nama'],
            'nip' => $validated['nip'],
            'alamat' => $validated['alamat'],
            'telepon' => $validated['telepon'],
            'jabatan_id' => $validated['jabatan_id'],
            'email' => $validated['user_email'],
            'tanggal_masuk' => $validated['tanggal_masuk'] ?? null,
            'jumlah_anak' => $validated['jumlah_anak'] ?? 0,
        ];

        // 2. TAMBAHAN: Logika Update Foto
        if ($request->hasFile('foto')) {
            // Hapus foto lama jika ada
            if ($karyawan->foto) {
                Storage::delete('public/foto_pegawai/' . $karyawan->foto);
            }

            // Simpan foto baru
            $filename = time() . '_' . $request->file('foto')->getClientOriginalName();
            $request->file('foto')->storeAs('public/foto_pegawai', $filename);
            $dataToUpdate['foto'] = $filename; // Tambahkan nama file baru ke data update
        }

        // 3. Update data karyawan
        $karyawan->update($dataToUpdate);

        // 4. Update data user yang terhubung
        if ($karyawan->user) {
            $karyawan->user->name = $validated['nama'];
            $karyawan->user->email = $validated['user_email'];
            if ($request->filled('password')) {
                $karyawan->user->password = Hash::make($validated['password']);
            }
            $karyawan->user->save();
        }

        return redirect()->route('karyawan.index')->with('success', 'Data karyawan dan akun login berhasil diperbarui.');
    }

    public function destroy(Karyawan $karyawan)
    {
        // TAMBAHAN: Hapus foto dari storage
        if ($karyawan->foto) {
            Storage::delete('public/foto_pegawai/' . $karyawan->foto);
        }

        // Hapus juga user yang terhubung
        if ($karyawan->user) {
            $karyawan->user->delete();
        }

        $karyawan->delete();

        return redirect()->route('karyawan.index')->with('success', 'Data karyawan berhasil dihapus.');
    }
}
