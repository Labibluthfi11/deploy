<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Carbon\Carbon;

class Absensi extends Model
{
    use HasFactory;

    private const LEAVE_SUBMISSION_TYPES = [
        'izin',
        'sakit',
        'cuti_tahunan',
        'cuti_melahirkan',
        'cuti_keguguran',
        'cuti_haji',
        'cuti_umroh',
        'cuti_menikah',
        'cuti_khitanan',
        'cuti_baptis',
        'cuti_meninggal',
        'change_off',
        'unpaid_leave',
        'izin_pulang_cepat',
    ];

    private const ATTENDANCE_CORRECTION_TYPES = [
        'koreksi_lupa_keduanya',
        'koreksi_lupa_pulang',
        'koreksi_pulang_cepat',
    ];

    // Konstanta Gaji
    const DAILY_SALARY = 150000;           // Gaji normal
    const WEEKEND_DAILY_SALARY = 300000;   // 🆕 Gaji weekend (2x lipat)
    const HOURLY_SALARY = 18750;           // Gaji per jam normal
    const WEEKEND_HOURLY_SALARY = 37500;   // 🆕 Gaji per jam weekend (2x)
    const SALARY_PER_MINUTE = 312.5;
    const WEEKEND_SALARY_PER_MINUTE = 625; // 🆕 Per menit weekend

    const OVERTIME_REST_DEDUCTION_MINUTES = 30;

    protected $fillable = [
        'user_id',
        'status',
        'tipe',
        'submission_type',
        'hari_potong_cuti',
        'hari_unpaid',
        'file_bukti',
        'keterangan_izin_sakit',
        'keterangan_admin',
        'check_in_at',
        'check_out_at',
        'end_date',
        'total_days',
        'parent_id',
        'lokasi_masuk',
        'lokasi_pulang',
        'foto_masuk',
        'foto_pulang',
        'foto_pulang_2',
        'foto_pulang_3',
        'foto_pulang_4',
        'foto_pulang_5',
        'foto_pulang_6',
        'lembur_start',
        'lembur_end',
        'lembur_rest',
        'lembur_keterangan',
        'keterangan_goals',
        'workflow_status',
        'current_approval_level',
        'late_minutes',
        'rounded_late_minutes',
        'overtime_minutes',
        'is_weekend_overtime',
        'is_mocked',
        'status_approval',
        'approved_at',
        'base_salary',
        'late_penalty',
        'final_salary',
        'overtime_pay',
        'adjustment_salary',
        'is_kurang_8_jam',
        'jam_pulang_rencana',
        'work_shift_id',
        'pay_scheme_id',
        'payroll_snapshot',
    ];

    // ✅ FIX SECURITY: Field yang benar-benar sensitif tetap di guarded
    protected $guarded = [
        'rejected_by',
        'rejected_at',
        'adjustment_reason',
        'adjustment_by',
        'adjustment_at',
        'catatan_admin',
    ];

    protected $casts = [
        'check_in_at' => 'datetime',
        'check_out_at' => 'datetime',
        'end_date' => 'date',
        'approved_at' => 'datetime',
        'lembur_start' => 'datetime',
        'lembur_end' => 'datetime',
        'lembur_rest' => 'boolean',
        'is_weekend_overtime' => 'boolean',
        'is_mocked' => 'boolean',
        'adjustment_at' => 'datetime',
        'adjustment_salary' => 'decimal:2',
        'workflow_status' => 'array',
        'rejected_at' => 'datetime',
        'base_salary' => 'decimal:2',
        'late_penalty' => 'decimal:2',
        'final_salary' => 'decimal:2',
        'overtime_minutes' => 'integer',
        'overtime_pay' => 'decimal:2',
        'payroll_snapshot' => 'array',
    ];

    protected $appends = [
        'late_duration_text',
        'is_late',
        'formatted_base_salary',
        'formatted_late_penalty',
        'formatted_final_salary',
        'formatted_overtime_pay'
    ];

    // ===================================================================
    // 🆕 HELPER: Cek apakah tanggal adalah weekend
    // ===================================================================
    public static function isWeekend($date): bool
    {
        $carbonDate = Carbon::parse($date);
        // dayOfWeek: 0=Sunday, 6=Saturday
        return in_array($carbonDate->dayOfWeek, [Carbon::SATURDAY, Carbon::SUNDAY]);
    }

