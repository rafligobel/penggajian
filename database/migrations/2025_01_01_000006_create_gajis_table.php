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
        Schema::create('gajis', function (Blueprint $table) {
            $table->id();
            $table->foreignId('karyawan_id')->constrained('karyawans')->onDelete('cascade');
            $table->date('bulan');
            // Kolom-kolom ini adalah INPUT MANUAL atau TEMPLATE
            $table->unsignedInteger('gaji_pokok')->default(0);
            $table->unsignedInteger('tunj_anak')->default(0);
            $table->unsignedInteger('tunj_komunikasi')->default(0);
            $table->unsignedInteger('tunj_pengabdian')->default(0);
            $table->unsignedInteger('tunj_kinerja')->default(0);
            $table->unsignedInteger('lembur')->default(0);
            $table->unsignedInteger('potongan')->default(0);

            // Relasi ke pengaturan tunjangan kehadiran yang digunakan. INI WAJIB ADA.
            $table->foreignId('tunjangan_kehadiran_id')->constrained('tunjangan_kehadirans');

            $table->timestamps();

            // Karyawan hanya bisa punya 1 record gaji per bulan
            $table->unique(['karyawan_id', 'bulan']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('gajis');
    }
};
