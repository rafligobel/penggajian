<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PenilaianKinerja extends Model
{
    use HasFactory;

    protected $fillable = [
        'gaji_id',
        'indikator_kinerja_id',
        'skor',
    ];
}
