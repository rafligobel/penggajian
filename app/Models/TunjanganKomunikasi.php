<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class TunjanganKomunikasi extends Model
{
    use HasFactory;
    protected $fillable = ['nama_level', 'besaran'];
}
