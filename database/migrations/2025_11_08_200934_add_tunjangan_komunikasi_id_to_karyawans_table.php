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
            // Menambahkan foreign key untuk "Hak" Tunjangan Komunikasi
            // Ini adalah master data yang menentukan apakah karyawan berhak.
            $table->foreignId('tunjangan_komunikasi_id')
                ->nullable()
                ->after('jabatan_id') // Posisikan setelah jabatan_id
                ->constrained('tunjangan_komunikasis') // Referensi ke tabel tunjangan_komunikasis
                ->onDelete('set null'); // Jika aturan tunjangan dihapus, data karyawan tidak hilang
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('karyawans', function (Blueprint $table) {
            // Hapus constraint foreign key terlebih dahulu
            $table->dropForeign(['tunjangan_komunikasi_id']);
            // Hapus kolom
            $table->dropColumn('tunjangan_komunikasi_id');
        });
    }
};
