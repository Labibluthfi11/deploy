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
            // Kolom ini yang error karena GA ADA
            $table->integer('overtime_minutes')->default(0)->nullable()->after('final_salary');
            $table->decimal('overtime_pay', 15, 2)->default(0)->nullable()->after('overtime_minutes');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('absensis', function (Blueprint $table) {
            $table->dropColumn(['overtime_minutes', 'overtime_pay']);
        });
    }
};
