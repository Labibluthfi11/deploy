<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Absensi;
use App\Models\Department;
use App\Models\EmployeeRoomAssignment;
use App\Models\Room;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class ManpowerController extends Controller
{
    private const EXCLUDED_ROLES = ['supervisor', 'manager', 'hrga', 'admin', 'super_admin', 'pkl'];

    public function index(Request $request): View
    {
        $this->ensureAdmin($request);

        $employees = User::query()
            ->where('is_active', true)
            ->where(function ($query) {
                $query->whereNull('is_admin')->orWhere('is_admin', false);
            })
            ->whereNotIn('role', self::EXCLUDED_ROLES)
            ->with(['currentRoomAssignment.room.department'])
            ->orderBy('name')
            ->get();

        $presentUserIds = Absensi::query()
            ->whereIn('user_id', $employees->pluck('id'))
            ->whereDate('check_in_at', now('Asia/Jakarta')->toDateString())
            ->where('status', 'hadir')
            ->where('status_approval', 'approved')
            ->whereNull('parent_id')
            ->distinct()
            ->pluck('user_id')
            ->map(fn ($id) => (int) $id)
            ->all();

        $presentLookup = array_fill_keys($presentUserIds, true);
        $departments = Department::query()
            ->where('is_active', true)
            ->with(['rooms' => fn ($query) => $query->where('is_active', true)->orderBy('name')])
            ->orderBy('name')
            ->get();

        foreach ($departments as $department) {
            foreach ($department->rooms as $room) {
                $roomEmployees = $employees->filter(
                    fn (User $employee) => $employee->currentRoomAssignment?->room_id === $room->id
                )->values();

                $room->setRelation('employees', $roomEmployees);
                $room->setAttribute(
                    'present_count',
                    $roomEmployees->filter(fn (User $employee) => isset($presentLookup[$employee->id]))->count()
                );
            }
        }

        $placedEmployees = $employees->filter(fn (User $employee) => $employee->currentRoomAssignment !== null);
        $poolEmployees = $employees->filter(fn (User $employee) => $employee->currentRoomAssignment === null)->values();

        return view('admin.manpower.index', [
            'departments' => $departments,
            'allRooms' => Room::query()
                ->where('is_active', true)
                ->whereHas('department', fn ($query) => $query->where('is_active', true))
                ->with('department')
                ->orderBy('name')
                ->get(),
            'poolEmployees' => $poolEmployees,
            'presentUserIds' => $presentUserIds,
            'totalPlaced' => $placedEmployees->count(),
            'totalPresent' => $placedEmployees->filter(fn (User $employee) => isset($presentLookup[$employee->id]))->count(),
            'assignmentHistory' => EmployeeRoomAssignment::query()
                ->whereNotNull('ended_at')
                ->with(['user', 'room.department'])
                ->orderByDesc('ended_at')
                ->limit(50)
                ->get(),
        ]);
    }

    public function storeDepartment(Request $request): RedirectResponse
    {
        $this->ensureAdmin($request);

        $data = $request->validate([
            'name' => ['required', 'string', 'max:255', 'unique:departments,name'],
        ]);

        Department::create($data);

        return back()->with('status', 'Departemen berhasil ditambahkan.');
    }

    public function updateDepartment(Request $request, Department $department): RedirectResponse
    {
        $this->ensureAdmin($request);

        $data = $request->validate([
            'name' => ['required', 'string', 'max:255', Rule::unique('departments', 'name')->ignore($department->id)],
        ]);

        DB::transaction(function () use ($department, $data) {
            $oldName = $department->name;
            $department->update($data);

            if ($oldName !== $data['name']) {
                User::where('departemen', $oldName)->update(['departemen' => $data['name']]);
            }
        });

        return back()->with('status', 'Nama departemen berhasil diperbarui.');
    }

    public function storeRoom(Request $request): RedirectResponse
    {
        $this->ensureAdmin($request);

        $data = $request->validate([
            'department_id' => ['required', 'integer', Rule::exists('departments', 'id')->where('is_active', true)],
            'name' => [
                'required',
                'string',
                'max:255',
                Rule::unique('rooms', 'name')->where('department_id', $request->input('department_id')),
            ],
        ]);

        Room::create($data);

        return back()->with('status', 'Ruangan berhasil ditambahkan.');
    }

    public function updateRoom(Request $request, Room $room): RedirectResponse
    {
        $this->ensureAdmin($request);

        $data = $request->validate([
            'name' => [
                'required',
                'string',
                'max:255',
                Rule::unique('rooms', 'name')
                    ->where('department_id', $room->department_id)
                    ->ignore($room->id),
            ],
        ]);

        $room->update($data);

        return back()->with('status', 'Nama ruangan berhasil diperbarui.');
    }

    public function assign(Request $request, User $user): RedirectResponse
    {
        $this->ensureAdmin($request);

        abort_unless(
            $user->is_active && !in_array($user->role, self::EXCLUDED_ROLES, true),
            404
        );

        $data = $request->validate([
            'room_id' => ['nullable', 'integer', 'exists:rooms,id'],
        ]);

        $room = null;
        if (!empty($data['room_id'])) {
            $room = Room::with('department')
                ->where('is_active', true)
                ->whereHas('department', fn ($query) => $query->where('is_active', true))
                ->findOrFail($data['room_id']);
        }

        DB::transaction(function () use ($user, $room, $request) {
            $lockedUser = User::query()->whereKey($user->id)->lockForUpdate()->firstOrFail();
            $currentAssignment = EmployeeRoomAssignment::query()
                ->where('user_id', $lockedUser->id)
                ->whereNull('ended_at')
                ->lockForUpdate()
                ->first();

            if ($currentAssignment?->room_id === $room?->id) {
                return;
            }

            $now = now();
            $currentAssignment?->update(['ended_at' => $now]);

            if ($room) {
                $lockedUser->update(['departemen' => $room->department->name]);

                EmployeeRoomAssignment::create([
                    'user_id' => $lockedUser->id,
                    'room_id' => $room->id,
                    'assigned_by' => $request->user()->id,
                    'started_at' => $now,
                ]);
            }
        });

        return back()->with(
            'status',
            $room ? 'Penempatan karyawan berhasil diperbarui.' : 'Karyawan dipindahkan ke Pool Uji Coba.'
        );
    }

    private function ensureAdmin(Request $request): void
    {
        abort_unless($request->user()?->isAnyAdmin(), 403);
    }
}
