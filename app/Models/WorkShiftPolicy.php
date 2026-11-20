<?php

namespace App\Models;

use Carbon\CarbonInterface;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class WorkShiftPolicy extends Model
{
    protected $fillable = [
        'effective_from',
        'work_shift_id',
        'created_by',
    ];

    protected $casts = [
        'effective_from' => 'date',
        'work_shift_id' => 'integer',
        'created_by' => 'integer',
    ];

    public function workShift(): BelongsTo
    {
        return $this->belongsTo(WorkShift::class);
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public static function forDate(CarbonInterface $date): ?self
    {
        return static::query()
            ->whereDate('effective_from', '<=', $date)
            ->with('workShift')
            ->orderByDesc('effective_from')
            ->first();
    }
}
