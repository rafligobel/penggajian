<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('karyawans', function (Blueprint $table) {
            // Untuk Tunjangan Pengabdian (Revisi 3)
            $table->date('tanggal_masuk')->nullable()->after('jabatan_id');
            // Untuk Tunjangan Anak (Revisi 2)
            $table->unsignedInteger('jumlah_anak')->default(0)->after('tanggal_masuk');
        });
    }

    public function down(): void
    {
        Schema::table('karyawans', function (Blueprint $table) {
            $table->dropColumn(['tanggal_masuk', 'jumlah_anak']);
        });
    }
};
