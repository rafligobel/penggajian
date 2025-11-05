<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class AturanKinerja extends Model
{
    use HasFactory;

    // Tentukan nama tabel jika tidak jamak
    protected $table = 'aturan_kinerja';

    protected $fillable = [
        'maksimal_tunjangan',
    ];

    protected $casts = [
        'maksimal_tunjangan' => 'float',
    ];
}
