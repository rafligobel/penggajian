<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Carbon\Carbon; // Pastikan ini di-import

class Gaji extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'karyawan_id',
        'bulan',
        'gaji_pokok',
        'tunj_anak',
        'tunj_komunikasi',
        'tunj_pengabdian',
        'tunj_kinerja',
        'lembur',
        'potongan',
        'tunjangan_kehadiran_id',
    ];

    /**
     * [PERBAIKAN 1: WAJIB DITAMBAHKAN]
     * The attributes that should be cast.
     * Secara otomatis mengubah 'bulan' menjadi objek Carbon.
     *
     * @var array
     */
    protected $casts = [
        'bulan' => 'date',
    ];

    /**
     * Relasi ke model Karyawan.
     */
    public function karyawan(): BelongsTo
    {
        return $this->belongsTo(Karyawan::class);
    }

    /**
     * Relasi ke model TunjanganKehadiran.
     */
    public function tunjanganKehadiran(): BelongsTo
    {
        return $this->belongsTo(TunjanganKehadiran::class);
    }

    /*
    |--------------------------------------------------------------------------
    | [PERBAIKAN 2: HAPUS ACCESSOR]
    |--------------------------------------------------------------------------
    |
    | Accessor 'totalTunjangan' dan 'gajiBersih' dihapus dari model ini.
    | Alasan: Memanggil service dari dalam model accessor menyebabkan masalah
    | performa serius (N+1 Problem) dan membuat model menjadi lambat.
    |
    | Kalkulasi total ini sudah ditangani dengan benar di dalam controller
    | (contoh: TenagaKerjaController dan LaporanController) sebelum data
    | dikirim ke view. Itu adalah pendekatan yang jauh lebih efisien.
    |
    */
}

//     protected function totalTunjangan(): Attribute
//     {
//         // Mengambil tunjangan jabatan langsung dari relasi karyawan
//         $tunjanganJabatan = $this->karyawan->jabatan->tunj_jabatan ?? 0;

//         // Mengambil tunjangan kehadiran dari service (lebih akurat)
//         $salaryService = app(\App\Services\SalaryService::class);
//         $detailGaji = $salaryService->calculateDetailsForForm($this->karyawan, $this->bulan);
//         $tunjanganKehadiran = $detailGaji['tunj_kehadiran'];

//         return Attribute::make(
//             get: fn() => $tunjanganKehadiran +
//                 $tunjanganJabatan +
//                 $this->tunj_anak +
//                 $this->tunj_komunikasi +
//                 $this->tunj_pengabdian +
//                 $this->tunj_kinerja +
//                 $this->lembur
//         );
//     }
//     protected function gajiBersih(): Attribute
//     {
//         return Attribute::make(
//             get: fn() => $this->gaji_pokok + $this->total_tunjangan - $this->potongan,
//         );
//     }
// }
