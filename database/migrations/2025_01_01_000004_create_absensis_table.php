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

            $table->foreignId('sesi_absensi_id')->constrained('sesi_absensis')->onDelete('cascade');

            // Kolom Foreign Key yang BARU dan BENAR (menggantikan NIP dan Nama)
            $table->foreignId('karyawan_id')->constrained('karyawans')->onDelete('cascade');

            $table->date('tanggal');
            $table->time('jam');
            $table->string('koordinat')->nullable();
            $table->double('jarak')->nullable();

            // Kunci unik yang penting: karyawan_id dan tanggal
            $table->unique(['karyawan_id', 'tanggal']);

            $table->timestamps();
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
