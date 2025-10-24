<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use App\Models\User;
use App\Models\Absensi;
use App\Models\Jabatan;
use App\Models\Gaji;

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
        'user_id',
    ];

    protected $casts = ['status_aktif' => 'boolean'];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function absensis(): HasMany
    {
        return $this->hasMany(Absensi::class);
    }

    public function jabatan(): BelongsTo
    {
        return $this->belongsTo(Jabatan::class, 'jabatan_id');
    }

    public function gajis()
    {
        return $this->hasMany(Gaji::class, 'karyawan_id');
    }
}
