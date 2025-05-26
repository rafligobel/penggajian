<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory; // Tambahkan jika Anda menggunakan factory
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Gaji extends Model
{
    // use HasFactory; // Aktifkan jika Anda memiliki factory untuk Gaji

    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'gajis'; // Sesuai dengan nama tabel di migrasi Anda

    /**
     * The attributes that are mass assignable.
     * 'karyawan_id' telah ditambahkan.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'karyawan_id', // Ditambahkan untuk mass assignment
        'bulan',
        'gaji_pokok',
        'tunj_kehadiran',
        'tunj_anak',
        'tunj_komunikasi',
        'tunj_pengabdian',
        'tunj_jabatan',
        'tunj_kinerja',
        'lembur',
        'kelebihan_jam',
        'potongan',
        'gaji_bersih',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'gaji_pokok' => 'integer',
        'tunj_kehadiran' => 'integer',
        'tunj_anak' => 'integer',
        'tunj_komunikasi' => 'integer',
        'tunj_pengabdian' => 'integer',
        'tunj_jabatan' => 'integer',
        'tunj_kinerja' => 'integer',
        'lembur' => 'integer',
        'kelebihan_jam' => 'integer',
        'potongan' => 'integer',
        'gaji_bersih' => 'integer',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    /**
     * Get the karyawan that owns the gaji.
     * Mendefinisikan relasi many-to-one (inverse) ke model Karyawan.
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function karyawan(): BelongsTo
    {
        // Menghubungkan model Gaji ini ke model Karyawan
        // menggunakan kolom 'karyawan_id' pada tabel 'gajis' (foreign key)
        // dan kolom 'id' pada tabel 'karyawans' (owner key).
        return $this->belongsTo(Karyawan::class, 'karyawan_id');
    }
}
