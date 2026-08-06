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
            $table->string('foto_pulang_4')->nullable()->after('foto_pulang_3');
            $table->string('foto_pulang_5')->nullable()->after('foto_pulang_4');
            $table->string('foto_pulang_6')->nullable()->after('foto_pulang_5');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('absensis', function (Blueprint $table) {
            $table->dropColumn(['foto_pulang_4', 'foto_pulang_5', 'foto_pulang_6']);
        });
    }
};
