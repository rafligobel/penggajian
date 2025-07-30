<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;
use Illuminate\Support\Facades\Auth;

class UserController extends Controller
{
    /**
     * Menampilkan daftar semua pengguna (khusus admin).
     */
    public function index()
    {
        // Ambil semua user KECUALI superadmin
        $users = User::where('role', '!=', 'superadmin')->latest()->paginate(10);
        return view('users.index', compact('users'));
    }


    public function create()
    {
        return view('users.create'); // Arahkan ke view create.blade.php
    }

    /**
     * BARU: Menyimpan pengguna baru ke database.
     */
    public function store(Request $request)
    {
        // 1. Validasi Input
        $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|string|email|max:255|unique:users', // Pastikan email unik
            'password' => 'required|string|min:8', // Password minimal 8 karakter
            'role' => 'required|string',
        ]);

        // 2. Buat Pengguna Baru
        User::create([
            'name' => $request->name,
            'email' => $request->email,
            'role' => $request->role,
            'password' => Hash::make($request->password), // Enkripsi password sebelum disimpan
        ]);

        // 3. Arahkan kembali ke halaman index dengan pesan sukses
        return redirect()->route('users.index')->with('success', 'Pengguna baru berhasil ditambahkan.');
    }


    /**
     * Menampilkan form untuk mengedit pengguna (khusus admin).
     */
    public function edit(User $user)
    {
        return view('users.edit', compact('user'));
    }

    /**
     * Menyimpan perubahan data pengguna ke database (khusus admin).
     */
    public function update(Request $request, User $user)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'email' => ['required', 'email', Rule::unique('users')->ignore($user->id)],
            'role' => 'required|string', // Validasi untuk role
        ]);

        $user->update($request->only(['name', 'email', 'role']));

        return redirect()->route('users.index')->with('success', 'Data pengguna berhasil diperbarui.');
    }

    /**
     * Menghapus pengguna (khusus admin).
     */
    public function destroy(User $user)
    {
        // Cek jika pengguna yang akan dihapus adalah superadmin
        if ($user->role === 'superadmin') {
            return redirect()->route('users.index')->with('error', 'Super Admin tidak dapat dihapus.');
        }

        // Cek agar pengguna tidak bisa menghapus dirinya sendiri
        if ($user->id === Auth::id()) {
            return redirect()->route('users.index')->with('error', 'Anda tidak dapat menghapus akun Anda sendiri.');
        }

        $user->delete();

        return redirect()->route('users.index')->with('success', 'Pengguna berhasil dihapus.');
    }
}
