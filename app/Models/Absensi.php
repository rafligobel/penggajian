<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Absensi extends Model
{
    use HasFactory;

    protected $table = 'absensis';

    /**
     * Atribut yang dapat diisi, disesuaikan dengan migrasi FINAL.
     */
    protected $fillable = [
        'nip',
        'nama',
        'tanggal',
        'jam', // Kolom jam_masuk dan jam_pulang dihilangkan
    ];

    /**
     * Casts disesuaikan.
     */
    protected $casts = [
        'tanggal' => 'date',
        'jam' => 'datetime:H:i:s', // Cast 'jam' sebagai waktu
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    public function karyawan(): BelongsTo
    {
        return $this->belongsTo(Karyawan::class, 'nip', 'nip');
    }
}
