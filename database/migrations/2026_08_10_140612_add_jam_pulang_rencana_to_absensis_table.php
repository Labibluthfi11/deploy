<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    
    public function up(): void
    {
        Schema::table('absensis', function (Blueprint $table) {
            $table->time('jam_pulang_rencana')->nullable()->after('is_kurang_8_jam');
        });
    }

    public function down(): void
    {
        Schema::table('absensis', function (Blueprint $table) {
            $table->dropColumn('jam_pulang_rencana');
        });
    }
};
