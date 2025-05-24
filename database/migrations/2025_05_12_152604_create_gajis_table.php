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
            $table->foreignId('karyawan_id')->constrained()->onDelete('cascade');
            $table->string('bulan');
            $table->integer('gaji_pokok')->default(0);
            $table->integer('tunj_kehadiran')->default(0);
            $table->integer('tunj_anak')->default(0);
            $table->integer('tunj_komunikasi')->default(0);
            $table->integer('tunj_pengabdian')->default(0);
            $table->integer('tunj_jabatan')->default(0);
            $table->integer('tunj_kinerja')->default(0);
            $table->integer('lembur')->default(0);
            $table->integer('kelebihan_jam')->default(0);
            $table->integer('potongan')->default(0); // Tambahkan kembali kolom potongan untuk fleksibilitas
            $table->integer('gaji_bersih')->default(0);
            $table->timestamps();
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
