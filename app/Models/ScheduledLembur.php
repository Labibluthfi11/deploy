<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ScheduledLembur extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'tanggal_lembur',
        'keterangan',
        'foto_bukti',
        'status',
        'current_approval_level',
        'is_reminder_sent'
    ];

    protected $casts = [
        'tanggal_lembur' => 'date',
        'is_reminder_sent' => 'boolean',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
