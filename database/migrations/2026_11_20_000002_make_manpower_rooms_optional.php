<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('employee_room_assignments')) {
            return;
        }

        if (!Schema::hasColumn('employee_room_assignments', 'department_id')) {
            Schema::table('employee_room_assignments', function (Blueprint $table) {
                $table->foreignId('department_id')->nullable()->after('user_id')->constrained()->restrictOnDelete();
            });

        }

        DB::table('employee_room_assignments')
            ->whereNull('department_id')
            ->orderBy('id')
            ->get(['id', 'room_id'])
            ->each(function ($assignment) {
                $departmentId = DB::table('rooms')
                    ->where('id', $assignment->room_id)
                    ->value('department_id');

                if ($departmentId !== null) {
                    DB::table('employee_room_assignments')
                        ->where('id', $assignment->id)
                        ->update(['department_id' => $departmentId]);
                }
            });

        if (DB::table('employee_room_assignments')->whereNull('department_id')->exists()) {
            throw new \RuntimeException('Could not map every existing placement to a department.');
        }

        $roomColumn = collect(Schema::getColumns('employee_room_assignments'))
            ->firstWhere('name', 'room_id');

        if ($roomColumn && !$roomColumn['nullable']) {
            Schema::table('employee_room_assignments', function (Blueprint $table) {
                $table->foreignId('room_id')->nullable()->change();
            });
        }
    }

    public function down(): void
    {
        if (!Schema::hasTable('employee_room_assignments') ||
            !Schema::hasColumn('employee_room_assignments', 'department_id')) {
            return;
        }

        if (DB::table('employee_room_assignments')->whereNull('room_id')->exists()) {
            throw new RuntimeException('Cannot make manpower rooms required while department-only placements exist.');
        }

        Schema::table('employee_room_assignments', function (Blueprint $table) {
            $table->dropConstrainedForeignId('department_id');
            $table->foreignId('room_id')->nullable(false)->change();
        });
    }
};
