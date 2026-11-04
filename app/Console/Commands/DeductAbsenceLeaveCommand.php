<?php

namespace App\Console\Commands;

use App\Models\Absensi;
use App\Models\Holiday;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class DeductAbsenceLeaveCommand extends Command
{
    protected $signature = 'attendance:deduct-absence-leave';

    protected $description = 'Deduct one day of leave from active organic employees absent without leave';

    public function handle(): int
    {
        $date = Carbon::yesterday();

        if (Absensi::isWeekend($date) || Holiday::isHoliday($date)) {
            $this->info("{$date->toDateString()} is not a working day; no leave was deducted.");

            return self::SUCCESS;
        }

        $totals = [
            'checked' => 0,
            'deducted' => 0,
            'no_balance' => 0,
            'excused' => 0,
            'already_processed' => 0,
        ];

        User::query()
            ->where('role', 'user')
            ->where('employment_type', 'organik')
            ->where('is_active', true)
            ->orderBy('id')
            ->chunkById(100, function ($users) use ($date, &$totals) {
                foreach ($users as $user) {
                    $totals['checked']++;

                    $result = DB::transaction(function () use ($user, $date) {
                        $lockedUser = User::query()
                            ->whereKey($user->id)
                            ->lockForUpdate()
                            ->first();

                        if (
                            !$lockedUser
                            || $lockedUser->role !== 'user'
                            || $lockedUser->employment_type !== 'organik'
                            || !$lockedUser->is_active
                        ) {
                            return 'excused';
                        }

                        $alreadyProcessed = DB::table('absence_leave_deductions')
                            ->where('user_id', $lockedUser->id)
                            ->whereDate('absence_date', $date->toDateString())
                            ->exists();

                        if ($alreadyProcessed) {
                            return 'already_processed';
                        }

                        if ($this->hasAttendanceOrLeave($lockedUser, $date)) {
                            return 'excused';
                        }

                        $remainingLeave = max(0, (int) $lockedUser->sisa_cuti);
                        $daysDeducted = $remainingLeave > 0 ? 1 : 0;

                        if ($daysDeducted > 0) {
                            $lockedUser->update([
                                'sisa_cuti' => $remainingLeave - 1,
                                'total_cuti_diambil' => ((int) $lockedUser->total_cuti_diambil) + 1,
                            ]);
                        }

                        DB::table('absence_leave_deductions')->insert([
                            'user_id' => $lockedUser->id,
                            'absence_date' => $date->toDateString(),
                            'deducted_days' => $daysDeducted,
                            'reason' => $daysDeducted > 0 ? 'absent' : 'no_balance',
                            'created_at' => now(),
                            'updated_at' => now(),
                        ]);

                        return $daysDeducted > 0 ? 'deducted' : 'no_balance';
                    });

                    $totals[$result]++;
                }
            });

        $this->info(sprintf(
            'Checked: %d; deducted: %d; no leave balance: %d; excused: %d; already processed: %d.',
            $totals['checked'],
            $totals['deducted'],
            $totals['no_balance'],
            $totals['excused'],
            $totals['already_processed'],
        ));

        return self::SUCCESS;
    }

    private function hasAttendanceOrLeave(User $user, Carbon $date): bool
    {
        return Absensi::query()
            ->where('user_id', $user->id)
            ->where(function ($query) use ($date) {
                $query->whereDate('check_in_at', $date->toDateString())
                    ->orWhere(function ($rangeQuery) use ($date) {
                        $rangeQuery->whereDate('check_in_at', '<', $date->toDateString())
                            ->whereDate('end_date', '>=', $date->toDateString());
                    });
            })
            ->where(function ($query) {
                $query->where('status', 'hadir')
                    ->orWhere(function ($leaveQuery) {
                        $leaveQuery->whereIn('status', ['izin', 'sakit'])
                            ->whereIn('status_approval', ['pending', 'approved']);
                    });
            })
            ->exists();
    }
}
