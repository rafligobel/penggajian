<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Jabatan extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    // --- PERBAIKAN: Mengganti 'gaji_pokok' dengan 'tunj_jabatan' ---
    protected $fillable = [
        'nama_jabatan',
        'tunj_jabatan',
    ];

    /**
     * Get the employees for the job position.
     */
    public function karyawans()
    {
        return $this->hasMany(Karyawan::class);
    }
}
