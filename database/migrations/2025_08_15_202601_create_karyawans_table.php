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
        Schema::create('karyawans', function (Blueprint $table) {
            $table->id();
            $table->string('nip')->unique();
            $table->string('nama');
            $table->foreignId('jabatan_id')->constrained('jabatans')->onDelete('cascade');
            $table->string('email')->nullable();
            $table->unsignedInteger('gaji_pokok')->default(0);

            // --- TAMBAHAN BARU: Menambahkan kolom yang hilang ---
            $table->string('telepon')->nullable();
            $table->text('alamat')->nullable();
            
            // Kolom tunjangan lainnya yang bersifat spesifik per karyawan
            $table->unsignedInteger('tunj_anak')->default(0);
            $table->unsignedInteger('tunj_komunikasi')->default(0);
            $table->unsignedInteger('tunj_pengabdian')->default(0);
            $table->unsignedInteger('tunj_kinerja')->default(0);
            
            $table->boolean('status_aktif')->default(true);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('karyawans');
    }
};
