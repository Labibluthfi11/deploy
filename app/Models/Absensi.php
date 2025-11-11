<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Carbon\Carbon;

class Absensi extends Model
{
    use HasFactory;

    // Konstanta Gaji
    const DAILY_SALARY = 150000;      // Gaji per hari
    const HOURLY_SALARY = 18750;      // Gaji per jam (150000 / 8)
    const SALARY_PER_MINUTE = 312.5;  // Gaji per menit (18750 / 60)

    // 🆕 Konstanta Potongan Lembur
    const OVERTIME_REST_DEDUCTION_MINUTES = 30; // Potongan 30 menit

    protected $fillable = [
        'user_id',
        'status',
        'tipe',
        'file_bukti',
        'keterangan_izin_sakit',
        'check_in_at',
        'check_out_at',
        'lokasi_masuk',
        'lokasi_pulang',
        'foto_masuk',
        'foto_pulang',
        'status_approval',
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
        // 🆕 Tambahan
        'overtime_minutes',
        'overtime_pay',
    ];

    protected $casts = [
        'check_in_at' => 'datetime',
        'check_out_at' => 'datetime',
        'lembur_start' => 'datetime',
        'lembur_end' => 'datetime',
        'lembur_rest' => 'boolean',
        'workflow_status' => 'array',
        'rejected_at' => 'datetime',
        'base_salary' => 'decimal:2',
        'late_penalty' => 'decimal:2',
        'final_salary' => 'decimal:2',
        // 🆕 Tambahan
        'overtime_minutes' => 'integer',
        'overtime_pay' => 'decimal:2',
    ];

    protected $appends = [
        'late_duration_text',
        'is_late',
        'formatted_base_salary',
        'formatted_late_penalty',
        'formatted_final_salary',
        // 🆕 Tambahan
        'formatted_overtime_pay'
    ];

    // ===================================================================
    // HELPER: Pembulatan keterlambatan ke kelipatan 15 menit
    // ===================================================================
    public static function roundLateMinutes(int $actualLateMinutes): int
    {
        if ($actualLateMinutes <= 0) return 0;
        return (int) ceil($actualLateMinutes / 15) * 15;
    }

    // ===================================================================
    // HELPER: Hitung gaji berdasarkan keterlambatan
    // ===================================================================
    // ❗️ INI YANG DIBENERIN: Tambah parameter $tipe
    public static function calculateSalary(int $actualLateMinutes, string $status, ?string $tipe = null): array
    {
        $baseSalary = 0;
        $latePenalty = 0;
        $finalSalary = 0;
        $roundedLateMinutes = 0;

        // ❗️ Perbaikan: Hitung gaji pokok JIKA status 'hadir' ATAU tipe 'lembur'
        if (strtolower($status) === 'hadir' || strtolower($tipe ?? '') === 'lembur') {
            $baseSalary = self::DAILY_SALARY;
            $roundedLateMinutes = self::roundLateMinutes($actualLateMinutes);
            $latePenalty = $roundedLateMinutes * self::SALARY_PER_MINUTE;
            $finalSalary = max(0, $baseSalary - $latePenalty);
        }

        return [
            'base_salary' => round($baseSalary, 2),
            'late_penalty' => round($latePenalty, 2),
            'final_salary' => round($finalSalary, 2),
            'rounded_late_minutes' => $roundedLateMinutes,
        ];
    }

    // ===================================================================
    // 🆕 HELPER: Hitung lembur (YANG DIPANGGIL APPROVAL CONTROLLER)
    // ===================================================================
    /**
     * Hitung lembur berdasarkan input jam
     *
     * @param string|Carbon $startTime
     * @param string|Carbon $endTime
     * @param bool $hasRest
     * @return array ['minutes' => (int) menit_lembur, 'pay' => (float) gaji_lembur]
     */
    public static function calculateOvertimeFromInput($startTime, $endTime, bool $hasRest): array
    {
        try {
            $start = Carbon::parse($startTime);
            $end = Carbon::parse($endTime);

            // ❗️❗️ INI YANG DIBENERIN ❗️❗️
            // Kita pake abs() (absolut) biar hasilnya PASTI positif
            // Parameter 'false' penting biar dia ga buletin ke hari
            $totalOvertimeMinutes = abs($end->diffInMinutes($start, false));
            // ❗️❗️ ------------------- ❗️❗️

            $deduction = $hasRest ? self::OVERTIME_REST_DEDUCTION_MINUTES : 0;
            $finalOvertimeMinutes = $totalOvertimeMinutes - $deduction;

            if ($finalOvertimeMinutes < 0) {
                $finalOvertimeMinutes = 0;
            }

            // Pake konstanta HOURLY_SALARY
            $hourlyRate = self::HOURLY_SALARY;
            $overtimePay = ($finalOvertimeMinutes / 60) * $hourlyRate;

            return [
                'minutes' => (int) round($finalOvertimeMinutes),
                'pay'     => (float) round($overtimePay, 2),
            ];

        } catch (\Exception $e) {
            // Kalo ada error di kalkulasi, catet di log dan balikin 0
            \Illuminate\Support\Facades\Log::error('--- [CRITICAL] ERROR DI DALEM KALKULASI LEMBUR ---', [
                'error' => $e->getMessage()
            ]);
            return ['minutes' => 0, 'pay' => 0.0]; // Kalo error, balikin 0
        }
    }

    // ===================================================================
    // ACCESSORS
    // ===================================================================
    public function getLateDurationTextAttribute(): ?string
    {
        $lateMinutes = $this->late_minutes ?? 0;
        // ❗️ Perbaikan: Tampilkan telat walau tipe 'lembur'
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
         // ❗️ Perbaikan: Dianggap telat walau tipe 'lembur'
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

    // 🆕 ACCESSOR BARU (OPSIONAL)
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
}
