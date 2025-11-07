<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('tunjangan_komunikasis', function (Blueprint $table) {
            $table->id();
            $table->string('nama_level'); // Misal: "Staff", "Manajer"
            $table->unsignedInteger('besaran'); // Misal: 500000
            $table->timestamps();
        });
    }
    public function down(): void
    {
        Schema::dropIfExists('tunjangan_komunikasis');
    }
};
