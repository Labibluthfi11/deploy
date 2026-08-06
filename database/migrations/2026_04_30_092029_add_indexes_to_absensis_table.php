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
            // Menambahkan index untuk mempercepat pencarian absensi berdasarkan tanggal (Check-in/Check-out)
            // Sangat krusial untuk menghindari Full Table Scan saat peak hour (jam absen masuk/pulang)
            $table->index('check_in_at');
            $table->index('check_out_at');
            
            // Index untuk filter status dan approval yang sering digunakan di Dashboard & Recap
            $table->index('status_approval');
            $table->index('status');
            $table->index('tipe');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('absensis', function (Blueprint $table) {
            $table->dropIndex(['check_in_at']);
            $table->dropIndex(['check_out_at']);
            $table->dropIndex(['status_approval']);
            $table->dropIndex(['status']);
            $table->dropIndex(['tipe']);
        });
    }
};
