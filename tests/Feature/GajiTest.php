<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use App\Models\User;
use App\Models\Jabatan;
use App\Models\Karyawan;
use App\Models\TunjanganKehadiran;
use App\Models\Absensi;
use App\Models\Gaji;
use Carbon\Carbon;
use App\Models\SesiAbsensi;

class GajiTest extends TestCase
{
    use RefreshDatabase;

    private User $bendahara;
    private Karyawan $karyawan;
    private Jabatan $jabatan;
    private TunjanganKehadiran $tunjanganKehadiran;
    private SesiAbsensi $sesiAbsensi;


    protected function setUp(): void
    {
        parent::setUp();

        $this->bendahara = User::factory()->create(['role' => 'bendahara']);
        $this->jabatan = Jabatan::factory()->create([
            'nama_jabatan' => 'Guru Senior',
            'tunj_jabatan' => 1500000,
        ]);
        $this->karyawan = Karyawan::factory()->create([
            'jabatan_id' => $this->jabatan->id,
        ]);
        $this->tunjanganKehadiran = TunjanganKehadiran::factory()->create([
            'jumlah_tunjangan' => 25000,
        ]);
        $this->sesiAbsensi = SesiAbsensi::factory()->create();
    }

    public function test_bendahara_dapat_melihat_halaman_kelola_gaji(): void
    {
        $response = $this->actingAs($this->bendahara)->get(route('gaji.index'));
        $response->assertStatus(200);
        $response->assertSee($this->karyawan->nama);
    }

    public function test_bendahara_dapat_menyimpan_dan_mengkalkulasi_gaji_dengan_benar(): void
    {
        $bulanGaji = '2025-11';
        $tanggal = Carbon::createFromFormat('Y-m', $bulanGaji)->startOfMonth();

        for ($i = 0; $i < 22; $i++) {
            Absensi::factory()->create([
                'nip' => $this->karyawan->nip,
                'nama' => $this->karyawan->nama,
                'tanggal' => $tanggal->copy()->addDays($i),
                'sesi_absensi_id' => $this->sesiAbsensi->id,
            ]);
        }

        $dataForm = [
            'karyawan_id' => $this->karyawan->id,
            'bulan' => $bulanGaji,
            'tunjangan_kehadiran_id' => $this->tunjanganKehadiran->id,
            'gaji_pokok' => 5000000,
            'tunj_anak' => 200000,
            'tunj_komunikasi' => 150000,
            'tunj_pengabdian' => 300000,
            'tunj_kinerja' => 400000,
            'lembur' => 250000,
            'potongan' => 125000,
            'tunj_jabatan' => $this->jabatan->tunj_jabatan,
        ];

        $response = $this->actingAs($this->bendahara)->post(route('gaji.save'), $dataForm);

        $response->assertOk();
        $response->assertJson(['success' => true]);

        $gajiTersimpan = Gaji::first();

        $totalPendapatan = 5000000

            + (22 * $this->tunjanganKehadiran->jumlah_tunjangan)
            + $this->jabatan->tunj_jabatan
            + 200000
            + 150000
            + 300000
            + 400000
            + 250000;

        $gajiBersihHarapan = $totalPendapatan - 125000;

        $this->assertDatabaseHas('gajis', [
            'karyawan_id' => $this->karyawan->id,
            'bulan' => $bulanGaji,
        ]);

        $salaryService = $this->app->make(\App\Services\SalaryService::class);
        $gajiData = $salaryService->calculateDetailsForForm($this->karyawan, $bulanGaji);


        $this->assertEquals($gajiBersihHarapan, $gajiData['gaji_bersih'], "Kalkulasi gaji bersih tidak sesuai.");
    }
}
