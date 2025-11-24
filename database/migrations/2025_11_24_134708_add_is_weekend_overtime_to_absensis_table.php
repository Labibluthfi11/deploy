<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('absensis', function (Blueprint $table) {
            // ✅ Kolom baru untuk tandai apakah ini lembur weekend
            $table->boolean('is_weekend_overtime')->default(false)->after('lembur_rest');
        });
    }

    public function down(): void
    {
        Schema::table('absensis', function (Blueprint $table) {
            $table->dropColumn('is_weekend_overtime');
        });
    }
};
