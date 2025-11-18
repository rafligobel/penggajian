<?php

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
        Schema::table('karyawans', function (Blueprint $table) {
            // --- PERBAIKAN: Tambahkan kolom Gaji Pokok Default ---
            $table->unsignedInteger('gaji_pokok_default')->default(0)->after('jabatan_id');
            // --- AKHIR PERBAIKAN ---

            $table->date('tanggal_masuk')->nullable()->after('alamat');
            $table->unsignedTinyInteger('jumlah_anak')->default(0)->after('tanggal_masuk');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('karyawans', function (Blueprint $table) {
            $table->dropColumn('gaji_pokok_default');
            $table->dropColumn('tanggal_masuk');
            $table->dropColumn('jumlah_anak');
        });
    }
};
