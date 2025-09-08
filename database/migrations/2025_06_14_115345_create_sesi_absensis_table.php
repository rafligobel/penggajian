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
        Schema::create('sesi_absensis', function (Blueprint $table) {
            $table->id();
            $table->date('tanggal')->unique();
            $table->string('tipe'); // contoh: default, aktif, nonaktif
            $table->time('waktu_mulai')->nullable();
            $table->time('waktu_selesai')->nullable();
            $table->text('keterangan')->nullable();
            $table->boolean('is_default')->default(false)->index();
            $table->json('hari_kerja')->nullable(); // Kolom krusial untuk menyimpan hari kerja
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('sesi_absensis');
    }
};
