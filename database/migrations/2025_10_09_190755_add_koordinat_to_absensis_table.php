<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     * * Metode 'up' akan dijalankan saat Anda menjalankan 'php artisan migrate'.
     * Kode ini menambahkan satu kolom baru ke tabel 'absensis'.
     */
    public function up(): void
    {
        Schema::table('absensis', function (Blueprint $table) {
            // Menambahkan kolom 'koordinat' dengan tipe data string.
            // ->nullable() berarti kolom ini boleh kosong.
            // ->after('jam') menempatkan kolom ini setelah kolom 'jam' agar rapi.
            $table->string('koordinat')->nullable()->after('jam');
        });
    }

    /**
     * Reverse the migrations.
     * * Metode 'down' akan dijalankan jika Anda perlu membatalkan migrasi (rollback).
     * Kode ini akan menghapus kolom 'koordinat' yang sudah ditambahkan.
     */
    public function down(): void
    {
        Schema::table('absensis', function (Blueprint $table) {
            $table->dropColumn('koordinat');
        });
    }
};
