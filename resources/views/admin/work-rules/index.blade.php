<x-app-layout>
    <x-slot name="header">
        <div>
            <h2 class="text-2xl font-bold text-gray-800 dark:text-white">Shift & Skema Upah</h2>
            <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">
                Atur jam kerja, aturan upah, dan penetapannya ke karyawan.
            </p>
        </div>
    </x-slot>

    <div class="space-y-8 px-4 py-8 sm:px-6 lg:px-8">
        <div class="mx-auto max-w-7xl space-y-6">
            @if(session('success'))
                <div class="rounded-lg border-l-4 border-emerald-500 bg-emerald-50 p-4 text-emerald-800 dark:bg-emerald-900/30 dark:text-emerald-200">
                    {{ session('success') }}
                </div>
            @endif

            @if($errors->any())
                <div class="rounded-lg border-l-4 border-red-500 bg-red-50 p-4 text-red-800 dark:bg-red-900/30 dark:text-red-200">
                    <p class="font-semibold">Periksa kembali data yang dimasukkan:</p>
                    <ul class="mt-2 list-inside list-disc text-sm">
                        @foreach($errors->all() as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                </div>
            @endif

            <section class="grid gap-6 xl:grid-cols-2">
                <div class="rounded-2xl border border-gray-200 bg-white p-6 shadow-sm dark:border-gray-700 dark:bg-gray-800">
                    <h3 class="text-lg font-bold text-gray-900 dark:text-white">Template Shift</h3>
                    <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">Jam selesai sebelum atau sama dengan jam mulai dianggap melewati tengah malam.</p>

                    <form action="{{ route('admin.work-rules.shifts.store') }}" method="POST" class="mt-5 grid gap-4 sm:grid-cols-2">
                        @csrf
                        <div class="sm:col-span-2">
                            <label class="mb-1 block text-sm font-medium">Nama shift</label>
                            <input name="name" value="{{ old('name') }}" required maxlength="100" placeholder="Contoh: Pagi"
                                   class="w-full rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white">
                        </div>
                        <div>
                            <label class="mb-1 block text-sm font-medium">Mulai</label>
                            <input type="time" name="starts_at" value="{{ old('starts_at', '08:00') }}" required
                                   class="w-full rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white">
                        </div>
                        <div>
                            <label class="mb-1 block text-sm font-medium">Selesai</label>
                            <input type="time" name="ends_at" value="{{ old('ends_at', '17:00') }}" required
                                   class="w-full rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white">
                        </div>
                        <div>
                            <label class="mb-1 block text-sm font-medium">Mulai istirahat</label>
                            <input type="time" name="break_starts_at" value="{{ old('break_starts_at', '12:00') }}"
                                   class="w-full rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white">
                        </div>
                        <div>
                            <label class="mb-1 block text-sm font-medium">Selesai istirahat</label>
                            <input type="time" name="break_ends_at" value="{{ old('break_ends_at', '13:00') }}"
                                   class="w-full rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white">
                        </div>
                        <div class="sm:col-span-2">
                            <button class="rounded-lg bg-indigo-600 px-4 py-2 font-semibold text-white hover:bg-indigo-700">Tambah Shift</button>
                        </div>
                    </form>

                    <div class="mt-6 space-y-4">
                        @forelse($shifts as $shift)
                            <form action="{{ route('admin.work-rules.shifts.update', $shift) }}" method="POST" class="rounded-xl border border-gray-200 p-4 dark:border-gray-700">
                                @csrf
                                @method('PUT')
                                <div class="grid gap-3 sm:grid-cols-2">
                                    <div class="sm:col-span-2">
                                        <label class="mb-1 block text-xs font-medium text-gray-500">Nama</label>
                                        <input name="name" value="{{ $shift->name }}" required maxlength="100"
                                               class="w-full rounded-lg border-gray-300 text-sm dark:border-gray-600 dark:bg-gray-700 dark:text-white">
                                    </div>
                                    <div>
                                        <label class="mb-1 block text-xs font-medium text-gray-500">Mulai</label>
                                        <input type="time" name="starts_at" value="{{ substr($shift->starts_at, 0, 5) }}" required
                                               class="w-full rounded-lg border-gray-300 text-sm dark:border-gray-600 dark:bg-gray-700 dark:text-white">
                                    </div>
                                    <div>
                                        <label class="mb-1 block text-xs font-medium text-gray-500">Selesai</label>
                                        <input type="time" name="ends_at" value="{{ substr($shift->ends_at, 0, 5) }}" required
                                               class="w-full rounded-lg border-gray-300 text-sm dark:border-gray-600 dark:bg-gray-700 dark:text-white">
                                    </div>
                                    <div>
                                        <label class="mb-1 block text-xs font-medium text-gray-500">Mulai istirahat</label>
                                        <input type="time" name="break_starts_at" value="{{ $shift->break_starts_at ? substr($shift->break_starts_at, 0, 5) : '' }}"
                                               class="w-full rounded-lg border-gray-300 text-sm dark:border-gray-600 dark:bg-gray-700 dark:text-white">
                                    </div>
                                    <div>
                                        <label class="mb-1 block text-xs font-medium text-gray-500">Selesai istirahat</label>
                                        <input type="time" name="break_ends_at" value="{{ $shift->break_ends_at ? substr($shift->break_ends_at, 0, 5) : '' }}"
                                               class="w-full rounded-lg border-gray-300 text-sm dark:border-gray-600 dark:bg-gray-700 dark:text-white">
                                    </div>
                                    <label class="flex items-center gap-2 text-sm sm:col-span-2">
                                        <input type="checkbox" name="is_active" value="1" @checked($shift->is_active) class="rounded border-gray-300 text-indigo-600">
                                        Aktif
                                        <span class="ml-auto text-xs text-gray-500">{{ $shift->employee_assignments_count }} penetapan · {{ $shift->employee_overrides_count }} jadwal khusus</span>
                                    </label>
                                    <div class="sm:col-span-2">
                                        <button class="rounded-lg border border-indigo-300 px-3 py-2 text-sm font-semibold text-indigo-700 hover:bg-indigo-50 dark:border-indigo-700 dark:text-indigo-300 dark:hover:bg-indigo-900/30">Simpan Shift</button>
                                    </div>
                                </div>
                            </form>
                        @empty
                            <p class="rounded-lg bg-gray-50 p-4 text-sm text-gray-500 dark:bg-gray-900">Belum ada template shift.</p>
                        @endforelse
                    </div>
                </div>

                <div class="rounded-2xl border border-gray-200 bg-white p-6 shadow-sm dark:border-gray-700 dark:bg-gray-800">
                    <h3 class="text-lg font-bold text-gray-900 dark:text-white">Skema Upah</h3>
                    <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">Tarif lembur memakai upah harian ÷ jam kerja standar × pengali lembur.</p>

                    <form action="{{ route('admin.work-rules.pay-schemes.store') }}" method="POST" class="mt-5 grid gap-4 sm:grid-cols-2">
                        @csrf
                        <div class="sm:col-span-2">
                            <label class="mb-1 block text-sm font-medium">Nama skema</label>
                            <input name="name" value="{{ old('name') }}" required maxlength="100" placeholder="Contoh: Harian 150"
                                   class="w-full rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white">
                        </div>
                        <div>
                            <label class="mb-1 block text-sm font-medium">Upah harian (Rp)</label>
                            <input type="number" name="daily_salary" value="{{ old('daily_salary') }}" min="0" step="0.01" required
                                   class="w-full rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white">
                        </div>
                        <div>
                            <label class="mb-1 block text-sm font-medium">Jam kerja standar (menit)</label>
                            <input type="number" name="standard_work_minutes" value="{{ old('standard_work_minutes', 480) }}" min="1" max="1440" required
                                   class="w-full rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white">
                        </div>
                        <div>
                            <label class="mb-1 block text-sm font-medium">Pembulatan telat (menit)</label>
                            <input type="number" name="late_rounding_minutes" value="{{ old('late_rounding_minutes', 15) }}" min="1" max="1440" required
                                   class="w-full rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white">
                        </div>
                        <div>
                            <label class="mb-1 block text-sm font-medium">Pembulatan lembur (menit)</label>
                            <input type="number" name="overtime_rounding_minutes" value="{{ old('overtime_rounding_minutes', 30) }}" min="1" max="1440" required
                                   class="w-full rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white">
                        </div>
                        <div>
                            <label class="mb-1 block text-sm font-medium">Pengali upah Sabtu/Minggu</label>
                            <input type="number" name="weekend_daily_multiplier" value="{{ old('weekend_daily_multiplier', 2) }}" min="0" max="100" step="0.01" required
                                   class="w-full rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white">
                        </div>
                        <div>
                            <label class="mb-1 block text-sm font-medium">Pengali lembur</label>
                            <input type="number" name="overtime_multiplier" value="{{ old('overtime_multiplier', 1) }}" min="0" max="100" step="0.01" required
                                   class="w-full rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white">
                        </div>
                        <div class="sm:col-span-2">
                            <button class="rounded-lg bg-indigo-600 px-4 py-2 font-semibold text-white hover:bg-indigo-700">Tambah Skema</button>
                        </div>
                    </form>

                    <div class="mt-6 space-y-4">
                        @forelse($paySchemes as $scheme)
                            <form action="{{ route('admin.work-rules.pay-schemes.update', $scheme) }}" method="POST" class="rounded-xl border border-gray-200 p-4 dark:border-gray-700">
                                @csrf
                                @method('PUT')
                                <div class="grid gap-3 sm:grid-cols-2">
                                    <div class="sm:col-span-2">
                                        <label class="mb-1 block text-xs font-medium text-gray-500">Nama skema</label>
                                        <input name="name" value="{{ $scheme->name }}" required maxlength="100"
                                               class="w-full rounded-lg border-gray-300 text-sm dark:border-gray-600 dark:bg-gray-700 dark:text-white">
                                    </div>
                                    <div>
                                        <label class="mb-1 block text-xs font-medium text-gray-500">Upah harian (Rp)</label>
                                        <input type="number" name="daily_salary" value="{{ $scheme->daily_salary }}" min="0" step="0.01" required
                                               class="w-full rounded-lg border-gray-300 text-sm dark:border-gray-600 dark:bg-gray-700 dark:text-white">
                                    </div>
                                    <div>
                                        <label class="mb-1 block text-xs font-medium text-gray-500">Jam kerja standar (menit)</label>
                                        <input type="number" name="standard_work_minutes" value="{{ $scheme->standard_work_minutes }}" min="1" max="1440" required
                                               class="w-full rounded-lg border-gray-300 text-sm dark:border-gray-600 dark:bg-gray-700 dark:text-white">
                                    </div>
                                    <div>
                                        <label class="mb-1 block text-xs font-medium text-gray-500">Pembulatan telat (menit)</label>
                                        <input type="number" name="late_rounding_minutes" value="{{ $scheme->late_rounding_minutes }}" min="1" max="1440" required
                                               class="w-full rounded-lg border-gray-300 text-sm dark:border-gray-600 dark:bg-gray-700 dark:text-white">
                                    </div>
                                    <div>
                                        <label class="mb-1 block text-xs font-medium text-gray-500">Pembulatan lembur (menit)</label>
                                        <input type="number" name="overtime_rounding_minutes" value="{{ $scheme->overtime_rounding_minutes }}" min="1" max="1440" required
                                               class="w-full rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white">
                                    </div>
                                    <div>
                                        <label class="mb-1 block text-xs font-medium text-gray-500">Pengali upah weekend</label>
                                        <input type="number" name="weekend_daily_multiplier" value="{{ $scheme->weekend_daily_multiplier }}" min="0" max="100" step="0.01" required
                                               class="w-full rounded-lg border-gray-300 text-sm dark:border-gray-600 dark:bg-gray-700 dark:text-white">
                                    </div>
                                    <div>
                                        <label class="mb-1 block text-xs font-medium text-gray-500">Pengali lembur</label>
                                        <input type="number" name="overtime_multiplier" value="{{ $scheme->overtime_multiplier }}" min="0" max="100" step="0.01" required
                                               class="w-full rounded-lg border-gray-300 text-sm dark:border-gray-600 dark:bg-gray-700 dark:text-white">
                                    </div>
                                    <label class="flex items-center gap-2 text-sm sm:col-span-2">
                                        <input type="checkbox" name="is_active" value="1" @checked($scheme->is_active) class="rounded border-gray-300 text-indigo-600">
                                        Aktif
                                        <span class="ml-auto text-xs text-gray-500">{{ $scheme->employee_assignments_count }} penetapan</span>
                                    </label>
                                    <div class="sm:col-span-2">
                                        <button class="rounded-lg border border-indigo-300 px-3 py-2 text-sm font-semibold text-indigo-700 hover:bg-indigo-50 dark:border-indigo-700 dark:text-indigo-300 dark:hover:bg-indigo-900/30">Simpan Skema</button>
                                    </div>
                                </div>
                            </form>
                        @empty
                            <p class="rounded-lg bg-gray-50 p-4 text-sm text-gray-500 dark:bg-gray-900">Belum ada skema upah.</p>
                        @endforelse
                    </div>
                </div>
            </section>

            <section class="grid gap-6 xl:grid-cols-2">
                <div class="rounded-2xl border border-gray-200 bg-white p-6 shadow-sm dark:border-gray-700 dark:bg-gray-800">
                    <h3 class="text-lg font-bold text-gray-900 dark:text-white">1. Daftarkan Shift & Upah Karyawan</h3>
                    <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">Pakai saat pertama kali menetapkan karyawan atau mengubah skema upah. Kalau karyawan sudah terdaftar dan hanya mau ubah shift/polanya, pakai bagian “Rotasi Shift Mingguan” di bawah—jangan daftarkan ulang.</p>
                    <form action="{{ route('admin.work-rules.assignments.store') }}" method="POST" class="mt-5 grid gap-4">
                        @csrf
                        <div>
                            <label class="mb-1 block text-sm font-medium">Karyawan</label>
                            <select id="work-assignment-user" name="user_id" required class="w-full rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white">
                                <option value="">Pilih karyawan</option>
                                @foreach($employees as $employee)
                                    <option value="{{ $employee->id }}" data-category="{{ $employee->kategori_absensi }}" @selected(old('user_id') == $employee->id)>{{ $employee->name }}{{ $employee->id_karyawan ? ' · ' . $employee->id_karyawan : '' }} · {{ ucfirst($employee->kategori_absensi) }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div>
                            <label class="mb-1 block text-sm font-medium">Shift</label>
                            <select name="work_shift_id" required class="w-full rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white">
                                <option value="">Pilih shift</option>
                                @foreach($shifts->where('is_active', true) as $shift)
                                    <option value="{{ $shift->id }}" @selected(old('work_shift_id') == $shift->id)>{{ $shift->name }} ({{ substr($shift->starts_at, 0, 5) }}–{{ substr($shift->ends_at, 0, 5) }})</option>
                                @endforeach
                            </select>
                            <p class="mt-1 text-xs text-gray-500">Untuk rolling, pilih shift yang berlaku pada minggu mulai; grup A/B ditentukan otomatis.</p>
                        </div>
                        <div>
                            <label class="mb-1 block text-sm font-medium">Skema upah</label>
                            <select id="work-assignment-pay-scheme" name="pay_scheme_id" class="w-full rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white">
                                <option value="" data-empty-pay-scheme>Pilih karyawan dulu</option>
                                @foreach($paySchemes->where('is_active', true) as $scheme)
                                    <option value="{{ $scheme->id }}" @selected(old('pay_scheme_id') == $scheme->id)>{{ $scheme->name }} · Rp{{ number_format((float) $scheme->daily_salary, 0, ',', '.') }}/hari</option>
                                @endforeach
                            </select>
                            <p class="mt-1 text-xs text-gray-500">Organik tidak memakai skema upah. Freelance, borongan, dan magang wajib memakai skema upah aktif. Jika kategorinya tidak sesuai, periksa data/ID karyawan.</p>
                        </div>
                        <div>
                            <label class="mb-1 block text-sm font-medium">Berlaku mulai tanggal</label>
                            <input type="date" name="effective_from" value="{{ old('effective_from', today()->toDateString()) }}" required class="w-full rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white">
                        </div>
                        <div>
                            <label class="mb-1 block text-sm font-medium">Pola jadwal</label>
                            <select name="schedule_mode" required class="w-full rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white">
                                <option value="fixed" @selected(old('schedule_mode', 'fixed') === 'fixed')>Tetap sesuai shift default</option>
                                <option value="rolling" @selected(old('schedule_mode') === 'rolling')>Rolling mingguan</option>
                            </select>
                        </div>
                        <button class="justify-self-start rounded-lg bg-indigo-600 px-4 py-2 font-semibold text-white hover:bg-indigo-700">Simpan Penetapan</button>
                    </form>
                </div>

                <div class="rounded-2xl border border-gray-200 bg-white p-6 shadow-sm dark:border-gray-700 dark:bg-gray-800">
                    <h3 class="text-lg font-bold text-gray-900 dark:text-white">Jadwal Shift Khusus</h3>
                    <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">Gunakan untuk tukar atau perubahan shift pada satu tanggal. Skema upah tetap mengikuti penetapan karyawan.</p>
                    <form action="{{ route('admin.work-rules.shift-overrides.store') }}" method="POST" class="mt-5 grid gap-4">
                        @csrf
                        <div>
                            <label class="mb-1 block text-sm font-medium">Karyawan</label>
                            <select name="user_id" required class="w-full rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white">
                                <option value="">Pilih karyawan</option>
                                @foreach($employees as $employee)
                                    <option value="{{ $employee->id }}" @selected(old('user_id') == $employee->id)>{{ $employee->name }}{{ $employee->id_karyawan ? ' · ' . $employee->id_karyawan : '' }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div>
                            <label class="mb-1 block text-sm font-medium">Shift pada tanggal tersebut</label>
                            <select name="work_shift_id" required class="w-full rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white">
                                <option value="">Pilih shift</option>
                                @foreach($shifts->where('is_active', true) as $shift)
                                    <option value="{{ $shift->id }}" @selected(old('work_shift_id') == $shift->id)>{{ $shift->name }} ({{ substr($shift->starts_at, 0, 5) }}–{{ substr($shift->ends_at, 0, 5) }})</option>
                                @endforeach
                            </select>
                        </div>
                        <div>
                            <label class="mb-1 block text-sm font-medium">Tanggal shift dimulai</label>
                            <input type="date" name="work_date" value="{{ old('work_date') }}" required class="w-full rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white">
                        </div>
                        <button class="justify-self-start rounded-lg bg-indigo-600 px-4 py-2 font-semibold text-white hover:bg-indigo-700">Simpan Jadwal Khusus</button>
                    </form>

                    <div class="mt-6">
                        <h4 class="mb-3 text-sm font-semibold text-gray-700 dark:text-gray-300">Jadwal khusus mendatang</h4>
                        <div class="space-y-2">
                            @forelse($overrides as $override)
                                <div class="flex items-center justify-between gap-3 rounded-lg bg-gray-50 px-3 py-2 text-sm dark:bg-gray-900">
                                    <span class="font-medium">{{ $override->user->name }}</span>
                                    <span class="text-gray-500 dark:text-gray-400">{{ $override->work_date->format('d M Y') }} · {{ $override->workShift->name }}</span>
                                </div>
                            @empty
                                <p class="text-sm text-gray-500 dark:text-gray-400">Belum ada jadwal khusus mendatang.</p>
                            @endforelse
                        </div>
                    </div>
                </div>
            </section>

            <section class="rounded-2xl border border-gray-200 bg-white p-6 shadow-sm dark:border-gray-700 dark:bg-gray-800">
                <div class="flex flex-col gap-4 sm:flex-row sm:items-end sm:justify-between">
                    <div>
                        <h3 class="text-lg font-bold text-gray-900 dark:text-white">Rotasi Shift Mingguan</h3>
                        <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">
                            Grup A dan B otomatis bertukar setiap Senin. Karyawan berpola tetap tidak ikut rolling.
                            Jadwal khusus per tanggal tetap menjadi pengecualian.
                        </p>
                    </div>
                    <form method="GET" action="{{ route('admin.work-rules.index') }}" class="flex items-end gap-2">
                        <div>
                            <label for="week" class="mb-1 block text-sm font-medium">Lihat minggu (pilih tanggal)</label>
                            <input id="week" type="date" name="week" value="{{ $weekStart->toDateString() }}" class="rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white">
                        </div>
                        <button class="rounded-lg border border-gray-300 px-3 py-2 text-sm font-semibold dark:border-gray-600">Lihat</button>
                    </form>
                </div>

                <form action="{{ route('admin.work-rules.rotation-settings.store') }}" method="POST" class="mt-5 grid gap-4 rounded-xl bg-gray-50 p-4 dark:bg-gray-900 sm:grid-cols-2 xl:grid-cols-4">
                        @csrf
                        <div class="sm:col-span-2 xl:col-span-4">
                            <h4 class="font-semibold text-gray-800 dark:text-gray-100">Atur pola rotasi</h4>
                            <p class="text-xs text-gray-500">Tanggal mulai harus hari Senin minggu depan atau setelahnya. Shift grup bertukar otomatis setiap minggu.</p>
                        </div>
                        <div>
                            <label class="mb-1 block text-sm font-medium">Mulai rotasi (Senin)</label>
                            <input type="date" name="anchor_week_start" value="{{ old('anchor_week_start', today()->startOfWeek(\Carbon\Carbon::MONDAY)->addWeek()->toDateString()) }}" required class="w-full rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white">
                        </div>
                        <div>
                            <label class="mb-1 block text-sm font-medium">Shift pertama Grup A</label>
                            <select name="group_a_shift_id" required class="w-full rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white">
                                <option value="">Pilih shift</option>
                                @foreach($shifts->where('is_active', true) as $shift)
                                    <option value="{{ $shift->id }}" @selected(old('group_a_shift_id') == $shift->id)>{{ $shift->name }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div>
                            <label class="mb-1 block text-sm font-medium">Shift pertama Grup B</label>
                            <select name="group_b_shift_id" required class="w-full rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white">
                                <option value="">Pilih shift</option>
                                @foreach($shifts->where('is_active', true) as $shift)
                                    <option value="{{ $shift->id }}" @selected(old('group_b_shift_id') == $shift->id)>{{ $shift->name }}</option>
                                @endforeach
                            </select>
                        </div>
                        <button class="justify-self-start rounded-lg bg-indigo-600 px-4 py-2 font-semibold text-white hover:bg-indigo-700">Simpan Pola Rolling</button>
                </form>

                <div class="mt-6 space-y-6">
                    <div>
                        <h4 class="text-lg font-bold text-gray-900 dark:text-white">
                            Atur Pola Karyawan · {{ $weekStart->format('d M') }} – {{ $weekEnd->format('d M Y') }}
                        </h4>
                        <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">Daftar dipisah berdasarkan grup. Ubah pola mulai Senin yang dipilih; skema upah tidak berubah.</p>
                        @if($rotationSetting)
                            @if($weekStart->lt($rotationSetting->anchor_week_start))
                                <p class="mt-2 text-sm text-amber-700 dark:text-amber-300">Rotasi belum dimulai untuk minggu ini.</p>
                            @else
                                <p class="mt-2 text-sm text-gray-500 dark:text-gray-400">Grup A: {{ $rotationShifts['A']->name }} · Grup B: {{ $rotationShifts['B']->name }}</p>
                            @endif
                        @else
                            <p class="mt-2 text-sm text-amber-700 dark:text-amber-300">Pola rolling belum diatur; karyawan memakai shift default.</p>
                        @endif
                    </div>

                    @foreach($employeeScheduleGroups as $group)
                        <section class="overflow-hidden rounded-xl border border-gray-200 dark:border-gray-700">
                            <div class="flex items-center justify-between bg-gray-50 px-4 py-3 dark:bg-gray-900">
                                <h5 class="font-bold text-gray-900 dark:text-white">{{ $group['title'] }}</h5>
                                <span class="text-sm text-gray-500 dark:text-gray-400">{{ $group['assignments']->count() }} karyawan</span>
                            </div>
                            @forelse($group['assignments'] as $assignment)
                                <div class="border-t border-gray-200 px-4 py-4 dark:border-gray-700">
                                    <div class="flex flex-col gap-3 lg:flex-row lg:items-center lg:justify-between">
                                        @php
                                            $editShift = $assignment->workShift;
                                            if (
                                                $assignment->schedule_mode === 'rolling'
                                                && $assignment->rotation_group
                                                && isset($scheduleRotationShifts[$assignment->rotation_group])
                                            ) {
                                                $editShift = $scheduleRotationShifts[$assignment->rotation_group];
                                            }
                                        @endphp
                                        <div class="min-w-56">
                                            <p class="font-semibold text-gray-900 dark:text-white">{{ $assignment->user->name }}{{ $assignment->user->id_karyawan ? ' · ' . $assignment->user->id_karyawan : '' }}</p>
                                            <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">
                                                @if($group['key'] === 'fixed')
                                                    Tetap · {{ $assignment->workShift->name }}
                                                @else
                                                    {{ isset($rotationShifts[$group['key']]) ? $rotationShifts[$group['key']]->name : $assignment->workShift->name }}
                                                @endif
                                                @if($assignment->user->kategori_absensi === 'organik')
                                                    · Absensi saja (tanpa hitungan upah)
                                                @elseif($assignment->payScheme)
                                                    · {{ $assignment->payScheme->name }} · Rp{{ number_format((float) $assignment->payScheme->daily_salary, 0, ',', '.') }}
                                                @else
                                                    · Absensi saja (tanpa hitungan upah)
                                                @endif
                                            </p>
                                        </div>
                                        <form action="{{ route('admin.work-rules.assignments.schedule.update', $assignment) }}" method="POST" class="grid w-full gap-2 sm:grid-cols-[1fr_1fr_1fr_auto] lg:max-w-4xl">
                                            @csrf
                                            @method('PUT')
                                            <input aria-label="Mulai berlaku" type="date" name="effective_from" min="{{ today()->startOfWeek(\Carbon\Carbon::MONDAY)->addWeek()->toDateString() }}" value="{{ $scheduleEffectiveFrom->toDateString() }}" required class="w-full rounded-lg border-gray-300 text-sm dark:border-gray-600 dark:bg-gray-700 dark:text-white">
                                            <select aria-label="Shift mulai berlaku" name="work_shift_id" required class="w-full rounded-lg border-gray-300 text-sm dark:border-gray-600 dark:bg-gray-700 dark:text-white">
                                                @foreach($shifts->where('is_active', true) as $shift)
                                                    <option value="{{ $shift->id }}" @selected($editShift && $editShift->id === $shift->id)>{{ $shift->name }} ({{ substr($shift->starts_at, 0, 5) }}–{{ substr($shift->ends_at, 0, 5) }})</option>
                                                @endforeach
                                            </select>
                                            <select aria-label="Pola jadwal" name="schedule_mode" required class="w-full rounded-lg border-gray-300 text-sm dark:border-gray-600 dark:bg-gray-700 dark:text-white">
                                                <option value="fixed" @selected($assignment->schedule_mode === 'fixed')>Tetap</option>
                                                <option value="rolling" @selected($assignment->schedule_mode === 'rolling')>Rolling mingguan</option>
                                            </select>
                                            <button class="rounded-lg bg-indigo-600 px-4 py-2 text-sm font-semibold text-white hover:bg-indigo-700">Simpan</button>
                                        </form>
                                    </div>
                                </div>
                            @empty
                                <p class="border-t border-gray-200 px-4 py-4 text-sm text-gray-500 dark:border-gray-700 dark:text-gray-400">Belum ada karyawan di {{ strtolower($group['title']) }}.</p>
                            @endforelse
                        </section>
                    @endforeach
                </div>
            </section>

        </div>
    </div>

    <script>
        (() => {
            const employeeSelect = document.getElementById('work-assignment-user');
            const paySchemeSelect = document.getElementById('work-assignment-pay-scheme');
            const emptyPaySchemeOption = paySchemeSelect?.querySelector('[data-empty-pay-scheme]');

            if (!employeeSelect || !paySchemeSelect || !emptyPaySchemeOption) {
                return;
            }

            const updatePaySchemeRequirement = () => {
                const selectedEmployee = employeeSelect.selectedOptions[0];
                const isOrganic = selectedEmployee?.dataset.category === 'organik';

                emptyPaySchemeOption.textContent = isOrganic
                    ? 'Tanpa perhitungan upah (khusus organik)'
                    : employeeSelect.value
                        ? 'Pilih skema upah'
                        : 'Pilih karyawan dulu';
                emptyPaySchemeOption.disabled = !isOrganic && Boolean(employeeSelect.value);
                paySchemeSelect.required = Boolean(employeeSelect.value) && !isOrganic;

                if (isOrganic) {
                    paySchemeSelect.value = '';
                }
            };

            employeeSelect.addEventListener('change', updatePaySchemeRequirement);
            updatePaySchemeRequirement();
        })();
    </script>
</x-app-layout>
