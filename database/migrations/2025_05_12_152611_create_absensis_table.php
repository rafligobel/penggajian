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
            $table->string('nip'); // Relasi ke karyawan
            $table->string('nama'); // Nama karyawan (opsional untuk cache)
            $table->date('tanggal'); // Tanggal absen
            $table->time('jam')->nullable(); // Jam absen (opsional)
            $table->timestamps();

            // Tambahkan indeks unik agar tidak bisa absen 2x di hari yang sama
            $table->unique(['nip', 'tanggal']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('absensis');
    }
};