    // ===================================================================
    // HELPER: Pembulatan keterlambatan ke kelipatan 15 menit
    // ===================================================================
    public static function roundLateMinutes(int $actualLateMinutes): int
    {
        if ($actualLateMinutes <= 0) return 0;
        return (int) ceil($actualLateMinutes / 15) * 15;
    }

    // ===================================================================
    // 🆕 HELPER: Hitung gaji (Support Weekend)
    // ===================================================================
    public static function calculateSalary(
    int $actualLateMinutes,
    string $status,
    ?string $tipe = null,
    bool $isWeekend = false,
    ?\Carbon\Carbon $checkIn = null,
    ?\Carbon\Carbon $checkOut = null,
    string $kategori = 'organik',
    ?string $adminNote = null,
    ?array $payrollSnapshot = null
): array
{
    $baseSalary = 0;
    $latePenalty = 0;
    $finalSalary = 0;
    $roundedLateMinutes = 0;

    $isHoliday = false;
    if ($checkIn) {
        $isHoliday = \App\Models\Holiday::isHoliday($checkIn);
    }

    if (strtolower($status) === 'hadir' || strtolower($tipe ?? '') === 'lembur') {
        $payScheme = $payrollSnapshot['pay_scheme'] ?? null;
        $payrollDisabled = strtolower($kategori) === 'organik'
            || ($payrollSnapshot['payroll_enabled'] ?? true) === false;
        $hasConfiguredScheme = !$payrollDisabled && is_array($payScheme);
        $isMultiplierDay = $hasConfiguredScheme
            ? self::isWeekend($checkIn ?? now())
            : ($isWeekend || $isHoliday);
        $dailySalary = $payrollDisabled
            ? 0
            : ($hasConfiguredScheme
                ? (float) $payScheme['daily_salary']
                : ($isMultiplierDay ? self::WEEKEND_DAILY_SALARY : self::DAILY_SALARY));
        $weekendMultiplier = $hasConfiguredScheme
            ? (float) ($payScheme['weekend_daily_multiplier'] ?? 2)
            : 2;
        $dailySalary *= $isMultiplierDay ? $weekendMultiplier : 1;
        $salaryPerMinute = $dailySalary / ($hasConfiguredScheme
            ? max(1, (int) ($payScheme['standard_work_minutes'] ?? 480))
            : 480);

        // 🆕 HITUNG TOTAL JAM KERJA
        $workedMinutes = 0;
        if ($checkIn && $checkOut) {
            $effectiveCheckIn = $checkIn;

            if ($hasConfiguredScheme && !empty($payrollSnapshot['shift']['starts_at'])) {
                $effectiveCheckIn = $checkIn->copy()
                    ->setTimeFromTimeString($payrollSnapshot['shift']['starts_at']);
            }
            
            // 🔥 KHUSUS FREELANCE & BORONGAN:
            // Gunakan jam 08:00 sebagai start rasio gaji pokok agar tidak "Double Potong" dengan late_penalty
            if (!$hasConfiguredScheme && in_array(strtolower($kategori), ['freelance', 'borongan'])) {
                $standardStart = $checkIn->copy()->setTime(8, 0, 0);
                if ($checkIn->greaterThan($standardStart)) {
                    $effectiveCheckIn = $standardStart;
                }
            }
            
            $workedMinutes = self::calculateWorkedMinutes(
                $effectiveCheckIn,
                $checkOut,
                $payrollSnapshot['shift'] ?? null
            );
            
            // 🔥 LOGIC AUTO-FLAG PENGINGAT ADMIN UNTUK ORGANIK
            if ($kategori === 'organik' && $adminNote === 'AUTO_CHECKOUT_LUPA_ABSEN') {
                $workedMinutes = 0; // Set ke 0 agar gaji tidak terhitung otomatis
            }
        } else {
            // Attendance without a checkout must not be paid as a full shift before verification.
            $workedMinutes = 0;
        }

        // 🆕 PRORATA GAJI BERDASARKAN JAM KERJA
        $expectedMinutes = $hasConfiguredScheme
            ? max(1, (int) ($payScheme['standard_work_minutes'] ?? 480))
            : 480;
        $workRatio = min(1, $workedMinutes / $expectedMinutes); // Max 1 (100%)
        $baseSalary = $dailySalary * $workRatio;

        // Potong penalty telat
        $lateInterval = $hasConfiguredScheme
            ? max(1, (int) ($payScheme['late_rounding_minutes'] ?? 15))
            : 15;
        $trackLateness = strtolower($kategori) !== 'organik'
            && ($payrollSnapshot['late_tracking_enabled'] ?? true) !== false;
        $roundedLateMinutes = $trackLateness && $actualLateMinutes > 0
            ? (int) ceil($actualLateMinutes / $lateInterval) * $lateInterval
            : 0;
        $latePenalty = $roundedLateMinutes * $salaryPerMinute;
        $finalSalary = max(0, $baseSalary - $latePenalty);
    }

    return [
        'base_salary' => round($baseSalary, 2),
        'late_penalty' => round($latePenalty, 2),
        'final_salary' => round($finalSalary, 2),
        'rounded_late_minutes' => $roundedLateMinutes,
        'worked_minutes' => $workedMinutes ?? 0,
    ];
}

/**
 * Hitung total menit kerja (exclude istirahat 12:00-13:00)
 */
private static function calculateWorkedMinutes(\Carbon\Carbon $checkIn, \Carbon\Carbon $checkOut, ?array $shift = null): int
{
    $totalMinutes = $checkIn->diffInMinutes($checkOut);

    $breakStartTime = $shift['break_starts_at'] ?? '12:00:00';
    $breakEndTime = $shift['break_ends_at'] ?? '13:00:00';
    if (!$breakStartTime || !$breakEndTime) {
        return max(0, $totalMinutes);
    }

    for (
        $date = $checkIn->copy()->startOfDay();
        $date->lte($checkOut) && ($shift !== null || $date->equalTo($checkIn->copy()->startOfDay()));
        $date->addDay()
    ) {
        $restStart = $date->copy()->setTimeFromTimeString($breakStartTime);
        $restEnd = $date->copy()->setTimeFromTimeString($breakEndTime);
        if ($restEnd->lte($restStart)) {
            $restEnd->addDay();
        }

        if ($checkIn->lt($restEnd) && $checkOut->gt($restStart)) {
            $overlapStart = $checkIn->gt($restStart) ? $checkIn : $restStart;
            $overlapEnd = $checkOut->lt($restEnd) ? $checkOut : $restEnd;
            $totalMinutes -= $overlapStart->diffInMinutes($overlapEnd);
        }
    }

    return max(0, $totalMinutes);
}

