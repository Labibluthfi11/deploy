<?php

use App\Models\Absensi;
use App\Models\EmployeeWorkAssignment;
use App\Models\PayScheme;
use App\Models\User;
use App\Models\WorkShift;

it('updates one assignment pay scheme without changing saved attendance payroll snapshots', function () {
    $admin = User::factory()->create(['role' => 'admin']);
    $employee = User::factory()->create([
        'role' => 'user',
        'employment_type' => 'freelance',
    ]);
    $shift = WorkShift::create([
        'name' => 'Assignment edit test shift',
        'starts_at' => '08:00:00',
        'ends_at' => '17:00:00',
        'is_active' => true,
    ]);
    $oldScheme = PayScheme::create([
        'name' => 'Assignment edit old scheme',
        'daily_salary' => 125000,
        'standard_work_minutes' => 480,
        'late_rounding_minutes' => 15,
        'overtime_rounding_minutes' => 30,
        'weekend_daily_multiplier' => 2,
        'overtime_multiplier' => 1,
        'is_active' => true,
    ]);
    $newScheme = PayScheme::create([
        'name' => 'Assignment edit new scheme',
        'daily_salary' => 150000,
        'standard_work_minutes' => 480,
        'late_rounding_minutes' => 15,
        'overtime_rounding_minutes' => 30,
        'weekend_daily_multiplier' => 2,
        'overtime_multiplier' => 1,
        'is_active' => true,
    ]);
    $assignment = EmployeeWorkAssignment::create([
        'user_id' => $employee->id,
        'work_shift_id' => $shift->id,
        'pay_scheme_id' => $oldScheme->id,
        'effective_from' => '2026-11-01',
        'schedule_mode' => 'fixed',
    ]);
    $attendance = Absensi::create([
        'user_id' => $employee->id,
        'status' => 'hadir',
        'check_in_at' => '2026-11-10 08:00:00',
        'work_shift_id' => $shift->id,
        'pay_scheme_id' => $oldScheme->id,
        'payroll_snapshot' => ['pay_scheme' => ['daily_salary' => 125000]],
    ]);

    $this->actingAs($admin)
        ->put(route('admin.work-rules.assignments.pay-scheme.update', $assignment), [
            'pay_scheme_id' => $newScheme->id,
            'week' => '2026-11-09',
        ])
        ->assertRedirect(route('admin.work-rules.index', ['week' => '2026-11-09']));

    expect($assignment->fresh()->pay_scheme_id)->toBe($newScheme->id)
        ->and($attendance->fresh()->pay_scheme_id)->toBe($oldScheme->id)
        ->and($attendance->fresh()->payroll_snapshot['pay_scheme']['daily_salary'])->toBe(125000);
});
