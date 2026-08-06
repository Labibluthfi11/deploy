<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('izin_keluars', function (Blueprint $table) {
            $table->enum('status_approval', ['pending', 'approved', 'rejected'])
                ->default('pending')
                ->after('is_pelanggaran');
            $table->unsignedBigInteger('approved_by')->nullable()->after('status_approval');
            $table->timestamp('approved_at')->nullable()->after('approved_by');

            $table->foreign('approved_by')->references('id')->on('users')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('izin_keluars', function (Blueprint $table) {
            $table->dropForeign(['approved_by']);
            $table->dropColumn(['status_approval', 'approved_by', 'approved_at']);
        });
    }
};
