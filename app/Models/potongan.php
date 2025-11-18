<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Potongan extends Model
{
    use HasFactory;

    // Nama tabel diset eksplisit agar sesuai dengan migrasi Anda
    protected $table = 'potongan';

    protected $fillable = [
        'tarif_lembur_per_jam',
        'tarif_potongan_absen',
    ];
}
