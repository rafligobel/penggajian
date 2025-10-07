<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\Karyawan;
use App\Models\SesiAbsensi;
use App\Models\Absensi;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Carbon\Carbon;
use Tests\TestCase;

class AbsensiTest extends TestCase
{
    use RefreshDatabase;

    private Karyawan $karyawan;
    private User $bendahara;

    protected function setUp(): void
    {
        parent::setUp();
        $this->karyawan = Karyawan::factory()->create();
        $this->bendahara = User::factory()->create(['role' => 'bendahara']);
        SesiAbsensi::factory()->create(['is_default' => true]);
    }

    public function test_pegawai_dapat_absen_pada_hari_kerja(): void
    {
        Carbon::setTestNow(Carbon::parse('Tuesday 08:00:00'));

        $response = $this->post(route('absensi.store'), [
            'identifier' => $this->karyawan->nip,
        ]);

        $response->assertRedirect();
        $response->assertSessionHas('success');

        $this->assertDatabaseHas('absensis', [
            'nip' => $this->karyawan->nip,
            'tanggal' => Carbon::today()->toDateString(),
        ]);
    }

    public function test_pegawai_tidak_dapat_absen_pada_hari_libur(): void
    {
        Carbon::setTestNow(Carbon::parse('Saturday 09:00:00'));

        $response = $this->post(route('absensi.store'), ['identifier' => $this->karyawan->nip]);

        $response->assertSessionHas('info');
        $this->assertDatabaseMissing('absensis', [
            'nip' => $this->karyawan->nip,
        ]);
    }

    public function test_pegawai_tidak_dapat_absen_dua_kali(): void
    {
        Carbon::setTestNow(Carbon::parse('Tuesday 08:30:00'));

        Absensi::factory()->create([
            'nip' => $this->karyawan->nip,
            'nama' => $this->karyawan->nama,
            'tanggal' => Carbon::today()->toDateString(),
        ]);

        $response = $this->post(route('absensi.store'), [
            'identifier' => $this->karyawan->nip
        ]);

        $response->assertSessionHas('info');
        $this->assertCount(1, Absensi::where('nip', $this->karyawan->nip)->get());
    }

    public function test_api_rekap_mengembalikan_data_absensi_dengan_benar(): void
    {
        Absensi::factory()->create([
            'nip' => $this->karyawan->nip,
            'nama' => $this->karyawan->nama,
            'tanggal' => Carbon::today()->toDateString(),
        ]);

        $bulan = Carbon::today()->format('Y-m');
        $response = $this->actingAs($this->bendahara)->getJson(route('laporan.absensi.data', ['bulan' => $bulan]));

        $response->assertStatus(200);
        $response->assertJsonFragment([
            'nama' => $this->karyawan->nama,
            'nip' => $this->karyawan->nip
        ]);
    }
}
