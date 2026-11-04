<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('absence_leave_deductions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->date('absence_date');
            $table->unsignedTinyInteger('deducted_days')->default(0);
            $table->string('reason', 20);
            $table->timestamps();

            $table->unique(['user_id', 'absence_date']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('absence_leave_deductions');
    }
};
