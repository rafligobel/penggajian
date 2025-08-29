<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class TunjanganKehadiran extends Model
{
    use HasFactory;

    protected $fillable = ['jenis_tunjangan', 'jumlah_tunjangan'];
}
