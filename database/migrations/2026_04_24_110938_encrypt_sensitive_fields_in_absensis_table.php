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
        Schema::table('absensis', function (Blueprint $table) {
            // Kita ubah tipe kolom menjadi TEXT agar bisa menampung data yang terenkripsi
            $table->text('base_salary')->nullable()->change();
            $table->text('late_penalty')->nullable()->change();
            $table->text('final_salary')->nullable()->change();
            $table->text('overtime_pay')->nullable()->change();
            
            $table->text('keterangan_izin_sakit')->nullable()->change();
            $table->text('catatan_admin')->nullable()->change();
            $table->text('lembur_keterangan')->nullable()->change();
            $table->text('keterangan_goals')->nullable()->change();
            
            $table->text('lokasi_masuk')->nullable()->change();
            $table->text('lokasi_pulang')->nullable()->change();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('absensis', function (Blueprint $table) {
            // Kembalikan ke tipe semula jika diperlukan
            $table->decimal('base_salary', 10, 2)->nullable()->change();
            $table->decimal('late_penalty', 10, 2)->nullable()->change();
            $table->decimal('final_salary', 10, 2)->nullable()->change();
            $table->decimal('overtime_pay', 10, 2)->nullable()->change();
            
            $table->string('keterangan_izin_sakit')->nullable()->change();
            $table->string('catatan_admin')->nullable()->change();
            $table->string('lembur_keterangan')->nullable()->change();
            $table->string('keterangan_goals')->nullable()->change();
            
            $table->string('lokasi_masuk')->nullable()->change();
            $table->string('lokasi_pulang')->nullable()->change();
        });
    }
};
