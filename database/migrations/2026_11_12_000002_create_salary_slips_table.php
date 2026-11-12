<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('salary_slips', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('issued_by')->nullable()->constrained('users')->nullOnDelete();
            $table->date('period_start');
            $table->date('period_end');
            $table->string('employment_type');
            $table->unsignedInteger('attendance_days')->default(0);
            $table->unsignedInteger('overtime_minutes')->default(0);
            $table->decimal('base_salary', 15, 2)->default(0);
            $table->decimal('overtime_pay', 15, 2)->default(0);
            $table->decimal('late_penalty', 15, 2)->default(0);
            $table->decimal('adjustment_salary', 15, 2)->default(0);
            $table->decimal('net_salary', 15, 2)->default(0);
            $table->json('daily_breakdown');
            $table->timestamp('issued_at');
            $table->timestamps();

            $table->unique(['user_id', 'period_start', 'period_end']);
            $table->index(['period_start', 'period_end']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('salary_slips');
    }
};
