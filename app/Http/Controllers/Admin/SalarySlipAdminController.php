<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Notification;
use App\Models\SalarySlip;
use App\Models\User;
use App\Services\SalarySlipService;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class SalarySlipAdminController extends Controller
{
    public function index(SalarySlipService $salarySlipService)
    {
        $this->authorizePublisher();

        return view('admin.salary-slips.index', [
            'users' => $salarySlipService->eligibleUsers(),
        ]);
    }

    public function preview(Request $request, SalarySlipService $salarySlipService)
    {
        $this->authorizePublisher();

        $data = $this->validatedInput($request);
        $startDate = Carbon::createFromFormat('Y-m-d', $data['start_date']);
        $endDate = Carbon::createFromFormat('Y-m-d', $data['end_date']);
        $users = $salarySlipService->recipients($data['recipient_mode'], $data['user_ids'] ?? []);

        $existingSlips = SalarySlip::query()
            ->whereIn('user_id', $users->pluck('id'))
            ->whereDate('period_start', '<=', $endDate->toDateString())
            ->whereDate('period_end', '>=', $startDate->toDateString())
            ->orderBy('period_start')
            ->get()
            ->groupBy('user_id');

        $previewRows = $users->map(function (User $user) use ($salarySlipService, $startDate, $endDate, $existingSlips) {
            return [
                'user' => $user,
                'payroll' => $salarySlipService->calculate($user, $startDate, $endDate),
                'existing_slip' => $existingSlips->get($user->id)?->first(),
            ];
        });

        return view('admin.salary-slips.index', [
            'users' => $salarySlipService->eligibleUsers(),
            'previewRows' => $previewRows,
            'formData' => $data,
            'periodStart' => $startDate,
            'periodEnd' => $endDate,
            'publishableCount' => $previewRows->filter(
                fn (array $row) => $row['payroll'] !== null && $row['existing_slip'] === null
            )->count(),
        ]);
    }

    public function issue(Request $request, SalarySlipService $salarySlipService)
    {
        $this->authorizePublisher();

        $data = $this->validatedInput($request);
        $startDate = Carbon::createFromFormat('Y-m-d', $data['start_date']);
        $endDate = Carbon::createFromFormat('Y-m-d', $data['end_date']);
        $users = $salarySlipService->recipients($data['recipient_mode'], $data['user_ids'] ?? []);
        $issuedCount = 0;
        $skippedCount = 0;

        DB::transaction(function () use (
            $users,
            $salarySlipService,
            $startDate,
            $endDate,
            &$issuedCount,
            &$skippedCount
        ) {
            foreach ($users as $user) {
                User::query()->whereKey($user->id)->lockForUpdate()->firstOrFail();

                $overlappingSlip = SalarySlip::query()
                    ->where('user_id', $user->id)
                    ->whereDate('period_start', '<=', $endDate->toDateString())
                    ->whereDate('period_end', '>=', $startDate->toDateString())
                    ->lockForUpdate()
                    ->first();

                $payroll = $salarySlipService->calculate($user, $startDate, $endDate);

                if ($overlappingSlip || $payroll === null) {
                    $skippedCount++;
                    continue;
                }

                $slip = SalarySlip::create([
                    'user_id' => $user->id,
                    'issued_by' => auth()->id(),
                    'period_start' => $startDate->toDateString(),
                    'period_end' => $endDate->toDateString(),
                    'employment_type' => $user->kategori_absensi,
                    'attendance_days' => $payroll['attendance_days'],
                    'overtime_minutes' => $payroll['overtime_minutes'],
                    'base_salary' => $payroll['base_salary'],
                    'overtime_pay' => $payroll['overtime_pay'],
                    'late_penalty' => $payroll['late_penalty'],
                    'adjustment_salary' => $payroll['adjustment_salary'],
                    'net_salary' => $payroll['net_salary'],
                    'daily_breakdown' => $payroll['daily_breakdown'],
                    'issued_at' => now(),
                ]);

                Notification::create([
                    'user_id' => $user->id,
                    'title' => 'Slip Gaji Tersedia',
                    'message' => 'Slip gaji periode '
                        . $startDate->format('d M Y') . ' - ' . $endDate->format('d M Y')
                        . ' sudah tersedia di aplikasi.',
                    'type' => 'salary_slip',
                    'target_page' => 'salary_slips',
                    'target_id' => $slip->id,
                    'is_read' => false,
                ]);

                $issuedCount++;
            }
        });

        if ($issuedCount === 0) {
            return redirect()
                ->route('admin.salary-slips.index')
                ->withInput()
                ->with('error', 'Tidak ada slip baru yang diterbitkan. Periksa data absensi approved dan periode slip yang sudah ada.');
        }

        $message = "{$issuedCount} slip gaji berhasil diterbitkan.";
        if ($skippedCount > 0) {
            $message .= " {$skippedCount} karyawan dilewati karena tidak memiliki absensi approved atau periodenya bertabrakan dengan slip yang sudah terbit.";
        }

        return redirect()
            ->route('admin.salary-slips.index')
            ->with('success', $message);
    }

    private function validatedInput(Request $request): array
    {
        return $request->validate([
            'start_date' => ['required', 'date_format:Y-m-d'],
            'end_date' => ['required', 'date_format:Y-m-d', 'after_or_equal:start_date'],
            'recipient_mode' => ['required', 'in:all,selected'],
            'user_ids' => ['required_if:recipient_mode,selected', 'array', 'min:1'],
            'user_ids.*' => ['integer', 'distinct', 'exists:users,id'],
        ], [
            'user_ids.required_if' => 'Pilih minimal satu karyawan.',
            'user_ids.min' => 'Pilih minimal satu karyawan.',
            'end_date.after_or_equal' => 'Tanggal akhir harus sama dengan atau setelah tanggal awal.',
        ]);
    }

    private function authorizePublisher(): void
    {
        abort_unless(in_array(auth()->user()?->role, ['admin', 'super_admin'], true), 403);
    }
}
