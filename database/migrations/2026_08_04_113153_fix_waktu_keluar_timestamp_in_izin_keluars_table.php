<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::statement("ALTER TABLE izin_keluars MODIFY waktu_keluar TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP");
    }

    public function down(): void
    {
        DB::statement("ALTER TABLE izin_keluars MODIFY waktu_keluar TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP");
    }
};
