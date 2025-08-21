<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class TandaTangan extends Model
{
    use HasFactory;

    // Menentukan nama tabel secara eksplisit
    protected $table = 'tanda_tangan';

    protected $fillable = [
        'key',
        'value',
    ];
}
