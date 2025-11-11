// database/migrations/..._add_target_id_to_notifications_table.php
<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('notifications', function (Blueprint $table) {
            // Tambahkan kolom ID pengajuan (lembur/sakit/izin) yang ditolak
            // Gunakan nullable jika Anda punya notifikasi lain yang tidak memerlukan ID target.
            $table->unsignedBigInteger('target_id')->nullable()->after('target_page');
        });
    }

    public function down(): void
    {
        Schema::table('notifications', function (Blueprint $table) {
            $table->dropColumn('target_id');
        });
    }
};
