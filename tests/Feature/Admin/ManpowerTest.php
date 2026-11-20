<?php

use App\Models\Absensi;
use App\Models\Department;
use App\Models\EmployeeRoomAssignment;
use App\Models\Room;
use App\Models\User;

function manpowerUser(array $attributes = []): User
{
    static $sequence = 0;
    $sequence++;

    return User::create(array_merge([
        'name' => 'Manpower User ' . $sequence,
        'email' => 'manpower' . $sequence . '@example.com',
        'password' => 'password',
        'id_karyawan' => 'MP-' . $sequence,
        'departemen' => 'Produksi',
        'employment_type' => 'organik',
        'work_location' => 'produksi',
        'role' => 'user',
        'is_active' => true,
    ], $attributes));
}

test('manpower only counts approved attendance for currently assigned employees', function () {
    $department = Department::create(['name' => 'Produksi']);
    $room = Room::create(['department_id' => $department->id, 'name' => 'Ruang A']);
    $admin = manpowerUser(['role' => 'super_admin']);
    $employee = manpowerUser();
    $pendingEmployee = manpowerUser();

    foreach ([$employee, $pendingEmployee] as $assignedEmployee) {
        EmployeeRoomAssignment::create([
            'user_id' => $assignedEmployee->id,
            'room_id' => $room->id,
            'assigned_by' => $admin->id,
            'started_at' => now()->subDay(),
        ]);
    }

    Absensi::create([
        'user_id' => $employee->id,
        'status' => 'hadir',
        'status_approval' => 'approved',
        'check_in_at' => now('Asia/Jakarta'),
    ]);

    Absensi::create([
        'user_id' => $pendingEmployee->id,
        'status' => 'hadir',
        'status_approval' => 'pending',
        'check_in_at' => now('Asia/Jakarta'),
    ]);

    $this->actingAs($admin)
        ->get(route('admin.manpower.index'))
        ->assertOk()
        ->assertSee('Ruang A')
        ->assertSee('2 ditempatkan')
        ->assertSee('1 hadir hari ini')
        ->assertSee('Karyawan ditempatkan')
        ->assertSee('Belum tercatat hadir');
});

test('moving an employee preserves old room history and keeps one active placement', function () {
    $department = Department::create(['name' => 'Produksi']);
    $roomA = Room::create(['department_id' => $department->id, 'name' => 'Ruang A']);
    $roomB = Room::create(['department_id' => $department->id, 'name' => 'Ruang B']);
    $admin = manpowerUser(['role' => 'super_admin']);
    $employee = manpowerUser();

    $this->actingAs($admin)
        ->put(route('admin.manpower.assignment', $employee), ['room_id' => $roomA->id])
        ->assertRedirect();

    $firstAssignment = EmployeeRoomAssignment::where('user_id', $employee->id)->firstOrFail();
    expect($firstAssignment->ended_at)->toBeNull();

    $this->actingAs($admin)
        ->put(route('admin.manpower.assignment', $employee), ['room_id' => $roomB->id])
        ->assertRedirect();

    expect(EmployeeRoomAssignment::where('user_id', $employee->id)->count())->toBe(2)
        ->and(EmployeeRoomAssignment::where('user_id', $employee->id)->whereNull('ended_at')->count())->toBe(1)
        ->and($firstAssignment->fresh()->ended_at)->not->toBeNull()
        ->and($employee->fresh()->currentRoomAssignment->room_id)->toBe($roomB->id);
});

test('registration departments endpoint only returns active department options', function () {
    Department::create(['name' => 'Produksi']);

    $this->getJson('/api/v1/departments')
        ->assertOk()
        ->assertJsonPath('data.0.name', 'Produksi');
});

test('employee registration rejects departments that are not configured', function () {
    $this->postJson('/api/v1/register', [
        'name' => 'New Employee',
        'email' => 'new.employee@example.com',
        'password' => 'password123',
        'password_confirmation' => 'password123',
        'id_karyawan' => 'MP-NEW-1',
        'departemen' => 'Unconfigured Department',
        'employment_type' => 'organik',
        'work_location' => 'produksi',
    ])->assertUnprocessable()
        ->assertJsonValidationErrors('departemen');
});
