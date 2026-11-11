<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('work_shifts', function (Blueprint $table) {
            $table->id();
            $table->string('name')->unique();
            $table->time('starts_at');
            $table->time('ends_at');
            $table->time('break_starts_at')->nullable();
            $table->time('break_ends_at')->nullable();
            $table->boolean('crosses_midnight')->default(false);
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        Schema::create('pay_schemes', function (Blueprint $table) {
            $table->id();
            $table->string('name')->unique();
            $table->decimal('daily_salary', 12, 2);
            $table->unsignedSmallInteger('standard_work_minutes')->default(480);
            $table->unsignedSmallInteger('late_rounding_minutes')->default(15);
            $table->unsignedSmallInteger('overtime_rounding_minutes')->default(30);
            $table->decimal('weekend_daily_multiplier', 5, 2)->default(2);
            $table->decimal('overtime_multiplier', 5, 2)->default(1);
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        Schema::create('employee_work_assignments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('work_shift_id')->constrained()->restrictOnDelete();
            $table->foreignId('pay_scheme_id')->constrained()->restrictOnDelete();
            $table->date('effective_from');
            $table->date('effective_until')->nullable();
            $table->timestamps();

            $table->index(['user_id', 'effective_from', 'effective_until'], 'employee_work_assignments_lookup');
        });

        Schema::create('employee_shift_overrides', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('work_shift_id')->constrained()->restrictOnDelete();
            $table->date('work_date');
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->unique(['user_id', 'work_date']);
        });

        Schema::table('absensis', function (Blueprint $table) {
            $table->foreignId('work_shift_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('pay_scheme_id')->nullable()->constrained()->nullOnDelete();
            $table->json('payroll_snapshot')->nullable();
        });
    }

    public function down(): void
    {
        Schema::table('absensis', function (Blueprint $table) {
            $table->dropConstrainedForeignId('work_shift_id');
            $table->dropConstrainedForeignId('pay_scheme_id');
            $table->dropColumn('payroll_snapshot');
        });

        Schema::dropIfExists('employee_shift_overrides');
        Schema::dropIfExists('employee_work_assignments');
        Schema::dropIfExists('pay_schemes');
        Schema::dropIfExists('work_shifts');
    }
};
