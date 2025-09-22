<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Casts\Attribute;

class Gaji extends Model
{
    use HasFactory;

    protected $fillable = [
        'karyawan_id',
        'bulan',
        'gaji_pokok',
        'tunj_anak',
        'tunj_pengabdian',
        'lembur',
        'potongan',
        'tunj_komunikasi',
        'tunj_kinerja',
        'kelebihan_jam',
        'tunj_jabatan',
        'tunj_kehadiran',
        'jumlah_kehadiran',
        'gaji_bersih',
    ];

    public function karyawan(): BelongsTo
    {
        return $this->belongsTo(Karyawan::class, 'karyawan_id');
    }

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

    protected function pendapatanLainnya(): Attribute
    {
        return Attribute::make(
            get: fn($value, $attributes) => ($attributes['lembur'] ?? 0) +
                ($attributes['kelebihan_jam'] ?? 0)
        );
    }
}
