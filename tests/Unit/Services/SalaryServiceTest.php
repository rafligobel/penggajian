<?php

namespace Tests\Unit\Services;

use Tests\TestCase;
use App\Models\Gaji;
use App\Models\Jabatan;
use App\Models\Karyawan;
use App\Models\Absensi;
use App\Models\TunjanganKehadiran;
use App\Services\SalaryService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Carbon\Carbon;
use PHPUnit\Framework\Attributes\Test;

class SalaryServiceTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->artisan('migrate');
    }

    #[Test]
    public function it_can_calculate_and_save_salary_data_correctly(): void
    {
        $jabatan = Jabatan::create([
            'nama_jabatan' => 'Guru Kelas',
            'tunj_jabatan' => 500000,
        ]);
        $karyawan = Karyawan::create([
            'nama' => 'Budi Santoso',
            'nip' => '199501012025031002',
            'jabatan_id' => $jabatan->id,
            'status_aktif' => true,
        ]);
        $tunjanganKehadiran = TunjanganKehadiran::create([
            'jenis_tunjangan' => 'Tunjangan Harian Pendidik',
            'jumlah_tunjangan' => 20000,
        ]);
        $bulanIni = '2025-09';
        for ($i = 1; $i <= 22; $i++) {
            Absensi::create([
                'nip' => $karyawan->nip,
                'nama' => $karyawan->nama,
                'tanggal' => Carbon::parse($bulanIni)->setDay($i),
                'jam' => '07:30:00',
            ]);
        }
        $dataDariFormBendahara = [
            'karyawan_id' => $karyawan->id,
            'bulan' => $bulanIni,
            'tunjangan_kehadiran_id' => $tunjanganKehadiran->id,
            'gaji_pokok' => 4000000,
            'tunj_anak' => 150000,
            'tunj_komunikasi' => 100000,
            'tunj_pengabdian' => 250000,
            'tunj_kinerja' => 300000,
            'lembur' => 100000,
            'kelebihan_jam' => 50000,
            'potongan' => 75000,
        ];
        $salaryService = new SalaryService();
        $karyawanHasil = $salaryService->saveSalaryData($dataDariFormBendahara);

        $this->assertInstanceOf(Karyawan::class, $karyawanHasil);
        $this->assertEquals($karyawan->id, $karyawanHasil->id);

        $this->assertDatabaseHas('gajis', [
            'karyawan_id' => $karyawan->id,
            'bulan' => $bulanIni,
        ]);

        $gajiTersimpan = Gaji::first();

        $this->assertEquals(4000000, $gajiTersimpan->gaji_pokok);
        $this->assertEquals(150000, $gajiTersimpan->tunj_anak);
        $this->assertEquals(75000, $gajiTersimpan->potongan);

        $this->assertEquals(500000, $gajiTersimpan->tunj_jabatan);
        $this->assertEquals(22, $gajiTersimpan->jumlah_kehadiran);
        $this->assertEquals(22 * 20000, $gajiTersimpan->tunj_kehadiran);
        $totalPendapatan = 4000000 + 500000 + (22 * 20000) + 150000 + 100000 + 250000 + 300000 + 100000 + 50000;
        $gajiBersihHarapan = $totalPendapatan - 75000;

        $this->assertEquals($gajiBersihHarapan, $gajiTersimpan->gaji_bersih);
    }
}
