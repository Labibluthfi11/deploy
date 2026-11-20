<?php

use App\Models\Absensi;
use App\Models\Department;
use App\Models\EmployeePlacement;
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
        EmployeePlacement::create([
            'user_id' => $assignedEmployee->id,
            'department_id' => $department->id,
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
        ->assertSee('2 karyawan · 1 ruangan')
        ->assertSee('1 hadir')
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
        ->put(route('admin.manpower.assignment', $employee), ['destination' => 'room:' . $roomA->id])
        ->assertRedirect();

    $firstAssignment = EmployeePlacement::where('user_id', $employee->id)->firstOrFail();
    expect($firstAssignment->ended_at)->toBeNull();

    $this->actingAs($admin)
        ->put(route('admin.manpower.assignment', $employee), ['destination' => 'room:' . $roomB->id])
        ->assertRedirect();

    $this->actingAs($admin)
        ->put(route('admin.manpower.assignment', $employee), ['destination' => 'department:' . $department->id])
        ->assertRedirect();

    expect(EmployeePlacement::where('user_id', $employee->id)->count())->toBe(3)
        ->and(EmployeePlacement::where('user_id', $employee->id)->whereNull('ended_at')->count())->toBe(1)
        ->and($firstAssignment->fresh()->ended_at)->not->toBeNull()
        ->and($employee->fresh()->currentPlacement->department_id)->toBe($department->id)
        ->and($employee->fresh()->currentPlacement->room_id)->toBeNull();
});

test('department-only placements are counted without requiring a room', function () {
    $department = Department::create(['name' => 'IT']);
    $admin = manpowerUser(['role' => 'super_admin']);
    $employee = manpowerUser();

    $this->actingAs($admin)
        ->put(route('admin.manpower.assignment', $employee), ['destination' => 'department:' . $department->id])
        ->assertRedirect();

    $this->actingAs($admin)
        ->get(route('admin.manpower.index'))
        ->assertOk()
        ->assertSee('Tim departemen')
        ->assertSee('1 orang')
        ->assertSee('IT · Langsung di departemen');
});

test('room leaders must be assigned employees and are cleared when moved out', function () {
    $department = Department::create(['name' => 'Produksi']);
    $room = Room::create(['department_id' => $department->id, 'name' => 'Ruang A']);
    $admin = manpowerUser(['role' => 'super_admin']);
    $leader = manpowerUser();
    $unassignedEmployee = manpowerUser();

    $this->actingAs($admin)
        ->put(route('admin.manpower.assignment', $leader), ['destination' => 'room:' . $room->id])
        ->assertRedirect();

    $this->put(route('admin.manpower.rooms.leader.update', $room), [
        'leader_user_id' => $unassignedEmployee->id,
    ])->assertSessionHasErrors('leader_user_id');

    $this->put(route('admin.manpower.rooms.leader.update', $room), [
        'leader_user_id' => $leader->id,
    ])->assertRedirect();

    expect($room->fresh()->leader_user_id)->toBe($leader->id);

    $this->put(route('admin.manpower.assignment', $leader), ['destination' => 'pool'])
        ->assertRedirect();

    expect($room->fresh()->leader_user_id)->toBeNull();
});

test('deleting a room removes its placement history and returns assigned employees to the pool', function () {
    $department = Department::create(['name' => 'Produksi']);
    $room = Room::create(['department_id' => $department->id, 'name' => 'Ruang A']);
    $admin = manpowerUser(['role' => 'super_admin']);
    $employee = manpowerUser();

    EmployeePlacement::create([
        'user_id' => $employee->id,
        'department_id' => $department->id,
        'room_id' => $room->id,
        'assigned_by' => $admin->id,
        'started_at' => now()->subDay(),
        'ended_at' => now(),
    ]);
    EmployeePlacement::create([
        'user_id' => $employee->id,
        'department_id' => $department->id,
        'room_id' => $room->id,
        'assigned_by' => $admin->id,
        'started_at' => now(),
    ]);

    $this->actingAs($admin)
        ->delete(route('admin.manpower.rooms.destroy', $room))
        ->assertRedirect();

    expect(Room::find($room->id))->toBeNull()
        ->and(EmployeePlacement::where('user_id', $employee->id)->exists())->toBeFalse()
        ->and($employee->fresh()->currentPlacement)->toBeNull()
        ->and($employee->fresh()->departemen)->toBe('Produksi');
});

test('deleting a department removes its history and preserves employees active elsewhere', function () {
    $department = Department::create(['name' => 'IT']);
    $room = Room::create(['department_id' => $department->id, 'name' => 'Ruang IT']);
    $otherDepartment = Department::create(['name' => 'HRGA']);
    $admin = manpowerUser(['role' => 'super_admin']);
    $employee = manpowerUser(['departemen' => 'IT']);
    $movedEmployee = manpowerUser(['departemen' => 'HRGA']);

    EmployeePlacement::create([
        'user_id' => $employee->id,
        'department_id' => $department->id,
        'room_id' => $room->id,
        'assigned_by' => $admin->id,
        'started_at' => now(),
    ]);
    EmployeePlacement::create([
        'user_id' => $movedEmployee->id,
        'department_id' => $department->id,
        'room_id' => $room->id,
        'assigned_by' => $admin->id,
        'started_at' => now()->subDays(2),
        'ended_at' => now()->subDay(),
    ]);
    EmployeePlacement::create([
        'user_id' => $movedEmployee->id,
        'department_id' => $otherDepartment->id,
        'room_id' => null,
        'assigned_by' => $admin->id,
        'started_at' => now()->subDay(),
    ]);

    $this->actingAs($admin)
        ->delete(route('admin.manpower.departments.destroy', $department))
        ->assertRedirect();

    expect(Department::find($department->id))->toBeNull()
        ->and(Room::find($room->id))->toBeNull()
        ->and(EmployeePlacement::where('user_id', $employee->id)->exists())->toBeFalse()
        ->and($employee->fresh()->departemen)->toBeNull()
        ->and(EmployeePlacement::where('user_id', $movedEmployee->id)->count())->toBe(1)
        ->and($movedEmployee->fresh()->departemen)->toBe('HRGA')
        ->and($movedEmployee->fresh())->not->toBeNull();
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
