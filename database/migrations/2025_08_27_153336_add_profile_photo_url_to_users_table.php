<?php

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
            // Menambahkan kolom baru untuk menyimpan URL publik dari foto profil
            $table->string('profile_photo_url', 2048)->nullable()->after('profile_photo_path');
        });
    }

    /**
     * Kembalikan (Rollback) migrasi.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            // Menghapus kolom jika migrasi di-rollback
            $table->dropColumn('profile_photo_url');
        });
    }
};
