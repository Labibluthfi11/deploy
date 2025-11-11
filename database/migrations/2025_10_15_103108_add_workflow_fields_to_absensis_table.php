<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::table('absensis', function (Blueprint $table) {
            // current_approval_level: 1 (Yuli/Nu), 2 (Nu/Nadya), 3 (Nadya/Final)
            $table->integer('current_approval_level')->default(1)->after('status_approval');

            // workflow_status: Menyimpan status detail per approver (JSON)
            // Contoh: {'yuli': 'approved', 'nu': 'pending', 'nadya': 'pending'}
            $table->json('workflow_status')->nullable()->after('current_approval_level');

            // rejected_by: Menyimpan nama/ID admin yang menolak
            $table->string('rejected_by')->nullable()->after('updated_at');
        });
    }

    public function down()
    {
        Schema::table('absensis', function (Blueprint $table) {
            $table->dropColumn(['current_approval_level', 'workflow_status', 'rejected_by']);
        });
    }
};
