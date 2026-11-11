<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class WorkShiftRotationSetting extends Model
{
    protected $fillable = [
        'anchor_week_start',
        'group_a_shift_id',
        'group_b_shift_id',
        'created_by',
    ];

    protected $casts = [
        'anchor_week_start' => 'date',
    ];

    public function groupAShift(): BelongsTo
    {
        return $this->belongsTo(WorkShift::class, 'group_a_shift_id');
    }

    public function groupBShift(): BelongsTo
    {
        return $this->belongsTo(WorkShift::class, 'group_b_shift_id');
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}
