<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Karyawan extends Model
{
    use HasFactory;

    protected $fillable = [
        'nama',
        'nip',
        'email',
        'alamat',
        'telepon',
        'jabatan_id',
        'status_aktif',
    ];

    protected $casts = ['status_aktif' => 'boolean'];

    public function absensi(): HasMany
    {
        return $this->hasMany(Absensi::class, 'nip', 'nip');
    }

    public function jabatan(): BelongsTo
    {
        return $this->belongsTo(Jabatan::class);
    }

    public function gaji(): HasMany
    {
        return $this->hasMany(Gaji::class, 'karyawan_id');
    }
}
