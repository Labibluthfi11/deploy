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

                    {{-- Pilihan Tipe Filter --}}
                    <div class="flex flex-col gap-2">
                        <label class="text-sm font-semibold text-gray-700 dark:text-gray-300">Tipe Filter</label>
                        <select name="filter_type" id="filter_type" class="filter-select" onchange="toggleFilterInputs()">
                            <option value="all" {{ request('filter_type', 'all') == 'all' ? 'selected' : '' }}>Semua Data</option>
                            <option value="monthly" {{ request('filter_type') == 'monthly' ? 'selected' : '' }}>Per Bulan</option>
                            <option value="custom" {{ request('filter_type') == 'custom' ? 'selected' : '' }}>Range Tanggal (Custom)</option> {{-- ⬅️ INI BARU --}}
                        </select>
                    </div>

                    {{-- Filter Bulan (Muncul kalo pilih Per Bulan) --}}
                    <div class="flex flex-col gap-2" id="month_section" style="display: none;">
                        <label class="text-sm font-semibold text-gray-700 dark:text-gray-300">Bulan & Tahun</label>
                        <div class="flex gap-2">
                            <select name="month" class="filter-select">
                                @for ($m = 1; $m <= 12; $m++)
                                    <option value="{{ $m }}" {{ request('month', now()->month) == $m ? 'selected' : '' }}>
                                        {{ \Carbon\Carbon::createFromFormat('!m', $m)->translatedFormat('F') }}
                                    </option>
                                @endfor
                            </select>
                            <select name="year" class="filter-select">
                                @for($y = now()->year; $y >= 2020; $y--)
                                    <option value="{{ $y }}" {{ request('year', now()->year) == $y ? 'selected' : '' }}>{{ $y }}</option>
                                @endfor
                            </select>
                        </div>
                    </div>

                    {{-- Filter Custom Tanggal (Muncul kalo pilih Range Tanggal) --}}
                    <div class="flex flex-col gap-2" id="custom_date_section" style="display: none;">
                        <label class="text-sm font-semibold text-gray-700 dark:text-gray-300">Pilih Tanggal</label>
                        <div class="flex items-center gap-2">
                            <input type="date" name="start_date" value="{{ request('start_date') }}" class="filter-select text-gray-700">
                            <span class="text-gray-500">s/d</span>
                            <input type="date" name="end_date" value="{{ request('end_date') }}" class="filter-select text-gray-700">
                        </div>
                    </div>

                    <button type="submit" class="filter-btn">
                        <i class="fas fa-filter mr-2"></i> Terapkan
                    </button>

                    {{-- Tombol Export Slip Gaji (Ikut Filter) --}}
                    <a href="{{ route('admin.absensi.user.export-slip', [
                            'user' => $user->id,
                            'filter_type' => request('filter_type', 'all'),
                            'month' => request('month', now()->month),
                            'year' => request('year', now()->year),
                            'start_date' => request('start_date'), // ⬅️ Kirim start_date
                            'end_date' => request('end_date')      // ⬅️ Kirim end_date
                        ]) }}"
                       class="inline-flex items-center gap-2 px-5 py-2.5 bg-blue-600 hover:bg-blue-700 text-white rounded-xl font-medium shadow-sm transition-all duration-200"
                       style="height: 46px;">
                        <i class="fas fa-file-invoice-dollar"></i> Export Slip Gaji
                    </a>
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

    {{-- 📄 Tombol Export Slip Gaji (EXCEL - Bisa Diedit) --}}
    <a href="{{ route('admin.absensi.user.export-slip', [
            'user' => $user->id,
            'filter_type' => request('filter_type', 'all'),
            'month' => request('month', now()->month),
            'year' => request('year', now()->year),
            'start_date' => request('start_date'),
            'end_date' => request('end_date')
        ]) }}"
       class="inline-flex items-center gap-2 px-5 py-2.5 bg-blue-600 hover:bg-blue-700 text-white rounded-xl font-medium shadow-sm transition-all duration-200"
       style="height: 46px;">
        <i class="fas fa-file-excel"></i> Export Slip Gaji (Excel)
    </a>

    {{-- 🆕 Tombol Export Slip Gaji (PDF - GA BISA DIEDIT) --}}
   <a href="{{ route('admin.absensi.user.export-slip-pdf', [
            'id' => $user->id,  {{-- ⚠️ GANTI DARI 'user' JADI 'id' --}}
            'filter_type' => request('filter_type', 'all'),
            'month' => request('month', now()->month),
            'year' => request('year', now()->year),
            'start_date' => request('start_date'),
            'end_date' => request('end_date')
        ]) }}"
       class="inline-flex items-center gap-2 px-5 py-2.5 bg-red-600 hover:bg-red-700 text-white rounded-xl font-medium shadow-sm transition-all duration-200"
       style="height: 46px;"
       title="Format PDF - Tidak bisa diedit">
        <i class="fas fa-file-pdf"></i> Export Slip Gaji (PDF) 🔒
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
                                <th>Aksi</th>
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
                                    <td>
                            @if($item->status === 'hadir' && $item->status_approval === 'approved')
                            <button onclick="editCheckIn({{ $item->id }}, '{{ $item->check_in_at->format('Y-m-d H:i:s') }}')"
                                    class="px-3 py-1.5 bg-yellow-600 hover:bg-yellow-700 text-white rounded-lg text-xs font-semibold transition-all">
                                Edit
                            </button>
                            @else
                            <span class="text-gray-400 text-xs">-</span>
                            @endif
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

    <div id="modalEditCheckIn" class="fixed inset-0 bg-gradient-to-br from-black/60 via-black/70 to-black/80 backdrop-blur-sm hidden items-center justify-center z-50 p-4 animate-fadeIn">
    <div class="bg-white dark:bg-gradient-to-br dark:from-gray-800 dark:via-gray-900 dark:to-gray-800 rounded-3xl w-full max-w-lg shadow-2xl transform transition-all duration-300 scale-95 hover:scale-100 border border-gray-200 dark:border-gray-700">

        {{-- Header dengan Gradient --}}
        <div class="relative bg-gradient-to-r from-blue-600 via-blue-500 to-purple-600 rounded-t-3xl p-6 overflow-hidden">
            {{-- Animated Background Pattern --}}
            <div class="absolute inset-0 opacity-20">
                <div class="absolute top-0 left-0 w-40 h-40 bg-white rounded-full blur-3xl animate-pulse"></div>
                <div class="absolute bottom-0 right-0 w-32 h-32 bg-purple-300 rounded-full blur-2xl animate-pulse delay-300"></div>
            </div>

            <div class="relative flex items-center justify-between">
                <div class="flex items-center gap-3">
                    <div class="w-12 h-12 bg-white/20 backdrop-blur-lg rounded-xl flex items-center justify-center">
                        <i class="fas fa-clock text-2xl text-white"></i>
                    </div>
                    <div>
                        <h3 class="text-xl font-bold text-white">Edit Waktu Check-In</h3>
                        <p class="text-xs text-blue-100 mt-0.5">Ubah jam masuk karyawan</p>
                    </div>
                </div>
                <button type="button" onclick="closeModal()"
                        class="w-10 h-10 bg-white/20 hover:bg-white/30 backdrop-blur-lg rounded-xl flex items-center justify-center transition-all duration-200 hover:rotate-90">
                    <i class="fas fa-times text-lg text-white"></i>
                </button>
            </div>
        </div>

        {{-- Form Content --}}
        <form id="formEditCheckIn" method="POST" class="p-6 space-y-6">
            @csrf
            @method('PUT')

            {{-- Info Card --}}
            <div class="bg-gradient-to-r from-blue-50 to-purple-50 dark:from-blue-900/20 dark:to-purple-900/20 rounded-2xl p-4 border border-blue-200 dark:border-blue-800">
                <div class="flex items-start gap-3">
                    <div class="w-10 h-10 bg-blue-600 dark:bg-blue-500 rounded-xl flex items-center justify-center flex-shrink-0">
                        <i class="fas fa-info-circle text-white"></i>
                    </div>
                    <div class="flex-1">
                        <h4 class="text-sm font-bold text-gray-900 dark:text-white mb-1">Perhatian!</h4>
                        <p class="text-xs text-gray-600 dark:text-gray-400 leading-relaxed">
                            Perubahan waktu check-in akan otomatis menghitung ulang keterlambatan dan potongan gaji.
                        </p>
                    </div>
                </div>
            </div>

            {{-- Input Field dengan Icon --}}
            <div class="space-y-2">
                <label class="flex items-center gap-2 text-sm font-bold text-gray-700 dark:text-gray-300">
                    <i class="fas fa-calendar-alt text-blue-600 dark:text-blue-400"></i>
                    <span>Pilih Waktu Check-In Baru</span>
                </label>

                <div class="relative group">
                    <input type="datetime-local"
                           name="new_check_in"
                           id="inputNewCheckIn"
                           class="w-full px-4 py-3.5 pl-12 border-2 border-gray-300 dark:border-gray-600 rounded-xl
                                  bg-white dark:bg-gray-800 text-gray-900 dark:text-white font-medium
                                  focus:border-blue-500 focus:ring-4 focus:ring-blue-500/20
                                  transition-all duration-200 outline-none
                                  hover:border-blue-400 dark:hover:border-blue-500"
                           required>

                    {{-- Icon di dalam input --}}
                    <div class="absolute left-4 top-1/2 -translate-y-1/2 text-gray-400 group-focus-within:text-blue-600 dark:group-focus-within:text-blue-400 transition-colors">
                        <i class="fas fa-clock"></i>
                    </div>

                    {{-- Animated border --}}
                    <div class="absolute inset-0 rounded-xl bg-gradient-to-r from-blue-500 to-purple-500 opacity-0 group-focus-within:opacity-20 blur transition-opacity duration-300 -z-10"></div>
                </div>

                {{-- Helper text --}}
                <p class="text-xs text-gray-500 dark:text-gray-400 flex items-center gap-1.5 ml-1">
                    <i class="fas fa-lightbulb text-yellow-500"></i>
                    <span>Format: Tanggal dan jam (contoh: 06/01/2025 08:00)</span>
                </p>
            </div>

            {{-- Action Buttons --}}
            <div class="flex gap-3 pt-4">
                <button type="button"
                        onclick="closeModal()"
                        class="flex-1 px-6 py-3.5 bg-gray-100 hover:bg-gray-200 dark:bg-gray-700 dark:hover:bg-gray-600
                               text-gray-700 dark:text-gray-300 rounded-xl font-bold text-sm
                               transition-all duration-200 transform hover:scale-105 active:scale-95
                               flex items-center justify-center gap-2 group">
                    <i class="fas fa-times group-hover:rotate-90 transition-transform duration-200"></i>
                    <span>Batal</span>
                </button>

                <button type="submit"
                        class="flex-1 px-6 py-3.5 bg-gradient-to-r from-blue-600 to-purple-600 hover:from-blue-700 hover:to-purple-700
                               text-white rounded-xl font-bold text-sm shadow-lg shadow-blue-500/50
                               transition-all duration-200 transform hover:scale-105 active:scale-95
                               flex items-center justify-center gap-2 group relative overflow-hidden">
                    {{-- Shine effect --}}
                    <div class="absolute inset-0 bg-gradient-to-r from-transparent via-white/20 to-transparent translate-x-[-200%] group-hover:translate-x-[200%] transition-transform duration-700"></div>

                    <i class="fas fa-save relative z-10"></i>
                    <span class="relative z-10">Simpan Perubahan</span>
                </button>
            </div>

            {{-- Security Badge --}}
            <div class="pt-4 border-t border-gray-200 dark:border-gray-700">
                <div class="flex items-center justify-center gap-2 text-xs text-gray-500 dark:text-gray-400">
                    <i class="fas fa-shield-alt text-green-600 dark:text-green-400"></i>
                    <span>Perubahan akan dicatat dalam sistem audit</span>
                </div>
            </div>
        </form>
    </div>
