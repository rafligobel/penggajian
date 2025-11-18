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
        'gaji_pokok_default',
        'tunjangan_komunikasi_id',
        'user_id',
        'tanggal_masuk',
        'jumlah_anak',
        'foto',
    ];

    protected $casts = [
        'status_aktif' => 'boolean',
        'tanggal_masuk' => 'date',
    ];

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

    public function tunjanganKomunikasi(): BelongsTo
    {
        return $this->belongsTo(TunjanganKomunikasi::class);
    }
    public function setFotoAttribute($value)
    {
        // Jika Anda memiliki logika untuk memproses $value (misalnya, memindah file),
        // lakukan di sini dan pastikan $value berisi nama file final.

        // PERBAIKAN: Gunakan $this->attributes['nama_kolom']
        // Ini adalah cara yang benar untuk mengatur nilai di dalam mutator
        // untuk menghindari pemanggilan berulang (infinite loop).
        $this->attributes['foto'] = $value;
    }

    public function getFotoUrlAttribute()
    {
        return $this->getImageUrlAttribute('foto');
    }
}
