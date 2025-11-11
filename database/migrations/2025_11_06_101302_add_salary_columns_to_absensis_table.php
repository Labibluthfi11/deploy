<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('absensis', function (Blueprint $table) {
            // Gaji yang seharusnya didapat (tanpa potongan)
            $table->decimal('base_salary', 10, 2)->nullable()->after('late_minutes');

            // Potongan gaji karena telat
            $table->decimal('late_penalty', 10, 2)->nullable()->after('base_salary');

            // Gaji bersih setelah potongan
            $table->decimal('final_salary', 10, 2)->nullable()->after('late_penalty');

            // Menit keterlambatan yang dibulatkan (untuk potong gaji)
            $table->integer('rounded_late_minutes')->nullable()->after('late_minutes');
        });
    }

    public function down(): void
    {
        Schema::table('absensis', function (Blueprint $table) {
            $table->dropColumn([
                'base_salary',
                'late_penalty',
                'final_salary',
                'rounded_late_minutes'
            ]);
        });
    }
};
