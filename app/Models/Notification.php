<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Notification extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'title',
        'message',
        'type',
        'target_page',
        'target_id',
        'is_read',
    ];

    // Relasi ke user (optional tapi bagus ditambah)
    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
