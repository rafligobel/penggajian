<?php
// File: app/Models/Gaji.php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Carbon\Carbon; // Pastikan ini di-import
use Illuminate\Database\Eloquent\Relations\HasMany;

class Gaji extends Model
{
    use HasFactory;

    protected $fillable = [
        'karyawan_id',
        'bulan',
        'nama_karyawan_snapshot',
        'nip_snapshot',
        'jabatan_snapshot',
        'gaji_pokok',
        'tunj_jabatan',
        'tunj_anak',
        'tunj_komunikasi',
        'tunj_pengabdian',
        'tunj_kinerja',
        'lembur',
        'potongan',
        'tunjangan_kehadiran_id',
        'tunjangan_komunikasi_id', // <-- TAMBAHKAN INI
    ];

    protected $casts = [
        'bulan' => 'date',
        'gaji_pokok' => 'integer',
        'tunj_jabatan' => 'integer',
        'potongan' => 'integer',
    ];

    protected static function booted()
    {
        static::creating(function ($gaji) {
            $karyawan = $gaji->karyawan;
            if ($karyawan) {
                $gaji->nama_karyawan_snapshot = $karyawan->nama;
                $gaji->nip_snapshot = $karyawan->nip;
                $gaji->jabatan_snapshot = $karyawan->jabatan->nama_jabatan ?? null;
            }
        });
    }

    public function karyawan(): BelongsTo
    {
        return $this->belongsTo(Karyawan::class);
    }

    public function tunjanganKehadiran(): BelongsTo
    {
        return $this->belongsTo(TunjanganKehadiran::class);
    }

    // <-- TAMBAHKAN RELASI INI ---
    /**
     * Mendapat aturan tunjangan komunikasi yang digunakan (Snapshot).
     */
    public function tunjanganKomunikasi(): BelongsTo
    {
        return $this->belongsTo(TunjanganKomunikasi::class);
    }
    // <-- AKHIR TAMBAHAN ---

    public function penilaianKinerjas(): HasMany
    {
        return $this->hasMany(PenilaianKinerja::class);
    }

    public function getTotalGajiAttribute()
    {
        return $this->gaji_pokok + $this->total_tunjangan + $this->lembur;
    }

    public function getTotalTunjanganAttribute()
    {
        return $this->tunj_jabatan +
            $this->tunj_anak +
            $this->tunj_komunikasi +
            $this->tunj_pengabdian +
            $this->tunj_kinerja +
            $this->nominal_tunjangan_kehadiran;
    }

    public function getNominalTunjanganKehadiranAttribute()
    {
        // Cek apakah relasi tunjanganKehadiran ada
        if (!$this->tunjanganKehadiran) {
            return 0;
        }

        // Hitung jumlah kehadiran di bulan tersebut
        $jumlahKehadiran = Absensi::where('karyawan_id', $this->karyawan_id)
            ->whereYear('tanggal', $this->bulan->year)
            ->whereMonth('tanggal', $this->bulan->month)
            ->count();

        return $jumlahKehadiran * $this->tunjanganKehadiran->jumlah_tunjangan;
    }

    public function getTotalGajiBersihAttribute()
    {
        return $this->total_gaji - $this->potongan;
    }
}
