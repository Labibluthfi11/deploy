{{-- resources/views/admin/absensi/index.blade.php --}}
<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-col gap-5 lg:flex-row lg:items-center lg:justify-between">
            <div>
                <h2 class="text-2xl font-bold text-gray-800 dark:text-white">
                    {{ $dashboardTitle ?? 'Dashboard Absensi Karyawan' }}
                </h2>
                <p class="text-sm text-gray-500 dark:text-gray-400 mt-1">
                    Kelola dan pantau kehadiran karyawan secara real-time
                </p>
            </div>
            <form method="GET" class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-3 lg:min-w-[660px]">
                <div>
                    <label for="filter-month" class="sr-only">Bulan</label>
                    <select id="filter-month" name="month" class="w-full px-4 py-2.5 bg-white dark:bg-gray-700 border border-gray-200 dark:border-gray-600 rounded-xl text-sm text-gray-700 dark:text-gray-200 shadow-sm focus:ring-2 focus:ring-indigo-500 focus:border-transparent">
                        @for ($m = 1; $m <= 12; $m++)
                            <option value="{{ $m }}" {{ request('month', $month) == $m ? 'selected' : '' }}>
                                {{ \Carbon\Carbon::createFromFormat('!m', $m)->translatedFormat('F') }}
                            </option>
                        @endfor
                    </select>
                </div>
                <div>
                    <label for="filter-year" class="sr-only">Tahun</label>
                    <select id="filter-year" name="year" class="w-full px-4 py-2.5 bg-white dark:bg-gray-700 border border-gray-200 dark:border-gray-600 rounded-xl text-sm text-gray-700 dark:text-gray-200 shadow-sm focus:ring-2 focus:ring-indigo-500 focus:border-transparent">
                        @for ($y = date('Y'); $y >= 2020; $y--)
                            <option value="{{ $y }}" {{ request('year', $year) == $y ? 'selected' : '' }}>{{ $y }}</option>
                        @endfor
                    </select>
                </div>
                <div>
                    <label for="filter-employment-status" class="sr-only">Status Kepegawaian</label>
                    <select id="filter-employment-status" name="employment_status" class="w-full px-4 py-2.5 bg-white dark:bg-gray-700 border border-gray-200 dark:border-gray-600 rounded-xl text-sm text-gray-700 dark:text-gray-200 shadow-sm focus:ring-2 focus:ring-indigo-500 focus:border-transparent">
                        <option value="" {{ !request('employment_status') ? 'selected' : '' }}>Semua Status</option>
                        <option value="organik" {{ request('employment_status') == 'organik' ? 'selected' : '' }}>Organik</option>
                        <option value="freelance" {{ request('employment_status') == 'freelance' ? 'selected' : '' }}>Freelance</option>
                    </select>
                </div>
                <button type="submit" class="w-full px-5 py-2.5 bg-gradient-to-r from-indigo-600 to-purple-600 hover:from-indigo-700 hover:to-purple-700 text-white text-sm font-semibold rounded-xl shadow-sm transition-colors flex items-center justify-center gap-2">
                    <i class="fas fa-filter"></i>
                    <span>Terapkan</span>
                </button>
            </form>
        </div>
    </x-slot>

    <div class="py-8 px-4 sm:px-6 lg:px-8">
        <div class="max-w-7xl mx-auto space-y-8">

            {{-- Summary Cards --}}
            <div class="grid grid-cols-1 sm:grid-cols-2 xl:grid-cols-5 gap-5">
                <div class="relative overflow-hidden bg-gradient-to-br from-blue-600 to-indigo-700 rounded-2xl shadow-md hover:shadow-lg transition-all p-6">
                    <div class="absolute top-0 right-0 w-24 h-24 bg-white/10 rounded-full -mr-12 -mt-12"></div>
                    <div class="relative flex items-center justify-between">
                        <div>
                            <p class="text-blue-100 text-xs font-semibold uppercase tracking-wide">Total Karyawan</p>
                            <p class="text-white text-3xl font-bold mt-2">{{ $statsProduksi['total'] + $statsOffice['total'] }}</p>
                            <p class="text-blue-100 text-sm mt-1">Produksi &amp; office</p>
                        </div>
                        <div class="p-3 bg-white/20 rounded-full">
                            <i class="fas fa-users text-white text-xl"></i>
                        </div>
                    </div>
                </div>

                <div class="relative overflow-hidden bg-gradient-to-br from-emerald-500 to-teal-600 rounded-2xl shadow-md hover:shadow-lg transition-all p-6">
                    <div class="absolute top-0 right-0 w-24 h-24 bg-white/10 rounded-full -mr-12 -mt-12"></div>
                    <div class="relative">
                        <div class="flex items-center justify-between mb-3">
                            <div>
                                <p class="text-emerald-100 text-xs font-semibold uppercase tracking-wide">Hadir</p>
                                <p class="text-white text-3xl font-bold mt-1">{{ $totalHadir }}</p>
                            </div>
                            <div class="p-3 bg-white/20 rounded-full">
                                <i class="fas fa-check-circle text-white text-xl"></i>
                            </div>
                        </div>
                        <p class="text-emerald-100 text-sm">Bulan ini</p>
                    </div>
                </div>

                <div class="relative overflow-hidden bg-gradient-to-br from-amber-500 to-orange-600 rounded-2xl shadow-md hover:shadow-lg transition-all p-6">
                    <div class="absolute top-0 right-0 w-24 h-24 bg-white/10 rounded-full -mr-12 -mt-12"></div>
                    <div class="relative">
                        <div class="flex items-center justify-between mb-3">
                            <div>
                                <p class="text-amber-100 text-xs font-semibold uppercase tracking-wide">Izin</p>
                                <p class="text-white text-3xl font-bold mt-1">{{ $totalIzin }}</p>
                            </div>
                            <div class="p-3 bg-white/20 rounded-full">
                                <i class="fas fa-sticky-note text-white text-xl"></i>
                            </div>
                        </div>
                        <p class="text-amber-100 text-sm">Bulan ini</p>
                    </div>
                </div>

                <div class="relative overflow-hidden bg-gradient-to-br from-rose-500 to-pink-600 rounded-2xl shadow-md hover:shadow-lg transition-all p-6">
                    <div class="absolute top-0 right-0 w-24 h-24 bg-white/10 rounded-full -mr-12 -mt-12"></div>
                    <div class="relative">
                        <div class="flex items-center justify-between mb-3">
                            <div>
                                <p class="text-rose-100 text-xs font-semibold uppercase tracking-wide">Sakit</p>
                                <p class="text-white text-3xl font-bold mt-1">{{ $totalSakit }}</p>
                            </div>
                            <div class="p-3 bg-white/20 rounded-full">
                                <i class="fas fa-medkit text-white text-xl"></i>
                            </div>
                        </div>
                        <p class="text-rose-100 text-sm">Bulan ini</p>
                    </div>
                </div>

                <div class="relative overflow-hidden bg-gradient-to-br from-violet-500 to-purple-600 rounded-2xl shadow-md hover:shadow-lg transition-all p-6">
                    <div class="absolute top-0 right-0 w-24 h-24 bg-white/10 rounded-full -mr-12 -mt-12"></div>
                    <div class="relative">
                        <div class="flex items-center justify-between mb-3">
                            <div>
                                <p class="text-violet-100 text-xs font-semibold uppercase tracking-wide">Lembur</p>
                                <p class="text-white text-3xl font-bold mt-1">{{ $totalLembur }}</p>
                            </div>
                            <div class="p-3 bg-white/20 rounded-full">
                                <i class="fas fa-clock text-white text-xl"></i>
                            </div>
                        </div>
                        <p class="text-violet-100 text-sm">Bulan ini</p>
                    </div>
                </div>
            </div>

            {{-- Attendance Today Summary (Produksi vs Office) --}}
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <div class="bg-white dark:bg-gray-800 rounded-2xl shadow-sm border border-gray-200 dark:border-gray-700 p-6 flex items-center justify-between">
                    <div class="flex items-center gap-4">
                        <div class="p-4 bg-orange-100 dark:bg-orange-900/30 rounded-2xl">
                            <i class="fas fa-industry text-orange-600 dark:text-orange-400 text-2xl"></i>
                        </div>
                        <div>
                            <h3 class="text-sm font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">Kehadiran Produksi</h3>
                            <div class="flex items-baseline gap-2 mt-1">
                                <span class="text-3xl font-bold text-gray-800 dark:text-white">{{ $statsProduksi['hadir'] }}</span>
                                <span class="text-lg text-gray-500">/ {{ $statsProduksi['total'] }}</span>
                            </div>
                            <p class="text-xs text-gray-400 mt-1">Karyawan hadir hari ini</p>
                        </div>
                    </div>
                    <div class="hidden sm:block">
                        @php
                            $percentProduksi = $statsProduksi['total'] > 0 ? ($statsProduksi['hadir'] / $statsProduksi['total']) * 100 : 0;
                        @endphp
                        <div class="relative inline-flex items-center justify-center">
                            <svg class="w-20 h-20 transform -rotate-90">
                                <circle cx="40" cy="40" r="34" stroke="currentColor" stroke-width="8" fill="transparent" class="text-gray-200 dark:text-gray-700" />
                                <circle cx="40" cy="40" r="34" stroke="currentColor" stroke-width="8" fill="transparent" stroke-dasharray="{{ 2 * pi() * 34 }}" stroke-dashoffset="{{ (1 - $percentProduksi/100) * (2 * pi() * 34) }}" class="text-orange-500" stroke-linecap="round" />
                            </svg>
                            <span class="absolute text-sm font-bold text-gray-700 dark:text-gray-300">{{ round($percentProduksi) }}%</span>
                        </div>
                    </div>
                </div>

                <div class="bg-white dark:bg-gray-800 rounded-2xl shadow-sm border border-gray-200 dark:border-gray-700 p-6 flex items-center justify-between">
                    <div class="flex items-center gap-4">
                        <div class="p-4 bg-blue-100 dark:bg-blue-900/30 rounded-2xl">
                            <i class="fas fa-building text-blue-600 dark:text-blue-400 text-2xl"></i>
                        </div>
                        <div>
                            <h3 class="text-sm font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">Kehadiran Office</h3>
                            <div class="flex items-baseline gap-2 mt-1">
                                <span class="text-3xl font-bold text-gray-800 dark:text-white">{{ $statsOffice['hadir'] }}</span>
                                <span class="text-lg text-gray-500">/ {{ $statsOffice['total'] }}</span>
                            </div>
                            <p class="text-xs text-gray-400 mt-1">Karyawan hadir hari ini</p>
                        </div>
                    </div>
                    <div class="hidden sm:block">
                        @php
                            $percentOffice = $statsOffice['total'] > 0 ? ($statsOffice['hadir'] / $statsOffice['total']) * 100 : 0;
                        @endphp
                        <div class="relative inline-flex items-center justify-center">
                            <svg class="w-20 h-20 transform -rotate-90">
                                <circle cx="40" cy="40" r="34" stroke="currentColor" stroke-width="8" fill="transparent" class="text-gray-200 dark:text-gray-700" />
                                <circle cx="40" cy="40" r="34" stroke="currentColor" stroke-width="8" fill="transparent" stroke-dasharray="{{ 2 * pi() * 34 }}" stroke-dashoffset="{{ (1 - $percentOffice/100) * (2 * pi() * 34) }}" class="text-blue-500" stroke-linecap="round" />
                            </svg>
                            <span class="absolute text-sm font-bold text-gray-700 dark:text-gray-300">{{ round($percentOffice) }}%</span>
                        </div>
                    </div>
                </div>
            </div>

            {{-- Comparison Cards --}}
            @if($currentStatus === 'semua' && $comparison)
                <div class="bg-white dark:bg-gray-800 rounded-2xl shadow-sm border border-gray-200 dark:border-gray-700 p-5 sm:p-6">
                    <div class="flex items-center gap-3 mb-6">
                        <div class="p-3 bg-gradient-to-br from-indigo-500 to-purple-600 rounded-xl">
                            <i class="fas fa-balance-scale text-white text-lg"></i>
                        </div>
                        <div>
                            <h3 class="text-lg font-bold text-gray-800 dark:text-gray-100">Perbandingan Organik vs Freelance</h3>
                            <p class="text-sm text-gray-500 dark:text-gray-400">Statistik berdasarkan status kepegawaian</p>
                        </div>
                    </div>

                    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6">
                        {{-- Hadir --}}
                        <div class="bg-gradient-to-br from-emerald-50 to-teal-50 dark:from-emerald-900/20 dark:to-teal-900/20 rounded-xl p-5 border border-emerald-200 dark:border-emerald-700">
                            <div class="flex items-center gap-2 mb-4">
                                <i class="fas fa-check-circle text-emerald-600 text-xl"></i>
                                <h4 class="font-bold text-gray-800 dark:text-gray-100">Kehadiran</h4>
                            </div>
                            <div class="space-y-3">
                                <div class="flex justify-between items-center">
                                    <span class="text-sm text-gray-600 dark:text-gray-400">Organik:</span>
                                    <span class="font-bold text-emerald-700 dark:text-emerald-400">{{ $comparison['organik']['hadir'] }}</span>
                                </div>
                                <div class="flex justify-between items-center">
                                    <span class="text-sm text-gray-600 dark:text-gray-400">Freelance:</span>
                                    <span class="font-bold text-emerald-700 dark:text-emerald-400">{{ $comparison['freelance']['hadir'] }}</span>
                                </div>
                                <div class="pt-3 border-t border-emerald-200 dark:border-emerald-700">
                                    <span class="text-xs font-medium text-gray-500 dark:text-gray-400">
                                        @if($comparison['organik']['hadir'] > $comparison['freelance']['hadir'])
                                            <i class="fas fa-arrow-up text-emerald-600"></i> Organik lebih tinggi
                                        @elseif($comparison['organik']['hadir'] < $comparison['freelance']['hadir'])
                                            <i class="fas fa-arrow-up text-emerald-600"></i> Freelance lebih tinggi
                                        @else
                                            <i class="fas fa-equals text-gray-600"></i> Sama rata
                                        @endif
                                    </span>
                                </div>
                            </div>
                        </div>

                        {{-- Izin --}}
                        <div class="bg-gradient-to-br from-amber-50 to-orange-50 dark:from-amber-900/20 dark:to-orange-900/20 rounded-xl p-5 border border-amber-200 dark:border-amber-700">
                            <div class="flex items-center gap-2 mb-4">
                                <i class="fas fa-sticky-note text-amber-600 text-xl"></i>
                                <h4 class="font-bold text-gray-800 dark:text-gray-100">Izin</h4>
                            </div>
                            <div class="space-y-3">
                                <div class="flex justify-between items-center">
                                    <span class="text-sm text-gray-600 dark:text-gray-400">Organik:</span>
                                    <span class="font-bold text-amber-700 dark:text-amber-400">{{ $comparison['organik']['izin'] }}</span>
                                </div>
                                <div class="flex justify-between items-center">
                                    <span class="text-sm text-gray-600 dark:text-gray-400">Freelance:</span>
                                    <span class="font-bold text-amber-700 dark:text-amber-400">{{ $comparison['freelance']['izin'] }}</span>
                                </div>
                                <div class="pt-3 border-t border-amber-200 dark:border-amber-700">
                                    <span class="text-xs font-medium text-gray-500 dark:text-gray-400">
                                        @if($comparison['organik']['izin'] > $comparison['freelance']['izin'])
                                            <i class="fas fa-arrow-up text-amber-600"></i> Organik lebih tinggi
                                        @elseif($comparison['organik']['izin'] < $comparison['freelance']['izin'])
                                            <i class="fas fa-arrow-up text-amber-600"></i> Freelance lebih tinggi
                                        @else
                                            <i class="fas fa-equals text-gray-600"></i> Sama rata
                                        @endif
                                    </span>
                                </div>
                            </div>
                        </div>

                        {{-- Sakit --}}
                        <div class="bg-gradient-to-br from-rose-50 to-pink-50 dark:from-rose-900/20 dark:to-pink-900/20 rounded-xl p-5 border border-rose-200 dark:border-rose-700">
                            <div class="flex items-center gap-2 mb-4">
                                <i class="fas fa-medkit text-rose-600 text-xl"></i>
                                <h4 class="font-bold text-gray-800 dark:text-gray-100">Sakit</h4>
                            </div>
                            <div class="space-y-3">
                                <div class="flex justify-between items-center">
                                    <span class="text-sm text-gray-600 dark:text-gray-400">Organik:</span>
                                    <span class="font-bold text-rose-700 dark:text-rose-400">{{ $comparison['organik']['sakit'] }}</span>
                                </div>
                                <div class="flex justify-between items-center">
                                    <span class="text-sm text-gray-600 dark:text-gray-400">Freelance:</span>
                                    <span class="font-bold text-rose-700 dark:text-rose-400">{{ $comparison['freelance']['sakit'] }}</span>
                                </div>
                                <div class="pt-3 border-t border-rose-200 dark:border-rose-700">
                                    <span class="text-xs font-medium text-gray-500 dark:text-gray-400">
                                        @if($comparison['organik']['sakit'] > $comparison['freelance']['sakit'])
                                            <i class="fas fa-arrow-up text-rose-600"></i> Organik lebih tinggi
                                        @elseif($comparison['organik']['sakit'] < $comparison['freelance']['sakit'])
                                            <i class="fas fa-arrow-up text-rose-600"></i> Freelance lebih tinggi
                                        @else
                                            <i class="fas fa-equals text-gray-600"></i> Sama rata
                                        @endif
                                    </span>
                                </div>
                            </div>
                        </div>

                        {{-- Lembur --}}
                        <div class="bg-gradient-to-br from-violet-50 to-purple-50 dark:from-violet-900/20 dark:to-purple-900/20 rounded-xl p-5 border border-violet-200 dark:border-violet-700">
                            <div class="flex items-center gap-2 mb-4">
                                <i class="fas fa-clock text-violet-600 text-xl"></i>
                                <h4 class="font-bold text-gray-800 dark:text-gray-100">Lembur</h4>
                            </div>
                            <div class="space-y-3">
                                <div class="flex justify-between items-center">
                                    <span class="text-sm text-gray-600 dark:text-gray-400">Organik:</span>
                                    <span class="font-bold text-violet-700 dark:text-violet-400">{{ $comparison['organik']['lembur'] }}</span>
                                </div>
                                <div class="flex justify-between items-center">
                                    <span class="text-sm text-gray-600 dark:text-gray-400">Freelance:</span>
                                    <span class="font-bold text-violet-700 dark:text-violet-400">{{ $comparison['freelance']['lembur'] }}</span>
                                </div>
                                <div class="pt-3 border-t border-violet-200 dark:border-violet-700">
                                    <span class="text-xs font-medium text-gray-500 dark:text-gray-400">
                                        @if($comparison['organik']['lembur'] > $comparison['freelance']['lembur'])
                                            <i class="fas fa-arrow-up text-violet-600"></i> Organik lebih tinggi
                                        @elseif($comparison['organik']['lembur'] < $comparison['freelance']['lembur'])
                                            <i class="fas fa-arrow-up text-violet-600"></i> Freelance lebih tinggi
                                        @else
                                            <i class="fas fa-equals text-gray-600"></i> Sama rata
                                        @endif
                                    </span>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            @endif

            {{-- Charts --}}
            <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
                {{-- Statistik Bulanan --}}
                <div class="bg-white dark:bg-gray-800 rounded-2xl shadow-sm border border-gray-200 dark:border-gray-700 p-5 sm:p-6">
                    <div class="flex items-center gap-3 mb-6">
                        <div class="p-3 bg-gradient-to-br from-blue-500 to-indigo-600 rounded-xl">
                            <i class="fas fa-chart-bar text-white"></i>
                        </div>
                        <div>
                            <h3 class="text-lg font-bold text-gray-800 dark:text-gray-100">Statistik Bulanan</h3>
                            <p class="text-sm text-gray-500 dark:text-gray-400">Tahun {{ $year }}</p>
                        </div>
                    </div>
                    <div class="h-72 px-2 pt-2">
                        <canvas id="grafikBulananLine"></canvas>
                    </div>
                </div>

                {{-- Ringkasan Status --}}
                <div class="bg-white dark:bg-gray-800 rounded-2xl shadow-sm border border-gray-200 dark:border-gray-700 p-5 sm:p-6">
                    <div class="flex items-center gap-3 mb-6">
                        <div class="p-3 bg-gradient-to-br from-orange-500 to-red-600 rounded-xl">
                            <i class="fas fa-chart-pie text-white"></i>
                        </div>
                        <div>
                            <h3 class="text-lg font-bold text-gray-800 dark:text-gray-100">Ringkasan Status</h3>
                            <p class="text-sm text-gray-500 dark:text-gray-400">Bulan ini</p>
                        </div>
                    </div>
                    <div class="h-72 flex items-center justify-center">
                        <canvas id="grafikStatus"></canvas>
                    </div>
                </div>
            </div>

            {{-- 🔥 TODAY'S STATUS: 4 TABEL DENGAN ALPINE.JS TABS 🔥 --}}
            @if($currentStatus === 'semua')
                <div data-attendance-tabs class="space-y-6">
                    
                    {{-- Tab Navigation Pills --}}
                    <div class="flex flex-wrap items-center gap-2 bg-white/90 dark:bg-gray-800/90 backdrop-blur-xl p-3 rounded-2xl shadow-md border border-gray-200/50 dark:border-gray-700/50">
                        <button type="button" data-attendance-tab="semua" class="bg-indigo-600 text-white shadow-md px-4 py-2 rounded-xl text-sm font-bold transition-all flex items-center gap-2">
                            <i class="fas fa-th-large"></i>
                            <span>Semua Status</span>
                        </button>
                        <button type="button" data-attendance-tab="organik" class="bg-gray-100 dark:bg-gray-700 text-gray-700 dark:text-gray-300 hover:bg-gray-200 dark:hover:bg-gray-600 px-4 py-2 rounded-xl text-sm font-bold transition-all flex items-center gap-2">
                            <i class="fas fa-users-cog"></i>
                            <span>Organik</span>
                        </button>
                        <button type="button" data-attendance-tab="freelance" class="bg-gray-100 dark:bg-gray-700 text-gray-700 dark:text-gray-300 hover:bg-gray-200 dark:hover:bg-gray-600 px-4 py-2 rounded-xl text-sm font-bold transition-all flex items-center gap-2">
                            <i class="fas fa-users"></i>
                            <span>Freelance</span>
                        </button>
                        <button type="button" data-attendance-tab="borongan" class="bg-gray-100 dark:bg-gray-700 text-gray-700 dark:text-gray-300 hover:bg-gray-200 dark:hover:bg-gray-600 px-4 py-2 rounded-xl text-sm font-bold transition-all flex items-center gap-2">
                            <i class="fas fa-hammer"></i>
                            <span>Borongan</span>
                        </button>
                        <button type="button" data-attendance-tab="magang" class="bg-gray-100 dark:bg-gray-700 text-gray-700 dark:text-gray-300 hover:bg-gray-200 dark:hover:bg-gray-600 px-4 py-2 rounded-xl text-sm font-bold transition-all flex items-center gap-2">
                            <i class="fas fa-graduation-cap"></i>
                            <span>Magang</span>
                        </button>
                    </div>

                    {{-- Tabel Organik --}}
                    <div data-attendance-panel="organik" id="tabel-organik" class="bg-white dark:bg-gray-800 rounded-2xl shadow-sm border border-gray-200 dark:border-gray-700 p-5 sm:p-6">
                    <div class="flex items-center gap-3 mb-6">
                        <div class="p-3 bg-gradient-to-br from-emerald-500 to-teal-600 rounded-xl">
                            <i class="fas fa-users-cog text-white"></i>
                        </div>
                        <div>
                            <h3 class="text-lg font-bold text-gray-800 dark:text-gray-100">Status Karyawan Organik Hari Ini</h3>
                            <p class="text-sm text-gray-500 dark:text-gray-400">{{ \Carbon\Carbon::today()->format('d M Y') }}</p>
                        </div>
                    </div>

                    {{-- Form Pencarian Organik --}}
                    <form action="{{ url()->current() }}" method="GET" data-live-search="organik" class="mb-4">
                        <div class="flex items-center gap-2">
                            <div class="relative flex-grow">
                                <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                                    <i class="fas fa-search text-gray-400"></i>
                                </div>
                                <input type="search" name="search_organik" id="search_organik" class="block w-full pl-10 pr-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-indigo-500 focus:border-indigo-500 dark:bg-gray-700 dark:border-gray-600 dark:text-white" placeholder="Cari nama karyawan organik..." value="{{ request('search_organik') }}">
                            </div>
                            <button type="button" data-search-clear class="hidden items-center px-4 py-2 rounded-lg text-sm font-medium text-gray-600 dark:text-gray-300 bg-gray-100 dark:bg-gray-700 hover:bg-gray-200 dark:hover:bg-gray-600">
                                Hapus
                            </button>
                            @if(request('search_organik'))
                                <a href="{{ route('admin.absensi.index') }}" class="text-sm text-gray-500 hover:text-indigo-600 dark:text-gray-400 dark:hover:text-white">
                                    Reset
                                </a>
                            @endif
                        </div>
                    </form>

                    <div class="overflow-x-auto">
                        <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
                            <thead class="bg-gray-900">
                                <tr>
                                    <th class="px-6 py-3 text-left text-xs font-bold text-gray-100 uppercase tracking-wider">Karyawan</th>
                                    <th class="px-6 py-3 text-left text-xs font-bold text-gray-100 uppercase tracking-wider">Status</th>
                                    <th class="px-6 py-3 text-left text-xs font-bold text-gray-100 uppercase tracking-wider">Check-in</th>
                                    <th class="px-6 py-3 text-left text-xs font-bold text-gray-100 uppercase tracking-wider">Keterlambatan</th>
                                    <th class="px-6 py-3 text-left text-xs font-bold text-gray-100 uppercase tracking-wider">Check-out</th>
                                    <th class="px-6 py-3 text-left text-xs font-bold text-gray-100 uppercase tracking-wider">Detail</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-100 dark:divide-gray-700 [&>tr:nth-child(even)]:bg-gray-50 dark:[&>tr:nth-child(even)]:bg-gray-700/30">
                                @forelse ($dailyStatusesOrganik as $daily)
                                    <x-admin.absensi-row :daily="$daily" type="organik" />
                                @empty
                                    <tr data-empty-state>
                                        <td colspan="6" class="text-center py-12">
                                            <div class="flex flex-col items-center">
                                                <i class="fas fa-calendar-times text-4xl text-gray-400 mb-3"></i>
                                                @if(request('search_organik'))
                                                    <p class="text-gray-500">Karyawan organik dengan nama "<strong class="text-indigo-600">{{ request('search_organik') }}</strong>" tidak ditemukan.</p>
                                                @else
                                                    <p class="text-gray-500">Tidak ada karyawan organik hari ini</p>
                                                @endif
                                            </div>
                                        </td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>

                {{-- Tabel Freelance --}}
                <div data-attendance-panel="freelance" id="tabel-freelance" class="bg-white dark:bg-gray-800 rounded-2xl shadow-sm border border-gray-200 dark:border-gray-700 p-5 sm:p-6">
                    <div class="flex items-center gap-3 mb-6">
                        <div class="p-3 bg-gradient-to-br from-cyan-500 to-blue-600 rounded-xl">
                            <i class="fas fa-users text-white"></i>
                        </div>
                        <div>
                            <h3 class="text-lg font-bold text-gray-800 dark:text-gray-100">Status Karyawan Freelance Hari Ini</h3>
                            <p class="text-sm text-gray-500 dark:text-gray-400">{{ \Carbon\Carbon::today()->format('d M Y') }}</p>
                        </div>
                    </div>

                    {{-- Form Pencarian Freelance --}}
                    <form action="{{ url()->current() }}" method="GET" data-live-search="freelance" class="mb-4">
                        <div class="flex items-center gap-2">
                            <div class="relative flex-grow">
                                <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                                    <i class="fas fa-search text-gray-400"></i>
                                </div>
                                <input type="search" name="search_freelance" id="search_freelance" class="block w-full pl-10 pr-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-indigo-500 focus:border-indigo-500 dark:bg-gray-700 dark:border-gray-600 dark:text-white" placeholder="Cari nama freelance..." value="{{ request('search_freelance') }}">
                            </div>
                            <button type="button" data-search-clear class="hidden items-center px-4 py-2 rounded-lg text-sm font-medium text-gray-600 dark:text-gray-300 bg-gray-100 dark:bg-gray-700 hover:bg-gray-200 dark:hover:bg-gray-600">
                                Hapus
                            </button>
                            @if(request('search_freelance'))
                                <a href="{{ route('admin.absensi.index') }}" class="text-sm text-gray-500 hover:text-indigo-600 dark:text-gray-400 dark:hover:text-white">
                                    Reset
                                </a>
                            @endif
                        </div>
                    </form>

                    <div class="overflow-x-auto">
                        <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
                            <thead class="bg-gray-900">
                                <tr>
                                    <th class="px-6 py-3 text-left text-xs font-bold text-gray-100 uppercase tracking-wider">Karyawan</th>
                                    <th class="px-6 py-3 text-left text-xs font-bold text-gray-100 uppercase tracking-wider">Status</th>
                                    <th class="px-6 py-3 text-left text-xs font-bold text-gray-100 uppercase tracking-wider">Check-in</th>
                                    <th class="px-6 py-3 text-left text-xs font-bold text-gray-100 uppercase tracking-wider">Keterlambatan</th>
                                    <th class="px-6 py-3 text-left text-xs font-bold text-gray-100 uppercase tracking-wider">Check-out</th>
                                    <th class="px-6 py-3 text-left text-xs font-bold text-gray-100 uppercase tracking-wider">Detail</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-100 dark:divide-gray-700 [&>tr:nth-child(even)]:bg-gray-50 dark:[&>tr:nth-child(even)]:bg-gray-700/30">
                                @forelse ($dailyStatusesFreelance as $daily)
                                    <x-admin.absensi-row :daily="$daily" type="freelance" />
                                @empty
                                    <tr data-empty-state>
                                        <td colspan="6" class="text-center py-12">
                                            <div class="flex flex-col items-center">
                                                <i class="fas fa-calendar-times text-4xl text-gray-400 mb-3"></i>
                                                @if(request('search_freelance'))
                                                    <p class="text-gray-500">Karyawan freelance dengan nama "<strong class="text-indigo-600">{{ request('search_freelance') }}</strong>" tidak ditemukan.</p>
                                                @else
                                                    <p class="text-gray-500">Tidak ada karyawan freelance hari ini</p>
                                                @endif
                                            </div>
                                        </td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>

                {{-- Tabel Borongan --}}
                <div data-attendance-panel="borongan" id="tabel-borongan" class="bg-white dark:bg-gray-800 rounded-2xl shadow-sm border border-gray-200 dark:border-gray-700 p-5 sm:p-6">
                    <div class="flex items-center gap-3 mb-6">
                        <div class="p-3 bg-gradient-to-br from-purple-500 to-pink-600 rounded-xl">
                            <i class="fas fa-hammer text-white"></i>
                        </div>
                        <div>
                            <h3 class="text-lg font-bold text-gray-800 dark:text-gray-100">Status Karyawan Borongan Hari Ini</h3>
                            <p class="text-sm text-gray-500 dark:text-gray-400">{{ \Carbon\Carbon::today()->format('d M Y') }}</p>
                        </div>
                    </div>

                    {{-- Form Pencarian Borongan --}}
                    <form action="{{ url()->current() }}" method="GET" data-live-search="borongan" class="mb-4">
                        <div class="flex items-center gap-2">
                            <div class="relative flex-grow">
                                <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                                    <i class="fas fa-search text-gray-400"></i>
                                </div>
                                <input type="search" name="search_borongan" id="search_borongan" class="block w-full pl-10 pr-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-indigo-500 focus:border-indigo-500 dark:bg-gray-700 dark:border-gray-600 dark:text-white" placeholder="Cari nama karyawan borongan..." value="{{ request('search_borongan') }}">
                            </div>
                            <button type="button" data-search-clear class="hidden items-center px-4 py-2 rounded-lg text-sm font-medium text-gray-600 dark:text-gray-300 bg-gray-100 dark:bg-gray-700 hover:bg-gray-200 dark:hover:bg-gray-600">
                                Hapus
                            </button>
                            @if(request('search_borongan'))
                                <a href="{{ route('admin.absensi.index') }}" class="text-sm text-gray-500 hover:text-indigo-600 dark:text-gray-400 dark:hover:text-white">
                                    Reset
                                </a>
                            @endif
                        </div>
                    </form>

                    <div class="overflow-x-auto">
                        <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
                            <thead class="bg-gray-900">
                                <tr>
                                    <th class="px-6 py-3 text-left text-xs font-bold text-gray-100 uppercase tracking-wider">Karyawan</th>
                                    <th class="px-6 py-3 text-left text-xs font-bold text-gray-100 uppercase tracking-wider">Status</th>
                                    <th class="px-6 py-3 text-left text-xs font-bold text-gray-100 uppercase tracking-wider">Check-in</th>
                                    <th class="px-6 py-3 text-left text-xs font-bold text-gray-100 uppercase tracking-wider">Keterlambatan</th>
                                    <th class="px-6 py-3 text-left text-xs font-bold text-gray-100 uppercase tracking-wider">Check-out</th>
                                    <th class="px-6 py-3 text-left text-xs font-bold text-gray-100 uppercase tracking-wider">Detail</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-100 dark:divide-gray-700 [&>tr:nth-child(even)]:bg-gray-50 dark:[&>tr:nth-child(even)]:bg-gray-700/30">
                                @forelse ($dailyStatusesBorongan as $daily)
                                    <x-admin.absensi-row :daily="$daily" type="borongan" />
                                @empty
                                    <tr data-empty-state>
                                        <td colspan="6" class="text-center py-12">
                                            <div class="flex flex-col items-center">
                                                <i class="fas fa-calendar-times text-4xl text-gray-400 mb-3"></i>
                                                @if(request('search_borongan'))
                                                    <p class="text-gray-500">Karyawan borongan dengan nama "<strong class="text-indigo-600">{{ request('search_borongan') }}</strong>" tidak ditemukan.</p>
                                                @else
                                                    <p class="text-gray-500">Tidak ada karyawan borongan hari ini</p>
                                                @endif
                                            </div>
                                        </td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>

                {{-- Tabel Magang --}}
                <div data-attendance-panel="magang" id="tabel-magang" class="bg-white dark:bg-gray-800 rounded-2xl shadow-sm border border-gray-200 dark:border-gray-700 p-5 sm:p-6">
                    <div class="flex items-center gap-3 mb-6">
                        <div class="p-3 bg-gradient-to-br from-sky-500 to-indigo-600 rounded-xl">
                            <i class="fas fa-graduation-cap text-white"></i>
                        </div>
                        <div>
                            <h3 class="text-lg font-bold text-gray-800 dark:text-gray-100">Status Karyawan Magang Hari Ini</h3>
                            <p class="text-sm text-gray-500 dark:text-gray-400">{{ \Carbon\Carbon::today()->format('d M Y') }}</p>
                        </div>
                    </div>

                    {{-- Form Pencarian Magang --}}
                    <form action="{{ url()->current() }}" method="GET" data-live-search="magang" class="mb-4">
                        <div class="flex items-center gap-2">
                            <div class="relative flex-grow">
                                <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                                    <i class="fas fa-search text-gray-400"></i>
                                </div>
                                <input type="search" name="search_magang" id="search_magang" class="block w-full pl-10 pr-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-indigo-500 focus:border-indigo-500 dark:bg-gray-700 dark:border-gray-600 dark:text-white" placeholder="Cari nama karyawan magang..." value="{{ request('search_magang') }}">
                            </div>
                            <button type="button" data-search-clear class="hidden items-center px-4 py-2 rounded-lg text-sm font-medium text-gray-600 dark:text-gray-300 bg-gray-100 dark:bg-gray-700 hover:bg-gray-200 dark:hover:bg-gray-600">
                                Hapus
                            </button>
                            @if(request('search_magang'))
                                <a href="{{ route('admin.absensi.index') }}" class="text-sm text-gray-500 hover:text-indigo-600 dark:text-gray-400 dark:hover:text-white">
                                    Reset
                                </a>
                            @endif
                        </div>
                    </form>

                    <div class="overflow-x-auto">
                        <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
                            <thead class="bg-gray-900">
                                <tr>
                                    <th class="px-6 py-3 text-left text-xs font-bold text-gray-100 uppercase tracking-wider">Karyawan</th>
                                    <th class="px-6 py-3 text-left text-xs font-bold text-gray-100 uppercase tracking-wider">Status</th>
                                    <th class="px-6 py-3 text-left text-xs font-bold text-gray-100 uppercase tracking-wider">Check-in</th>
                                    <th class="px-6 py-3 text-left text-xs font-bold text-gray-100 uppercase tracking-wider">Keterlambatan</th>
                                    <th class="px-6 py-3 text-left text-xs font-bold text-gray-100 uppercase tracking-wider">Check-out</th>
                                    <th class="px-6 py-3 text-left text-xs font-bold text-gray-100 uppercase tracking-wider">Detail</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-100 dark:divide-gray-700 [&>tr:nth-child(even)]:bg-gray-50 dark:[&>tr:nth-child(even)]:bg-gray-700/30">
                                @forelse ($dailyStatusesMagang as $daily)
                                    <x-admin.absensi-row :daily="$daily" type="magang" />
                                @empty
                                    <tr data-empty-state>
                                        <td colspan="6" class="text-center py-12">
                                            <div class="flex flex-col items-center">
                                                <i class="fas fa-calendar-times text-4xl text-gray-400 mb-3"></i>
                                                @if(request('search_magang'))
                                                    <p class="text-gray-500">Karyawan magang dengan nama "<strong class="text-indigo-600">{{ request('search_magang') }}</strong>" tidak ditemukan.</p>
                                                @else
                                                    <p class="text-gray-500">Tidak ada karyawan magang hari ini</p>
                                                @endif
                                            </div>
                                        </td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
            @else
                {{-- Tabel Single (Kalo Filter Dipilih) --}}
                <div class="bg-white dark:bg-gray-800 rounded-2xl shadow-sm border border-gray-200 dark:border-gray-700 p-5 sm:p-6">
                    <div class="flex items-center gap-3 mb-6">
                        <div class="p-3 bg-gradient-to-br from-cyan-500 to-blue-600 rounded-xl">
                            <i class="fas fa-users-cog text-white"></i>
                        </div>
                        <div>
                            <h3 class="text-lg font-bold text-gray-800 dark:text-gray-100">Status Absensi Hari Ini</h3>
                            <p class="text-sm text-gray-500 dark:text-gray-400">{{ \Carbon\Carbon::today()->format('d M Y') }}</p>
                        </div>
                    </div>

                    <div class="overflow-x-auto">
                        <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
                            <thead class="bg-gray-900">
                                <tr>
                                    <th class="px-6 py-3 text-left text-xs font-bold text-gray-100 uppercase tracking-wider">Karyawan</th>
                                    <th class="px-6 py-3 text-left text-xs font-bold text-gray-100 uppercase tracking-wider">Status</th>
                                    <th class="px-6 py-3 text-left text-xs font-bold text-gray-100 uppercase tracking-wider">Check-in</th>
                                    <th class="px-6 py-3 text-left text-xs font-bold text-gray-100 uppercase tracking-wider">Keterlambatan</th>
                                    <th class="px-6 py-3 text-left text-xs font-bold text-gray-100 uppercase tracking-wider">Check-out</th>
                                    <th class="px-6 py-3 text-left text-xs font-bold text-gray-100 uppercase tracking-wider">Detail</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-100 dark:divide-gray-700 [&>tr:nth-child(even)]:bg-gray-50 dark:[&>tr:nth-child(even)]:bg-gray-700/30">
                                @forelse ($dailyStatuses as $daily)
                                    <tr class="hover:bg-gray-50 dark:hover:bg-gray-700/50 transition-colors">
                                        <td class="px-6 py-4 whitespace-nowrap">
                                            <a href="{{ route('admin.absensi.user', $daily['user']->id) }}" class="block group">
                                                <div class="font-semibold text-gray-900 dark:text-gray-100 group-hover:text-indigo-600">
                                                    {{ $daily['user']->name }}
                                                </div>
                                                <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-gray-100 dark:bg-gray-800 text-gray-600 dark:text-gray-400 mt-1">
                                                    {{ ucfirst($daily['user']->employment_type ?? '-') }}
                                                </span>
                                            </a>
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap">
                                            <span class="px-3 py-1 rounded-full text-xs font-bold inline-flex items-center gap-1
                                                @if(str_contains($daily['status'], 'Belum')) bg-gray-100 text-gray-700 dark:bg-gray-700 dark:text-gray-200
                                                @elseif(str_contains($daily['status'], 'Hadir')) bg-emerald-100 text-emerald-800 dark:bg-emerald-900/50 dark:text-emerald-200
                                                @elseif(str_contains($daily['status'], 'Izin')) bg-yellow-100 text-yellow-800 dark:bg-yellow-900/50 dark:text-yellow-200
                                                @elseif(str_contains($daily['status'], 'Sakit')) bg-red-100 text-red-800 dark:bg-red-900/50 dark:text-red-200
                                                @elseif(str_contains($daily['status'], 'Pending')) bg-orange-100 text-orange-800 dark:bg-orange-900/50 dark:text-orange-200
                                                @endif">
                                                {{ $daily['status'] }}
                                            </span>
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap">
                                            @if($daily['check_in_time'])
                                                <div class="flex items-center gap-2">
                                                    <i class="fas fa-sign-in-alt text-green-600 text-xs"></i>
                                                    <span class="text-sm font-medium text-gray-700 dark:text-gray-300">
                                                        {{ \Carbon\Carbon::parse($daily['check_in_time'])->format('H:i') }}
                                                    </span>
                                                </div>
                                            @else
                                                <span class="text-gray-400 text-sm">-</span>
                                            @endif
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap">
                                            @php
                                                $lateMinutes = $daily['late_minutes'] ?? 0;
                                            @endphp
                                            @if($lateMinutes > 0)
                                                <span class="inline-flex items-center gap-1 px-3 py-1 rounded-full text-xs font-bold bg-amber-100 text-amber-800 dark:bg-amber-900 dark:text-amber-200">
                                                    <i class="fas fa-clock"></i>
                                                    @if($lateMinutes < 60)
                                                        {{ $lateMinutes }} menit
                                                    @else
                                                        @php
                                                            $hours = floor($lateMinutes / 60);
                                                            $mins = $lateMinutes % 60;
                                                        @endphp
                                                        {{ $hours }} jam {{ $mins > 0 ? $mins . ' menit' : '' }}
                                                    @endif
                                                </span>
                                            @else
                                                <span class="text-green-600 dark:text-green-400 text-sm font-semibold">✓ Tepat waktu</span>
                                            @endif
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap">
                                            @if($daily['check_out_time'])
                                                <div class="flex items-center gap-2">
                                                    <i class="fas fa-sign-out-alt text-red-600 text-xs"></i>
                                                    <span class="text-sm font-medium text-gray-700 dark:text-gray-300">
                                                        {{ \Carbon\Carbon::parse($daily['check_out_time'])->format('H:i') }}
                                                    </span>
                                                </div>
                                            @else
                                                <span class="text-gray-400 text-sm">-</span>
                                            @endif
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap">
                                            <a href="{{ route('admin.absensi.user', $daily['user']->id) }}" class="inline-flex items-center gap-2 px-4 py-2 bg-indigo-100 text-indigo-700 rounded-lg text-sm font-medium hover:bg-indigo-200 transition-all">
                                                <i class="fas fa-eye"></i>
                                                <span>Detail</span>
                                            </a>
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="6" class="text-center py-12">
                                            <div class="flex flex-col items-center">
                                                <i class="fas fa-calendar-times text-4xl text-gray-400 mb-3"></i>
                                                <p class="text-gray-500">Tidak ada data absensi hari ini</p>
                                            </div>
                                        </td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
            @endif

        </div>
    </div>

    <script nonce="{{ config('app.csp_nonce') }}" src="https://cdn.jsdelivr.net/npm/chart.js@4.4.7/dist/chart.umd.min.js"></script>
    <script nonce="{{ config('app.csp_nonce') }}">
        document.addEventListener('DOMContentLoaded', () => {
            const tabButtons = document.querySelectorAll('[data-attendance-tab]');
            const tabPanels = document.querySelectorAll('[data-attendance-panel]');

            tabButtons.forEach((button) => {
                button.addEventListener('click', () => {
                    const activeTab = button.dataset.attendanceTab;

                    tabButtons.forEach((tab) => {
                        const selected = tab === button;
                        tab.classList.toggle('bg-indigo-600', selected && activeTab === 'semua');
                        tab.classList.toggle('bg-emerald-600', selected && activeTab === 'organik');
                        tab.classList.toggle('bg-cyan-600', selected && activeTab === 'freelance');
                        tab.classList.toggle('bg-purple-600', selected && activeTab === 'borongan');
                        tab.classList.toggle('bg-sky-600', selected && activeTab === 'magang');
                        tab.classList.toggle('text-white', selected);
                        tab.classList.toggle('shadow-md', selected);
                        tab.classList.toggle('bg-gray-100', !selected);
                        tab.classList.toggle('dark:bg-gray-700', !selected);
                        tab.classList.toggle('text-gray-700', !selected);
                        tab.classList.toggle('dark:text-gray-300', !selected);
                    });

                    tabPanels.forEach((panel) => {
                        panel.classList.toggle('hidden', activeTab !== 'semua' && panel.dataset.attendancePanel !== activeTab);
                    });
                });
            });

            document.querySelectorAll('[data-live-search]').forEach((form) => {
                const category = form.dataset.liveSearch;
                const input = form.querySelector('input[type="search"]');
                const clearButton = form.querySelector('[data-search-clear]');
                const panel = document.getElementById(`tabel-${category}`);
                const tbody = panel ? panel.querySelector('tbody') : null;

                if (!input || !tbody) {
                    return;
                }

                const filterRows = () => {
                    const query = input.value.trim().toLocaleLowerCase();
                    const rows = Array.from(tbody.querySelectorAll('tr:not([data-empty-state]):not([data-live-empty])'));
                    const emptyState = tbody.querySelector('[data-empty-state]');
                    let visibleRows = 0;

                    rows.forEach((row) => {
                        const matches = row.textContent.toLocaleLowerCase().includes(query);
                        row.classList.toggle('hidden', !matches);
                        if (matches) {
                            visibleRows++;
                        }
                    });

                    if (emptyState) {
                        emptyState.classList.toggle('hidden', query.length > 0);
                    }

                    let noResults = tbody.querySelector('[data-live-empty]');
                    if (!noResults && query.length > 0) {
                        noResults = document.createElement('tr');
                        noResults.dataset.liveEmpty = '';
                        const cell = document.createElement('td');
                        cell.colSpan = 6;
                        cell.className = 'text-center py-8 text-sm text-gray-500 dark:text-gray-400';
                        cell.textContent = 'Tidak ada karyawan yang cocok dengan pencarian.';
                        noResults.appendChild(cell);
                        tbody.appendChild(noResults);
                    }
                    if (noResults) {
                        noResults.classList.toggle('hidden', query.length === 0 || visibleRows > 0);
                    }

                    if (clearButton) {
                        clearButton.classList.toggle('hidden', query.length === 0);
                        clearButton.classList.toggle('inline-flex', query.length > 0);
                    }
                };

                form.addEventListener('submit', (event) => event.preventDefault());
                input.addEventListener('input', filterRows);
                clearButton?.addEventListener('click', () => {
                    input.value = '';
                    filterRows();
                    input.focus();
                });
                filterRows();
            });

            const isDark = document.documentElement.classList.contains('dark');

            Chart.defaults.color = isDark ? '#cbd5e0' : '#4b5563';
            Chart.defaults.borderColor = isDark ? '#4a5568' : '#e5e7eb';

            new Chart(document.getElementById('grafikBulananLine'), {
                type: 'bar',
                data: {
                    labels: [@for ($i = 1; $i <= 12; $i++) "{{ \Carbon\Carbon::createFromFormat('!m', $i)->translatedFormat('M') }}", @endfor],
                    datasets: [
                        {
                            label: 'Organik',
                            data: [@for ($i = 1; $i <= 12; $i++) {{ $grafikBulananOrganik[$i] ?? 0 }}, @endfor],
                            backgroundColor: [
                                '#2563eb', '#4f46e5', '#7c3aed', '#9333ea', '#c026d3', '#db2777',
                                '#e11d48', '#ea580c', '#d97706', '#65a30d', '#059669', '#0891b2'
                            ],
                            borderRadius: 6,
                            borderSkipped: false,
                        },
                        {
                            label: 'Freelance',
                            data: [@for ($i = 1; $i <= 12; $i++) {{ $grafikBulananFreelance[$i] ?? 0 }}, @endfor],
                            backgroundColor: [
                                '#60a5fa', '#818cf8', '#a78bfa', '#c084fc', '#e879f9', '#f472b6',
                                '#fb7185', '#fb923c', '#fbbf24', '#a3e635', '#34d399', '#22d3ee'
                            ],
                            borderRadius: 6,
                            borderSkipped: false,
                        }
                    ]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: {
                        legend: {
                            display: true,
                            labels: {
                                color: isDark ? '#cbd5e0' : '#4b5563',
                                font: { weight: 'bold' }
                            }
                        }
                    },
                    scales: {
                        x: {
                            grid: { display: false },
                            ticks: { color: isDark ? '#cbd5e0' : '#4b5563', font: { weight: 'bold' } }
                        },
                        y: {
                            beginAtZero: true,
                            grid: { color: isDark ? 'rgba(100,116,139,0.15)' : 'rgba(100,116,139,0.07)' },
                            ticks: { color: isDark ? '#cbd5e0' : '#4b5563' }
                        }
                    }
                }
            });

            const centerTextPlugin = {
                id: 'attendanceCenterText',
                afterDraw(chart) {
                    const { ctx, chartArea } = chart;
                    if (!chartArea) {
                        return;
                    }

                    const total = [{{ $totalHadir }}, {{ $totalIzin }}, {{ $totalSakit }}, {{ $totalLembur }}]
                        .reduce((sum, value) => sum + value, 0);
                    const centerX = (chartArea.left + chartArea.right) / 2;
                    const centerY = (chartArea.top + chartArea.bottom) / 2;

                    ctx.save();
                    ctx.textAlign = 'center';
                    ctx.textBaseline = 'middle';
                    ctx.fillStyle = isDark ? '#f9fafb' : '#111827';
                    ctx.font = '700 24px sans-serif';
                    ctx.fillText(total.toLocaleString(), centerX, centerY - 8);
                    ctx.fillStyle = isDark ? '#9ca3af' : '#6b7280';
                    ctx.font = '500 12px sans-serif';
                    ctx.fillText('Total', centerX, centerY + 16);
                    ctx.restore();
                }
            };

            new Chart(document.getElementById('grafikStatus'), {
                type: 'doughnut',
                plugins: [centerTextPlugin],
                data: {
                    labels: ['Hadir', 'Izin', 'Sakit', 'Lembur'],
                    datasets: [{
                        data: [{{ $totalHadir }}, {{ $totalIzin }}, {{ $totalSakit }}, {{ $totalLembur }}],
                        backgroundColor: [
                            'rgba(16,185,129,0.95)',
                            'rgba(245,158,11,0.95)',
                            'rgba(239,68,68,0.95)',
                            'rgba(168,85,247,0.95)'
                        ],
                        borderWidth: 2
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: {
                        legend: {
                            position: 'right',
                            labels: {
                                color: isDark ? '#cbd5e0' : '#4b5563',
                                font: { weight: 'bold' }
                            }
                        }
                    },
                    cutout: '72%'
                }
            });
        });
    </script>
</x-app-layout>
