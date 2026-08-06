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
        Schema::table('absensis', function (Blueprint $row) {
            $row->decimal('adjustment_salary', 15, 2)->default(0)->after('final_salary');
            $row->string('adjustment_reason')->nullable()->after('adjustment_salary');
            $row->foreignId('adjustment_by')->nullable()->after('adjustment_reason')->constrained('users')->onDelete('set null');
            $row->timestamp('adjustment_at')->nullable()->after('adjustment_by');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('absensis', function (Blueprint $row) {
            $row->dropForeign(['adjustment_by']);
            $row->dropColumn(['adjustment_salary', 'adjustment_reason', 'adjustment_by', 'adjustment_at']);
        });
    }
};
