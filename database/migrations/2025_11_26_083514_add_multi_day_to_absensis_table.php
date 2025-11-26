<?php


use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('absensis', function (Blueprint $table) {
            // ✅ Kolom baru untuk multi-day sick/leave
            $table->date('end_date')->nullable()->after('check_in_at');
            $table->integer('total_days')->default(1)->after('end_date');

            // ✅ Kolom untuk tracking parent record (opsional, buat grouping)
            $table->unsignedBigInteger('parent_id')->nullable()->after('total_days');
            $table->foreign('parent_id')->references('id')->on('absensis')->onDelete('cascade');
        });
    }

    public function down(): void
    {
        Schema::table('absensis', function (Blueprint $table) {
            $table->dropForeign(['parent_id']);
            $table->dropColumn(['end_date', 'total_days', 'parent_id']);
        });
    }
};
