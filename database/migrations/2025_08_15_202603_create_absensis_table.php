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

            // PERBAIKAN: Mengganti satu kolom 'jam' menjadi 'jam_masuk' dan 'jam_pulang'
            $table->time('jam_masuk')->nullable();
            $table->time('jam_pulang')->nullable();

            $table->timestamps();

            // Kunci unik untuk memastikan satu karyawan hanya bisa punya satu record per hari
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