    // ===================================================================
    // 🆕 HELPER: Hitung lembur (Support Weekend Multiplier)
    // ===================================================================
    public static function calculateOvertimeFromInput(
        $startTime,
        $endTime,
        bool $hasRest,
        bool $isWeekend = false,
        ?array $payrollSnapshot = null,
        bool $calculatePay = true
    ): array
    {
        try {
            $start = Carbon::parse($startTime);
            $end = Carbon::parse($endTime);

            $totalOvertimeMinutes = abs($end->diffInMinutes($start, false));
            $deduction = $hasRest ? self::OVERTIME_REST_DEDUCTION_MINUTES : 0;
            $finalOvertimeMinutes = $totalOvertimeMinutes - $deduction;

            if ($finalOvertimeMinutes < 0) {
                $finalOvertimeMinutes = 0;
            }

            $payScheme = $payrollSnapshot['pay_scheme'] ?? null;
            if (is_array($payScheme)) {
                $roundingMinutes = max(1, (int) ($payScheme['overtime_rounding_minutes'] ?? 30));
                $finalOvertimeMinutes = $finalOvertimeMinutes > 0
                    ? (int) ceil($finalOvertimeMinutes / $roundingMinutes) * $roundingMinutes
                    : 0;
                if ($calculatePay) {
                    $standardMinutes = max(1, (int) ($payScheme['standard_work_minutes'] ?? 480));
                    $hourlyRate = ((float) $payScheme['daily_salary'] / $standardMinutes) * 60;
                    $overtimeMultiplier = (float) ($payScheme['overtime_multiplier'] ?? 1);
                }
            } else {
                if ($calculatePay) {
                    $isHoliday = \App\Models\Holiday::isHoliday($startTime);
                    $isMultiplierDay = $isWeekend || $isHoliday;
                    $hourlyRate = $isMultiplierDay ? self::WEEKEND_HOURLY_SALARY : self::HOURLY_SALARY;
                    $overtimeMultiplier = 1;
                }
            }
            $overtimePay = $calculatePay
                ? ($finalOvertimeMinutes / 60) * $hourlyRate * $overtimeMultiplier
                : 0;

            return [
                'minutes' => (int) round($finalOvertimeMinutes),
                'pay'     => (float) round($overtimePay, 2),
            ];

        } catch (\Exception $e) {
            \Illuminate\Support\Facades\Log::error('--- [CRITICAL] ERROR DI KALKULASI LEMBUR ---', [
                'error' => $e->getMessage()
            ]);
            return ['minutes' => 0, 'pay' => 0.0];
        }
    }

