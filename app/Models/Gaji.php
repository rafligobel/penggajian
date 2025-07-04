<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Casts\Attribute;

class Gaji extends Model
{
    use HasFactory;

    protected $table = 'gajis';

    protected $fillable = [
        'karyawan_id',
        'bulan',
        'gaji_pokok',
        'tunj_kehadiran',
        'tunj_anak',
        'tunj_komunikasi',
        'tunj_pengabdian',
        'tunj_jabatan',
        'tunj_kinerja',
        'lembur',
        'kelebihan_jam',
        'potongan',
        'gaji_bersih',
    ];

    protected $casts = [
        'gaji_pokok' => 'integer',
        'tunj_kehadiran' => 'integer',
        'tunj_anak' => 'integer',
        'tunj_komunikasi' => 'integer',
        'tunj_pengabdian' => 'integer',
        'tunj_jabatan' => 'integer',
        'tunj_kinerja' => 'integer',
        'lembur' => 'integer',
        'kelebihan_jam' => 'integer',
        'potongan' => 'integer',
        'gaji_bersih' => 'integer',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    public function karyawan(): BelongsTo
    {
        return $this->belongsTo(Karyawan::class, 'karyawan_id');
    }

    /**
     * Accessor untuk mendapatkan total semua tunjangan.
     */
    protected function totalTunjangan(): Attribute
    {
        return Attribute::make(
            get: fn($value, $attributes) => ($attributes['tunj_kehadiran'] ?? 0) +
                ($attributes['tunj_anak'] ?? 0) +
                ($attributes['tunj_komunikasi'] ?? 0) +
                ($attributes['tunj_pengabdian'] ?? 0) +
                ($attributes['tunj_jabatan'] ?? 0) +
                ($attributes['tunj_kinerja'] ?? 0)
        );
    }

    /**
     * Accessor untuk mendapatkan total pendapatan lain-lain.
     */
    protected function pendapatanLainnya(): Attribute
    {
        return Attribute::make(
            get: fn($value, $attributes) => ($attributes['lembur'] ?? 0) + ($attributes['kelebihan_jam'] ?? 0)
        );
    }
}
