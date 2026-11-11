<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class PayScheme extends Model
{
    protected $fillable = [
        'name',
        'daily_salary',
        'standard_work_minutes',
        'late_rounding_minutes',
        'overtime_rounding_minutes',
        'weekend_daily_multiplier',
        'overtime_multiplier',
        'is_active',
    ];

    protected $casts = [
        'daily_salary' => 'decimal:2',
        'standard_work_minutes' => 'integer',
        'late_rounding_minutes' => 'integer',
        'overtime_rounding_minutes' => 'integer',
        'weekend_daily_multiplier' => 'decimal:2',
        'overtime_multiplier' => 'decimal:2',
        'is_active' => 'boolean',
    ];

    public function employeeAssignments(): HasMany
    {
        return $this->hasMany(EmployeeWorkAssignment::class);
    }
}
