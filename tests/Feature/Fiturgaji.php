<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use App\Models\User;
use App\Models\Jabatan;
use App\Models\Karyawan;
use App\Models\TunjanganKehadiran;
use App\Models\Absensi;
use Carbon\Carbon;
use PHPUnit\Framework\Attributes\Test;

class Fiturgaji extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function bendahara_dapat_menyimpan_dan_mengkalkulasi_data_gaji_dengan_benar(): void
    {

        $bendahara = User::factory()->create(['role' => 'bendahara']);

        $jabatan = Jabatan::create([
            'nama_jabatan' => 'Kepala Administrasi',
            'tunj_jabatan' => 1200000,
        ]);

        $karyawan = Karyawan::factory()->create([
            'nama' => 'Siti Aminah',
            'jabatan_id' => $jabatan->id,
        ]);

        $tunjanganKehadiran = TunjanganKehadiran::create([
            'jenis_tunjangan' => 'Tunjangan Harian Staf Administrasi',
            'jumlah_tunjangan' => 35000,
        ]);

        $bulanGaji = '2025-11';
        Absensi::factory()->count(21)->create([
            'nip' => $karyawan->nip,
            'nama' => $karyawan->nama,
            'tanggal' => Carbon::parse($bulanGaji)->startOfMonth(),
        ]);

        $dataForm = [
            'karyawan_id' => $karyawan->id,
            'bulan' => $bulanGaji,
            'tunjangan_kehadiran_id' => $tunjanganKehadiran->id,
            'gaji_pokok' => 5500000,
            'tunj_anak' => 300000,
            'tunj_komunikasi' => 150000,
            'tunj_pengabdian' => 450000,
            'tunj_kinerja' => 500000,
            'lembur' => 200000,
            'kelebihan_jam' => 100000,
            'potongan' => 125000,
        ];


        $response = $this->actingAs($bendahara)
            ->post(route('gaji.save'), $dataForm);

        $response->assertStatus(302);
        $response->assertRedirect(route('gaji.index', ['bulan' => $bulanGaji]));
        $response->assertSessionHas('success');
        $this->assertDatabaseHas('gajis', [
            'karyawan_id' => $karyawan->id,
            'bulan' => $bulanGaji,
            'gaji_pokok' => 5500000,
            'tunj_anak' => 300000,
            'potongan' => 125000,
            'tunj_jabatan' => 1200000,
            'jumlah_kehadiran' => 21,
            'tunj_kehadiran' => 21 * 35000,
        ]);

        $gajiTersimpan = \App\Models\Gaji::first();
        $totalPendapatan = 5500000 + 1200000 + (21 * 35000) + 300000 + 150000 + 450000 + 500000 + 200000 + 100000;
        $gajiBersihHarapan = $totalPendapatan - 125000;

        $this->assertEquals($gajiBersihHarapan, $gajiTersimpan->gaji_bersih, "Perhitungan gaji bersih tidak akurat.");
    }
}