    // ===================================================================
    // ACCESSORS (Tetap sama, cuma sekarang support weekend)
    // ===================================================================
    public function getLateDurationTextAttribute(): ?string
    {
        $lateMinutes = $this->late_minutes ?? 0;
        if ($lateMinutes <= 0 || strtolower($this->status ?? '') !== 'hadir') {
             if(strtolower($this->tipe ?? '') !== 'lembur') {
                return null;
             }
        }
        if ($lateMinutes < 60) {
            return "{$lateMinutes} menit";
        }
        $hours = floor($lateMinutes / 60);
        $minutes = $lateMinutes % 60;
        return $minutes > 0 ? "{$hours} jam {$minutes} menit" : "{$hours} jam";
    }

    public function getIsLateAttribute(): bool
    {
        $isHadirAtauLembur = strtolower($this->status ?? '') === 'hadir' || strtolower($this->tipe ?? '') === 'lembur';
        return ($this->late_minutes ?? 0) > 0 && $isHadirAtauLembur;
    }

    public function getFormattedBaseSalaryAttribute(): ?string
    {
        return $this->base_salary ? 'Rp ' . number_format($this->base_salary, 0, ',', '.') : null;
    }

    public function getFormattedLatePenaltyAttribute(): ?string
    {
        return $this->late_penalty ? 'Rp ' . number_format($this->late_penalty, 0, ',', '.') : null;
    }

    public function getFormattedFinalSalaryAttribute(): ?string
    {
        return $this->final_salary ? 'Rp ' . number_format($this->final_salary, 0, ',', '.') : null;
    }

    public function getFormattedOvertimePayAttribute(): ?string
    {
        return $this->overtime_pay ? 'Rp ' . number_format($this->overtime_pay, 0, ',', '.') : null;
    }


    // ===================================================================
    // RELATIONSHIPS
    // ===================================================================
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function workShift(): BelongsTo
    {
        return $this->belongsTo(WorkShift::class);
    }

    public function payScheme(): BelongsTo
    {
        return $this->belongsTo(PayScheme::class);
    }

    public function editor()
    {
        return $this->belongsTo(User::class, 'adjustment_by');
    }
    public function parent()
{
    return $this->belongsTo(Absensi::class, 'parent_id');
}

public function children()
{
    return $this->hasMany(Absensi::class, 'parent_id');
}

public function isApprovalRequest(): bool
{
    $type = strtolower(trim((string) $this->tipe));
    $status = strtolower(trim((string) $this->status));
    $submissionType = strtolower(trim((string) $this->submission_type));

    if (in_array($submissionType, self::ATTENDANCE_CORRECTION_TYPES, true)) {
        return true;
    }

    if ($submissionType === 'izin_pulang_cepat') {
        return in_array($type, ['izin', 'sakit'], true);
    }

    if (in_array($type, ['lembur', 'telat', 'koreksi lupa absen', 'koreksi lupa pulang'], true)) {
        return true;
    }

    return in_array($status, ['izin', 'sakit'], true);
}

public function approvalSummary(): array
{
    $workflow = $this->workflow_status;
    if (!is_array($workflow)) {
        $workflow = null;
    }

    $submissionType = trim((string) $this->submission_type);
    $requestType = $submissionType !== ''
        ? $submissionType
        : (trim((string) $this->tipe) !== '' ? $this->tipe : $this->status);

    $adminNote = in_array($this->status_approval, ['rejected', 'ditolak'], true)
        ? trim((string) $this->catatan_admin)
        : '';
    if ($adminNote === $submissionType || in_array($adminNote, self::LEAVE_SUBMISSION_TYPES, true)) {
        $adminNote = '';
    }

    return [
        'request_type' => $requestType,
        'status' => $this->status_approval === 'ditolak' ? 'rejected' : $this->status_approval,
        'workflow' => $workflow,
        'reason' => $this->keterangan_izin_sakit ?: $this->lembur_keterangan ?: $this->keterangan_goals,
        'admin_note' => $adminNote !== '' ? $adminNote : null,
    ];
}

}
