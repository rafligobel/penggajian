<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\Jabatan;
use App\Models\Karyawan;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class KaryawanTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;

    protected function setUp(): void
    {
        parent::setUp();
        $this->admin = User::factory()->create(['role' => 'admin']);
    }

    public function test_halaman_index_karyawan_berhasil_ditampilkan(): void
    {
        $karyawan = Karyawan::factory()->create();

        $response = $this->actingAs($this->admin)->get(route('karyawan.index'));

        $response->assertStatus(200);
        $response->assertSee($karyawan->nama);
        $response->assertSee($karyawan->jabatan->nama_jabatan);
    }

    public function test_admin_dapat_membuat_karyawan_baru(): void
    {
        $jabatan = Jabatan::factory()->create();
        $dataKaryawanBaru = [
            'nip' => '1234567890',
            'nama' => 'Budi Santoso',
            'jabatan_id' => $jabatan->id,
            'email' => 'budi.santoso@example.com',
            'telepon' => '081234567890',
            'alamat' => 'Jl. Merdeka No. 1, Jakarta',
            'status_aktif' => true,
        ];

        $response = $this->actingAs($this->admin)->post(route('karyawan.store'), $dataKaryawanBaru);

        $response->assertRedirect(route('karyawan.index'));
        $response->assertSessionHas('success');
        $this->assertDatabaseHas('karyawans', [
            'nip' => '1234567890',
            'nama' => 'Budi Santoso',
        ]);
    }

    public function test_admin_dapat_memperbarui_data_karyawan(): void
    {
        $karyawan = Karyawan::factory()->create();
        $jabatanBaru = Jabatan::factory()->create();
        $dataUpdate = [
            'nip' => $karyawan->nip, 
            'nama' => 'Budi Diperbarui',
            'jabatan_id' => $jabatanBaru->id,
            'email' => $karyawan->email, 
            'telepon' => $karyawan->telepon,
            'alamat' => $karyawan->alamat,
            'status_aktif' => $karyawan->status_aktif,
        ];

        $response = $this->actingAs($this->admin)->put(route('karyawan.update', $karyawan->id), $dataUpdate);

        $response->assertRedirect(route('karyawan.index'));
        $response->assertSessionHas('success');
        $this->assertDatabaseHas('karyawans', [
            'id' => $karyawan->id,
            'nama' => 'Budi Diperbarui',
            'jabatan_id' => $jabatanBaru->id,

        ]);
    }

    public function test_admin_dapat_menghapus_karyawan(): void
    {
        $karyawan = Karyawan::factory()->create();

        $response = $this->actingAs($this->admin)->delete(route('karyawan.destroy', $karyawan->id));

        $response->assertRedirect(route('karyawan.index'));
        $response->assertSessionHas('success');
        $this->assertDatabaseMissing('karyawans', [
            'id' => $karyawan->id,
        ]);
    }
}
