<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('aturan_tunjangan_anak', function (Blueprint $table) {
            $table->id();
            $table->string('nama_aturan')->default('Nilai Tunjangan per Anak');
            $table->unsignedBigInteger('nilai_per_anak')->default(0);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('aturan_tunjangan_anak');
    }
};
