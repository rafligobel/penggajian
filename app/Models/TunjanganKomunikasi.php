<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class TunjanganKomunikasi extends Model
{
    use HasFactory;
    protected $fillable = ['nama_level', 'besaran'];


    public function karyawans(): HasMany
    {
        return $this->hasMany(Karyawan::class);
    }

    /**
     * Riwayat gaji yang menggunakan aturan tunjangan ini.
     */
    public function gajis(): HasMany
    {
        return $this->hasMany(Gaji::class);
    }
}
