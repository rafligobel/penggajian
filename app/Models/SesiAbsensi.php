<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class SesiAbsensi extends Model
{
    use HasFactory;

    protected $table = 'sesi_absensis';


    protected $fillable = [
        'tanggal',
        'waktu_mulai',
        'waktu_selesai',
        'is_active',
    ];


    protected $casts = [
        'tanggal' => 'date',
        'is_active' => 'boolean',
    ];
}
