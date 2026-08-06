<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('izin_keluars', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->enum('tipe_izin', ['mendesak', 'kepentingan_kantor']);
            // Untuk tipe izin = kepentingan_kantor saja
            $table->enum('tipe_durasi', ['setengah_hari', 'full_hari', 'custom'])->nullable();
            
            $table->string('foto_surat');
            $table->text('alasan_keluar');
            $table->timestamp('waktu_keluar');
            
            // Kolom saat proses kembali (menutup izin)
            $table->timestamp('waktu_kembali')->nullable();
            $table->string('dokumen_kembali')->nullable();
            $table->text('keterangan_kembali')->nullable();
            
            // Status track
            $table->enum('status_izin', ['berjalan', 'selesai'])->default('berjalan');
            $table->boolean('is_pelanggaran')->default(false);
            
            $table->timestamps();
        });
    }

    public function down()
    {
        Schema::dropIfExists('izin_keluars');
    }
};
