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
        'nip',
        'nama',
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
}
