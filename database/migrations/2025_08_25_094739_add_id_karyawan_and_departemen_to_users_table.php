<?php
// FILE: database/migrations/<timestamp>_add_id_karyawan_and_departemen_to_users_table.php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Jalankan migrasi.
     */
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            // Menambahkan kolom id_karyawan setelah kolom 'name'
            $table->string('id_karyawan')->unique()->after('name');

            // Menambahkan kolom departemen setelah kolom 'id_karyawan'
            $table->string('departemen')->nullable()->after('id_karyawan');
        });
    }

    /**
     * Balikkan migrasi.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn('id_karyawan');
            $table->dropColumn('departemen');
        });
    }
};
