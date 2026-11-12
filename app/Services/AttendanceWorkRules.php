<?php

namespace App\Services;

use App\Models\Absensi;
use App\Models\EmployeeShiftOverride;
use App\Models\EmployeeWorkAssignment;
use App\Models\User;
use App\Models\WorkShiftRotationSetting;
use Carbon\Carbon;

class AttendanceWorkRules
{
    public function forEmployee(User $user, Carbon $workDate, ?Absensi $absensi = null): array
    {
        if ($absensi?->payroll_snapshot) {
            return [
                'assignment' => null,
                'shift' => null,
                'pay_scheme' => null,
                'work_shift_id' => $absensi->work_shift_id,
                'pay_scheme_id' => $absensi->pay_scheme_id,
                'snapshot' => $absensi->payroll_snapshot,
            ];
        }

        $assignment = null;
        if ($absensi?->pay_scheme_id || $absensi?->work_shift_id) {
            $assignment = EmployeeWorkAssignment::query()
                ->where('user_id', $user->id)
                ->where('pay_scheme_id', $absensi->pay_scheme_id)
                ->where('work_shift_id', $absensi->work_shift_id)
                ->whereDate('effective_from', '<=', $workDate)
                ->where(function ($query) use ($workDate) {
                    $query->whereNull('effective_until')
                        ->orWhereDate('effective_until', '>=', $workDate);
                })
                ->with(['workShift', 'payScheme'])
                ->first();
        }

        $assignment ??= EmployeeWorkAssignment::query()
            ->where('user_id', $user->id)
            ->whereDate('effective_from', '<=', $workDate)
            ->where(function ($query) use ($workDate) {
                $query->whereNull('effective_until')
                    ->orWhereDate('effective_until', '>=', $workDate);
            })
            ->with(['workShift', 'payScheme'])
            ->orderByDesc('effective_from')
            ->first();

        $override = EmployeeShiftOverride::query()
            ->where('user_id', $user->id)
            ->whereDate('work_date', $workDate)
            ->with('workShift')
            ->first();

        $shift = $override?->workShift;
        if (!$shift && $assignment?->schedule_mode === 'rolling' && $assignment->rotation_group) {
            $weekStart = $workDate->copy()->startOfWeek(Carbon::MONDAY);
            $rotation = WorkShiftRotationSetting::query()
                ->whereDate('anchor_week_start', '<=', $weekStart)
                ->orderByDesc('anchor_week_start')
                ->with(['groupAShift', 'groupBShift'])
                ->first();

            if ($rotation) {
                $weekOffset = intdiv((int) $rotation->anchor_week_start->diffInDays($weekStart), 7);
                $groupAIsOnFirstShift = $weekOffset % 2 === 0;
                $useGroupAShift = ($assignment->rotation_group === 'A') === $groupAIsOnFirstShift;
                $shift = $useGroupAShift
                    ? $rotation->groupAShift
                    : $rotation->groupBShift;
            }
        }
        $shift ??= $assignment?->workShift;
        $payrollEnabled = $user->kategori_absensi !== 'organik';
        $payScheme = $payrollEnabled ? $assignment?->payScheme : null;
        $snapshot = null;
        if ($shift) {
            $snapshot = [
                'payroll_enabled' => $payrollEnabled,
                'shift' => [
                    'name' => $shift->name,
                    'starts_at' => $shift->starts_at,
                    'ends_at' => $shift->ends_at,
                    'break_starts_at' => $shift->break_starts_at,
                    'break_ends_at' => $shift->break_ends_at,
                    'crosses_midnight' => $shift->crosses_midnight,
                ],
            ];
            if ($payScheme) {
                $snapshot['pay_scheme'] = [
                    'name' => $payScheme->name,
                    'daily_salary' => (float) $payScheme->daily_salary,
                    'standard_work_minutes' => $payScheme->standard_work_minutes,
                    'late_rounding_minutes' => $payScheme->late_rounding_minutes,
                    'overtime_rounding_minutes' => $payScheme->overtime_rounding_minutes,
                    'weekend_daily_multiplier' => (float) $payScheme->weekend_daily_multiplier,
                    'overtime_multiplier' => (float) $payScheme->overtime_multiplier,
                ];
            }
        }

        return [
            'assignment' => $assignment,
            'shift' => $shift,
            'pay_scheme' => $payScheme,
            'work_shift_id' => $shift?->id,
            'pay_scheme_id' => $payScheme?->id,
            'snapshot' => $snapshot,
        ];
    }

    public function lateMinutes(Carbon $checkIn, ?array $snapshot, bool $trackLateness = true): int
    {
        if (!$trackLateness || ($snapshot['payroll_enabled'] ?? true) === false) {
            return 0;
        }

        $shiftStart = $snapshot['shift']['starts_at'] ?? null;
        if (!$shiftStart) {
            $shiftStart = '08:00:00';
        }

        $scheduledStart = $checkIn->copy()->setTimeFromTimeString($shiftStart);

        return $checkIn->greaterThan($scheduledStart)
            ? (int) $scheduledStart->diffInMinutes($checkIn)
            : 0;
    }

    public function attendanceAttributes(array $configuration): array
    {
        return [
            'work_shift_id' => $configuration['work_shift_id'],
            'pay_scheme_id' => $configuration['pay_scheme_id'],
            'payroll_snapshot' => $configuration['snapshot'],
        ];
    }
}
