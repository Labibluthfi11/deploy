<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('employee_work_assignments', function (Blueprint $table) {
            $table->string('schedule_mode')->default('fixed');
            $table->string('rotation_group')->nullable();
        });

        Schema::create('work_shift_rotation_settings', function (Blueprint $table) {
            $table->id();
            $table->date('anchor_week_start')->unique();
            $table->foreignId('group_a_shift_id')->constrained('work_shifts')->restrictOnDelete();
            $table->foreignId('group_b_shift_id')->constrained('work_shifts')->restrictOnDelete();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('work_shift_rotation_settings');

        Schema::table('employee_work_assignments', function (Blueprint $table) {
            $table->dropColumn(['schedule_mode', 'rotation_group']);
        });
    }
};
