<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ActivityLog;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class LeaveBalanceController extends Controller
{
    public function update(Request $request, User $user): RedirectResponse
    {
        $this->ensureCanManageBalance();
        $this->ensureOrganicEmployee($user);

        $validated = $request->validate([
            'operation' => ['required', 'in:add,subtract'],
            'days' => ['required', 'integer', 'between:1,365'],
            'reason' => ['required', 'string', 'max:500'],
        ]);

        DB::transaction(function () use ($user, $validated) {
            $lockedUser = User::query()->lockForUpdate()->findOrFail($user->id);
            $this->ensureOrganicEmployee($lockedUser);

            $before = max(0, (int) $lockedUser->sisa_cuti);
            $days = (int) $validated['days'];

            if ($validated['operation'] === 'subtract' && $days > $before) {
                throw ValidationException::withMessages([
                    'days' => "Pengurangan melebihi saldo cuti saat ini ({$before} hari).",
                ]);
            }

            $after = $validated['operation'] === 'add'
                ? $before + $days
                : $before - $days;

            $lockedUser->update(['sisa_cuti' => $after]);

            ActivityLog::log(
                'Penyesuaian Saldo Cuti',
                "Karyawan: {$lockedUser->name} (ID: {$lockedUser->id})",
                [
                    'aksi' => $validated['operation'] === 'add' ? 'Tambah' : 'Kurangi',
                    'jumlah_hari' => $days,
                    'saldo_sebelum' => $before,
                    'saldo_sesudah' => $after,
                    'alasan' => $validated['reason'],
                ],
            );
        });

        return redirect()
            ->route('admin.absensi.user', $user)
            ->with('success', 'Saldo cuti berhasil diperbarui.');
    }

    private function ensureOrganicEmployee(User $user): void
    {
        abort_unless(
            $user->role === 'user' && $user->employment_type === 'organik',
            404,
        );
    }

    private function ensureCanManageBalance(): void
    {
        abort_unless(auth()->user()?->isAnyAdmin(), 403);
    }
}