</div>


<script>
console.log('🔥 Script loaded');

function toggleFilterInputs() {
    const type = document.getElementById('filter_type').value;
    const monthSection = document.getElementById('month_section');
    const customSection = document.getElementById('custom_date_section');

    monthSection.style.display = 'none';
    customSection.style.display = 'none';

    if (type === 'monthly') {
        monthSection.style.display = 'flex';
    } else if (type === 'custom') {
        customSection.style.display = 'flex';
    }
}

document.addEventListener('DOMContentLoaded', toggleFilterInputs);

function editCheckIn(absensiId, currentTime) {
    console.log('🔥 editCheckIn() called!', {absensiId, currentTime});

    const form = document.getElementById('formEditCheckIn');
    const inputField = document.getElementById('inputNewCheckIn');
    const modal = document.getElementById('modalEditCheckIn');

    if (!form || !inputField || !modal) {
        alert('ERROR: Element tidak ditemukan!');
        return;
    }

    // Set form action
    const newAction = `/admin/absensi/${absensiId}/edit-checkin`;
    form.action = newAction;
    console.log('✅ Form action set to:', form.action);

    // 🔥 ALERT BUAT CEK
    alert('Form action akan di-set ke: ' + newAction + '\n\nAction sekarang: ' + form.action);

    // Format waktu
    const formatted = currentTime.replace(' ', 'T').substring(0, 16);
    inputField.value = formatted;
    console.log('✅ Input value set to:', formatted);

    // Show modal
    modal.classList.remove('hidden');
    modal.classList.add('flex');
    console.log('✅ Modal shown');
}

