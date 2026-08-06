<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\User;
use Illuminate\Support\Facades\Hash;

class AdminUserSeeder extends Seeder
{
    public function run(): void
    {
        // Manager (Full access)
        User::updateOrCreate(
            ['email' => 'manager@anselmudaberkarya.com'],
            [
                'name' => 'Manager',
                'password' => Hash::make('anselsukses1010'),
                'role' => 'manager',
                'is_admin' => 1,
                'id_karyawan' => 'ADM001',
                'departemen' => 'Management',
                'employment_type' => 'organik',
            ]
        );

        // Supervisor (Full access)
        User::updateOrCreate(
            ['email' => 'supervisor@anselmudaberkarya.com'],
            [
                'name' => 'Supervisor',
                'password' => Hash::make('anselsukses1010'),
                'role' => 'supervisor',
                'is_admin' => 1,
                'id_karyawan' => 'ADM002',
                'departemen' => 'Operations',
                'employment_type' => 'organik',
            ]
        );

        // HRGA (Full access)
        User::updateOrCreate(
            ['email' => 'hrga@anselmudaberkarya.com'],
            [
                'name' => 'HRGA',
                'password' => Hash::make('password'),
                'role' => 'hrga',
                'is_admin' => 1,
                'id_karyawan' => 'ADM003',
                'departemen' => 'HRGA',
                'employment_type' => 'organik',
            ]
        );
    }
}