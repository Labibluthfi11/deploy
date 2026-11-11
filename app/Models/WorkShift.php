<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class WorkShift extends Model
{
    protected $fillable = [
        'name',
        'starts_at',
        'ends_at',
        'break_starts_at',
        'break_ends_at',
        'crosses_midnight',
        'is_active',
    ];

    protected $casts = [
        'crosses_midnight' => 'boolean',
        'is_active' => 'boolean',
    ];

    public function employeeAssignments(): HasMany
    {
        return $this->hasMany(EmployeeWorkAssignment::class);
    }

    public function employeeOverrides(): HasMany
    {
        return $this->hasMany(EmployeeShiftOverride::class);
    }
}
