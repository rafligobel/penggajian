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
        Schema::table('absensis', function (Blueprint $table) {
            // Menambahkan kolom 'koordinat' dengan tipe data string.
            $table->string('koordinat')->nullable()->after('jam');

            // --- TAMBAHAN BARU ---
            // Menambahkan kolom 'jarak' untuk menyimpan jarak (dalam meter).
            $table->float('jarak')->nullable()->after('koordinat');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('absensis', function (Blueprint $table) {
            // --- MODIFIKASI ---
            // Menghapus kedua kolom jika migrasi di-rollback
            $table->dropColumn(['koordinat', 'jarak']);
        });
    }
};