function closeModal() {
    console.log('🔥 closeModal() called');
    const modal = document.getElementById('modalEditCheckIn');
    if (modal) {
        modal.classList.add('hidden');
        modal.classList.remove('flex');
        console.log('✅ Modal hidden');
    }
}

// Submit listener dengan ALERT
document.addEventListener('DOMContentLoaded', function() {
    console.log('🔥 DOM ready');

    const form = document.getElementById('formEditCheckIn');
    if (form) {
        form.addEventListener('submit', function(e) {
            console.log('🔥 FORM SUBMIT TRIGGERED!');
            console.log('Form action:', this.action);
            console.log('Form method:', this.method);

            // 🔥 ALERT BUAT CEK SEBELUM SUBMIT
            const confirm = window.confirm(
                'FORM AKAN DI-SUBMIT!\n\n' +
                'Action: ' + this.action + '\n' +
                'Method: ' + this.method + '\n\n' +
                'Klik OK untuk lanjut submit, Cancel untuk batal'
            );

            if (!confirm) {
                e.preventDefault();
                alert('Submit dibatalkan!');
            }
        });
        console.log('✅ Submit listener attached');
    } else {
        console.error('❌ Form tidak ditemukan saat DOM ready!');
    }
});

// Close modal on outside click
document.addEventListener('DOMContentLoaded', function() {
    const modal = document.getElementById('modalEditCheckIn');
    if (modal) {
        modal.addEventListener('click', function(e) {
            if (e.target === this) {
                closeModal();
            }
        });
        console.log('✅ Click-outside listener attached');
    }
});

console.log('🔥 Script initialized');
</script>
</x-app-layout>
