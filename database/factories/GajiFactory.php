<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use App\Models\Karyawan;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Gaji>
 */
class GajiFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'karyawan_id' => Karyawan::factory(),
            'bulan' => $this->faker->date('Y-m'),
            'gaji_pokok' => $this->faker->numberBetween(4000000, 8000000),
            'tunj_anak' => $this->faker->numberBetween(100000, 500000),
            'tunj_pengabdian' => $this->faker->numberBetween(100000, 500000),
            'lembur' => $this->faker->numberBetween(0, 1000000),
            'potongan' => $this->faker->numberBetween(0, 500000),
            'tunj_komunikasi' => $this->faker->numberBetween(100000, 300000),
            'tunj_kinerja' => $this->faker->numberBetween(200000, 700000),
            'kelebihan_jam' => $this->faker->numberBetween(0, 500000),
            'tunj_jabatan' => $this->faker->numberBetween(500000, 2000000),
            'tunj_kehadiran' => $this->faker->numberBetween(300000, 800000),
            'jumlah_kehadiran' => $this->faker->numberBetween(20, 26),
            'gaji_bersih' => $this->faker->numberBetween(5000000, 15000000),
        ];
    }
}