<?php

use App\Models\EmployeeShiftOverride;
use App\Models\EmployeeWorkAssignment;
use App\Models\PayScheme;
use App\Models\User;
use App\Models\WorkShift;
use App\Models\WorkShiftPolicy;
use App\Services\AttendanceWorkRules;
use Carbon\Carbon;

function shiftPolicyTemplate(string $name, string $startsAt, string $endsAt): WorkShift
{
    return WorkShift::create([
        'name' => $name,
        'starts_at' => $startsAt,
        'ends_at' => $endsAt,
        'is_active' => true,
    ]);
}

function shiftPolicyPayScheme(): PayScheme
{
    return PayScheme::create([
        'name' => 'Shift policy test scheme',
        'daily_salary' => 125000,
        'standard_work_minutes' => 480,
        'late_rounding_minutes' => 15,
        'overtime_rounding_minutes' => 30,
        'weekend_daily_multiplier' => 2,
        'overtime_multiplier' => 1,
        'is_active' => true,
    ]);
}

it('applies one fixed shift from the effective date while preserving assignment history and pay', function () {
    $effectiveFrom = Carbon::today()->startOfWeek(Carbon::MONDAY)->addWeek();
    $admin = User::factory()->create(['role' => 'admin']);
    $employee = User::factory()->create([
        'role' => 'user',
        'employment_type' => 'freelance',
    ]);
    $morning = shiftPolicyTemplate('Policy Morning', '08:00:00', '17:00:00');
    $night = shiftPolicyTemplate('Policy Night', '20:00:00', '05:00:00');
    $payScheme = shiftPolicyPayScheme();
    $oldAssignment = EmployeeWorkAssignment::create([
        'user_id' => $employee->id,
        'work_shift_id' => $night->id,
        'pay_scheme_id' => $payScheme->id,
        'effective_from' => $effectiveFrom->copy()->subMonth()->toDateString(),
        'schedule_mode' => 'rolling',
        'rotation_group' => 'A',
    ]);
    EmployeeShiftOverride::create([
        'user_id' => $employee->id,
        'work_shift_id' => $night->id,
        'work_date' => $effectiveFrom->copy()->addDay()->toDateString(),
        'created_by' => $admin->id,
    ]);

    $this->actingAs($admin)
        ->post(route('admin.work-rules.single-shift-policy.store'), [
            'effective_from' => $effectiveFrom->toDateString(),
            'work_shift_id' => $morning->id,
            'confirm' => '1',
        ])
        ->assertRedirect(route('admin.work-rules.index', ['week' => $effectiveFrom->toDateString()]));

    $newAssignment = EmployeeWorkAssignment::query()
        ->where('user_id', $employee->id)
        ->whereDate('effective_from', $effectiveFrom)
        ->firstOrFail();

    expect($oldAssignment->fresh()->effective_until->toDateString())
        ->toBe($effectiveFrom->copy()->subDay()->toDateString())
        ->and($newAssignment->work_shift_id)->toBe($morning->id)
        ->and($newAssignment->pay_scheme_id)->toBe($payScheme->id)
        ->and($newAssignment->schedule_mode)->toBe('fixed')
        ->and($newAssignment->rotation_group)->toBeNull()
        ->and($night->fresh()->is_active)->toBeFalse()
        ->and($morning->fresh()->is_active)->toBeTrue()
        ->and(EmployeeShiftOverride::where('work_date', '>=', $effectiveFrom)->exists())->toBeFalse()
        ->and(WorkShiftPolicy::forDate($effectiveFrom)->work_shift_id)->toBe($morning->id);

    $this->actingAs($admin)
        ->post(route('admin.work-rules.shift-overrides.store'), [
            'user_id' => $employee->id,
            'work_shift_id' => $night->id,
            'work_date' => $effectiveFrom->copy()->subDay()->toDateString(),
        ])
        ->assertSessionHasNoErrors();

    expect(EmployeeShiftOverride::where('user_id', $employee->id)
        ->whereDate('work_date', $effectiveFrom->copy()->subDay())
        ->exists())->toBeTrue();
});

it('gives the permanent shift policy priority over rolling schedules and daily overrides', function () {
    $effectiveFrom = Carbon::today()->startOfWeek(Carbon::MONDAY)->addWeek();
    $employee = User::factory()->create([
        'role' => 'user',
        'employment_type' => 'freelance',
    ]);
    $morning = shiftPolicyTemplate('Policy priority morning', '08:00:00', '17:00:00');
    $night = shiftPolicyTemplate('Policy priority night', '20:00:00', '05:00:00');
    $payScheme = shiftPolicyPayScheme();

    EmployeeWorkAssignment::create([
        'user_id' => $employee->id,
        'work_shift_id' => $night->id,
        'pay_scheme_id' => $payScheme->id,
        'effective_from' => $effectiveFrom->copy()->subMonth()->toDateString(),
        'schedule_mode' => 'rolling',
        'rotation_group' => 'A',
    ]);
    EmployeeShiftOverride::create([
        'user_id' => $employee->id,
        'work_shift_id' => $night->id,
        'work_date' => $effectiveFrom->copy()->addDay()->toDateString(),
    ]);
    WorkShiftPolicy::create([
        'effective_from' => $effectiveFrom->toDateString(),
        'work_shift_id' => $morning->id,
    ]);

    $rules = app(AttendanceWorkRules::class);
    $beforePolicy = $rules->forEmployee($employee, $effectiveFrom->copy()->subDay());
    $duringPolicy = $rules->forEmployee($employee, $effectiveFrom->copy()->addDay());

    expect($beforePolicy['shift']->id)->toBe($night->id)
        ->and($duringPolicy['shift']->id)->toBe($morning->id);
});
