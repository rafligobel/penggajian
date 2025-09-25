<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

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
        'gaji_pokok',
        'tunj_anak',
        'tunj_komunikasi',
        'tunj_pengabdian',
        'tunj_kinerja',
        'lembur',
        'potongan',
        // --- INILAH KUNCI PERBAIKANNYA ---
        'tunjangan_kehadiran_id', 
    ];

    /**
     * Relasi ke model Karyawan.
     */
    public function karyawan(): BelongsTo
    {
        return $this->belongsTo(Karyawan::class);
    }

    /**
     * Relasi ke model TunjanganKehadiran.
     */
    public function tunjanganKehadiran(): BelongsTo
    {
        return $this->belongsTo(TunjanganKehadiran::class);
    }
}