<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\User;
use Illuminate\Support\Facades\Hash;

class AdminUserSeeder extends Seeder
{
    public function run(): void
    {
        // Super Admin (akses penuh)
        User::updateOrCreate(
            ['email' => 'superadmin@example.com'],
            [
                'name' => 'Super Admin',
                'password' => Hash::make('password'),
                'role' => 'super_admin', // ✅
                'is_admin' => 1,
                'id_karyawan' => 'ADM002',
                'departemen' => 'IT',
                'employment_type' => 'organik',
            ]
        );

        // Admin biasa (tidak bisa akses organik)
        User::updateOrCreate(
            ['email' => 'admin@example.com'],
            [
                'name' => 'Admin',
                'password' => Hash::make('password'),
                'role' => 'admin', // ✅
                'is_admin' => 1,
                'id_karyawan' => 'ADM003',
                'departemen' => 'HR',
                'employment_type' => 'organik',
            ]
        );
    }
}