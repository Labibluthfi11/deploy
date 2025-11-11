<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

class User extends Authenticatable
{
    use HasFactory, Notifiable, HasApiTokens;

    protected $fillable = [
        'name',
        'email',
        'password',
        'profile_photo_path',
        'id_karyawan',
        'departemen',
        'employment_type',
        'is_admin', // <--- FIX 1: Tambahkan is_admin ke fillable
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'is_admin' => 'boolean', // <--- FIX 2: Casting agar dibaca True/False
        ];
    }

    // ✅ Relasi ke Absensi
    public function absensis()
    {
        return $this->hasMany(Absensi::class);
    }
}
