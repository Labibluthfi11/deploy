<?php
// database/migrations/xxxx_xx_xx_add_employment_status_to_users_table.php

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
        Schema::table('users', function (Blueprint $table) {
            // Tambahkan kolom employment_type dengan nilai default 'organik'
            $table->enum('employment_type', ['organik', 'freelance'])->default('organik')->after('departemen');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            // Hapus kolom saat rollback
            $table->dropColumn('employment_type');
        });
    }
};
