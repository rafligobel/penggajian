<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory; // Opsional, jika Anda menggunakan factory
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Karyawan extends Model
{
    // Jika Anda menggunakan Laravel Sail atau memiliki factory untuk Karyawan,
    // Anda bisa mengaktifkan trait HasFactory.
    // use HasFactory;

    /**
     * Nama tabel yang terhubung dengan model.
     *
     * @var string
     */
    protected $table = 'karyawans';

    /**
     * Atribut yang dapat diisi secara massal.
     * Kolom-kolom ini sesuai dengan migrasi tabel 'karyawans'.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'nama',
        'nip',
        'alamat',
        'telepon',
        'jabatan',
        'status_aktif',
    ];

    /**
     * Atribut yang harus di-cast ke tipe data tertentu.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'status_aktif' => 'boolean', // Meng-cast kolom status_aktif sebagai boolean
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    /**
     * Mendapatkan semua record absensi untuk Karyawan ini.
     * Mendefinisikan relasi one-to-many ke model Absensi.
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function absensi(): HasMany
    {
        // Relasi ini mengasumsikan tabel 'absensis' memiliki kolom 'nip'
        // yang merujuk ke kolom 'nip' pada tabel 'karyawans'.
        return $this->hasMany(Absensi::class, 'nip', 'nip');
    }

    // Anda bisa menambahkan metode atau lingkup kueri lain di sini jika diperlukan.
}
