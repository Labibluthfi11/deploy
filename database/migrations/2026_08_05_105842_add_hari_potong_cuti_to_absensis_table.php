<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('absensis', function (Blueprint $table) {
            $table->integer('hari_potong_cuti')->default(0)->after('submission_type');
            $table->integer('hari_unpaid')->default(0)->after('hari_potong_cuti');
        });
    }

    public function down(): void
    {
        Schema::table('absensis', function (Blueprint $table) {
            $table->dropColumn(['hari_potong_cuti', 'hari_unpaid']);
        });
    }
};
