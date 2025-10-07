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
            // Menambahkan foreign key ke tabel sesi_absensis
            $table->foreignId('sesi_absensi_id')->constrained('sesi_absensis')->onDelete('cascade');
            $table->string('nip');
            $table->string('nama');
            $table->date('tanggal');
            $table->time('jam');
            $table->timestamps();
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
