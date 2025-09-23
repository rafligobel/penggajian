<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\TunjanganKehadiran>
 */
class TunjanganKehadiranFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            // PERBAIKAN: Menggunakan 'jenis_tunjangan' agar sesuai dengan migrasi
            'jenis_tunjangan' => $this->faker->words(2, true) . ' Tunjangan',

            // Kolom ini sudah benar
            'jumlah_tunjangan' => $this->faker->numberBetween(15000, 30000),
        ];
    }
}
