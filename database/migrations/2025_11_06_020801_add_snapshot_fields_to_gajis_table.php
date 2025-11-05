<?php
// File: database/migrations/YYYY_MM_DD_HHMMSS_add_snapshot_fields_to_gajis_table.php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('gajis', function (Blueprint $table) {
            // Kolom-kolom ini akan 'mengunci' data karyawan pada saat gaji diproses
            // Kita letakkan setelah 'bulan' agar rapi
            $table->string('nama_karyawan_snapshot')->after('bulan');
            $table->string('nip_snapshot')->after('nama_karyawan_snapshot');
            $table->string('jabatan_snapshot')->after('nip_snapshot')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('gajis', function (Blueprint $table) {
            $table->dropColumn([
                'nama_karyawan_snapshot',
                'nip_snapshot',
                'jabatan_snapshot'
            ]);
        });
    }
};
