<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\EmployeeShiftOverride;
use App\Models\EmployeeWorkAssignment;
use App\Models\PayScheme;
use App\Models\User;
use App\Models\WorkShift;
use App\Models\WorkShiftRotationSetting;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class WorkRuleAdminController extends Controller
{
    public function index(Request $request)
    {
        $filters = $request->validate([
            'week' => ['nullable', 'date'],
        ]);
        $weekStart = Carbon::parse($filters['week'] ?? Carbon::today()->addWeek()->startOfWeek(Carbon::MONDAY))
            ->startOfWeek(Carbon::MONDAY);
        $weekEnd = $weekStart->copy()->endOfWeek(Carbon::SUNDAY);
        $shifts = WorkShift::withCount(['employeeAssignments', 'employeeOverrides'])
            ->orderBy('name')
            ->get();
        $paySchemes = PayScheme::withCount('employeeAssignments')
            ->orderBy('name')
            ->get();
        $employees = User::query()
            ->whereNotIn('role', ['supervisor', 'manager', 'hrga', 'admin', 'super_admin', 'pkl'])
            ->orderBy('name')
            ->get(['id', 'name', 'id_karyawan', 'employment_type']);
        $nextWeekStart = Carbon::today()->startOfWeek(Carbon::MONDAY)->addWeek();
        $scheduleEffectiveFrom = $weekStart->greaterThan($nextWeekStart)
            ? $weekStart->copy()
            : $nextWeekStart->copy();
        $overrides = EmployeeShiftOverride::with(['user:id,name,id_karyawan', 'workShift'])
            ->whereDate('work_date', '>=', today())
            ->orderBy('work_date')
            ->limit(50)
            ->get();
        $rotationSetting = WorkShiftRotationSetting::with(['groupAShift', 'groupBShift'])
            ->whereDate('anchor_week_start', '<=', $weekStart)
            ->orderByDesc('anchor_week_start')
            ->first();
        $weekAssignments = EmployeeWorkAssignment::with(['user:id,name,id_karyawan,employment_type', 'workShift', 'payScheme'])
            ->whereDate('effective_from', '<=', $weekEnd)
            ->where(function ($query) use ($weekStart) {
                $query->whereNull('effective_until')
                    ->orWhereDate('effective_until', '>=', $weekStart);
            })
            ->orderBy('user_id')
            ->orderByDesc('effective_from')
            ->get()
            ->groupBy('user_id')
            ->map(fn ($employeeAssignments) => $employeeAssignments->first())
            ->values();
        $employeeScheduleGroups = [
            [
                'key' => 'A',
                'title' => 'Grup A',
                'assignments' => $weekAssignments
                    ->where('schedule_mode', 'rolling')
                    ->where('rotation_group', 'A')
                    ->values(),
            ],
            [
                'key' => 'B',
                'title' => 'Grup B',
                'assignments' => $weekAssignments
                    ->where('schedule_mode', 'rolling')
                    ->where('rotation_group', 'B')
                    ->values(),
            ],
            [
                'key' => 'fixed',
                'title' => 'Jadwal Tetap',
                'assignments' => $weekAssignments
                    ->where('schedule_mode', 'fixed')
                    ->values(),
            ],
        ];
        $rotationShifts = [];
        if ($rotationSetting) {
            $weekOffset = intdiv((int) $rotationSetting->anchor_week_start->diffInDays($weekStart), 7);
            $groupAShift = $weekOffset % 2 === 0
                ? $rotationSetting->groupAShift
                : $rotationSetting->groupBShift;
            $groupBShift = $weekOffset % 2 === 0
                ? $rotationSetting->groupBShift
                : $rotationSetting->groupAShift;
            $rotationShifts = ['A' => $groupAShift, 'B' => $groupBShift];
        }

        return view('admin.work-rules.index', compact(
            'shifts',
            'paySchemes',
            'employees',
            'overrides',
            'weekStart',
            'weekEnd',
            'rotationSetting',
            'weekAssignments',
            'employeeScheduleGroups',
            'scheduleEffectiveFrom',
            'rotationShifts'
        ));
    }

    public function storeShift(Request $request)
    {
        $data = $this->validateShift($request);
        $data['crosses_midnight'] = $data['ends_at'] <= $data['starts_at'];

        WorkShift::create($data);

        return $this->redirectWithSuccess('Template shift berhasil ditambahkan.');
    }

    public function updateShift(Request $request, WorkShift $shift)
    {
        $data = $this->validateShift($request, $shift);
        $data['crosses_midnight'] = $data['ends_at'] <= $data['starts_at'];

        $shift->update($data);

        return $this->redirectWithSuccess('Template shift berhasil diperbarui.');
    }

    public function storePayScheme(Request $request)
    {
        PayScheme::create($this->validatePayScheme($request));

        return $this->redirectWithSuccess('Skema upah berhasil ditambahkan.');
    }

    public function updatePayScheme(Request $request, PayScheme $payScheme)
    {
        $payScheme->update($this->validatePayScheme($request, $payScheme));

        return $this->redirectWithSuccess('Skema upah berhasil diperbarui.');
    }

    public function storeAssignment(Request $request)
    {
        $data = $request->validate([
            'user_id' => ['required', 'integer', 'exists:users,id'],
            'work_shift_id' => ['required', 'integer', 'exists:work_shifts,id'],
            'pay_scheme_id' => ['nullable', 'integer', 'exists:pay_schemes,id'],
            'effective_from' => ['required', 'date'],
            'schedule_mode' => ['required', 'in:fixed,rolling'],
            'rotation_group' => ['nullable', 'required_if:schedule_mode,rolling', 'in:A,B'],
        ]);

        $effectiveFrom = Carbon::parse($data['effective_from'])->startOfDay();

        DB::transaction(function () use ($data, $effectiveFrom, $request) {
            $user = User::query()->lockForUpdate()->findOrFail($data['user_id']);
            $shift = WorkShift::query()->findOrFail($data['work_shift_id']);
            $isOrganic = $user->kategori_absensi === 'organik';
            $payScheme = $isOrganic || empty($data['pay_scheme_id'])
                ? null
                : PayScheme::query()->findOrFail($data['pay_scheme_id']);

            if (!$isOrganic && !$payScheme) {
                throw ValidationException::withMessages([
                    'pay_scheme_id' => 'Pilih skema upah aktif untuk karyawan non-organik.',
                ]);
            }
            if ($payScheme && !$payScheme->is_active) {
                throw ValidationException::withMessages([
                    'pay_scheme_id' => 'Skema upah yang dipilih harus aktif.',
                ]);
            }
            if (!$shift->is_active) {
                throw ValidationException::withMessages([
                    'work_shift_id' => 'Shift yang dipilih harus aktif.',
                ]);
            }

            $sameStartExists = EmployeeWorkAssignment::query()
                ->where('user_id', $user->id)
                ->whereDate('effective_from', $effectiveFrom)
                ->exists();

            if ($sameStartExists) {
                throw ValidationException::withMessages([
                    'effective_from' => 'Sudah ada penetapan untuk karyawan ini pada tanggal mulai tersebut.',
                ]);
            }

            $previous = EmployeeWorkAssignment::query()
                ->where('user_id', $user->id)
                ->whereDate('effective_from', '<', $effectiveFrom)
                ->where(function ($query) use ($effectiveFrom) {
                    $query->whereNull('effective_until')
                        ->orWhereDate('effective_until', '>=', $effectiveFrom);
                })
                ->orderByDesc('effective_from')
                ->lockForUpdate()
                ->first();

            $next = EmployeeWorkAssignment::query()
                ->where('user_id', $user->id)
                ->whereDate('effective_from', '>', $effectiveFrom)
                ->orderBy('effective_from')
                ->first();

            if ($previous) {
                $previous->update([
                    'effective_until' => $effectiveFrom->copy()->subDay()->toDateString(),
                ]);
            }

            EmployeeWorkAssignment::create([
                'user_id' => $user->id,
                'work_shift_id' => $shift->id,
                'pay_scheme_id' => $payScheme?->id,
                'effective_from' => $effectiveFrom->toDateString(),
                'effective_until' => $next
                    ? Carbon::parse($next->effective_from)->subDay()->toDateString()
                    : null,
                'schedule_mode' => $data['schedule_mode'],
                'rotation_group' => $data['schedule_mode'] === 'rolling' ? $data['rotation_group'] : null,
            ]);

            EmployeeShiftOverride::query()
                ->where('user_id', $user->id)
                ->whereDate('work_date', $effectiveFrom)
                ->delete();
        });

        return $this->redirectWithSuccess('Penetapan shift dan skema upah berhasil disimpan.');
    }

    public function updateAssignmentSchedule(Request $request, EmployeeWorkAssignment $assignment)
    {
        $data = $request->validate([
            'effective_from' => ['required', 'date'],
            'schedule_mode' => ['required', 'in:fixed,rolling'],
            'rotation_group' => ['nullable', 'required_if:schedule_mode,rolling', 'in:A,B'],
        ]);

        $effectiveFrom = Carbon::parse($data['effective_from'])->startOfDay();
        $nextWeekStart = Carbon::today()->startOfWeek(Carbon::MONDAY)->addWeek();
        if ($effectiveFrom->dayOfWeekIso !== Carbon::MONDAY || $effectiveFrom->lt($nextWeekStart)) {
            throw ValidationException::withMessages([
                'effective_from' => 'Perubahan pola harus mulai hari Senin minggu depan atau setelahnya.',
            ]);
        }

        if ($data['schedule_mode'] === 'rolling') {
            $hasRotationSetting = WorkShiftRotationSetting::query()
                ->whereDate('anchor_week_start', '<=', $effectiveFrom)
                ->exists();
            if (!$hasRotationSetting) {
                throw ValidationException::withMessages([
                    'schedule_mode' => 'Atur pola rotasi mingguan terlebih dahulu sebelum memasukkan karyawan ke grup.',
                ]);
            }
        }

        DB::transaction(function () use ($assignment, $data, $effectiveFrom) {
            $currentAssignment = EmployeeWorkAssignment::query()
                ->lockForUpdate()
                ->findOrFail($assignment->id);
            $currentAssignment->loadMissing('user');
            $isOrganic = $currentAssignment->user->kategori_absensi === 'organik';

            if (
                $effectiveFrom->lt($currentAssignment->effective_from->copy()->startOfDay())
                || (
                    $currentAssignment->effective_until
                    && $effectiveFrom->gt($currentAssignment->effective_until->copy()->startOfDay())
                )
            ) {
                throw ValidationException::withMessages([
                    'effective_from' => 'Tanggal mulai berada di luar masa berlaku penetapan ini. Pilih penetapan yang berlaku pada tanggal tersebut.',
                ]);
            }

            $schedule = [
                'schedule_mode' => $data['schedule_mode'],
                'rotation_group' => $data['schedule_mode'] === 'rolling' ? $data['rotation_group'] : null,
            ];

            if ($effectiveFrom->equalTo($currentAssignment->effective_from->copy()->startOfDay())) {
                $currentAssignment->update($schedule + ($isOrganic ? ['pay_scheme_id' => null] : []));
                return;
            }

            $assignmentEnd = $currentAssignment->effective_until?->toDateString();
            $previousEnd = $effectiveFrom->copy()->subDay()->toDateString();
            $currentAssignment->update(['effective_until' => $previousEnd]);

            EmployeeWorkAssignment::create([
                'user_id' => $currentAssignment->user_id,
                'work_shift_id' => $currentAssignment->work_shift_id,
                'pay_scheme_id' => $isOrganic ? null : $currentAssignment->pay_scheme_id,
                'effective_from' => $effectiveFrom->toDateString(),
                'effective_until' => $assignmentEnd,
                ...$schedule,
            ]);
        });

        return redirect()
            ->route('admin.work-rules.index', ['week' => $effectiveFrom->toDateString()])
            ->with('success', 'Pola shift karyawan berhasil diperbarui tanpa mengubah skema upah.');
    }

    public function storeRotationSetting(Request $request)
    {
        $data = $request->validate([
            'anchor_week_start' => ['required', 'date'],
            'group_a_shift_id' => ['required', 'integer', 'different:group_b_shift_id', 'exists:work_shifts,id'],
            'group_b_shift_id' => ['required', 'integer', 'exists:work_shifts,id'],
        ]);

        $anchorWeekStart = Carbon::parse($data['anchor_week_start'])->startOfDay();
        $nextWeekStart = Carbon::today()->startOfWeek(Carbon::MONDAY)->addWeek();
        if (!$anchorWeekStart->isMonday() || $anchorWeekStart->lt($nextWeekStart)) {
            throw ValidationException::withMessages([
                'anchor_week_start' => 'Tanggal mulai rotasi harus hari Senin minggu depan atau setelahnya.',
            ]);
        }

        $shifts = WorkShift::query()
            ->whereIn('id', [$data['group_a_shift_id'], $data['group_b_shift_id']])
            ->where('is_active', true)
            ->count();
        if ($shifts !== 2) {
            throw ValidationException::withMessages([
                'group_a_shift_id' => 'Pilih dua template shift yang aktif dan berbeda.',
            ]);
        }

        WorkShiftRotationSetting::updateOrCreate(
            ['anchor_week_start' => $anchorWeekStart->toDateString()],
            [
                'group_a_shift_id' => $data['group_a_shift_id'],
                'group_b_shift_id' => $data['group_b_shift_id'],
                'created_by' => $request->user()->id,
            ]
        );

        return redirect()
            ->route('admin.work-rules.index', ['week' => $anchorWeekStart->toDateString()])
            ->with('success', 'Pola rolling mingguan berhasil disimpan.');
    }

    public function storeOverride(Request $request)
    {
        $data = $request->validate([
            'user_id' => ['required', 'integer', 'exists:users,id'],
            'work_shift_id' => ['required', 'integer', 'exists:work_shifts,id'],
            'work_date' => ['required', 'date'],
        ]);

        $shift = WorkShift::query()->findOrFail($data['work_shift_id']);
        if (!$shift->is_active) {
            throw ValidationException::withMessages([
                'work_shift_id' => 'Shift yang dipilih tidak aktif.',
            ]);
        }

        $hasAssignment = EmployeeWorkAssignment::query()
            ->where('user_id', $data['user_id'])
            ->whereDate('effective_from', '<=', $data['work_date'])
            ->where(function ($query) use ($data) {
                $query->whereNull('effective_until')
                    ->orWhereDate('effective_until', '>=', $data['work_date']);
            })
            ->exists();
        if (!$hasAssignment) {
            throw ValidationException::withMessages([
                'work_date' => 'Tetapkan shift dan skema upah karyawan sebelum membuat jadwal khusus.',
            ]);
        }

        EmployeeShiftOverride::updateOrCreate(
            [
                'user_id' => $data['user_id'],
                'work_date' => $data['work_date'],
            ],
            [
                'work_shift_id' => $shift->id,
                'created_by' => $request->user()->id,
            ]
        );

        return $this->redirectWithSuccess('Jadwal shift khusus berhasil disimpan.');
    }

    private function validateShift(Request $request, ?WorkShift $shift = null): array
    {
        $uniqueName = 'unique:work_shifts,name';
        if ($shift) {
            $uniqueName .= ',' . $shift->id;
        }

        return $request->validate([
            'name' => ['required', 'string', 'max:100', $uniqueName],
            'starts_at' => ['required', 'date_format:H:i'],
            'ends_at' => ['required', 'date_format:H:i'],
            'break_starts_at' => ['nullable', 'date_format:H:i', 'required_with:break_ends_at'],
            'break_ends_at' => ['nullable', 'date_format:H:i', 'required_with:break_starts_at'],
            'is_active' => ['nullable', 'boolean'],
        ]) + [
            'is_active' => $request->boolean('is_active'),
        ];
    }

    private function validatePayScheme(Request $request, ?PayScheme $payScheme = null): array
    {
        $uniqueName = 'unique:pay_schemes,name';
        if ($payScheme) {
            $uniqueName .= ',' . $payScheme->id;
        }

        return $request->validate([
            'name' => ['required', 'string', 'max:100', $uniqueName],
            'daily_salary' => ['required', 'numeric', 'min:0', 'max:9999999999.99'],
            'standard_work_minutes' => ['required', 'integer', 'min:1', 'max:1440'],
            'late_rounding_minutes' => ['required', 'integer', 'min:1', 'max:1440'],
            'overtime_rounding_minutes' => ['required', 'integer', 'min:1', 'max:1440'],
            'weekend_daily_multiplier' => ['required', 'numeric', 'min:0', 'max:100'],
            'overtime_multiplier' => ['required', 'numeric', 'min:0', 'max:100'],
            'is_active' => ['nullable', 'boolean'],
        ]) + [
            'is_active' => $request->boolean('is_active'),
        ];
    }

    private function redirectWithSuccess(string $message)
    {
        return redirect()->route('admin.work-rules.index')->with('success', $message);
    }
}
