<?php

namespace Database\Seeders;

use App\Models\Karyawan;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash; // <-- Import Hash

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run()
    {


        User::create([
            'name' => 'Admin',
            'email' => 'admin@example.com',
            'password' => Hash::make('password'), // <-- Gunakan Hash::make()
            'role' => 'admin'
        ]);

        User::create([
            'name' => 'Bendahara',
            'email' => 'bendahara@example.com',
            'password' => Hash::make('password'), // <-- Gunakan Hash::make()
            'role' => 'bendahara'
        ]);

        $karyawanUser = User::create([
            'name' => 'Tenaga Kerja 1',
            'email' => 'tenagakerja@example.com',
            'password' => Hash::make('password'),
            'role' => 'tenaga_kerja'
        ]);

        // Hubungkan dengan data karyawan (jika sudah ada)
        // Ganti 'NIP_KARYAWAN' dengan NIP yang sesuai
        $karyawanData = Karyawan::where('nip', 'NIP_KARYAWAN')->first();
        if ($karyawanData) {
            $karyawanData->user_id = $karyawanUser->id;
            $karyawanData->save();
        }
    }
}
