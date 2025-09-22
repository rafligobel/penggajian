<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('gajis', function (Blueprint $table) {
            $table->id();
            $table->foreignId('karyawan_id')->constrained('karyawans')->onDelete('cascade');
            $table->string('bulan'); // Format 'YYYY-MM'

            // --- Kolom Komponen Gaji (Input Bendahara) ---
            $table->unsignedInteger('gaji_pokok')->default(0);
            $table->unsignedInteger('tunj_anak')->default(0);
            $table->unsignedInteger('tunj_pengabdian')->default(0);
            $table->unsignedInteger('lembur')->default(0);
            $table->unsignedInteger('potongan')->default(0);
            $table->unsignedInteger('tunj_komunikasi')->default(0);
            $table->unsignedInteger('tunj_kinerja')->default(0);
            $table->unsignedInteger('kelebihan_jam')->default(0);

            // --- Kolom Komponen Gaji (Otomatis) ---
            $table->unsignedInteger('tunj_jabatan')->default(0);
            $table->unsignedInteger('tunj_kehadiran')->default(0);
            $table->unsignedTinyInteger('jumlah_kehadiran')->default(0);

            // --- Total ---
            $table->bigInteger('gaji_bersih')->default(0);

            $table->timestamps();

            // Kunci unik untuk memastikan satu karyawan hanya punya satu data gaji per bulan
            $table->unique(['karyawan_id', 'bulan']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('gajis');
    }
};
