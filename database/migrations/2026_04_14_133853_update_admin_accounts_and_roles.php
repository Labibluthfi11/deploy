<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // 1. Rename absensi@anselmudaberkarya.com -> supervisor@anselmudaberkarya.com
        $supervisor = \App\Models\User::where('email', 'absensi@anselmudaberkarya.com')
            ->orWhere('email', 'supervisor@anselmudaberkarya.com')
            ->first();
            
        if ($supervisor) {
            $supervisor->update([
                'email' => 'supervisor@anselmudaberkarya.com',
                'password' => \Illuminate\Support\Facades\Hash::make('anselsukses1010'),
                'role' => 'supervisor',
                'name' => 'Supervisor',
            ]);
        }

        // 2. Rename superadmin@example.com -> manager@anselmudaberkarya.com
        $manager = \App\Models\User::where('email', 'superadmin@example.com')
            ->orWhere('email', 'manager@anselmudaberkarya.com')
            ->first();
            
        if ($manager) {
            $manager->update([
                'email' => 'manager@anselmudaberkarya.com',
                'password' => \Illuminate\Support\Facades\Hash::make('anselsukses1010'),
                'role' => 'manager',
                'name' => 'Manager',
            ]);
        }

        // 3. Create HRGA account (or reset password if exists)
        $hrga = \App\Models\User::firstOrCreate(
            ['email' => 'hrga@anselmudaberkarya.com'],
            [
                'name' => 'HRGA',
                'password' => \Illuminate\Support\Facades\Hash::make('anselsukses1010'),
                'role' => 'hrga',
                'is_admin' => 1,
                'id_karyawan' => 'ADM_HRGA',
                'employment_type' => 'organik',
                'work_location' => 'office',
            ]
        );
        $hrga->update(['password' => \Illuminate\Support\Facades\Hash::make('anselsukses1010')]);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Revert supervisor -> absensi
        $supervisor = \App\Models\User::where('email', 'supervisor@anselmudaberkarya.com')->first();
        if ($supervisor) {
            $supervisor->update([
                'email' => 'absensi@anselmudaberkarya.com',
                'role' => 'admin',
                'name' => 'Admin Absensi',
            ]);
        }

        // Revert manager -> superadmin
        $manager = \App\Models\User::where('email', 'manager@anselmudaberkarya.com')->first();
        if ($manager) {
            $manager->update([
                'email' => 'superadmin@example.com',
                'role' => 'super_admin',
                'name' => 'Super Admin',
            ]);
        }

        // Delete HRGA
        \App\Models\User::where('email', 'hrga@anselmudaberkarya.com')->delete();
    }
};
