<x-app-layout>
    <x-slot name="header">
        <div>
            <h1 class="text-xl font-bold text-gray-900 dark:text-white">Manpower</h1>
            <p class="text-sm text-gray-500 dark:text-gray-400">Penempatan dan kehadiran karyawan per departemen dan ruangan.</p>
        </div>
    </x-slot>

    <div class="mx-auto max-w-7xl space-y-6 p-4 sm:p-6">
        @if (session('status'))
            <div class="flex items-start gap-3 rounded-2xl border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm text-emerald-800 shadow-sm dark:border-emerald-800 dark:bg-emerald-900/30 dark:text-emerald-200">
                <i class="fas fa-circle-check mt-0.5"></i>
                {{ session('status') }}
            </div>
        @endif

        @if ($errors->any())
            <div class="flex items-start gap-3 rounded-2xl border border-rose-200 bg-rose-50 px-4 py-3 text-sm text-rose-800 shadow-sm dark:border-rose-800 dark:bg-rose-900/30 dark:text-rose-200">
                <i class="fas fa-circle-exclamation mt-0.5"></i>
                <ul class="list-disc space-y-1 pl-5">
                    @foreach ($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        <section class="relative overflow-hidden rounded-3xl bg-gradient-to-br from-slate-950 via-indigo-950 to-indigo-800 p-6 text-white shadow-xl shadow-indigo-950/10 sm:p-8">
            <div class="absolute -right-10 -top-20 h-64 w-64 rounded-full bg-indigo-400/10 blur-2xl"></div>
            <div class="absolute -bottom-24 right-1/3 h-48 w-48 rounded-full bg-cyan-300/10 blur-2xl"></div>
            <div class="relative flex flex-col gap-6 sm:flex-row sm:items-end sm:justify-between">
                <div class="max-w-2xl">
                    <div class="mb-4 inline-flex items-center gap-2 rounded-full border border-white/15 bg-white/10 px-3 py-1.5 text-xs font-medium text-indigo-100">
                        <span class="h-2 w-2 animate-pulse rounded-full bg-emerald-400"></span>
                        Ringkasan manpower · {{ now('Asia/Jakarta')->format('d M Y') }}
                    </div>
                    <h2 class="max-w-xl text-3xl font-bold tracking-tight sm:text-4xl">Pantau tim. Atur penempatan.</h2>
                    <p class="mt-3 max-w-xl text-sm leading-6 text-indigo-100/80 sm:text-base">
                        Lihat sebaran karyawan per ruangan dan kehadiran yang sudah terverifikasi hari ini.
                    </p>
                </div>
                <a href="#kelola-struktur" class="inline-flex shrink-0 items-center justify-center gap-2 rounded-xl border border-white/20 bg-white/10 px-4 py-2.5 text-sm font-semibold text-white transition hover:bg-white/20">
                    <i class="fas fa-sliders"></i>
                    Kelola struktur
                    <i class="fas fa-arrow-down text-xs"></i>
                </a>
            </div>
        </section>

        <section class="grid grid-cols-1 gap-4 sm:grid-cols-3">
            <div class="group rounded-2xl border border-slate-200 bg-white p-5 shadow-sm transition hover:-translate-y-0.5 hover:shadow-md dark:border-slate-700 dark:bg-slate-800">
                <div class="flex items-center justify-between">
                    <p class="text-sm font-medium text-slate-500 dark:text-slate-400">Karyawan ditempatkan</p>
                    <span class="flex h-10 w-10 items-center justify-center rounded-xl bg-indigo-50 text-indigo-600 dark:bg-indigo-500/10 dark:text-indigo-300"><i class="fas fa-people-group"></i></span>
                </div>
                <p class="mt-4 text-3xl font-bold tracking-tight text-slate-900 dark:text-white">{{ $totalPlaced }}</p>
                <p class="mt-1 text-xs text-slate-500 dark:text-slate-400">Penempatan aktif di seluruh ruangan</p>
            </div>
            <div class="group rounded-2xl border border-emerald-200 bg-white p-5 shadow-sm transition hover:-translate-y-0.5 hover:shadow-md dark:border-emerald-900 dark:bg-slate-800">
                <div class="flex items-center justify-between">
                    <p class="text-sm font-medium text-slate-500 dark:text-slate-400">Hadir hari ini</p>
                    <span class="flex h-10 w-10 items-center justify-center rounded-xl bg-emerald-50 text-emerald-600 dark:bg-emerald-500/10 dark:text-emerald-300"><i class="fas fa-fingerprint"></i></span>
                </div>
                <p class="mt-4 text-3xl font-bold tracking-tight text-emerald-700 dark:text-emerald-300">{{ $totalPresent }}</p>
                <p class="mt-1 text-xs text-slate-500 dark:text-slate-400">Absensi hadir yang sudah disetujui</p>
            </div>
            <div class="group rounded-2xl border border-amber-200 bg-white p-5 shadow-sm transition hover:-translate-y-0.5 hover:shadow-md dark:border-amber-900 dark:bg-slate-800">
                <div class="flex items-center justify-between">
                    <p class="text-sm font-medium text-slate-500 dark:text-slate-400">Pool Uji Coba</p>
                    <span class="flex h-10 w-10 items-center justify-center rounded-xl bg-amber-50 text-amber-600 dark:bg-amber-500/10 dark:text-amber-300"><i class="fas fa-user-clock"></i></span>
                </div>
                <p class="mt-4 text-3xl font-bold tracking-tight text-amber-700 dark:text-amber-300">{{ $poolEmployees->count() }}</p>
                <p class="mt-1 text-xs text-slate-500 dark:text-slate-400">Belum masuk hitungan ruangan</p>
            </div>
        </section>

        <section class="space-y-4">
            <div class="flex flex-col gap-2 sm:flex-row sm:items-end sm:justify-between">
                <div>
                    <p class="text-xs font-bold uppercase tracking-[0.18em] text-indigo-600 dark:text-indigo-400">Struktur tim</p>
                    <h2 class="mt-1 text-xl font-bold tracking-tight text-slate-900 dark:text-white">Departemen & ruangan</h2>
                </div>
                <p class="max-w-xl text-sm text-slate-500 dark:text-slate-400">Perhitungan hanya memakai penempatan aktif dan absensi yang disetujui. Riwayat tidak ikut terhitung.</p>
            </div>

            @forelse ($departments as $department)
                @php
                    $departmentTotal = $department->directEmployees->count()
                        + $department->rooms->sum(fn ($room) => $room->employees->count());
                    $departmentPresent = $department->direct_present_count
                        + $department->rooms->sum('present_count');
                @endphp
                <div class="overflow-hidden rounded-3xl border border-slate-200 bg-white shadow-sm dark:border-slate-700 dark:bg-slate-800">
                    <div class="flex flex-col gap-4 border-b border-slate-100 bg-gradient-to-r from-slate-50 to-white px-5 py-5 dark:border-slate-700 dark:from-slate-800 dark:to-slate-800 sm:flex-row sm:items-center sm:justify-between sm:px-6">
                        <div class="flex items-center gap-4">
                            <div class="flex h-12 w-12 shrink-0 items-center justify-center rounded-2xl bg-indigo-600 text-white shadow-lg shadow-indigo-600/20"><i class="fas fa-building"></i></div>
                            <div>
                                <h3 class="text-lg font-bold text-slate-900 dark:text-white">{{ $department->name }}</h3>
                                <p class="mt-0.5 text-xs text-slate-500 dark:text-slate-400">{{ $departmentTotal }} karyawan ditempatkan · {{ $department->rooms->count() }} ruangan khusus</p>
                            </div>
                        </div>
                        <div class="flex items-center gap-2 self-start rounded-xl bg-white px-3 py-2 text-sm shadow-sm ring-1 ring-slate-200 dark:bg-slate-900 dark:ring-slate-700 sm:self-auto">
                            <span class="h-2 w-2 rounded-full bg-emerald-500"></span>
                            <span class="font-semibold text-slate-700 dark:text-slate-200">{{ $departmentPresent }}</span>
                            <span class="text-slate-500 dark:text-slate-400">hadir hari ini</span>
                        </div>
                    </div>

                    @if ($department->directEmployees->isNotEmpty())
                        <div class="mx-4 mt-4 overflow-hidden rounded-2xl border border-slate-200 dark:border-slate-700 sm:mx-5">
                            <div class="flex flex-col gap-1 border-b border-slate-100 bg-slate-50 px-4 py-3 dark:border-slate-700 dark:bg-slate-900/50 sm:flex-row sm:items-center sm:justify-between">
                                <div>
                                    <h4 class="text-sm font-bold text-slate-800 dark:text-slate-200">Tim departemen</h4>
                                    <p class="text-xs text-slate-500 dark:text-slate-400">Penempatan langsung, tanpa ruangan khusus</p>
                                </div>
                                <span class="text-xs font-semibold text-slate-600 dark:text-slate-300">{{ $department->directEmployees->count() }} orang · {{ $department->direct_present_count }} hadir</span>
                            </div>
                            <div class="divide-y divide-slate-100 dark:divide-slate-700">
                                @foreach ($department->directEmployees as $employee)
                                    @php $isPresent = in_array($employee->id, $presentUserIds, true); @endphp
                                    <div class="flex flex-col gap-3 px-4 py-3.5 sm:flex-row sm:items-center sm:justify-between">
                                        <div class="flex min-w-0 items-center gap-3">
                                            <div class="flex h-9 w-9 shrink-0 items-center justify-center rounded-full bg-gradient-to-br from-indigo-500 to-violet-600 text-xs font-bold text-white">{{ strtoupper(substr($employee->name ?? 'U', 0, 1)) }}</div>
                                            <div class="min-w-0">
                                                <p class="truncate text-sm font-semibold text-slate-900 dark:text-white">{{ $employee->name }}</p>
                                                <span class="mt-1 inline-flex items-center gap-1.5 rounded-full px-2 py-0.5 text-[10px] font-semibold {{ $isPresent ? 'bg-emerald-50 text-emerald-700 dark:bg-emerald-500/10 dark:text-emerald-300' : 'bg-slate-100 text-slate-600 dark:bg-slate-700 dark:text-slate-300' }}">
                                                    <span class="h-1.5 w-1.5 rounded-full {{ $isPresent ? 'bg-emerald-500' : 'bg-slate-400' }}"></span>
                                                    {{ $isPresent ? 'Hadir' : 'Belum terverifikasi' }}
                                                </span>
                                            </div>
                                        </div>
                                        <form method="POST" action="{{ route('admin.manpower.assignment', $employee) }}" class="flex w-full gap-2 sm:w-auto">
                                            @csrf
                                            @method('PUT')
                                            <select name="destination" aria-label="Ubah penempatan {{ $employee->name }}" class="min-w-0 flex-1 rounded-lg border-slate-200 py-2 text-xs text-slate-700 focus:border-indigo-500 focus:ring-indigo-500 dark:border-slate-600 dark:bg-slate-900 dark:text-slate-200 sm:max-w-64">
                                                <option value="pool">Pool Uji Coba</option>
                                                @foreach ($departments as $targetDepartment)
                                                    <option value="department:{{ $targetDepartment->id }}" @selected($employee->currentPlacement->department_id === $targetDepartment->id && $employee->currentPlacement->room_id === null)>
                                                        {{ $targetDepartment->name }} · Langsung di departemen
                                                    </option>
                                                    @foreach ($targetDepartment->rooms as $targetRoom)
                                                        <option value="room:{{ $targetRoom->id }}" @selected($employee->currentPlacement->room_id === $targetRoom->id)>
                                                            {{ $targetDepartment->name }} · {{ $targetRoom->name }}
                                                        </option>
                                                    @endforeach
                                                @endforeach
                                            </select>
                                            <button class="rounded-lg bg-slate-900 px-3 py-2 text-xs font-semibold text-white transition hover:bg-indigo-700 dark:bg-indigo-600 dark:hover:bg-indigo-500">Ubah</button>
                                        </form>
                                    </div>
                                @endforeach
                            </div>
                        </div>
                    @endif

                    @if ($department->rooms->isNotEmpty())
                        <div class="grid grid-cols-1 gap-4 p-4 sm:grid-cols-2 sm:p-5 lg:grid-cols-3 2xl:grid-cols-4">
                            @foreach ($department->rooms as $room)
                                @php
                                    $roomTotal = $room->employees->count();
                                    $roomAttendanceRate = $roomTotal > 0 ? (int) round(($room->present_count / $roomTotal) * 100) : 0;
                                @endphp
                                <div class="flex min-w-0 flex-col overflow-hidden rounded-2xl border border-slate-200 bg-white transition hover:border-indigo-200 hover:shadow-md dark:border-slate-700 dark:bg-slate-800 dark:hover:border-indigo-800">
                                    <div class="p-4">
                                        <div class="flex items-start justify-between gap-3">
                                            <div class="flex items-center gap-3">
                                                <div class="flex h-10 w-10 items-center justify-center rounded-xl bg-slate-100 text-slate-600 dark:bg-slate-700 dark:text-slate-300"><i class="fas fa-door-open"></i></div>
                                                <div class="min-w-0">
                                                    <h4 class="font-bold text-slate-900 dark:text-white">{{ $room->name }}</h4>
                                                    <p class="text-xs text-slate-500 dark:text-slate-400">Ruangan kerja</p>
                                                </div>
                                            </div>
                                            <div class="rounded-xl bg-slate-50 px-3 py-2 text-right dark:bg-slate-900">
                                                <p class="text-lg font-bold leading-none text-slate-900 dark:text-white">{{ $roomTotal }}</p>
                                                <p class="mt-1 text-[10px] font-semibold uppercase tracking-wide text-slate-500 dark:text-slate-400">orang</p>
                                            </div>
                                        </div>

                                        <div class="mt-4 rounded-xl border border-indigo-100 bg-indigo-50/70 p-3 dark:border-indigo-900/60 dark:bg-indigo-950/30">
                                            <div class="flex items-center gap-2 text-[10px] font-bold uppercase tracking-wide text-indigo-700 dark:text-indigo-300">
                                                <i class="fas fa-crown"></i>
                                                Leader ruangan
                                            </div>
                                            <p class="mt-1 truncate text-sm font-semibold text-slate-900 dark:text-white">{{ $room->leader?->name ?? 'Belum ditentukan' }}</p>
                                            <form method="POST" action="{{ route('admin.manpower.rooms.leader.update', $room) }}" class="mt-2 flex min-w-0 gap-2" data-preserve-scroll>
                                                @csrf
                                                @method('PUT')
                                                <select name="leader_user_id" aria-label="Pilih leader ruangan {{ $room->name }}" @disabled($room->employees->isEmpty()) class="min-w-0 flex-1 rounded-lg border-slate-200 bg-white py-2 text-xs text-slate-700 focus:border-indigo-500 focus:ring-indigo-500 disabled:cursor-not-allowed disabled:bg-slate-100 dark:border-slate-600 dark:bg-slate-800 dark:text-slate-200 dark:disabled:bg-slate-700">
                                                    @if ($room->employees->isEmpty())
                                                        <option>Belum ada karyawan</option>
                                                    @else
                                                        <option value="" @selected(!$room->leader_user_id)>Tanpa leader</option>
                                                        @foreach ($room->employees as $leaderCandidate)
                                                            <option value="{{ $leaderCandidate->id }}" @selected($room->leader_user_id === $leaderCandidate->id)>{{ $leaderCandidate->name }}</option>
                                                        @endforeach
                                                    @endif
                                                </select>
                                                <button type="submit" @disabled($room->employees->isEmpty()) class="shrink-0 rounded-lg bg-indigo-600 px-3 py-2 text-xs font-semibold text-white transition hover:bg-indigo-700 disabled:cursor-not-allowed disabled:opacity-50" aria-label="Simpan leader ruangan {{ $room->name }}">
                                                    Simpan
                                                </button>
                                            </form>
                                        </div>

                                        <div class="mt-4 flex items-center justify-between text-xs">
                                            <span class="font-medium text-slate-500 dark:text-slate-400">Kehadiran disetujui</span>
                                            <span class="font-semibold text-emerald-700 dark:text-emerald-300">{{ $room->present_count }} / {{ $roomTotal }}</span>
                                        </div>
                                        <div class="mt-2 h-1.5 overflow-hidden rounded-full bg-slate-100 dark:bg-slate-700">
                                            <div class="h-full rounded-full bg-gradient-to-r from-emerald-500 to-teal-400" style="width: {{ $roomAttendanceRate }}%"></div>
                                        </div>
                                    </div>

                                    <details class="group border-t border-slate-100 dark:border-slate-700">
                                        <summary class="flex cursor-pointer list-none items-center justify-between gap-2 px-4 py-3 text-xs font-semibold text-slate-600 hover:bg-slate-50 dark:text-slate-300 dark:hover:bg-slate-700/40 [&::-webkit-details-marker]:hidden">
                                            <span class="flex items-center gap-2">
                                                <i class="fas fa-users text-slate-400"></i>
                                                Daftar karyawan ({{ $roomTotal }})
                                            </span>
                                            <i class="fas fa-chevron-down text-[10px] text-slate-400 transition group-open:rotate-180"></i>
                                        </summary>
                                        <div class="divide-y divide-slate-100 border-t border-slate-100 dark:divide-slate-700 dark:border-slate-700">
                                        @forelse ($room->employees as $employee)
                                            @php $isPresent = in_array($employee->id, $presentUserIds, true); @endphp
                                            <div class="flex flex-col gap-3 px-4 py-3.5">
                                                <div class="flex min-w-0 items-center gap-3">
                                                    <div class="flex h-9 w-9 shrink-0 items-center justify-center rounded-full bg-gradient-to-br from-indigo-500 to-violet-600 text-xs font-bold text-white">
                                                        {{ strtoupper(substr($employee->name ?? 'U', 0, 1)) }}
                                                    </div>
                                                    <div class="min-w-0">
                                                        <p class="truncate text-sm font-semibold text-slate-900 dark:text-white">{{ $employee->name }}</p>
                                                        <span class="mt-1 inline-flex items-center gap-1.5 rounded-full px-2 py-0.5 text-[10px] font-semibold {{ $isPresent ? 'bg-emerald-50 text-emerald-700 dark:bg-emerald-500/10 dark:text-emerald-300' : 'bg-slate-100 text-slate-600 dark:bg-slate-700 dark:text-slate-300' }}">
                                                            <span class="h-1.5 w-1.5 rounded-full {{ $isPresent ? 'bg-emerald-500' : 'bg-slate-400' }}"></span>
                                                            {{ $isPresent ? 'Hadir' : 'Belum terverifikasi' }}
                                                        </span>
                                                    </div>
                                                </div>
                                                <form method="POST" action="{{ route('admin.manpower.assignment', $employee) }}" class="flex w-full gap-2" data-preserve-scroll>
                                                    @csrf
                                                    @method('PUT')
                                                    <select name="destination" aria-label="Ubah penempatan {{ $employee->name }}" class="min-w-0 flex-1 rounded-lg border-slate-200 py-2 text-xs text-slate-700 focus:border-indigo-500 focus:ring-indigo-500 dark:border-slate-600 dark:bg-slate-900 dark:text-slate-200">
                                                        <option value="pool">Pool Uji Coba</option>
                                                        @foreach ($departments as $targetDepartment)
                                                            <option value="department:{{ $targetDepartment->id }}" @selected($employee->currentPlacement->department_id === $targetDepartment->id && $employee->currentPlacement->room_id === null)>
                                                                {{ $targetDepartment->name }} · Langsung di departemen
                                                            </option>
                                                            @foreach ($targetDepartment->rooms as $targetRoom)
                                                                <option value="room:{{ $targetRoom->id }}" @selected($employee->currentPlacement->room_id === $targetRoom->id)>
                                                                    {{ $targetDepartment->name }} · {{ $targetRoom->name }}
                                                                </option>
                                                            @endforeach
                                                        @endforeach
                                                            </select>
                                                            <button class="shrink-0 rounded-lg bg-slate-900 px-3 py-2 text-xs font-semibold text-white transition hover:bg-indigo-700 dark:bg-indigo-600 dark:hover:bg-indigo-500">Ubah</button>
                                                        </form>
                                            </div>
                                        @empty
                                            <div class="px-4 py-8 text-center">
                                                <div class="mx-auto flex h-10 w-10 items-center justify-center rounded-full bg-slate-100 text-slate-400 dark:bg-slate-700 dark:text-slate-500"><i class="fas fa-user-group"></i></div>
                                                <p class="mt-2 text-xs text-slate-500 dark:text-slate-400">Belum ada karyawan di ruangan ini.</p>
                                            </div>
                                        @endforelse
                                        </div>
                                    </details>
                                </div>
                            @endforeach
                        </div>
                    @endif
                    @if ($department->directEmployees->isEmpty() && $department->rooms->isEmpty())
                        <div class="p-6 text-center text-sm text-slate-500 dark:text-slate-400">
                            Belum ada ruangan atau karyawan yang ditempatkan di departemen ini.
                        </div>
                    @endif
                </div>
            @empty
                <div class="rounded-2xl border border-dashed border-gray-300 p-8 text-center text-sm text-gray-500 dark:border-gray-700 dark:text-gray-400">
                    Belum ada departemen. Tambahkan departemen terlebih dahulu.
                </div>
            @endforelse
        </section>

        <section class="space-y-5">
            <div class="rounded-3xl border border-amber-200 bg-white p-5 shadow-sm dark:border-amber-900 dark:bg-slate-800 sm:p-6">
                <div class="mb-4 flex items-start gap-3">
                    <div class="flex h-11 w-11 shrink-0 items-center justify-center rounded-2xl bg-amber-100 text-amber-700 dark:bg-amber-500/10 dark:text-amber-300"><i class="fas fa-user-clock"></i></div>
                    <div>
                        <div class="flex flex-wrap items-center gap-2">
                            <h2 class="font-bold text-slate-900 dark:text-white">Pool Uji Coba</h2>
                            <span class="rounded-full bg-amber-100 px-2 py-0.5 text-[10px] font-bold text-amber-800 dark:bg-amber-500/10 dark:text-amber-300">{{ $poolEmployees->count() }} BELUM DITEMPATKAN</span>
                        </div>
                        <p class="mt-1 text-xs leading-5 text-slate-500 dark:text-slate-400">Departemen pilihan tetap tercatat. Karyawan di pool belum masuk hitungan manpower aktif.</p>
                    </div>
                </div>
                <div class="divide-y divide-gray-100 dark:divide-gray-700">
                    @forelse ($poolEmployees as $employee)
                        <div class="flex flex-col gap-3 py-4 sm:flex-row sm:items-center sm:justify-between">
                            <div class="flex items-center gap-3">
                            <div class="flex h-9 w-9 shrink-0 items-center justify-center rounded-full bg-amber-100 text-xs font-bold text-amber-800 dark:bg-amber-500/10 dark:text-amber-200">{{ strtoupper(substr($employee->name ?? 'U', 0, 1)) }}</div>
                            <div>
                                <p class="text-sm font-semibold text-slate-900 dark:text-white">{{ $employee->name }}</p>
                                <p class="text-xs text-slate-500 dark:text-slate-400">Departemen: {{ $employee->departemen ?: 'Belum dipilih' }}</p>
                            </div>
                            </div>
                            @if ($departments->isEmpty())
                            <p class="text-xs text-amber-700 dark:text-amber-300">Tambahkan departemen terlebih dahulu melalui Kelola Struktur.</p>
                            @else
                            <form method="POST" action="{{ route('admin.manpower.assignment', $employee) }}" class="flex w-full gap-2 sm:w-auto">
                                @csrf
                                @method('PUT')
                                <select name="destination" required aria-label="Pilih penempatan untuk {{ $employee->name }}" class="min-w-0 flex-1 rounded-lg border-slate-200 py-2 text-xs text-slate-700 focus:border-indigo-500 focus:ring-indigo-500 dark:border-slate-600 dark:bg-slate-900 dark:text-slate-200 sm:max-w-64">
                                    <option value="">Pilih departemen atau ruangan</option>
                                    @foreach ($departments as $targetDepartment)
                                        <option value="department:{{ $targetDepartment->id }}">{{ $targetDepartment->name }} · Langsung di departemen</option>
                                        @foreach ($targetDepartment->rooms as $targetRoom)
                                            <option value="room:{{ $targetRoom->id }}">{{ $targetDepartment->name }} · {{ $targetRoom->name }}</option>
                                        @endforeach
                                    @endforeach
                                </select>
                                <button class="shrink-0 rounded-lg bg-indigo-600 px-3 py-2 text-xs font-semibold text-white shadow-sm transition hover:bg-indigo-700">Tempatkan</button>
                            </form>
                            @endif
                        </div>
                    @empty
                        <div class="rounded-xl border border-dashed border-amber-200 bg-amber-50/50 py-8 text-center dark:border-amber-900 dark:bg-amber-950/20">
                            <i class="fas fa-circle-check text-xl text-emerald-500"></i>
                            <p class="mt-2 text-sm font-medium text-slate-700 dark:text-slate-200">Semua karyawan sudah ditempatkan</p>
                            <p class="mt-1 text-xs text-slate-500 dark:text-slate-400">Karyawan baru akan muncul di sini setelah mendaftar.</p>
                        </div>
                    @endforelse
                </div>
            </div>

            <details id="kelola-struktur" @if (request()->boolean('manage')) open @endif class="group rounded-3xl border border-slate-200 bg-white shadow-sm dark:border-slate-700 dark:bg-slate-800">
                <summary class="flex cursor-pointer list-none items-center justify-between gap-4 px-5 py-4 [&::-webkit-details-marker]:hidden sm:px-6">
                    <div class="flex items-center gap-3">
                        <div class="flex h-10 w-10 items-center justify-center rounded-xl bg-indigo-50 text-indigo-600 dark:bg-indigo-500/10 dark:text-indigo-300"><i class="fas fa-sliders"></i></div>
                        <div>
                            <h2 class="font-bold text-slate-900 dark:text-white">Kelola struktur</h2>
                            <p class="text-xs text-slate-500 dark:text-slate-400">Atur daftar departemen dan ruangan</p>
                        </div>
                    </div>
                    <span class="flex items-center gap-3">
                        <span class="hidden text-xs text-slate-500 dark:text-slate-400 sm:inline">Buka pengaturan</span>
                        <i class="fas fa-chevron-down text-sm text-slate-400 transition group-open:rotate-180"></i>
                    </span>
                </summary>

                <div class="grid grid-cols-1 gap-5 border-t border-slate-100 p-4 dark:border-slate-700 sm:p-6 xl:grid-cols-2">
                    <div class="overflow-hidden rounded-2xl border border-slate-200 dark:border-slate-700">
                        <div class="flex items-center justify-between border-b border-slate-100 bg-slate-50 px-4 py-3 dark:border-slate-700 dark:bg-slate-900/60">
                            <div>
                            <h3 class="text-sm font-bold text-slate-900 dark:text-white">Departemen</h3>
                            <p class="text-xs text-slate-500 dark:text-slate-400">{{ $departments->count() }} terdaftar</p>
                            </div>
                            <i class="fas fa-building text-indigo-500"></i>
                        </div>
                        <div class="space-y-4 p-4">
                            <form method="POST" action="{{ route('admin.manpower.departments.store') }}" class="flex flex-col gap-2 sm:flex-row">
                            @csrf
                            <input name="name" required maxlength="255" placeholder="Nama departemen baru" class="min-w-0 flex-1 rounded-xl border-slate-200 text-sm focus:border-indigo-500 focus:ring-indigo-500 dark:border-slate-600 dark:bg-slate-900 dark:text-white">
                            <button class="inline-flex items-center justify-center gap-2 rounded-xl bg-indigo-600 px-4 py-2 text-sm font-semibold text-white shadow-sm transition hover:bg-indigo-700">
                                <i class="fas fa-plus text-xs"></i> Tambah
                            </button>
                            </form>
                            <div class="max-h-72 space-y-2 overflow-y-auto pr-1">
                            @forelse ($departments as $department)
                                <div class="flex min-w-0 items-center gap-2 rounded-xl border border-slate-100 p-2 dark:border-slate-700">
                                    <form method="POST" action="{{ route('admin.manpower.departments.update', $department) }}" class="flex min-w-0 flex-1 items-center gap-2">
                                        @csrf
                                        @method('PUT')
                                        <input name="name" value="{{ $department->name }}" required maxlength="255" aria-label="Nama departemen" class="min-w-0 flex-1 rounded-lg border-slate-200 bg-white text-sm focus:border-indigo-500 focus:ring-indigo-500 dark:border-slate-600 dark:bg-slate-800 dark:text-white">
                                        <button class="shrink-0 rounded-lg px-3 py-2 text-xs font-semibold text-indigo-700 transition hover:bg-indigo-50 dark:text-indigo-300 dark:hover:bg-slate-700">Simpan</button>
                                    </form>
                                    <form method="POST" action="{{ route('admin.manpower.departments.destroy', $department) }}" data-confirm="Hapus departemen beserta semua ruangan dan riwayat penempatannya? Tindakan ini tidak dapat dibatalkan.">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" aria-label="Hapus departemen {{ $department->name }}" title="Hapus departemen dan seluruh riwayat" class="rounded-lg px-3 py-2 text-xs font-semibold text-rose-600 transition hover:bg-rose-50 dark:text-rose-400 dark:hover:bg-rose-950/40">
                                            <i class="fas fa-trash"></i>
                                        </button>
                                    </form>
                                </div>
                            @empty
                                <p class="rounded-xl bg-slate-50 px-3 py-4 text-center text-xs text-slate-500 dark:bg-slate-900 dark:text-slate-400">Belum ada departemen.</p>
                            @endforelse
                            </div>
                        </div>
                    </div>

                    <div class="overflow-hidden rounded-2xl border border-slate-200 dark:border-slate-700">
                        <div class="flex items-center justify-between border-b border-slate-100 bg-slate-50 px-4 py-3 dark:border-slate-700 dark:bg-slate-900/60">
                            <div>
                            <h3 class="text-sm font-bold text-slate-900 dark:text-white">Ruangan</h3>
                            <p class="text-xs text-slate-500 dark:text-slate-400">{{ $departments->sum(fn ($department) => $department->rooms->count()) }} terdaftar</p>
                            </div>
                            <i class="fas fa-door-open text-indigo-500"></i>
                        </div>
                        <div class="space-y-4 p-4">
                            <form method="POST" action="{{ route('admin.manpower.rooms.store') }}" class="grid min-w-0 grid-cols-1 gap-2 sm:grid-cols-2">
                                @csrf
                                <select name="department_id" required class="w-full min-w-0 rounded-xl border-slate-200 text-sm focus:border-indigo-500 focus:ring-indigo-500 dark:border-slate-600 dark:bg-slate-900 dark:text-white">
                                    <option value="">Pilih departemen</option>
                                    @foreach ($departments as $department)
                                        <option value="{{ $department->id }}">{{ $department->name }}</option>
                                    @endforeach
                                </select>
                                <input name="name" required maxlength="255" placeholder="Nama ruangan baru" class="w-full min-w-0 rounded-xl border-slate-200 text-sm focus:border-indigo-500 focus:ring-indigo-500 dark:border-slate-600 dark:bg-slate-900 dark:text-white">
                                <button class="inline-flex w-full items-center justify-center gap-2 rounded-xl bg-indigo-600 px-4 py-2 text-sm font-semibold text-white shadow-sm transition hover:bg-indigo-700 sm:col-span-2">
                                    <i class="fas fa-plus text-xs"></i> Tambah ruangan
                                </button>
                            </form>
                            <div class="space-y-4">
                            @forelse ($departments->filter(fn ($department) => $department->rooms->isNotEmpty()) as $department)
                                <div>
                                    <h4 class="mb-2 flex items-center gap-2 text-xs font-bold uppercase tracking-wide text-slate-500 dark:text-slate-400">
                                        <span class="h-1.5 w-1.5 rounded-full bg-indigo-500"></span>{{ $department->name }}
                                    </h4>
                                    <div class="space-y-2">
                                        @foreach ($department->rooms as $room)
                                            <div class="flex min-w-0 items-center gap-2 rounded-xl border border-slate-100 p-2 dark:border-slate-700">
                                                <form method="POST" action="{{ route('admin.manpower.rooms.update', $room) }}" class="flex min-w-0 flex-1 items-center gap-2">
                                                    @csrf
                                                    @method('PUT')
                                                    <i class="fas fa-door-open shrink-0 px-1 text-xs text-slate-400"></i>
                                                    <input name="name" value="{{ $room->name }}" required maxlength="255" aria-label="Nama ruangan" class="min-w-0 flex-1 rounded-lg border-slate-200 bg-white text-sm focus:border-indigo-500 focus:ring-indigo-500 dark:border-slate-600 dark:bg-slate-800 dark:text-white">
                                                    <button class="shrink-0 rounded-lg px-3 py-2 text-xs font-semibold text-indigo-700 transition hover:bg-indigo-50 dark:text-indigo-300 dark:hover:bg-slate-700">Simpan</button>
                                                </form>
                                                <form method="POST" action="{{ route('admin.manpower.rooms.destroy', $room) }}" data-confirm="Hapus ruangan beserta seluruh riwayat penempatannya? Karyawan aktif di ruangan ini akan kembali ke Pool Uji Coba. Tindakan ini tidak dapat dibatalkan.">
                                                    @csrf
                                                    @method('DELETE')
                                                    <button type="submit" aria-label="Hapus ruangan {{ $room->name }}" title="Hapus ruangan dan seluruh riwayat" class="rounded-lg px-3 py-2 text-xs font-semibold text-rose-600 transition hover:bg-rose-50 dark:text-rose-400 dark:hover:bg-rose-950/40">
                                                        <i class="fas fa-trash"></i>
                                                    </button>
                                                </form>
                                            </div>
                                        @endforeach
                                    </div>
                                </div>
                            @empty
                                <p class="rounded-xl bg-slate-50 px-3 py-4 text-center text-xs text-slate-500 dark:bg-slate-900 dark:text-slate-400">Belum ada ruangan. Pilih departemen dan tambahkan ruangan baru.</p>
                            @endforelse
                            </div>
                        </div>
                    </div>
                </div>
            </details>
        </section>

        <section class="overflow-hidden rounded-3xl border border-slate-200 bg-white shadow-sm dark:border-slate-700 dark:bg-slate-800">
            <div class="flex flex-col gap-2 border-b border-slate-100 px-5 py-4 dark:border-slate-700 sm:flex-row sm:items-center sm:justify-between sm:px-6">
            <div>
                <h2 class="font-bold text-slate-900 dark:text-white">Riwayat penempatan</h2>
                <p class="mt-1 text-xs text-slate-500 dark:text-slate-400">Perpindahan terakhir · Riwayat tidak masuk hitungan manpower aktif</p>
            </div>
            <span class="inline-flex w-fit items-center gap-2 rounded-full bg-slate-100 px-3 py-1 text-[11px] font-semibold text-slate-600 dark:bg-slate-700 dark:text-slate-300">
                <i class="fas fa-clock-rotate-left"></i> {{ $assignmentHistory->count() }} catatan
            </span>
            </div>
            <div class="overflow-x-auto">
            <table class="min-w-full text-left text-sm">
                <thead class="bg-slate-50 text-[10px] uppercase tracking-wider text-slate-500 dark:bg-slate-900/60 dark:text-slate-400">
                        <tr>
                        <th class="px-5 py-3">Karyawan</th>
                        <th class="px-5 py-3">Departemen / Ruangan</th>
                        <th class="px-5 py-3">Mulai</th>
                        <th class="px-5 py-3">Selesai</th>
                        </tr>
                    </thead>
                <tbody class="divide-y divide-slate-100 dark:divide-slate-700">
                        @forelse ($assignmentHistory as $history)
                        <tr class="transition hover:bg-slate-50 dark:hover:bg-slate-700/40">
                            <td class="whitespace-nowrap px-5 py-3 font-semibold text-slate-800 dark:text-slate-200">{{ $history->user->name ?? 'Karyawan dihapus' }}</td>
                            <td class="whitespace-nowrap px-5 py-3 text-slate-600 dark:text-slate-300">
                                {{ $history->department->name }}
                                @if ($history->room)
                                    <span class="px-1 text-slate-400">/</span> {{ $history->room->name }}
                                @else
                                    <span class="ml-1 rounded-full bg-slate-100 px-2 py-0.5 text-[10px] text-slate-500 dark:bg-slate-700 dark:text-slate-300">Tim departemen</span>
                                @endif
                            </td>
                            <td class="whitespace-nowrap px-5 py-3 text-slate-500 dark:text-slate-400">{{ $history->started_at->timezone('Asia/Jakarta')->format('d M Y H:i') }}</td>
                            <td class="whitespace-nowrap px-5 py-3 text-slate-500 dark:text-slate-400">{{ $history->ended_at->timezone('Asia/Jakarta')->format('d M Y H:i') }}</td>
                            </tr>
                        @empty
                            <tr>
                            <td colspan="4" class="px-5 py-10 text-center">
                                <div class="mx-auto flex h-11 w-11 items-center justify-center rounded-full bg-slate-100 text-slate-400 dark:bg-slate-700"><i class="fas fa-clock-rotate-left"></i></div>
                                <p class="mt-2 text-sm font-medium text-slate-700 dark:text-slate-200">Belum ada riwayat perpindahan</p>
                                <p class="mt-1 text-xs text-slate-500 dark:text-slate-400">Riwayat akan tercatat saat karyawan dipindahkan.</p>
                            </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </section>
    </div>

    <dialog id="manpower-delete-dialog" aria-labelledby="manpower-delete-title" aria-describedby="manpower-delete-message" class="w-[calc(100%-2rem)] max-w-md rounded-2xl border border-slate-200 bg-white p-0 text-slate-900 shadow-2xl backdrop:bg-slate-950/60 dark:border-slate-700 dark:bg-slate-800 dark:text-white">
        <form method="dialog" class="p-6">
            <div class="flex items-start gap-4">
                <span class="flex h-11 w-11 shrink-0 items-center justify-center rounded-full bg-rose-100 text-rose-600 dark:bg-rose-500/10 dark:text-rose-400">
                    <i class="fas fa-trash"></i>
                </span>
                <div>
                    <h2 id="manpower-delete-title" class="text-lg font-bold">Yakin ingin menghapus?</h2>
                    <p id="manpower-delete-message" class="mt-2 text-sm leading-6 text-slate-600 dark:text-slate-300"></p>
                </div>
            </div>
            <div class="mt-6 flex justify-end gap-3">
                <button value="cancel" autofocus class="rounded-xl border border-slate-200 px-4 py-2 text-sm font-semibold text-slate-700 transition hover:bg-slate-50 dark:border-slate-600 dark:text-slate-200 dark:hover:bg-slate-700">
                    Batal
                </button>
                <button id="manpower-delete-confirm" type="button" class="rounded-xl bg-rose-600 px-4 py-2 text-sm font-semibold text-white transition hover:bg-rose-700 disabled:cursor-wait disabled:opacity-60">
                    Ya, hapus
                </button>
            </div>
        </form>
    </dialog>

    @push('scripts')
        <script nonce="{{ config('app.csp_nonce') }}">
            var deleteDialog = document.getElementById('manpower-delete-dialog');
            var deleteMessage = document.getElementById('manpower-delete-message');
            var deleteConfirmButton = document.getElementById('manpower-delete-confirm');
            var pendingDeleteForm = null;

            function saveManpowerScrollPosition() {
                try {
                    window.sessionStorage.setItem('manpower-scroll-position', String(window.scrollY));
                } catch (error) {
                    console.warn('Posisi halaman Manpower tidak dapat disimpan.', error);
                }
            }

            document.addEventListener('submit', function (event) {
                var form = event.target.closest('form');
                if (!form) return;

                if (form.hasAttribute('data-confirm')) {
                    event.preventDefault();
                    pendingDeleteForm = form;
                    deleteMessage.textContent = form.dataset.confirm;
                    deleteDialog.showModal();
                    return;
                }

                if (form.hasAttribute('data-preserve-scroll')) {
                    saveManpowerScrollPosition();
                }
            });

            deleteDialog.addEventListener('close', function () {
                pendingDeleteForm = null;
                deleteConfirmButton.disabled = false;
            });

            deleteConfirmButton.addEventListener('click', function () {
                if (!pendingDeleteForm) return;

                saveManpowerScrollPosition();
                deleteConfirmButton.disabled = true;
                pendingDeleteForm.submit();
            });

            try {
                var savedScroll = window.sessionStorage.getItem('manpower-scroll-position');
                if (savedScroll !== null) {
                    window.sessionStorage.removeItem('manpower-scroll-position');
                    var scrollPosition = Number(savedScroll);
                    if (Number.isFinite(scrollPosition) && scrollPosition >= 0) {
                        window.requestAnimationFrame(function () {
                            window.scrollTo(0, scrollPosition);
                        });
                    }
                }
            } catch (error) {
                console.warn('Posisi halaman Manpower tidak dapat dipulihkan.', error);
            }
        </script>
    @endpush
</x-app-layout>
