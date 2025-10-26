<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class AturanTunjanganPengabdian extends Model
{
    use HasFactory;
    protected $table = 'aturan_tunjangan_pengabdian';
    protected $fillable = ['nama_aturan', 'minimal_tahun_kerja', 'maksimal_tahun_kerja', 'nilai_tunjangan'];
}
