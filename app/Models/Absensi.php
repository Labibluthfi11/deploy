<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Carbon\Carbon;

class Absensi extends Model
{
    use HasFactory;

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
    'file_bukti',
    'keterangan_izin_sakit',
    'check_in_at',
    'check_out_at',
    'end_date',
    'total_days',
    'parent_id',
    'lokasi_masuk',
    'lokasi_pulang',
    'foto_masuk',
    'foto_pulang',
    'status_approval',
    'approved_at', // 👈 TAMBAHIN INI
    'catatan_admin',
    'lembur_start',
    'lembur_end',
    'lembur_rest',
    'lembur_keterangan',
    'workflow_status',
    'current_approval_level',
    'rejected_by',
    'rejected_at',
    'late_minutes',
    'rounded_late_minutes',
    'base_salary',
    'late_penalty',
    'final_salary',
    'overtime_minutes',
    'overtime_pay',
    'is_weekend_overtime',
];

protected $casts = [
    'check_in_at' => 'datetime',
    'check_out_at' => 'datetime',
    'end_date' => 'date',
    'approved_at' => 'datetime', // 👈 TAMBAHIN INI
    'lembur_start' => 'datetime',
    'lembur_end' => 'datetime',
    'lembur_rest' => 'boolean',
    'is_weekend_overtime' => 'boolean',
    'workflow_status' => 'array',
    'rejected_at' => 'datetime',
    'base_salary' => 'decimal:2',
    'late_penalty' => 'decimal:2',
    'final_salary' => 'decimal:2',
    'overtime_minutes' => 'integer',
    'overtime_pay' => 'decimal:2',
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
    ?\Carbon\Carbon $checkIn = null,  // 🆕 TAMBAH INI
    ?\Carbon\Carbon $checkOut = null  // 🆕 TAMBAH INI
): array
{
    $baseSalary = 0;
    $latePenalty = 0;
    $finalSalary = 0;
    $roundedLateMinutes = 0;

    if (strtolower($status) === 'hadir' || strtolower($tipe ?? '') === 'lembur') {
        $dailySalary = $isWeekend ? self::WEEKEND_DAILY_SALARY : self::DAILY_SALARY;
        $salaryPerMinute = $isWeekend ? self::WEEKEND_SALARY_PER_MINUTE : self::SALARY_PER_MINUTE;

        // 🆕 HITUNG TOTAL JAM KERJA
        $workedMinutes = 0;
        if ($checkIn && $checkOut) {
            $workedMinutes = self::calculateWorkedMinutes($checkIn, $checkOut);
        } else {
            // Kalo belum checkout, assume 8 jam kerja penuh
            $workedMinutes = 480; // 8 jam
        }

        // 🆕 PRORATA GAJI BERDASARKAN JAM KERJA
        $expectedMinutes = 480; // 8 jam kerja normal (exclude istirahat)
        $workRatio = min(1, $workedMinutes / $expectedMinutes); // Max 1 (100%)
        $baseSalary = $dailySalary * $workRatio;

        // Potong penalty telat
        $roundedLateMinutes = self::roundLateMinutes($actualLateMinutes);
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
private static function calculateWorkedMinutes(\Carbon\Carbon $checkIn, \Carbon\Carbon $checkOut): int
{
    // Total menit dari check-in sampai check-out
    $totalMinutes = $checkIn->diffInMinutes($checkOut);

    // Define waktu istirahat: 12:00 - 13:00
    $restStart = $checkIn->copy()->setTime(12, 0, 0);
    $restEnd = $checkIn->copy()->setTime(13, 0, 0);

    // Cek apakah periode kerja overlap dengan jam istirahat
    if ($checkIn->lessThan($restEnd) && $checkOut->greaterThan($restStart)) {
        // Hitung overlap duration
        $overlapStart = $checkIn->greaterThan($restStart) ? $checkIn : $restStart;
        $overlapEnd = $checkOut->lessThan($restEnd) ? $checkOut : $restEnd;

        $restMinutes = $overlapStart->diffInMinutes($overlapEnd);
        $totalMinutes -= $restMinutes;
    }

    return max(0, $totalMinutes);
}

    // ===================================================================
    // 🆕 HELPER: Hitung lembur (Support Weekend Multiplier)
    // ===================================================================
    public static function calculateOvertimeFromInput($startTime, $endTime, bool $hasRest, bool $isWeekend = false): array
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

            // 🆕 Pake rate sesuai weekend atau bukan
            $hourlyRate = $isWeekend ? self::WEEKEND_HOURLY_SALARY : self::HOURLY_SALARY;
            $overtimePay = ($finalOvertimeMinutes / 60) * $hourlyRate;

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
    public function parent()
{
    return $this->belongsTo(Absensi::class, 'parent_id');
}

public function children()
{
    return $this->hasMany(Absensi::class, 'parent_id');
}

}
