<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Absensi;
use App\Models\Department;
use App\Models\EmployeePlacement;
use App\Models\Room;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
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
            ->with(['currentPlacement.department', 'currentPlacement.room.department'])
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
            $directEmployees = $employees->filter(
                fn (User $employee) => $employee->currentPlacement?->department_id === $department->id
                    && $employee->currentPlacement?->room_id === null
            )->values();

            $department->setRelation('directEmployees', $directEmployees);
            $department->setAttribute(
                'direct_present_count',
                $directEmployees->filter(fn (User $employee) => isset($presentLookup[$employee->id]))->count()
            );

            foreach ($department->rooms as $room) {
                $roomEmployees = $employees->filter(
                    fn (User $employee) => $employee->currentPlacement?->room_id === $room->id
                )->values();

                $room->setRelation('employees', $roomEmployees);
                $room->setAttribute(
                    'present_count',
                    $roomEmployees->filter(fn (User $employee) => isset($presentLookup[$employee->id]))->count()
                );
            }
        }

        $placedEmployees = $employees->filter(fn (User $employee) => $employee->currentPlacement !== null);
        $poolEmployees = $employees->filter(fn (User $employee) => $employee->currentPlacement === null)->values();

        return view('admin.manpower.index', [
            'departments' => $departments,
            'poolEmployees' => $poolEmployees,
            'presentUserIds' => $presentUserIds,
            'totalPlaced' => $placedEmployees->count(),
            'totalPresent' => $placedEmployees->filter(fn (User $employee) => isset($presentLookup[$employee->id]))->count(),
            'assignmentHistory' => EmployeePlacement::query()
                ->whereNotNull('ended_at')
                ->with(['user', 'department', 'room'])
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

    public function destroyDepartment(Request $request, Department $department): RedirectResponse
    {
        $this->ensureAdmin($request);

        DB::transaction(function () use ($department) {
            $roomIds = $department->rooms()->pluck('id');
            $activePlacementUserIds = EmployeePlacement::query()
                ->where(function ($query) use ($department, $roomIds) {
                    $query->where('department_id', $department->id)
                        ->orWhereIn('room_id', $roomIds);
                })
                ->whereNull('ended_at')
                ->pluck('user_id');

            User::query()
                ->where(function ($query) use ($activePlacementUserIds, $department) {
                    $query->whereIn('id', $activePlacementUserIds)
                        ->orWhere('departemen', $department->name);
                })
                ->update(['departemen' => null]);

            EmployeePlacement::query()
                ->where(function ($query) use ($department, $roomIds) {
                    $query->where('department_id', $department->id)
                        ->orWhereIn('room_id', $roomIds);
                })
                ->delete();

            $department->rooms()->delete();
            $department->delete();
        });

        return back()->with('status', 'Departemen, ruangan, dan riwayat penempatannya berhasil dihapus.');
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

    public function destroyRoom(Request $request, Room $room): RedirectResponse
    {
        $this->ensureAdmin($request);

        DB::transaction(function () use ($room) {
            EmployeePlacement::query()
                ->where('room_id', $room->id)
                ->delete();

            $room->delete();
        });

        return back()->with('status', 'Ruangan dan seluruh riwayat penempatannya berhasil dihapus.');
    }

    public function assign(Request $request, User $user): RedirectResponse
    {
        $this->ensureAdmin($request);

        abort_unless(
            $user->is_active && !in_array($user->role, self::EXCLUDED_ROLES, true),
            404
        );

        $data = $request->validate([
            'destination' => ['required', 'string', 'max:64'],
        ]);

        $department = null;
        $room = null;
        if ($data['destination'] === 'pool') {
            // Pool is represented by closing the active placement without creating another one.
        } elseif (preg_match('/\Adepartment:(\d+)\z/', $data['destination'], $matches)) {
            $department = Department::query()
                ->where('is_active', true)
                ->findOrFail((int) $matches[1]);
        } elseif (preg_match('/\Aroom:(\d+)\z/', $data['destination'], $matches)) {
            $room = Room::with('department')
                ->where('is_active', true)
                ->whereHas('department', fn ($query) => $query->where('is_active', true))
                ->findOrFail((int) $matches[1]);
            $department = $room->department;
        } else {
            throw ValidationException::withMessages([
                'destination' => 'Pilih Pool Uji Coba, departemen, atau ruangan yang tersedia.',
            ]);
        }

        DB::transaction(function () use ($user, $department, $room, $request) {
            $lockedUser = User::query()->whereKey($user->id)->lockForUpdate()->firstOrFail();
            $currentPlacement = EmployeePlacement::query()
                ->where('user_id', $lockedUser->id)
                ->whereNull('ended_at')
                ->lockForUpdate()
                ->first();

            if ($currentPlacement?->department_id === $department?->id &&
                $currentPlacement?->room_id === $room?->id) {
                return;
            }

            $now = now();
            $currentPlacement?->update(['ended_at' => $now]);

            if ($department) {
                $lockedUser->update(['departemen' => $department->name]);

                EmployeePlacement::create([
                    'user_id' => $lockedUser->id,
                    'department_id' => $department->id,
                    'room_id' => $room?->id,
                    'assigned_by' => $request->user()->id,
                    'started_at' => $now,
                ]);
            }
        });

        return back()->with(
            'status',
            $department
                ? 'Penempatan karyawan berhasil diperbarui.'
                : 'Karyawan dipindahkan ke Pool Uji Coba.'
        );
    }

    private function ensureAdmin(Request $request): void
    {
        abort_unless($request->user()?->isAnyAdmin(), 403);
    }
}
