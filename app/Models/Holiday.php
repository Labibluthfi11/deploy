<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Holiday extends Model
{
    protected $fillable = [
        'holiday_date',
        'name',
        'is_national',
    ];

    protected $casts = [
        'holiday_date' => 'date',
        'is_national' => 'boolean',
    ];

    public static function isHoliday($date): bool
    {
        $carbonDate = \Carbon\Carbon::parse($date);
        
        // 1. Otomatis anggap Minggu sebagai libur
        if ($carbonDate->isSunday()) {
            return true;
        }

        // 2. Cek ke database untuk tanggal merah/cuti bersama
        return self::where('holiday_date', $carbonDate->toDateString())->exists();
    }
}
