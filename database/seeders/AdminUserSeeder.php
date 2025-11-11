<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\User;
use Illuminate\Support\Facades\Hash;

class AdminUserSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        User::updateOrCreate(
            ['email' => 'admin@example.com'], // biar gak duplikat
            [
                'name' => 'Admin',
                'password' => Hash::make('password'),
                'role' => 'admin',
                'is_active' => true,
                'id_karyawan' => 'ADM001',
                'departemen' => 'IT',
                'employment_type' => 'organik',
                'is_admin' => 1, // ✅ WAJIB BANGET
            ]
        );
    }
}
