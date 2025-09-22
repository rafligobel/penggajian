<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use App\Models\Karyawan;
use App\Models\SesiAbsensi;
use App\Models\Absensi;
use Carbon\Carbon;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\Attributes\DataProvider;

/**
 * PENGUJIAN FITUR UTAMA: ABSENSI
 *
 * Test ini mensimulasikan alur kerja lengkap dari seorang Karyawan
 * yang mencoba melakukan absensi pada halaman publik.
 */
class Fiturabsensi extends TestCase
{
    use RefreshDatabase;

    /**
     * Menyiapkan environment sebelum setiap test dijalankan.
     */
    protected function setUp(): void
    {
        parent::setUp();
        $this->artisan('migrate');
    }

    #[Test]
    public function karyawan_dapat_melakukan_absensi_saat_sesi_aktif(): void
    {
        // 1. Arrange: Siapkan kondisi ideal
        $karyawan = Karyawan::factory()->create();

        // Kita atur "waktu saat ini" ke hari Senin, jam 10 pagi (sesi aktif)
        $waktuTes = Carbon::create(2025, 9, 22, 10, 0, 0); // Senin
        Carbon::setTestNow($waktuTes);

        // Buat sesi absensi untuk hari ini yang sedang berlangsung
        SesiAbsensi::create([
            'tanggal' => $waktuTes->toDateString(),
            'jam_mulai' => '08:00:00',
            'jam_selesai' => '16:00:00',
        ]);

        // 2. Act: Simulasikan karyawan mengirim form absensi
        $response = $this->post(route('absensi.store'), [
            'nip' => $karyawan->nip,
        ]);

        // 3. Assert: Pastikan hasilnya sukses
        $response->assertRedirect(route('absensi.index'));
        $response->assertSessionHas('success', 'Absensi berhasil dicatat.');

        // Verifikasi terpenting: data absensi benar-benar ada di database
        $this->assertDatabaseHas('absensis', [
            'nip' => $karyawan->nip,
            'tanggal' => $waktuTes->toDateString(),
        ]);
    }

    #[Test]
    public function karyawan_tidak_dapat_absen_dua_kali_pada_hari_yang_sama(): void
    {
        // 1. Arrange: Siapkan kondisi di mana karyawan sudah pernah absen hari ini
        $karyawan = Karyawan::factory()->create();
        $waktuTes = Carbon::create(2025, 9, 22, 11, 0, 0); // Senin, jam 11 siang
        Carbon::setTestNow($waktuTes);

        SesiAbsensi::create([
            'tanggal' => $waktuTes->toDateString(),
            'jam_mulai' => '08:00:00',
            'jam_selesai' => '16:00:00',
        ]);

        // Buat data absensi "sebelumnya" di hari yang sama
        Absensi::create([
            'nip' => $karyawan->nip,
            'nama' => $karyawan->nama,
            'tanggal' => $waktuTes->toDateString(),
            'jam' => '08:05:00',
        ]);

        // 2. Act: Karyawan mencoba absen lagi
        $response = $this->post(route('absensi.store'), [
            'nip' => $karyawan->nip,
        ]);

        // 3. Assert: Pastikan proses digagalkan
        $response->assertRedirect(route('absensi.index'));
        $response->assertSessionHas('error', 'Anda sudah melakukan absensi hari ini.');

        // Pastikan jumlah data absensi untuk karyawan ini tetap 1
        $this->assertDatabaseCount('absensis', 1);
    }

    /**
     * Menyediakan berbagai skenario di mana sesi absensi tidak aktif.
     */
    public static function provideInactiveSessions(): array
    {
        return [
            'Sesi Belum Dimulai' => [Carbon::create(2025, 9, 22, 7, 0, 0), false], // Senin, 7 pagi
            'Sesi Sudah Berakhir' => [Carbon::create(2025, 9, 22, 17, 0, 0), false], // Senin, 5 sore
            'Saat Hari Libur'   => [Carbon::create(2025, 9, 23, 9, 0, 0), true], // Selasa, ditandai libur
            'Saat Weekend'      => [Carbon::create(2025, 9, 27, 9, 0, 0), false], // Sabtu
        ];
    }

    #[Test]
    #[DataProvider('provideInactiveSessions')]
    public function karyawan_tidak_dapat_absen_saat_sesi_tidak_aktif(Carbon $waktuTes, bool $isLibur): void
    {
        // 1. Arrange: Siapkan kondisi sesuai skenario dari DataProvider
        $karyawan = Karyawan::factory()->create();
        Carbon::setTestNow($waktuTes);

        SesiAbsensi::create([
            'tanggal' => $waktuTes->toDateString(),
            'jam_mulai' => '08:00:00',
            'jam_selesai' => '16:00:00',
            'is_libur' => $isLibur,
        ]);

        // 2. Act: Karyawan mencoba absen
        $response = $this->post(route('absensi.store'), [
            'nip' => $karyawan->nip,
        ]);

        // 3. Assert: Pastikan proses digagalkan
        $response->assertRedirect(route('absensi.index'));
        $response->assertSessionHas('error'); // Pastikan ada pesan error, apa pun isinya

        // Verifikasi terpenting: TIDAK ADA data absensi baru yang masuk ke database
        $this->assertDatabaseMissing('absensis', [
            'nip' => $karyawan->nip,
        ]);
    }
}
