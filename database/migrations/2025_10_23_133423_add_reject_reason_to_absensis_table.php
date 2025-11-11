<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('absensis', function (Blueprint $table) {
            if (!Schema::hasColumn('absensis', 'catatan_admin')) {
                $table->text('catatan_admin')->nullable()->after('status_approval');
            }
            if (!Schema::hasColumn('absensis', 'rejected_at')) {
                $table->timestamp('rejected_at')->nullable()->after('catatan_admin');
            }
        });
    }

    public function down(): void
    {
        Schema::table('absensis', function (Blueprint $table) {
            $table->dropColumn(['catatan_admin', 'rejected_at']);
        });
    }
};
