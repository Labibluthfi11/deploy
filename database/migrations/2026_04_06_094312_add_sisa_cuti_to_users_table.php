<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->integer('sisa_cuti')->default(12)->after('employment_type');
            $table->integer('total_cuti_diambil')->default(0)->after('sisa_cuti');
            $table->year('tahun_cuti')->default(date('Y'))->after('total_cuti_diambil');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn(['sisa_cuti', 'total_cuti_diambil', 'tahun_cuti']);
        });
    }
};