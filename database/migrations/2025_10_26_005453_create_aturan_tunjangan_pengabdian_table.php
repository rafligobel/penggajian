<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('aturan_tunjangan_pengabdian', function (Blueprint $table) {
            $table->id();
            $table->string('nama_aturan'); // cth: "Masa Kerja 0-5 Tahun"
            $table->unsignedInteger('minimal_tahun_kerja');
            $table->unsignedInteger('maksimal_tahun_kerja');
            $table->unsignedBigInteger('nilai_tunjangan')->default(0);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('aturan_tunjangan_pengabdian');
    }
};
