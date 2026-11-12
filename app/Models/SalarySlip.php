<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SalarySlip extends Model
{
    protected $fillable = [
        'user_id',
        'issued_by',
        'period_start',
        'period_end',
        'employment_type',
        'attendance_days',
        'overtime_minutes',
        'base_salary',
        'overtime_pay',
        'late_penalty',
        'adjustment_salary',
        'net_salary',
        'daily_breakdown',
        'issued_at',
    ];

    protected $casts = [
        'period_start' => 'date',
        'period_end' => 'date',
        'attendance_days' => 'integer',
        'overtime_minutes' => 'integer',
        'base_salary' => 'decimal:2',
        'overtime_pay' => 'decimal:2',
        'late_penalty' => 'decimal:2',
        'adjustment_salary' => 'decimal:2',
        'net_salary' => 'decimal:2',
        'daily_breakdown' => 'array',
        'issued_at' => 'datetime',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function issuer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'issued_by');
    }
}
