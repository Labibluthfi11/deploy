<?php

use App\Models\Absensi;
use App\Services\AttendanceWorkRules;
use Carbon\Carbon;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

uses(TestCase::class);

beforeEach(function () {
    Schema::dropIfExists('holidays');
    Schema::create('holidays', function (Blueprint $table) {
        $table->id();
        $table->date('holiday_date')->unique();
        $table->string('name')->nullable();
        $table->timestamps();
    });
});

afterEach(function () {
    Schema::dropIfExists('holidays');
});

function configuredPayrollSnapshot(float $dailySalary, string $start, string $breakStart, string $breakEnd): array
{
    return [
        'shift' => [
            'starts_at' => $start,
            'break_starts_at' => $breakStart,
            'break_ends_at' => $breakEnd,
            'crosses_midnight' => $start > '12:00:00',
        ],
        'pay_scheme' => [
            'daily_salary' => $dailySalary,
            'standard_work_minutes' => 480,
            'late_rounding_minutes' => 15,
            'overtime_rounding_minutes' => 30,
            'weekend_daily_multiplier' => 2,
            'overtime_multiplier' => 1,
        ],
    ];
}

it('rounds tardiness up to 15-minute blocks at each configured daily rate', function () {
    $checkIn = Carbon::parse('2026-11-09 08:16:00');
    $checkOut = Carbon::parse('2026-11-09 17:00:00');

    $salary150 = Absensi::calculateSalary(
        16,
        'hadir',
        null,
        false,
        $checkIn,
        $checkOut,
        'freelance',
        null,
        configuredPayrollSnapshot(150000, '08:00:00', '12:00:00', '13:00:00')
    );
    $salary125 = Absensi::calculateSalary(
        16,
        'hadir',
        null,
        false,
        $checkIn,
        $checkOut,
        'freelance',
        null,
        configuredPayrollSnapshot(125000, '08:00:00', '12:00:00', '13:00:00')
    );

    expect($salary150['rounded_late_minutes'])->toBe(30)
        ->and($salary150['late_penalty'])->toBe(9375.0)
        ->and($salary150['base_salary'])->toBe(150000.0)
        ->and($salary125['rounded_late_minutes'])->toBe(30)
        ->and($salary125['late_penalty'])->toBe(7812.5)
        ->and($salary125['base_salary'])->toBe(125000.0);
});

it('uses the shift start date for weekend daily pay and excludes the overnight break', function () {
    $checkIn = Carbon::parse('2026-11-07 20:00:00');
    $checkOut = Carbon::parse('2026-11-08 05:00:00');
    $fridayCheckIn = Carbon::parse('2026-11-06 20:00:00');
    $fridayCheckOut = Carbon::parse('2026-11-07 05:00:00');

    $salary = Absensi::calculateSalary(
        0,
        'hadir',
        null,
        false,
        $checkIn,
        $checkOut,
        'freelance',
        null,
        configuredPayrollSnapshot(125000, '20:00:00', '00:00:00', '01:00:00')
    );
    $fridaySalary = Absensi::calculateSalary(
        0,
        'hadir',
        null,
        false,
        $fridayCheckIn,
        $fridayCheckOut,
        'freelance',
        null,
        configuredPayrollSnapshot(125000, '20:00:00', '00:00:00', '01:00:00')
    );

    expect($salary['worked_minutes'])->toBe(480)
        ->and($salary['base_salary'])->toBe(250000.0)
        ->and($fridaySalary['worked_minutes'])->toBe(480)
        ->and($fridaySalary['base_salary'])->toBe(125000.0);
});

it('rounds configured overtime up by 30 minutes without a weekend multiplier', function () {
    $snapshot = configuredPayrollSnapshot(150000, '08:00:00', '12:00:00', '13:00:00');
    $start = Carbon::parse('2026-11-07 17:00:00');

    $twentyFiveMinutes = Absensi::calculateOvertimeFromInput(
        $start,
        $start->copy()->addMinutes(25),
        false,
        true,
        $snapshot
    );
    $thirtyOneMinutes = Absensi::calculateOvertimeFromInput(
        $start,
        $start->copy()->addMinutes(31),
        false,
        true,
        $snapshot
    );

    expect($twentyFiveMinutes['minutes'])->toBe(30)
        ->and($twentyFiveMinutes['pay'])->toBe(9375.0)
        ->and($thirtyOneMinutes['minutes'])->toBe(60)
        ->and($thirtyOneMinutes['pay'])->toBe(18750.0);
});

it('keeps organic attendance and overtime amounts at zero while retaining attendance minutes', function () {
    $snapshot = [
        'payroll_enabled' => false,
        'shift' => [
            'starts_at' => '08:00:00',
            'ends_at' => '17:00:00',
            'break_starts_at' => '12:00:00',
            'break_ends_at' => '13:00:00',
            'crosses_midnight' => false,
        ],
    ];
    $checkIn = Carbon::parse('2026-11-09 08:16:00');
    $checkOut = Carbon::parse('2026-11-09 17:00:00');

    $salary = Absensi::calculateSalary(
        16,
        'hadir',
        null,
        false,
        $checkIn,
        $checkOut,
        'organik',
        null,
        $snapshot
    );
    $lateMinutes = app(AttendanceWorkRules::class)->lateMinutes($checkIn, $snapshot);
    $overtime = Absensi::calculateOvertimeFromInput(
        $checkOut,
        $checkOut->copy()->addMinutes(45),
        false,
        false,
        $snapshot,
        false
    );

    expect($salary['worked_minutes'])->toBe(480)
        ->and($lateMinutes)->toBe(0)
        ->and($salary['rounded_late_minutes'])->toBe(0)
        ->and($salary['base_salary'])->toBe(0.0)
        ->and($salary['late_penalty'])->toBe(0.0)
        ->and($salary['final_salary'])->toBe(0.0)
        ->and($overtime['minutes'])->toBe(45)
        ->and($overtime['pay'])->toBe(0.0);
});
