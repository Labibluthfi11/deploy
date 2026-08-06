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
            $table->string('foto_pulang_2')->nullable()->after('foto_pulang');
            $table->string('foto_pulang_3')->nullable()->after('foto_pulang_2');
            $table->text('keterangan_goals')->nullable()->after('lembur_keterangan');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('absensis', function (Blueprint $table) {
            $table->dropColumn(['foto_pulang_2', 'foto_pulang_3', 'keterangan_goals']);
        });
    }
};
