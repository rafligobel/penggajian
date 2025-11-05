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
        Schema::create('penilaian_kinerjas', function (Blueprint $table) {
            $table->id();
            // Relasi ke tabel gaji
            $table->foreignId('gaji_id')->constrained('gajis')->onDelete('cascade');
            // Relasi ke tabel master indikator
            $table->foreignId('indikator_kinerja_id')->constrained('indikator_kinerjas')->onDelete('cascade');
            $table->integer('skor')->default(0); // Skor 0-100
            $table->timestamps();

            // 1 indikator hanya bisa dinilai 1x per data gaji
            $table->unique(['gaji_id', 'indikator_kinerja_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('penilaian_kinerjas');
    }
};
