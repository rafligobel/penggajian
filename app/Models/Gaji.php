<?php
// File: app/Models/Gaji.php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Carbon\Carbon; // Pastikan ini di-import

class Gaji extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'karyawan_id',
        'bulan',

        // --- AWAL TAMBAHAN SNAPSHOT ---
        'nama_karyawan_snapshot',
        'nip_snapshot',
        'jabatan_snapshot',
        // --- AKHIR TAMBAHAN SNAPSHOT ---

        'gaji_pokok',
        'tunj_anak',
        'tunj_komunikasi',
        'tunj_pengabdian',
        'tunj_kinerja',
        'lembur',
        'potongan',
        'tunjangan_kehadiran_id',
    ];

    /**
     * [PERBAIKAN 1: WAJIB DITAMBAHKAN]
     * The attributes that should be cast.
     * Secara otomatis mengubah 'bulan' menjadi objek Carbon.
     *
     * @var array
     */
    protected $casts = [
        'bulan' => 'date',
    ];

    /**
     * Relasi ke model Karyawan.
     */
    public function karyawan()
    {
        return $this->belongsTo(\App\Models\Karyawan::class, 'karyawan_id');
    }

    /**
     * Relasi ke model TunjanganKehadiran.
     */
    public function tunjanganKehadiran()
    {
        return $this->belongsTo(\App\Models\TunjanganKehadiran::class, 'tunjangan_kehadiran_id');
    }

    public function penilaianKinerjas()
    {
        return $this->hasMany(PenilaianKinerja::class);
    }
}
