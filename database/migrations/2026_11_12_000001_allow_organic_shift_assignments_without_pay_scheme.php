<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('employee_work_assignments', function (Blueprint $table) {
            $table->foreignId('pay_scheme_id')->nullable()->change();
        });
    }

    public function down(): void
    {
        if (DB::table('employee_work_assignments')->whereNull('pay_scheme_id')->exists()) {
            throw new RuntimeException(
                'Cannot make employee_work_assignments.pay_scheme_id required while organic shift assignments have no pay scheme.'
            );
        }

        Schema::table('employee_work_assignments', function (Blueprint $table) {
            $table->foreignId('pay_scheme_id')->nullable(false)->change();
        });
    }
};
