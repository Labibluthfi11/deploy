<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class EmployeeWorkAssignment extends Model
{
    protected $fillable = [
        'user_id',
        'work_shift_id',
        'pay_scheme_id',
        'effective_from',
        'effective_until',
        'schedule_mode',
        'rotation_group',
    ];

    protected $casts = [
        'effective_from' => 'date',
        'effective_until' => 'date',
    ];

    public function user(): BelongsTo
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
}
