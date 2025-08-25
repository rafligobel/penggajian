<?php


use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('sesi_absensis', function (Blueprint $table) {
            $table->id();
            // $table->string('nama_sesi'); // Contoh: 'Masuk Pagi', 'Pulang Sore'
            $table->date('tanggal')->unique();
            $table->time('waktu_mulai');
            $table->time('waktu_selesai');
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('sesi_absensis');
    }
};
