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
        Schema::table('gajis', function (Blueprint $table) {
            // Menambahkan foreign key untuk "Snapshot" Tunjangan Komunikasi
            // Ini adalah arsip/catatan histori aturan mana yang dipakai saat gaji dibuat.
            $table->foreignId('tunjangan_komunikasi_id')
                ->nullable()
                ->after('tunjangan_kehadiran_id') // Posisikan setelah tunjangan_kehadiran_id
                ->constrained('tunjangan_komunikasis') // Referensi ke tabel tunjangan_komunikasis
                ->onDelete('set null'); // Jika aturan tunjangan dihapus, histori gaji tidak hilang
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('gajis', function (Blueprint $table) {
            $table->dropForeign(['tunjangan_komunikasi_id']);
            $table->dropColumn('tunjangan_komunikasi_id');
        });
    }
};
