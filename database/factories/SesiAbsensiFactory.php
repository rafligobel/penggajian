<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use App\Models\SesiAbsensi;
use Carbon\Carbon;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\SesiAbsensi>
 */
class SesiAbsensiFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var string
     */
    protected $model = SesiAbsensi::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            // Kita set tanggal ke masa lalu, karena is_default=true yang akan jadi acuan
            'tanggal' => Carbon::yesterday(),
            'tipe' => 'default',
            'waktu_mulai' => '07:00:00', // Sesi dibuka jam 7 pagi
            'waktu_selesai' => '17:00:00', // Sesi ditutup jam 5 sore
            'keterangan' => 'Sesi absensi default untuk hari kerja.',
            'is_default' => true, // Tanda bahwa ini adalah sesi utama
            'hari_kerja' => json_encode(['Senin', 'Selasa', 'Rabu', 'Kamis', 'Jumat']),
        ];
    }
}
