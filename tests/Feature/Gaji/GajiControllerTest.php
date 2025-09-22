<?php

namespace Tests\Feature\Gaji;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use App\Models\User;
use App\Models\Jabatan;
use App\Models\Karyawan;
use App\Models\TunjanganKehadiran;
use App\Models\Absensi;
use Carbon\Carbon;
use PHPUnit\Framework\Attributes\Test;

class GajiControllerTest extends TestCase
{
    use RefreshDatabase;

    private User $bendahara;

    protected function setUp(): void
    {
        parent::setUp();
        $this->artisan('migrate');
        $this->bendahara = User::factory()->create(['role' => 'bendahara']);
    }

    #[Test]
    public function bendahara_can_view_the_salary_management_page(): void
    {
        $karyawan = Karyawan::factory()->create();
        $response = $this->actingAs($this->bendahara)->get(route('gaji.index'));

        $response->assertStatus(200);
        $response->assertViewIs('gaji.index');
        $response->assertSee($karyawan->nama);
    }

    #[Test]
    public function bendahara_can_save_salary_data_via_form(): void
    {
        // 1. Arrange: Siapkan semua data master
        $jabatan = Jabatan::create(['nama_jabatan' => 'Bendahara Yayasan', 'tunj_jabatan' => 1000000]);
        $karyawan = Karyawan::factory()->create(['jabatan_id' => $jabatan->id]);
        $tunjanganKehadiran = TunjanganKehadiran::create(['jenis_tunjangan' => 'Tunjangan Harian Staf', 'jumlah_tunjangan' => 30000]);
        $bulanIni = '2025-10';

        // PERBAIKAN DI SINI: Tambahkan 'nama' => $karyawan->nama saat membuat absensi
        Absensi::factory()->count(20)->create([
            'nip' => $karyawan->nip,
            'nama' => $karyawan->nama, // Ini yang ditambahkan
            'tanggal' => Carbon::parse($bulanIni)->startOfMonth()
        ]);

        // Siapkan data yang akan dikirim dari form
        $formData = [
            'karyawan_id' => $karyawan->id,
            'bulan' => $bulanIni,
            'tunjangan_kehadiran_id' => $tunjanganKehadiran->id,
            'gaji_pokok' => 5000000,
            'tunj_anak' => 250000,
            'potongan' => 100000,
            'tunj_komunikasi' => 0, 'tunj_pengabdian' => 0, 'tunj_kinerja' => 0,
            'lembur' => 0, 'kelebihan_jam' => 0,
        ];

        // 2. Act: Simulasikan Bendahara login dan mengirimkan form
        $response = $this->actingAs($this->bendahara)->post(route('gaji.save'), $formData);

        // 3. Assert: Verifikasi hasilnya
        $response->assertRedirect(route('gaji.index', ['bulan' => $bulanIni]));
        $response->assertSessionHas('success');

        $this->assertDatabaseHas('gajis', [
            'karyawan_id' => $karyawan->id,
            'bulan' => $bulanIni,
            'gaji_pokok' => 5000000,
            'tunj_jabatan' => 1000000,
            'jumlah_kehadiran' => 20,
            'tunj_kehadiran' => 20 * 30000,
            'potongan' => 100000,
        ]);
    }
}
