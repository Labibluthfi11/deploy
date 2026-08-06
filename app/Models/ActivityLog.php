<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ActivityLog extends Model
{
    protected $fillable = [
        'user_id',
        'action',
        'subject',
        'details',
        'ip_address',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Helper log function
     */
    public static function log($action, $subject = null, $details = null)
    {
        return self::create([
            'user_id' => auth()->id(),
            'action' => $action,
            'subject' => $subject,
            'details' => is_array($details) ? json_encode($details) : $details,
            'ip_address' => request()->ip(),
        ]);
    }
}
