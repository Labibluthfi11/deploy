<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\EmployeeShiftOverride;
use App\Models\EmployeeWorkAssignment;
use App\Models\PayScheme;
use App\Models\User;
use App\Models\WorkShift;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class WorkRuleAdminController extends Controller
{
    public function index()
    {
        $shifts = WorkShift::withCount(['employeeAssignments', 'employeeOverrides'])
            ->orderBy('name')
            ->get();
        $paySchemes = PayScheme::withCount('employeeAssignments')
            ->orderBy('name')
            ->get();
        $employees = User::query()
            ->whereNotIn('role', ['supervisor', 'manager', 'hrga', 'admin', 'super_admin', 'pkl'])
            ->orderBy('name')
            ->get(['id', 'name', 'id_karyawan']);
        $assignments = EmployeeWorkAssignment::with(['user:id,name,id_karyawan', 'workShift', 'payScheme'])
            ->orderByDesc('effective_from')
            ->paginate(25, ['*'], 'assignments_page');
        $overrides = EmployeeShiftOverride::with(['user:id,name,id_karyawan', 'workShift'])
            ->whereDate('work_date', '>=', today())
            ->orderBy('work_date')
            ->limit(50)
            ->get();

        return view('admin.work-rules.index', compact(
            'shifts',
            'paySchemes',
            'employees',
            'assignments',
            'overrides'
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
            'pay_scheme_id' => ['required', 'integer', 'exists:pay_schemes,id'],
            'effective_from' => ['required', 'date'],
        ]);

        $effectiveFrom = Carbon::parse($data['effective_from'])->startOfDay();

        DB::transaction(function () use ($data, $effectiveFrom, $request) {
            $user = User::query()->lockForUpdate()->findOrFail($data['user_id']);
            $shift = WorkShift::query()->findOrFail($data['work_shift_id']);
            $payScheme = PayScheme::query()->findOrFail($data['pay_scheme_id']);

            if (!$shift->is_active || !$payScheme->is_active) {
                throw ValidationException::withMessages([
                    'work_shift_id' => 'Shift dan skema upah yang dipilih harus aktif.',
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
                'pay_scheme_id' => $payScheme->id,
                'effective_from' => $effectiveFrom->toDateString(),
                'effective_until' => $next
                    ? Carbon::parse($next->effective_from)->subDay()->toDateString()
                    : null,
            ]);

            EmployeeShiftOverride::query()
                ->where('user_id', $user->id)
                ->whereDate('work_date', $effectiveFrom)
                ->delete();
        });

        return $this->redirectWithSuccess('Penetapan shift dan skema upah berhasil disimpan.');
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
