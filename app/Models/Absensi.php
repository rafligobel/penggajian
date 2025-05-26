<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory; // Opsional, jika Anda menggunakan factory
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Absensi extends Model
{
    // Jika Anda menggunakan Laravel Sail atau memiliki factory untuk Absensi,
    // Anda bisa mengaktifkan trait HasFactory.
    // use HasFactory;

    /**
     * Nama tabel yang terhubung dengan model.
     *
     * @var string
     */
    protected $table = 'absensis';

    /**
     * Atribut yang dapat diisi secara massal.
     * Kolom-kolom ini sesuai dengan migrasi tabel 'absensis'.
     * Kolom 'status' dihilangkan karena tidak ada dalam migrasi.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'nip',    // Foreign key ke tabel karyawans (menggunakan NIP)
        'nama',   // Nama karyawan (disimpan juga di tabel absensis sesuai migrasi)
        'tanggal', // Tanggal absensi
        'jam',    // Waktu absensi (sesuai migrasi)
    ];

    /**
     * Atribut yang harus di-cast ke tipe data tertentu.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'tanggal' => 'date',         // Cast 'tanggal' ke objek Carbon Date
        'jam' => 'datetime:H:i:s', // Cast 'jam' ke format waktu saja jika disimpan sebagai TIME
        // Jika 'jam' disimpan sebagai DATETIME atau TIMESTAMP, gunakan 'datetime'
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    /**
     * Mendapatkan karyawan yang memiliki record absensi ini.
     * Mendefinisikan relasi many-to-one (inverse) ke model Karyawan.
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function karyawan(): BelongsTo
    {
        // Menghubungkan model Absensi ini ke model Karyawan
        // menggunakan kolom 'nip' pada tabel 'absensis' (foreign key)
        // dan kolom 'nip' pada tabel 'karyawans' (local key).
        return $this->belongsTo(Karyawan::class, 'nip', 'nip');
    }
}
