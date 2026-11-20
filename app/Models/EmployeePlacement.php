<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class EmployeePlacement extends Model
{
    protected $table = 'employee_room_assignments';

    protected $fillable = [
        'user_id',
        'department_id',
        'room_id',
        'assigned_by',
        'started_at',
        'ended_at',
    ];

    protected function casts(): array
    {
        return [
            'department_id' => 'integer',
            'room_id' => 'integer',
            'started_at' => 'datetime',
            'ended_at' => 'datetime',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function department(): BelongsTo
    {
        return $this->belongsTo(Department::class);
    }

    public function room(): BelongsTo
    {
        return $this->belongsTo(Room::class);
    }

    public function assignedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'assigned_by');
    }
}
