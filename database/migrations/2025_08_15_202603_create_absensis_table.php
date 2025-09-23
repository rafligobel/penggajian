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
        Schema::create('absensis', function (Blueprint $table) {
            $table->id();
            $table->string('nip');
            $table->string('nama');
            $table->date('tanggal');

            // --- PERBAIKAN FINAL ---
            // Hanya ada satu kolom 'jam' untuk mencatat waktu absensi (satu kali klik)
            $table->time('jam');

            $table->timestamps();

            // Kunci unik untuk memastikan satu karyawan hanya bisa absen sekali sehari
            $table->unique(['nip', 'tanggal']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('absensis');
    }
};
