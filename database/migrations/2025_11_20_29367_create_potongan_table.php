<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('potongan', function (Blueprint $table) {
            $table->id();
            // Tarif Lembur per Jam (Misal: 20.000/jam)
            $table->unsignedInteger('tarif_lembur_per_jam')->default(0);
            // Tarif Potongan per hari Alpha (Misal: 50.000/hari)
            $table->unsignedInteger('tarif_potongan_absen')->default(0);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('potongan');
    }
};
