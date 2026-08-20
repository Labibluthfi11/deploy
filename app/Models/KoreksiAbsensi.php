<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class KoreksiAbsensi extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'tanggal_absen',
        'data_koreksi',
        'alasan',
        'status',
        'admin_note'
    ];

    protected $casts = [
        'data_koreksi' => 'array',
        'tanggal_absen' => 'date'
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
