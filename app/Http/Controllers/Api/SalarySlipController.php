<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\SalarySlip;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Arr;

class SalarySlipController extends Controller
{
    public function index(Request $request)
    {
        $user = $request->user();
        if (!$this->isEligible($user)) {
            return response()->json([
                'success' => false,
                'message' => 'Slip gaji tidak tersedia untuk akun ini.',
            ], 403);
        }

        $slips = SalarySlip::query()
            ->where('user_id', $user->id)
            ->orderByDesc('period_end')
            ->orderByDesc('id')
            ->get()
            ->map(fn (SalarySlip $slip) => $this->present($slip));

        return response()->json([
            'success' => true,
            'data' => $slips,
        ]);
    }

    public function show(Request $request, SalarySlip $salarySlip)
    {
        $user = $request->user();
        if (!$this->isEligible($user)) {
            return response()->json([
                'success' => false,
                'message' => 'Slip gaji tidak tersedia untuk akun ini.',
            ], 403);
        }

        if ($salarySlip->user_id !== $user->id) {
            return response()->json([
                'success' => false,
                'message' => 'Slip gaji tidak ditemukan.',
            ], 404);
        }

        return response()->json([
            'success' => true,
            'data' => $this->present($salarySlip),
        ]);
    }

    private function present(SalarySlip $slip): array
    {
        return [
            'id' => $slip->id,
            'period_start' => $slip->period_start->toDateString(),
            'period_end' => $slip->period_end->toDateString(),
            'employment_type' => $slip->employment_type,
            'attendance_days' => $slip->attendance_days,
            'overtime_minutes' => $slip->overtime_minutes,
            'base_salary' => $slip->base_salary,
            'overtime_pay' => $slip->overtime_pay,
            'late_penalty' => $slip->late_penalty,
            'adjustment_salary' => $slip->adjustment_salary,
            'net_salary' => $slip->net_salary,
            'daily_breakdown' => collect($slip->daily_breakdown ?? [])
                ->map(fn (array $day) => Arr::except($day, ['payroll_snapshot']))
                ->values(),
            'issued_at' => $slip->issued_at?->toISOString(),
        ];
    }

    private function isEligible(User $user): bool
    {
        return in_array($user->kategori_absensi, ['freelance', 'borongan'], true);
    }
}
