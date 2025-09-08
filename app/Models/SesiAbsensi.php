<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class SesiAbsensi extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'tanggal',
        'tipe',
        'waktu_mulai',
        'waktu_selesai',
        'keterangan',
        'is_default',
        'hari_kerja', // Memberikan izin untuk mengisi kolom 'hari_kerja'
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'is_default' => 'boolean',
        'hari_kerja' => 'array', // Otomatis mengubah data array ke JSON saat disimpan, dan sebaliknya
    ];
}
