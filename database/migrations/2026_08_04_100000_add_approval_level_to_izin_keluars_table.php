<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('izin_keluars', function (Blueprint $table) {
            $table->integer('current_approval_level')->default(1)->after('status_approval');
        });
    }

    public function down(): void
    {
        Schema::table('izin_keluars', function (Blueprint $table) {
            $table->dropColumn('current_approval_level');
        });
    }
};
