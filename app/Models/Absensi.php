<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Absensi extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'karyawan_id',
        'tanggal',
        'jam',
        'sesi_absensi_id', // <-- TAMBAHKAN BARIS INI
        'koordinat', // <-- TAMBAHKAN BARIS INI
        'jarak',
    ];

    /**
     * Get the session for the attendance.
     */
    public function sesiAbsensi()
    {
        return $this->belongsTo(SesiAbsensi::class);
    }

    public function karyawan(): BelongsTo
    {
        return $this->belongsTo(Karyawan::class);
    }

    // Accessor untuk kompatibilitas: ambil nip dari relasi karyawan
    public function getNipAttribute()
    {
        return $this->karyawan->nip ?? null;
    }

    // Accessor untuk kompatibilitas: ambil nama dari relasi karyawan
    public function getNamaAttribute()
    {
        return $this->karyawan->nama ?? null;
    }
}
