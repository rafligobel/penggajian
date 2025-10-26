<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class AturanTunjanganAnak extends Model
{
    use HasFactory;
    protected $table = 'aturan_tunjangan_anak';
    protected $fillable = ['nama_aturan', 'nilai_per_anak'];
}
