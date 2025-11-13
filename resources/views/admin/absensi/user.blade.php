<x-app-layout>
    <x-slot name="header">
        <div class="flex justify-between items-center">
            <div>
                <h2 class="font-bold text-3xl text-gray-900 dark:text-white tracking-tight">
                    {{ __('Detail Absensi Karyawan') }}
                </h2>
                <p class="text-base text-gray-600 dark:text-gray-400 font-medium mt-1">{{ $user->name }}</p>
            </div>
            <a href="{{ route('admin.absensi.index') }}"
                class="group inline-flex items-center gap-2 px-5 py-2.5 bg-gray-900 dark:bg-gray-700 hover:bg-gray-800 dark:hover:bg-gray-600 text-white rounded-xl font-medium transition-all duration-200 shadow-sm hover:shadow-md">
                <i class="fas fa-arrow-left transition-transform group-hover:-translate-x-1"></i>
                <span>Kembali</span>
            </a>
        </div>
    </x-slot>

    <style>
        /* ... (CSS lo udah bagus, gue biarin aja) ... */
        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, 'Helvetica Neue', Arial, sans-serif;
        }
        .premium-bg { background: #f8fafc; }
        .dark .premium-bg { background: #0f172a; }
        .stat-card { transition: all 0.3s ease; }
        .stat-card:hover { transform: translateY(-4px); box-shadow: 0 12px 24px -8px rgba(0, 0, 0, 0.12); }
        .dark .stat-card:hover { box-shadow: 0 12px 24px -8px rgba(0, 0, 0, 0.4); }
        .stat-icon { transition: transform 0.3s ease; }
        .stat-card:hover .stat-icon { transform: scale(1.08); }
        .premium-table { border-collapse: separate; border-spacing: 0; }
        .premium-table thead th { background: #1f2937; color: #f3f4f6; font-weight: 600; text-transform: uppercase; letter-spacing: 0.05em; font-size: 0.75rem; padding: 1rem; border-bottom: 2px solid #374151; white-space: nowrap; }
        .dark .premium-table thead th { background: #111827; color: #e5e7eb; border-bottom-color: #1f2937; }
        .premium-table tbody tr { transition: all 0.15s ease; background: white; border-bottom: 1px solid #e5e7eb; }
        .dark .premium-table tbody tr { background: #1e293b; border-bottom-color: #334155; }
        .premium-table tbody tr:hover { background: #f1f5f9; box-shadow: 0 2px 8px rgba(0, 0, 0, 0.08); }
        .dark .premium-table tbody tr:hover { background: #334155; box-shadow: 0 2px 8px rgba(0, 0, 0, 0.3); }
        .premium-table tbody td { padding: 1rem; vertical-align: middle; white-space: nowrap; }
        .badge-premium { display: inline-flex; align-items: center; gap: 0.375rem; padding: 0.375rem 0.875rem; border-radius: 0.5rem; font-size: 0.75rem; font-weight: 600; letter-spacing: 0.025em; transition: all 0.2s ease; }
        .link-premium { transition: all 0.2s ease; display: inline-flex; align-items: center; gap: 0.375rem; padding: 0.375rem 0.75rem; border-radius: 0.5rem; font-weight: 500; font-size: 0.875rem; }
        .link-premium:hover { background: rgba(107, 114, 128, 0.12); }
        .premium-scroll::-webkit-scrollbar { height: 8px; width: 8px; }
        .premium-scroll::-webkit-scrollbar-track { background: #f1f5f9; }
        .dark .premium-scroll::-webkit-scrollbar-track { background: #1e293b; }
        .premium-scroll::-webkit-scrollbar-thumb { background: #94a3b8; border-radius: 10px; }
        .dark .premium-scroll::-webkit-scrollbar-thumb { background: #475569; }
        .premium-scroll::-webkit-scrollbar-thumb:hover { background: #64748b; }
        .premium-card { background: white; border: 1px solid #e5e7eb; box-shadow: 0 1px 3px rgba(0, 0, 0, 0.08); transition: all 0.2s ease; }
        .dark .premium-card { background: #1e293b; border-color: #334155; box-shadow: 0 1px 3px rgba(0, 0, 0, 0.4); }
        .text-late { color: #dc2626; font-weight: 600; font-size: 0.875rem; }
        .text-ontime { color: #16a34a; font-weight: 600; font-size: 0.875rem; }
        .filter-select { padding: 0.625rem 1rem; border: 1px solid #e5e7eb; border-radius: 0.75rem; background: white; font-weight: 500; font-size: 0.875rem; transition: all 0.2s ease; min-width: 150px; }
        .dark .filter-select { background: #1e293b; border-color: #334155; color: #e5e7eb; }
        .filter-select:focus { outline: none; border-color: #3b82f6; box-shadow: 0 0 0 3px rgba(59, 130, 246, 0.1); }
        .filter-btn { padding: 0.625rem 1.5rem; background: #1f2937; color: white; border-radius: 0.75rem; font-weight: 600; font-size: 0.875rem; transition: all 0.2s ease; border: none; cursor: pointer; }
        .filter-btn:hover { background: #111827; transform: translateY(-1px); box-shadow: 0 4px 12px rgba(0, 0, 0, 0.15); }
        .dark .filter-btn { background: #374151; }
        .dark .filter-btn:hover { background: #4b5563; }
    </style>

    <div class="py-8 premium-bg min-h-screen">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 space-y-6">

            {{-- Filter Section --}}
            <div class="premium-card p-6 rounded-2xl">
                <form method="GET" action="{{ route('admin.absensi.user', $user->id) }}" class="flex flex-wrap items-end gap-4">
                    <div class="flex flex-col gap-2">
                        <label class="text-sm font-semibold text-gray-700 dark:text-gray-300">Periode</label>
                        <select name="filter_type" id="filter_type" class="filter-select" onchange="toggleFilterInputs()">
                            <option value="all" {{ request('filter_type', 'all') == 'all' ? 'selected' : '' }}>Semua Data</option>
                            <option value="monthly" {{ request('filter_type') == 'monthly' ? 'selected' : '' }}>Per Bulan</option>
                            <option value="weekly" {{ request('filter_type') == 'weekly' ? 'selected' : '' }}>Per Minggu</option>
                            <option value="yearly" {{ request('filter_type') == 'yearly' ? 'selected' : '' }}>Per Tahun</option>
                        </select>
                    </div>

                    <div class="flex flex-col gap-2" id="year_filter">
                        <label class="text-sm font-semibold text-gray-700 dark:text-gray-300">Tahun</label>
                        <select name="year" class="filter-select">
                            @for($y = now()->year; $y >= 2020; $y--)
                                <option value="{{ $y }}" {{ request('year', now()->year) == $y ? 'selected' : '' }}>{{ $y }}</option>
                            @endfor
                        </select>
                    </div>

                    <div class="flex flex-col gap-2" id="month_filter" style="display: none;">
                        <label class="text-sm font-semibold text-gray-700 dark:text-gray-300">Bulan</label>
                        <select name="month" class="filter-select">
                            <option value="1" {{ request('month', now()->month) == 1 ? 'selected' : '' }}>Januari</option>
                            <option value="2" {{ request('month', now()->month) == 2 ? 'selected' : '' }}>Februari</option>
                            <option value="3" {{ request('month', now()->month) == 3 ? 'selected' : '' }}>Maret</option>
                            <option value="4" {{ request('month', now()->month) == 4 ? 'selected' : '' }}>April</option>
                            <option value="5" {{ request('month', now()->month) == 5 ? 'selected' : '' }}>Mei</option>
                            <option value="6" {{ request('month', now()->month) == 6 ? 'selected' : '' }}>Juni</option>
                            <option value="7" {{ request('month', now()->month) == 7 ? 'selected' : '' }}>Juli</option>
                            <option value="8" {{ request('month', now()->month) == 8 ? 'selected' : '' }}>Agustus</option>
                            <option value="9" {{ request('month', now()->month) == 9 ? 'selected' : '' }}>September</option>
                            <option value="10" {{ request('month', now()->month) == 10 ? 'selected' : '' }}>Oktober</option>
                            <option value="11" {{ request('month', now()->month) == 11 ? 'selected' : '' }}>November</option>
                            <option value="12" {{ request('month', now()->month) == 12 ? 'selected' : '' }}>Desember</option>
                        </select>
                    </div>

                    <div class="flex flex-col gap-2" id="week_filter" style="display: none;">
                        <label class="text-sm font-semibold text-gray-700 dark:text-gray-300">Minggu Ke-</label>
                        <select name="week" class="filter-select">
                            <option value="1" {{ request('week') == 1 ? 'selected' : '' }}>Minggu 1</option>
                            <option value="2" {{ request('week') == 2 ? 'selected' : '' }}>Minggu 2</option>
                            <option value="3" {{ request('week') == 3 ? 'selected' : '' }}>Minggu 3</option>
                            <option value="4" {{ request('week') == 4 ? 'selected' : '' }}>Minggu 4</option>
                            <option value="5" {{ request('week') == 5 ? 'selected' : '' }}>Minggu 5</option>
                        </select>
                    </div>

                    <button type="submit" class="filter-btn">
                        <i class="fas fa-filter mr-2"></i>
                        Terapkan Filter
                    </button>

                    @if(request()->hasAny(['filter_type', 'year', 'month', 'week']))
                        <a href="{{ route('admin.absensi.user', $user->id) }}" class="filter-btn" style="background: #ef4444;">
                            <i class="fas fa-times mr-2"></i>
                            Reset
                        </a>
                    @endif
                </form>
            </div>

            {{-- Summary --}}
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-5 gap-5 fade-in">
                {{-- Total Hadir --}}
                <div class="stat-card premium-card p-6 rounded-2xl">
                    <div class="flex justify-between">
                        <div>
                            <p class="text-gray-600 dark:text-gray-400 text-xs uppercase font-semibold mb-2">Total Hadir</p>
                            <p class="text-gray-900 dark:text-white text-3xl font-bold">{{ $absensiStats['hadir'] ?? 0 }}</p>
                        </div>
                        <div class="stat-icon w-12 h-12 bg-green-100 dark:bg-green-900/30 rounded-xl flex items-center justify-center">
                            <i class="fas fa-check-circle text-xl text-green-600 dark:text-green-400"></i>
                        </div>
                    </div>
                </div>
                {{-- Total Telat --}}
                <div class="stat-card premium-card p-6 rounded-2xl">
                    <div class="flex justify-between">
                        <div>
                            <p class="text-gray-600 dark:text-gray-400 text-xs uppercase font-semibold mb-2">Total Telat</p>
                            <p class="text-gray-900 dark:text-white text-3xl font-bold">{{ $absensiStats['telat'] ?? 0 }}</p>
                        </div>
                        <div class="stat-icon w-12 h-12 bg-orange-100 dark:bg-orange-900/30 rounded-xl flex items-center justify-center">
                            <i class="fas fa-clock text-xl text-orange-600 dark:text-orange-400"></i>
                        </div>
                    </div>
                </div>
                {{-- Total Izin --}}
                <div class="stat-card premium-card p-6 rounded-2xl">
                    <div class="flex justify-between">
                        <div>
                            <p class="text-gray-600 dark:text-gray-400 text-xs uppercase font-semibold mb-2">Total Izin</p>
                            <p class="text-gray-900 dark:text-white text-3xl font-bold">{{ $absensiStats['izin'] ?? 0 }}</p>
                        </div>
                        <div class="stat-icon w-12 h-12 bg-yellow-100 dark:bg-yellow-900/30 rounded-xl flex items-center justify-center">
                            <i class="fas fa-sticky-note text-xl text-yellow-600 dark:text-yellow-400"></i>
                        </div>
                    </div>
                </div>
                {{-- Total Sakit --}}
                <div class="stat-card premium-card p-6 rounded-2xl">
                    <div class="flex justify-between">
                        <div>
                            <p class="text-gray-600 dark:text-gray-400 text-xs uppercase font-semibold mb-2">Total Sakit</p>
                            <p class="text-gray-900 dark:text-white text-3xl font-bold">{{ $absensiStats['sakit'] ?? 0 }}</p>
                        </div>
                        <div class="stat-icon w-12 h-12 bg-red-100 dark:bg-red-900/30 rounded-xl flex items-center justify-center">
                            <i class="fas fa-medkit text-xl text-red-600 dark:text-red-400"></i>
                        </div>
                    </div>
                </div>
                {{-- Total Lembur --}}
                <div class="stat-card premium-card p-6 rounded-2xl">
                    <div class="flex justify-between">
                        <div>
                            <p class="text-gray-600 dark:text-gray-400 text-xs uppercase font-semibold mb-2">Total Lembur</p>
                            <p class="text-gray-900 dark:text-white text-3xl font-bold">{{ $absensiStats['lembur'] ?? 0 }}</p>
                        </div>
                        <div class="stat-icon w-12 h-12 bg-purple-100 dark:bg-purple-900/30 rounded-xl flex items-center justify-center">
                            <i class="fas fa-clock text-xl text-purple-600 dark:text-purple-400"></i>
                        </div>
                    </div>
                </div>
            </div>

            <!-- 🆕 KOTAK BARU: RINGKASAN GAJI (SESUAI REQUEST LO) -->
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-5 fade-in">

                <!-- Card Potongan Telat -->
                <div class="stat-card premium-card p-6 rounded-2xl bg-red-50 dark:bg-red-900/20">
                    <div class="flex justify-between">
                        <div>
                            <p class="text-gray-600 dark:text-gray-400 text-xs uppercase font-semibold mb-2">Total Potongan Telat</p>
                            <p class="text-red-700 dark:text-red-300 text-3xl font-bold">
                                -Rp {{ number_format($absensiStats['total_potongan'] ?? 0, 0, ',', '.') }}
                            </p>
                        </div>
                        <div class="stat-icon w-12 h-12 bg-red-100 dark:bg-red-900/30 rounded-xl flex items-center justify-center">
                            <i class="fas fa-arrow-circle-down text-xl text-red-600 dark:text-red-400"></i>
                        </div>
                    </div>
                </div>

                <!-- Card Gaji Lembur -->
                <div class="stat-card premium-card p-6 rounded-2xl bg-purple-50 dark:bg-purple-900/20">
                    <div class="flex justify-between">
                        <div>
                            <p class="text-gray-600 dark:text-gray-400 text-xs uppercase font-semibold mb-2">Total Gaji Lembur</p>
                            <p class="text-purple-700 dark:text-purple-300 text-3xl font-bold">
                                +Rp {{ number_format($absensiStats['total_gaji_lembur'] ?? 0, 0, ',', '.') }}
                            </p>
                        </div>
                        <div class="stat-icon w-12 h-12 bg-purple-100 dark:bg-purple-900/30 rounded-xl flex items-center justify-center">
                            <i class="fas fa-arrow-circle-up text-xl text-purple-600 dark:text-purple-400"></i>
                        </div>
                    </div>
                </div>

                <!-- Card Gaji Pokok -->
                <div class="stat-card premium-card p-6 rounded-2xl bg-green-50 dark:bg-green-900/20">
                    <div class="flex justify-between">
                        <div>
                            <p class="text-gray-600 dark:text-gray-400 text-xs uppercase font-semibold mb-2">Total Gaji Pokok</p>
                            <p class="text-green-700 dark:text-green-300 text-3xl font-bold">
                                Rp {{ number_format($absensiStats['total_gaji_pokok'] ?? 0, 0, ',', '.') }}
                            </p>
                        </div>
                        <div class="stat-icon w-12 h-12 bg-green-100 dark:bg-green-900/30 rounded-xl flex items-center justify-center">
                            <i class="fas fa-plus-circle text-xl text-green-600 dark:text-green-400"></i>
                        </div>
                    </div>
                </div>

                <!-- Card Gaji Bersih -->
                <div class="stat-card premium-card p-6 rounded-2xl bg-blue-50 dark:bg-blue-900/20">
                    <div class="flex justify-between">
                        <div>
                            <p class="text-gray-600 dark:text-gray-400 text-xs uppercase font-semibold mb-2">Total Gaji Bersih</p>
                            <p class="text-blue-700 dark:text-blue-300 text-3xl font-bold">
                                Rp {{ number_format($absensiStats['total_gaji_bersih'] ?? 0, 0, ',', '.') }}
                            </p>
                        </div>
                        <div class="stat-icon w-12 h-12 bg-blue-100 dark:bg-blue-900/30 rounded-xl flex items-center justify-center">
                            <i class="fas fa-equals text-xl text-blue-600 dark:text-blue-400"></i>
                        </div>
                    </div>
                </div>

            </div>
            <!-- 🆕 END RINGKASAN GAJI -->

           {{-- Tombol Export --}}
        <div class="flex justify-end mb-4 gap-4">

            {{-- Tombol Slip Gaji (BARU) --}}
            <a href="{{ route('admin.absensi.user.export-slip', [
                    'user' => $user->id,
                    'filter_type' => request('filter_type', 'all'),
                    'month' => request('month', now()->month),
                    'year' => request('year', now()->year),
                    'week' => request('week', 1)
                ]) }}"
               class="inline-flex items-center gap-2 px-5 py-2.5 bg-blue-600 hover:bg-blue-700 text-white rounded-xl font-medium shadow-sm transition-all duration-200">
                <i class="fas fa-file-invoice-dollar"></i>
                Export Slip Gaji
            </a>

            {{-- Tombol Export Detail (LAMA) --}}
            <a href="{{ route('admin.absensi.user.export', [
                    'id' => $user->id,
                    'filter_type' => request('filter_type', 'all'),
                    'month' => request('month', now()->month),
                    'year' => request('year', now()->year),
                ]) }}"
               class="inline-flex items-center gap-2 px-5 py-2.5 bg-green-600 hover:bg-green-700 text-white rounded-xl font-medium shadow-sm transition-all duration-200">
                <i class="fas fa-file-excel"></i>
                Export Detail (Tabel)
            </a>
        </div>


            {{-- Tabel --}}
            <div class="premium-card p-6 rounded-2xl fade-in">
                <div class="flex items-center gap-3 mb-6">
                    <div class="w-10 h-10 bg-gray-900 dark:bg-gray-700 rounded-lg flex items-center justify-center">
                        <i class="fas fa-table text-white"></i>
                    </div>
                    <div>
                        <h3 class="text-xl font-bold text-gray-900 dark:text-white">Riwayat Absensi</h3>
                        <p class="text-sm text-gray-600 dark:text-gray-400">Detail kehadiran lengkap</p>
                    </div>
                </div>

                <div class="overflow-x-auto premium-scroll rounded-xl border border-gray-200 dark:border-gray-700">
                    <table class="premium-table min-w-full">
                        <thead>
                            <tr>
                                <th>Tanggal</th>
                                <th>Check-in</th>
                                <th>Telat</th>
                                <th>Gaji Pokok</th>
                                <th>Potongan</th>
                                <th>Gaji Bersih</th>
                                <!-- 🆕 TAMBAH 2 HEADER INI -->
                                <th>Menit Lembur</th>
                                <th>Gaji Lembur</th>
                                <!-- ------------------- -->
                                <th>Check-out</th>
                                <th>Status</th>
                                <th>Tipe</th>
                                <th>Lokasi Masuk</th>
                                <th>Foto Masuk</th>
                                <th>Lokasi Pulang</th>
                                <th>Foto Pulang</th>
                                <th>Bukti</th>
                                <th>Approval</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($absensi as $item)
                                <tr>
                                    {{-- Tanggal --}}
                                    <td class="font-semibold text-gray-900 dark:text-white">
                                        {{ \Carbon\Carbon::parse($item->check_in_at)->format('d M Y') }}
                                    </td>

                                    {{-- Check-in --}}
                                    <td>
                                        <span class="px-2.5 py-1 bg-blue-100 dark:bg-blue-900/30 text-blue-800 dark:text-blue-300 rounded-lg text-xs font-semibold">
                                            {{ \Carbon\Carbon::parse($item->check_in_at)->format('H:i') }}
                                        </span>
                                    </td>

                                    {{-- Telat --}}
                                    <td>
                                        @if($item->late_minutes > 0)
                                            <div class="flex flex-col gap-1">
                                                <span class="text-late">
                                                    {{ floor($item->late_minutes / 60) > 0 ? floor($item->late_minutes / 60).' jam ' : '' }}{{ $item->late_minutes % 60 }} menit
                                                </span>
                                                @if($item->rounded_late_minutes)
                                                    <span class="text-xs text-gray-500 dark:text-gray-400">
                                                        Dibulatkan: {{ $item->rounded_late_minutes }} menit
                                                    </span>
                                                @endif
                                            </div>
                                        @else
                                            <span class="text-ontime">Tepat Waktu</span>
                                        @endif
                                    </td>

                                    {{-- Gaji Pokok --}}
                                    <td>
                                        @if($item->base_salary)
                                            <span class="px-2.5 py-1 bg-green-100 dark:bg-green-900/30 text-green-800 dark:text-green-300 rounded-lg text-xs font-semibold">
                                                Rp {{ number_format($item->base_salary, 0, ',', '.') }}
                                            </span>
                                        @else
                                            <span class="text-gray-500 dark:text-gray-400 text-sm">-</span>
                                        @endif
                                    </td>

                                    {{-- Potongan Telat --}}
                                    <td>
                                        @if($item->late_penalty && $item->late_penalty > 0)
                                            <div class="flex flex-col gap-1">
                                                <span class="px-2.5 py-1 bg-red-100 dark:bg-red-900/30 text-red-800 dark:text-red-300 rounded-lg text-xs font-semibold">
                                                    -Rp {{ number_format($item->late_penalty, 0, ',', '.') }}
                                                </span>
                                                <span class="text-xs text-gray-500 dark:text-gray-400">
                                                    ({{ $item->rounded_late_minutes }} menit)
                                                </span>
                                            </div>
                                        @else
                                            <span class="text-green-600 dark:text-green-400 text-sm font-semibold">Tidak Ada</span>
                                        @endif
                                    </td>

                                    {{-- Gaji Bersih --}}
                                    <td>
                                        @if($item->final_salary)
                                            <span class="px-3 py-1.5 bg-blue-100 dark:bg-blue-900/30 text-blue-900 dark:text-blue-200 rounded-lg text-sm font-bold">
                                                Rp {{ number_format($item->final_salary, 0, ',', '.') }}
                                            </span>
                                        @else
                                            <span class="text-gray-500 dark:text-gray-400 text-sm">-</span>
                                        @endif
                                    </td>

                                    <!-- 🆕 TAMBAH 2 KOLOM INI -->
                                    <td>
                                        @if($item->overtime_minutes > 0)
                                            <span class="px-2.5 py-1 bg-purple-100 dark:bg-purple-900/30 text-purple-800 dark:text-purple-300 rounded-lg text-xs font-semibold">
                                                {{ $item->overtime_minutes }} Menit
                                            </span>
                                        @else
                                            <span class="text-gray-500 dark:text-gray-400 text-sm">-</span>
                                        @endif
                                    </td>
                                    <td>
                                        @if($item->overtime_pay > 0)
                                            <span class="px-2.5 py-1 bg-purple-100 dark:bg-purple-900/30 text-purple-800 dark:text-purple-300 rounded-lg text-xs font-semibold">
                                                Rp {{ number_format($item->overtime_pay, 0, ',', '.') }}
                                            </span>
                                        @else
                                            <span class="text-gray-500 dark:text-gray-400 text-sm">-</span>
                                        @endif
                                    </td>
                                    <!-- ------------------- -->

                                    {{-- Check-out --}}
                                    <td>
                                        @if($item->check_out_at)
                                            <span class="px-2.5 py-1 bg-orange-100 dark:bg-orange-900/30 text-orange-800 dark:text-orange-300 rounded-lg text-xs font-semibold">
                                                {{ \Carbon\Carbon::parse($item->check_out_at)->format('H:i') }}
                                            </span>
                                        @else
                                            <span class="text-gray-500 dark:text-gray-400 text-sm">-</span>
                                        @endif
                                    </td>

                                    {{-- Status --}}
                                    <td>
                                        <span class="badge-premium
                                            @if($item->status == 'hadir') bg-green-100 dark:bg-green-900/30 text-green-800 dark:text-green-300
                                            @elseif($item->status == 'izin') bg-yellow-100 dark:bg-yellow-900/30 text-yellow-800 dark:text-yellow-300
                                            @elseif($item->status == 'sakit') bg-red-100 dark:bg-red-900/30 text-red-800 dark:text-red-300
                                            @endif">
                                            {{ ucfirst($item->status) }}
                                        </span>
                                    </td>

                                    {{-- Tipe --}}
                                    <td>
                                        @if($item->tipe)
                                            <span class="badge-premium bg-purple-100 dark:bg-purple-900/30 text-purple-800 dark:text-purple-300">
                                                {{ ucfirst($item->tipe) }}
                                            </span>
                                        @else
                                            <span class="text-gray-500 dark:text-gray-400 text-sm">-</span>
                                        @endif
                                    </td>

                                    {{-- Lokasi Masuk --}}
                                    <td>
                                        @if ($item->lokasi_masuk)
                                            @php
                                                $coords = explode(',', $item->lokasi_masuk);
                                                $lat = trim($coords[0]);
                                                $lng = trim($coords[1]);
                                                $googleMapsUrl = "https://www.google.com/maps/search/?api=1&query=$lat,$lng";
                                            @endphp
                                            <a href="{{ $googleMapsUrl }}" target="_blank" class="link-premium text-gray-700 dark:text-gray-300 hover:text-gray-900 dark:hover:text-white">
                                                <i class="fas fa-map-marker-alt"></i>
                                                <span>Lokasi</span>
                                            </a>
                                        @else
                                            <span class="text-gray-500 dark:text-gray-400 text-sm">-</span>
                                        @endif
                                    </td>

                                    {{-- Foto Masuk --}}
                                    <td>
                                        @if ($item->foto_masuk)
                                            <a href="{{ Storage::url($item->foto_masuk) }}" target="_blank" class="link-premium text-gray-700 dark:text-gray-300 hover:text-gray-900 dark:hover:text-white">
                                                <i class="fas fa-image"></i>
                                                <span>Lihat</span>
                                            </a>
                                        @else
                                            <span class="text-gray-500 dark:text-gray-400 text-sm">-</span>
                                        @endif
                                    </td>

                                    {{-- Lokasi Pulang --}}
                                    <td>
                                        @if ($item->lokasi_pulang)
                                            @php
                                                $coords = explode(',', $item->lokasi_pulang);
                                                $lat = trim($coords[0]);
                                                $lng = trim($coords[1]);
                                                $googleMapsUrl = "https://www.google.com/maps/search/?api=1&query=$lat,$lng";
                                            @endphp
                                            <a href="{{ $googleMapsUrl }}" target="_blank" class="link-premium text-gray-700 dark:text-gray-300 hover:text-gray-900 dark:hover:text-white">
                                                <i class="fas fa-map-marker-alt"></i>
                                                <span>Lokasi</span>
                                            </a>
                                        @else
                                            <span class="text-gray-500 dark:text-gray-400 text-sm">-</span>
                                        @endif
                                    </td>

                                    {{-- Foto Pulang --}}
                                    <td>
                                        @if ($item->foto_pulang)
                                            <a href="{{ Storage::url($item->foto_pulang) }}" target="_blank" class="link-premium text-gray-700 dark:text-gray-300 hover:text-gray-900 dark:hover:text-white">
                                                <i class="fas fa-image"></i>
                                                <span>Lihat</span>
                                            </a>
                                        @else
                                            <span class="text-gray-500 dark:text-gray-400 text-sm">-</span>
                                        @endif
                                    </td>

                                    {{-- Bukti --}}
                                    <td>
                                        @if ($item->file_bukti)
                                            <a href="{{ Storage::url($item->file_bukti) }}" target="_blank" class="link-premium text-gray-700 dark:text-gray-300 hover:text-gray-900 dark:hover:text-white">
                                                <i class="fas fa-file-alt"></i>
                                                <span>File</span>
                                            </a>
                                        @else
                                            <span class="text-gray-500 dark:text-gray-400 text-sm">-</span>
                                        @endif
                                    </td>

                                    {{-- Approval --}}
                                    <td>
                                        <div class="flex items-center gap-2">
                                            <span class="badge-premium
                                                @if($item->status_approval == 'approved') bg-green-100 dark:bg-green-900/30 text-green-800 dark:text-green-300
                                                @elseif($item->status_approval == 'pending') bg-yellow-100 dark:bg-yellow-900/30 text-yellow-800 dark:text-yellow-300
                                                @elseif($item->status_approval == 'rejected') bg-red-100 dark:bg-red-900/30 text-red-800 dark:text-red-300
                                                @endif">
                                                {{ ucfirst($item->status_approval) }}
                                            </span>
                                            @if ($item->catatan_admin)
                                                <i class="fas fa-info-circle text-gray-500 dark:text-gray-400 text-sm cursor-help" title="{{ $item->catatan_admin }}"></i>
                                            @endif
                                        </div>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <!-- 🆕 UPDATE COLSPAN (15 + 2 = 17) -->
                                    <td colspan="17" class="text-center py-16">
                                        <div class="flex flex-col items-center gap-3">
                                            <div class="w-16 h-16 bg-gray-100 dark:bg-gray-800 rounded-full flex items-center justify-center">
                                                <i class="fas fa-inbox text-gray-400 dark:text-gray-500 text-2xl"></i>
                                            </div>
                                            <p class="text-gray-600 dark:text-gray-300 font-semibold">Belum ada data absensi</p>
                                            <p class="text-gray-500 dark:text-gray-400 text-sm">Data akan muncul setelah karyawan melakukan absensi</p>
                                        </div>
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <script src="https://kit.fontawesome.com/a076d05399.js" crossorigin="anonymous"></script>
    <script>
        function toggleFilterInputs() {
            const filterType = document.getElementById('filter_type').value;
            const yearFilter = document.getElementById('year_filter');
            const monthFilter = document.getElementById('month_filter');
            const weekFilter = document.getElementById('week_filter');

            // Reset semua dulu
            yearFilter.style.display = 'none';
            monthFilter.style.display = 'none';
            weekFilter.style.display = 'none';

            if (filterType === 'yearly') {
                yearFilter.style.display = 'flex';
            } else if (filterType === 'monthly') {
                yearFilter.style.display = 'flex';
                monthFilter.style.display = 'flex';
            } else if (filterType === 'weekly') {
                yearFilter.style.display = 'flex';
                monthFilter.style.display = 'flex';
                weekFilter.style.display = 'flex';
            } else if (filterType === 'all') {
                // 'all' tidak menampilkan apa-apa (selain tombol)
            }
        }

        // Panggil saat halaman dimuat
        document.addEventListener('DOMContentLoaded', toggleFilterInputs);
    </script>
</x-app-layout>
