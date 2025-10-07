<?php

namespace Database\Factories;

use App\Models\Absensi;
use App\Models\Karyawan;
use App\Models\SesiAbsensi; // Tambahkan ini
use Illuminate\Database\Eloquent\Factories\Factory;

class AbsensiFactory extends Factory
{
    protected $model = Absensi::class;

    public function definition(): array
    {
        // Ambil atau buat satu SesiAbsensi untuk digunakan
        $sesiAbsensi = SesiAbsensi::first() ?? SesiAbsensi::factory()->create();

        return [
            'nip' => Karyawan::factory(),
            'nama' => function (array $attributes) {
                return Karyawan::find($attributes['nip'])->nama;
            },
            'tanggal' => $this->faker->date(),
            'jam' => $this->faker->dateTimeBetween('07:00:00', '09:00:00')->format('H:i:s'),
            'sesi_absensi_id' => $sesiAbsensi->id, // <-- TAMBAHKAN BARIS INI
        ];
    }
}
