<?php

namespace App\Console\Commands;

use App\Models\Absensi;
use App\Models\SalarySlip;
use App\Models\WorkShift;
use App\Models\WorkShiftPolicy;
use App\Services\AttendanceWorkRules;
use Carbon\Carbon;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class RepairSingleShiftPayrollCommand extends Command
{
    protected $signature = 'attendance:repair-single-shift-payroll
        {from : First work date to repair (YYYY-MM-DD)}
        {to : Last work date to repair (YYYY-MM-DD)}
        {shift_id : Work shift ID to apply}
        {--apply : Save the policy and repair matching attendance rows}';

    protected $description = 'Preview or repair attendance payroll snapshots after a single-shift change';

    public function handle(): int
    {
        $from = $this->parseDate((string) $this->argument('from'));
        $to = $this->parseDate((string) $this->argument('to'));

        if (!$from || !$to || $from->gt($to)) {
            $this->error('Use valid dates in YYYY-MM-DD format, with "from" on or before "to".');

            return self::FAILURE;
        }

        $shift = WorkShift::query()->find($this->argument('shift_id'));
        if (!$shift || !$shift->is_active) {
            $this->error('The target shift must exist and be active.');

            return self::FAILURE;
        }

        $conflictingPolicy = WorkShiftPolicy::query()
            ->whereDate('effective_from', '>', $from->toDateString())
            ->where('work_shift_id', '!=', $shift->id)
            ->first();

        if ($conflictingPolicy) {
            $this->error(
                "A different shift policy is already scheduled from {$conflictingPolicy->effective_from->toDateString()}."
            );

            return self::FAILURE;
        }

        if (!$this->option('apply')) {
            $plans = $this->repairPlan($from, $to, $shift);
            $this->showPlan($plans, $from, $to, $shift);
            $this->warn('Preview only. Add --apply after reviewing these rows to save changes.');

            return self::SUCCESS;
        }

        $result = DB::transaction(function () use ($from, $to, $shift): array {
            $plans = $this->repairPlan($from, $to, $shift, true);
            $slipConflicts = $this->issuedSlipConflicts($plans, $from, $to);

            if ($slipConflicts !== []) {
                return ['plans' => $plans, 'slip_conflicts' => $slipConflicts];
            }

            WorkShiftPolicy::updateOrCreate(
                ['effective_from' => $from->toDateString()],
                ['work_shift_id' => $shift->id, 'created_by' => null]
            );

            foreach ($plans as $plan) {
                $record = $plan['record'];
                $update = [
                    'work_shift_id' => $shift->id,
                    'payroll_snapshot' => $plan['snapshot'],
                    'late_minutes' => $plan['late_minutes'],
                ];

                if ($plan['salary']) {
                    $update += [
                        'rounded_late_minutes' => $plan['salary']['rounded_late_minutes'],
                        'base_salary' => $plan['salary']['base_salary'],
                        'late_penalty' => $plan['salary']['late_penalty'],
                        'final_salary' => $plan['new_final_salary'],
                    ];
                }

                $record->forceFill($update)->save();
            }

            return ['plans' => $plans, 'slip_conflicts' => []];
        });

        $this->showPlan($result['plans'], $from, $to, $shift);

        if ($result['slip_conflicts'] !== []) {
            $this->error('No changes were made: affected attendance is already included in an issued salary slip.');
            $this->table(['Slip ID', 'Attendance ID'], $result['slip_conflicts']);

            return self::FAILURE;
        }

        $completed = collect($result['plans'])->filter(fn (array $plan) => $plan['salary'])->count();
        $open = count($result['plans']) - $completed;
        $this->info("Saved the morning shift policy and repaired {$completed} completed attendance rows.");
        if ($open > 0) {
            $this->info("Updated {$open} open attendance snapshots; their pay remains pending until checkout.");
        }

        return self::SUCCESS;
    }

    private function parseDate(string $value): ?Carbon
    {
        if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $value)) {
            return null;
        }

        try {
            $date = Carbon::createFromFormat('!Y-m-d', $value);
        } catch (\Carbon\Exceptions\InvalidFormatException) {
            return null;
        }

        return $date && $date->format('Y-m-d') === $value ? $date : null;
    }

    private function repairPlan(Carbon $from, Carbon $to, WorkShift $shift, bool $lock = false): array
    {
        $query = Absensi::query()
            ->with('user')
            ->whereBetween('check_in_at', [$from->copy()->startOfDay(), $to->copy()->endOfDay()])
            ->where('status', 'hadir')
            ->where('status_approval', 'approved')
            ->whereNull('parent_id')
            ->where(function ($query) {
                $query->whereNull('tipe')->orWhere('tipe', 'normal');
            })
            ->whereNotNull('payroll_snapshot')
            ->orderBy('id');

        if ($lock) {
            $query->lockForUpdate();
        }

        $workRules = app(AttendanceWorkRules::class);
        $plans = [];

        foreach ($query->get() as $record) {
            if (!$record->user || !in_array($record->user->kategori_absensi, ['freelance', 'borongan'], true)) {
                continue;
            }

            $snapshot = $record->payroll_snapshot;
            if (
                !is_array($snapshot)
                || !is_array($snapshot['shift'] ?? null)
                || ($snapshot['payroll_enabled'] ?? true) === false
                || ($snapshot['shift']['starts_at'] ?? null) === $shift->starts_at
            ) {
                continue;
            }

            $snapshot['payroll_enabled'] = true;
            $snapshot['shift'] = [
                'name' => $shift->name,
                'starts_at' => $shift->starts_at,
                'ends_at' => $shift->ends_at,
                'break_starts_at' => $shift->break_starts_at,
                'break_ends_at' => $shift->break_ends_at,
                'crosses_midnight' => $shift->crosses_midnight,
            ];

            $lateMinutes = $workRules->lateMinutes($record->check_in_at, $snapshot);
            $salary = $record->check_out_at
                ? Absensi::calculateSalary(
                    $lateMinutes,
                    $record->status,
                    $record->tipe,
                    Absensi::isWeekend($record->check_in_at),
                    $record->check_in_at,
                    $record->check_out_at,
                    $record->user->kategori_absensi,
                    $record->catatan_admin,
                    $snapshot
                )
                : null;

            $plans[] = [
                'record' => $record,
                'old_shift_start' => $record->payroll_snapshot['shift']['starts_at'] ?? '-',
                'old_final_salary' => $record->final_salary ?? 0,
                'snapshot' => $snapshot,
                'late_minutes' => $lateMinutes,
                'salary' => $salary,
                'new_final_salary' => $salary
                    ? max(0, $salary['final_salary'] + (float) ($record->overtime_pay ?? 0))
                    : null,
            ];
        }

        return $plans;
    }

    private function issuedSlipConflicts(array $plans, Carbon $from, Carbon $to): array
    {
        if ($plans === []) {
            return [];
        }

        $attendanceIds = collect($plans)
            ->map(fn (array $plan) => (int) $plan['record']->id)
            ->flip();
        $userIds = collect($plans)
            ->map(fn (array $plan) => (int) $plan['record']->user_id)
            ->unique()
            ->values();
        $conflicts = [];

        $slips = SalarySlip::query()
            ->whereIn('user_id', $userIds)
            ->whereDate('period_start', '<=', $to->toDateString())
            ->whereDate('period_end', '>=', $from->toDateString())
            ->get(['id', 'daily_breakdown']);

        foreach ($slips as $slip) {
            foreach ($slip->daily_breakdown ?? [] as $day) {
                $attendanceId = (int) ($day['attendance_id'] ?? 0);
                if ($attendanceIds->has($attendanceId)) {
                    $conflicts[] = [$slip->id, $attendanceId];
                }
            }
        }

        return $conflicts;
    }

    private function showPlan(array $plans, Carbon $from, Carbon $to, WorkShift $shift): void
    {
        $this->info(sprintf(
            'Target: %s (%s); dates: %s through %s; affected attendance rows: %d.',
            $shift->name,
            $shift->starts_at,
            $from->toDateString(),
            $to->toDateString(),
            count($plans)
        ));

        $rows = array_map(fn (array $plan) => [
            $plan['record']->id,
            $plan['record']->user_id,
            $plan['old_shift_start'],
            $plan['record']->check_out_at ? 'complete' : 'awaiting checkout',
            $plan['old_final_salary'],
            $plan['new_final_salary'] ?? 'pending',
        ], array_slice($plans, 0, 100));

        if ($rows !== []) {
            $this->table(
                ['Attendance ID', 'User ID', 'Old shift start', 'State', 'Old final', 'New final'],
                $rows
            );
        }

        if (count($plans) > 100) {
            $this->warn('Preview is limited to the first 100 rows.');
        }
    }
}
