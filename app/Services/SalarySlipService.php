<?php

namespace App\Services;

use App\Models\Absensi;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Validation\ValidationException;

class SalarySlipService
{
    private const ELIGIBLE_CATEGORIES = ['freelance', 'borongan'];

    private const NON_EMPLOYEE_ROLES = [
        'admin',
        'super_admin',
        'manager',
        'supervisor',
        'hrga',
        'pkl',
    ];

    public function eligibleUsers(): Collection
    {
        return User::query()
            ->where(function ($query) {
                $query->whereIn('employment_type', self::ELIGIBLE_CATEGORIES)
                    ->orWhere('id_karyawan', 'like', 'AMB%')
                    ->orWhere('id_karyawan', 'like', 'CS-AMB%');
            })
            ->where(function ($query) {
                $query->whereNull('role')
                    ->orWhereNotIn('role', self::NON_EMPLOYEE_ROLES);
            })
            ->orderBy('name')
            ->get()
            ->filter(fn (User $user) => in_array($user->kategori_absensi, self::ELIGIBLE_CATEGORIES, true))
            ->values();
    }

    public function recipients(string $mode, array $userIds = []): Collection
    {
        if ($mode === 'all') {
            return $this->eligibleUsers()->sortBy('id')->values();
        }

        $eligibleUsers = $this->eligibleUsers()->keyBy('id');
        $invalidIds = collect($userIds)->reject(fn ($id) => $eligibleUsers->has($id));

        if ($invalidIds->isNotEmpty()) {
            throw ValidationException::withMessages([
                'user_ids' => 'Pilih hanya karyawan Freelance atau Borongan.',
            ]);
        }

        return collect($userIds)
            ->map(fn ($id) => $eligibleUsers->get($id))
            ->sortBy('id')
            ->values();
    }

    public function calculate(User $user, Carbon $startDate, Carbon $endDate): ?array
    {
        $records = Absensi::query()
            ->where('user_id', $user->id)
            ->whereBetween('check_in_at', [
                $startDate->copy()->startOfDay(),
                $endDate->copy()->endOfDay(),
            ])
            ->where('status_approval', 'approved')
            ->whereNull('parent_id')
            ->orderBy('check_in_at')
            ->get();

        if ($records->isEmpty()) {
            return null;
        }

        $sum = fn (string $field): float => round(
            (float) $records->sum(fn (Absensi $record) => (float) ($record->{$field} ?? 0)),
            2
        );

        $adjustmentSalary = $sum('adjustment_salary');

        return [
            'attendance_days' => $records
                ->where('status', 'hadir')
                ->map(fn (Absensi $record) => $record->check_in_at?->toDateString())
                ->filter()
                ->unique()
                ->count(),
            'overtime_minutes' => (int) $records->sum(fn (Absensi $record) => (int) ($record->overtime_minutes ?? 0)),
            'base_salary' => $sum('base_salary'),
            'overtime_pay' => $sum('overtime_pay'),
            'late_penalty' => $sum('late_penalty'),
            'adjustment_salary' => $adjustmentSalary,
            'net_salary' => round($sum('final_salary') + $adjustmentSalary, 2),
            'daily_breakdown' => $records->map(fn (Absensi $record) => [
                'attendance_id' => $record->id,
                'work_date' => $record->check_in_at?->toDateString(),
                'status' => $record->status,
                'base_salary' => (float) ($record->base_salary ?? 0),
                'late_penalty' => (float) ($record->late_penalty ?? 0),
                'overtime_minutes' => (int) ($record->overtime_minutes ?? 0),
                'overtime_pay' => (float) ($record->overtime_pay ?? 0),
                'adjustment_salary' => (float) ($record->adjustment_salary ?? 0),
                'final_salary' => (float) ($record->final_salary ?? 0),
                'payroll_snapshot' => $record->payroll_snapshot,
            ])->values()->all(),
        ];
    }
}
