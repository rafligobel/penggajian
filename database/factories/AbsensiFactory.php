<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Absensi>
 */
class AbsensiFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            // Kolom 'nip' dan 'nama' akan kita isi secara manual dari dalam test
            // agar sesuai dengan data karyawan yang sedang diuji.
            // Di sini kita hanya mendefinisikan kolom lainnya.
            'tanggal' => $this->faker->dateTimeThisMonth(),
            'jam' => $this->faker->time('H:i:s'),
        ];
    }
}
