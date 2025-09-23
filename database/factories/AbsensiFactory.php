<?php

namespace Database\Factories;

use App\Models\Absensi;
use App\Models\Karyawan;
use Illuminate\Database\Eloquent\Factories\Factory;

class AbsensiFactory extends Factory
{
    protected $model = Absensi::class;

    public function definition(): array
    {
        $karyawan = Karyawan::factory()->create();

        return [
            'nip' => $karyawan->nip,
            'nama' => $karyawan->nama,
            'tanggal' => $this->faker->date(),
            // --- PERBAIKAN FINAL ---
            // Mengisi kolom 'jam' sesuai migrasi baru
            'jam' => $this->faker->dateTimeBetween('07:00:00', '09:00:00')->format('H:i:s'),
        ];
    }
}