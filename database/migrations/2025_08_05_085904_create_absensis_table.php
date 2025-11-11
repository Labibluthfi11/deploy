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
        Schema::create('absensis', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');

            // Kolom-kolom absensi utama
            $table->enum('status', ['hadir', 'izin', 'sakit']);
            $table->string('tipe')->nullable(); // Untuk lembur, cuti, dll.
            $table->string('file_bukti')->nullable(); // Untuk bukti sakit/izin/cuti

            // Kolom TAMBAHAN untuk menyimpan Catatan/Alasan saat status Izin atau Sakit
            $table->text('keterangan_izin_sakit')->nullable();  

            // Waktu absensi
            $table->timestamp('check_in_at')->nullable();
            $table->timestamp('check_out_at')->nullable();

            // Lokasi absensi
            $table->string('lokasi_masuk')->nullable(); // Menyimpan 'lat,lng'
            $table->string('lokasi_pulang')->nullable(); // Menyimpan 'lat,lng'

            // Foto absensi
            $table->string('foto_masuk')->nullable(); // Menyimpan path foto masuk
            $table->string('foto_pulang')->nullable(); // Menyimpan path foto pulang

            // Kolom untuk approval admin
            $table->enum('status_approval', ['pending', 'approved', 'rejected'])->default('pending');
            $table->text('catatan_admin')->nullable();

            // ====== Kolom Detail Lembur ======
            $table->timestamp('lembur_start')->nullable();      // Jam mulai lembur
            $table->timestamp('lembur_end')->nullable();        // Jam selesai lembur
            $table->boolean('lembur_rest')->nullable();         // Istirahat (ya/tidak)
            $table->text('lembur_keterangan')->nullable();      // Keterangan lembur

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('absensis');
    }
};
