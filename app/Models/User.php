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
        'work_location',
        'role',
        'sisa_cuti',           
        'total_cuti_diambil',  
        'tahun_cuti',
        
    ];

    
    protected $guarded = [
        'is_admin',
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
            'is_admin' => 'boolean', 
        ];
    }

public function resetCutiTahunan(): void
{
    $tahunSekarang = (int) date('Y');
    if ((int) $this->tahun_cuti !== $tahunSekarang) {
        $this->update([
            'sisa_cuti' => 12,
            'total_cuti_diambil' => 0,
            'tahun_cuti' => $tahunSekarang,
        ]);
    }
}

public static function cutiYangMemotong(): array
{
    return ['cuti_tahunan', 'cuti_haji', 'cuti_umroh'];
}
    
    public function absensis()
    {
        return $this->hasMany(Absensi::class);
    }

    public function isSuperAdmin(): bool
{
    return $this->role === 'super_admin';
}

public function isAdminOnly(): bool
{
    return $this->role === 'admin';
}

public function isAnyAdmin(): bool
{
    return in_array($this->role, ['super_admin', 'admin']);
}

}