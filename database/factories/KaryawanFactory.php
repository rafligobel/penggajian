<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use App\Models\Karyawan;
use App\Models\Jabatan;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Karyawan>
 */
class KaryawanFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var string
     */
    protected $model = Karyawan::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            // Kolom-kolom yang sesuai dengan migrasi create_karyawans_table.php
            'nip' => $this->faker->unique()->numerify('##########'), // Membuat 10 digit NIP unik
            'nama' => $this->faker->name(),
            'jabatan_id' => Jabatan::factory(),
            'email' => $this->faker->unique()->safeEmail(),
            'telepon' => $this->faker->phoneNumber(),
            'alamat' => $this->faker->address(),
            'status_aktif' => $this->faker->boolean(90), // 90
        ];
    }
}