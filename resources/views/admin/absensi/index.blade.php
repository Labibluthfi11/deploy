{{-- resources/views/admin/absensi/index.blade.php --}}
<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center justify-between">
            <div>
                <h2 class="text-2xl font-bold text-gray-800 dark:text-white">
                    {{ $dashboardTitle ?? 'Dashboard Absensi Karyawan' }}
                </h2>
                <p class="text-sm text-gray-500 dark:text-gray-400 mt-1">
                    Kelola dan pantau kehadiran karyawan secara real-time
                </p>
            </div>
        </div>
    </x-slot>

    <div class="py-8 px-4 sm:px-6 lg:px-8">
        <div class="max-w-7xl mx-auto space-y-8">

            {{-- Filter Section --}}
            <div class="bg-white/90 dark:bg-gray-800/90 backdrop-blur-xl rounded-2xl shadow-lg border border-gray-200/50 dark:border-gray-700/50 p-6">
                <div class="flex items-center gap-3 mb-6">
                    <div class="p-3 bg-gradient-to-br from-indigo-500 to-purple-600 rounded-xl">
                        <i class="fas fa-filter text-white text-lg"></i>
                    </div>
                    <div>
                        <h3 class="text-lg font-bold text-gray-800 dark:text-gray-100">Filter Statistik</h3>
                        <p class="text-sm text-gray-500 dark:text-gray-400">Sesuaikan periode statistik</p>
                    </div>
                </div>

                <form method="GET" class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Bulan</label>
                        <select name="month" class="w-full px-4 py-2.5 bg-white dark:bg-gray-700 border border-gray-300 dark:border-gray-600 rounded-xl focus:ring-2 focus:ring-indigo-500 focus:border-transparent">
                            @for ($m = 1; $m <= 12; $m++)
                                <option value="{{ $m }}" {{ request('month', $month) == $m ? 'selected' : '' }}>
                                    {{ \Carbon\Carbon::createFromFormat('!m', $m)->translatedFormat('F') }}
                                </option>
                            @endfor
                        </select>
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Tahun</label>
                        <select name="year" class="w-full px-4 py-2.5 bg-white dark:bg-gray-700 border border-gray-300 dark:border-gray-600 rounded-xl focus:ring-2 focus:ring-indigo-500 focus:border-transparent">
                            @for ($y = date('Y'); $y >= 2020; $y--)
                                <option value="{{ $y }}" {{ request('year', $year) == $y ? 'selected' : '' }}>{{ $y }}</option>
                            @endfor
                        </select>
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Status Kepegawaian</label>
                        <select name="employment_status" class="w-full px-4 py-2.5 bg-white dark:bg-gray-700 border border-gray-300 dark:border-gray-600 rounded-xl focus:ring-2 focus:ring-indigo-500 focus:border-transparent">
                            <option value="" {{ !request('employment_status') ? 'selected' : '' }}>Semua Status</option>
                            <option value="organik" {{ request('employment_status') == 'organik' ? 'selected' : '' }}>Organik</option>
                            <option value="freelance" {{ request('employment_status') == 'freelance' ? 'selected' : '' }}>Freelance</option>
                        </select>
                    </div>

                    <div class="flex items-end">
                        <button type="submit" class="w-full px-6 py-2.5 bg-gradient-to-r from-indigo-600 to-purple-600 hover:from-indigo-700 hover:to-purple-700 text-white font-semibold rounded-xl shadow-md hover:shadow-lg transition-all transform hover:scale-105 flex items-center justify-center gap-2">
                            <i class="fas fa-search"></i>
                            <span>Terapkan</span>
                        </button>
                    </div>
                </form>
            </div>

            {{-- Summary Cards --}}
            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-6">
                <div class="relative overflow-hidden bg-gradient-to-br from-emerald-500 to-teal-600 rounded-2xl shadow-lg hover:shadow-xl transition-all p-6">
                    <div class="absolute top-0 right-0 w-24 h-24 bg-white/10 rounded-full -mr-12 -mt-12"></div>
                    <div class="relative">
                        <div class="flex items-center justify-between mb-3">
                            <div>
                                <p class="text-emerald-100 text-xs font-medium uppercase">Total Kehadiran</p>
                                <p class="text-white text-3xl font-bold mt-1">{{ $totalHadir }}</p>
                            </div>
                            <div class="p-3 bg-white/20 rounded-xl">
                                <i class="fas fa-check-circle text-white text-xl"></i>
                            </div>
                        </div>
                        <p class="text-emerald-100 text-sm">Bulan ini</p>
                    </div>
                </div>

                <div class="relative overflow-hidden bg-gradient-to-br from-amber-500 to-orange-600 rounded-2xl shadow-lg hover:shadow-xl transition-all p-6">
                    <div class="absolute top-0 right-0 w-24 h-24 bg-white/10 rounded-full -mr-12 -mt-12"></div>
                    <div class="relative">
                        <div class="flex items-center justify-between mb-3">
                            <div>
                                <p class="text-amber-100 text-xs font-medium uppercase">Total Izin</p>
                                <p class="text-white text-3xl font-bold mt-1">{{ $totalIzin }}</p>
                            </div>
                            <div class="p-3 bg-white/20 rounded-xl">
                                <i class="fas fa-sticky-note text-white text-xl"></i>
                            </div>
                        </div>
                        <p class="text-amber-100 text-sm">Bulan ini</p>
                    </div>
                </div>

                <div class="relative overflow-hidden bg-gradient-to-br from-rose-500 to-pink-600 rounded-2xl shadow-lg hover:shadow-xl transition-all p-6">
                    <div class="absolute top-0 right-0 w-24 h-24 bg-white/10 rounded-full -mr-12 -mt-12"></div>
                    <div class="relative">
                        <div class="flex items-center justify-between mb-3">
                            <div>
                                <p class="text-rose-100 text-xs font-medium uppercase">Total Sakit</p>
                                <p class="text-white text-3xl font-bold mt-1">{{ $totalSakit }}</p>
                            </div>
                            <div class="p-3 bg-white/20 rounded-xl">
                                <i class="fas fa-medkit text-white text-xl"></i>
                            </div>
                        </div>
                        <p class="text-rose-100 text-sm">Bulan ini</p>
                    </div>
                </div>

                <div class="relative overflow-hidden bg-gradient-to-br from-violet-500 to-purple-600 rounded-2xl shadow-lg hover:shadow-xl transition-all p-6">
                    <div class="absolute top-0 right-0 w-24 h-24 bg-white/10 rounded-full -mr-12 -mt-12"></div>
                    <div class="relative">
                        <div class="flex items-center justify-between mb-3">
                            <div>
                                <p class="text-violet-100 text-xs font-medium uppercase">Total Lembur</p>
                                <p class="text-white text-3xl font-bold mt-1">{{ $totalLembur }}</p>
                            </div>
                            <div class="p-3 bg-white/20 rounded-xl">
                                <i class="fas fa-clock text-white text-xl"></i>
                            </div>
                        </div>
                        <p class="text-violet-100 text-sm">Bulan ini</p>
                    </div>
                </div>
            </div>

            {{-- Comparison Cards --}}
            @if($currentStatus === 'semua' && $comparison)
                <div class="bg-white/90 dark:bg-gray-800/90 backdrop-blur-xl rounded-2xl shadow-lg border border-gray-200/50 dark:border-gray-700/50 p-6">
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
            <div class="grid grid-cols-1 lg:grid-cols-2 gap-8">
                {{-- Statistik Bulanan --}}
                <div class="bg-white/90 dark:bg-gray-800/90 backdrop-blur-xl rounded-2xl shadow-lg border border-gray-200/50 dark:border-gray-700/50 p-6">
                    <div class="flex items-center gap-3 mb-6">
                        <div class="p-3 bg-gradient-to-br from-blue-500 to-indigo-600 rounded-xl">
                            <i class="fas fa-chart-bar text-white"></i>
                        </div>
                        <div>
                            <h3 class="text-lg font-bold text-gray-800 dark:text-gray-100">Statistik Bulanan</h3>
                            <p class="text-sm text-gray-500 dark:text-gray-400">Tahun {{ $year }}</p>
                        </div>
                    </div>
                    <div class="h-80 px-2 pt-2">
                        <canvas id="grafikBulananLine"></canvas>
                    </div>
                </div>

                {{-- Ringkasan Status --}}
                <div class="bg-white/90 dark:bg-gray-800/90 backdrop-blur-xl rounded-2xl shadow-lg border border-gray-200/50 dark:border-gray-700/50 p-6">
                    <div class="flex items-center gap-3 mb-6">
                        <div class="p-3 bg-gradient-to-br from-orange-500 to-red-600 rounded-xl">
                            <i class="fas fa-chart-pie text-white"></i>
                        </div>
                        <div>
                            <h3 class="text-lg font-bold text-gray-800 dark:text-gray-100">Ringkasan Status</h3>
                            <p class="text-sm text-gray-500 dark:text-gray-400">Bulan ini</p>
                        </div>
                    </div>
                    <div class="h-80 flex items-center justify-center">
                        <canvas id="grafikStatus"></canvas>
                    </div>
                </div>
            </div>

            {{-- Today's Status --}}
            @if($currentStatus === 'semua')
                {{-- Tabel Organik --}}
                <div class="bg-white/90 dark:bg-gray-800/90 backdrop-blur-xl rounded-2xl shadow-lg border border-gray-200/50 dark:border-gray-700/50 p-6">
                    <div class="flex items-center gap-3 mb-6">
                        <div class="p-3 bg-gradient-to-br from-emerald-500 to-teal-600 rounded-xl">
                            <i class="fas fa-users-cog text-white"></i>
                        </div>
                        <div>
                            <h3 class="text-lg font-bold text-gray-800 dark:text-gray-100">Status Karyawan Organik Hari Ini</h3>
                            <p class="text-sm text-gray-500 dark:text-gray-400">{{ \Carbon\Carbon::today()->format('d M Y') }}</p>
                        </div>
                    </div>

                    <div class="overflow-x-auto">
                        <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
                            <thead class="bg-gray-50 dark:bg-gray-900">
                                <tr>
                                    <th class="px-6 py-3 text-left text-xs font-bold text-gray-700 dark:text-gray-300 uppercase">Karyawan</th>
                                    <th class="px-6 py-3 text-left text-xs font-bold text-gray-700 dark:text-gray-300 uppercase">Status</th>
                                    <th class="px-6 py-3 text-left text-xs font-bold text-gray-700 dark:text-gray-300 uppercase">Check-in</th>
                                    <th class="px-6 py-3 text-left text-xs font-bold text-gray-700 dark:text-gray-300 uppercase">Keterlambatan</th>
                                    <th class="px-6 py-3 text-left text-xs font-bold text-gray-700 dark:text-gray-300 uppercase">Check-out</th>
                                    <th class="px-6 py-3 text-left text-xs font-bold text-gray-700 dark:text-gray-300 uppercase">Detail</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-100 dark:divide-gray-800">
                                @forelse ($dailyStatusesOrganik as $daily)
                                    <tr class="hover:bg-gray-50 dark:hover:bg-gray-700/50 transition-colors">
                                        <td class="px-6 py-4 whitespace-nowrap">
                                            <a href="{{ route('admin.absensi.user', $daily['user']->id) }}" class="block group">
                                                <div class="font-semibold text-gray-900 dark:text-gray-100 group-hover:text-indigo-600">
                                                    {{ $daily['user']->name }}
                                                </div>
                                                <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-emerald-100 dark:bg-emerald-900 text-emerald-700 dark:text-emerald-300 mt-1">
                                                    Organik
                                                </span>
                                            </a>
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap">
                                            <span class="text-sm text-gray-600 dark:text-gray-300">{{ $daily['status'] ?? '-' }}</span>
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
                                                <p class="text-gray-500">Tidak ada karyawan organik hari ini</p>
                                            </div>
                                        </td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>

                {{-- Tabel Freelance --}}
                <div class="bg-white/90 dark:bg-gray-800/90 backdrop-blur-xl rounded-2xl shadow-lg border border-gray-200/50 dark:border-gray-700/50 p-6">
                    <div class="flex items-center gap-3 mb-6">
                        <div class="p-3 bg-gradient-to-br from-cyan-500 to-blue-600 rounded-xl">
                            <i class="fas fa-users text-white"></i>
                        </div>
                        <div>
                            <h3 class="text-lg font-bold text-gray-800 dark:text-gray-100">Status Karyawan Freelance Hari Ini</h3>
                            <p class="text-sm text-gray-500 dark:text-gray-400">{{ \Carbon\Carbon::today()->format('d M Y') }}</p>
                        </div>
                    </div>

                    {{-- Form Pencarian --}}
                    <form action="{{ url()->current() }}" method="GET" class="mb-4">
                        <div class="flex items-center gap-2">
                            <div class="relative flex-grow">
                                <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                                    <i class="fas fa-search text-gray-400"></i>
                                </div>
                                <input type="search" name="search_freelance" id="search_freelance" class="block w-full pl-10 pr-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-indigo-500 focus:border-indigo-500 dark:bg-gray-700 dark:border-gray-600 dark:text-white" placeholder="Cari nama freelance..." value="{{ request('search_freelance') }}">
                            </div>
                            <button type="submit" class="inline-flex items-center px-4 py-2 border border-transparent text-sm font-medium rounded-lg shadow-sm text-white bg-indigo-600 hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500">
                                Cari
                            </button>
                            @if(request('search_freelance'))
                                <a href="{{ route('admin.absensi.freelance') }}" class="text-sm text-gray-500 hover:text-indigo-600 dark:text-gray-400 dark:hover:text-white">
                                    Reset
                                </a>
                            @endif
                        </div>
                    </form>

                    <div class="overflow-x-auto">
                        <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
                            <thead class="bg-gray-50 dark:bg-gray-900">
                                <tr>
                                    <th class="px-6 py-3 text-left text-xs font-bold text-gray-700 dark:text-gray-300 uppercase">Karyawan</th>
                                    <th class="px-6 py-3 text-left text-xs font-bold text-gray-700 dark:text-gray-300 uppercase">Status</th>
                                    <th class="px-6 py-3 text-left text-xs font-bold text-gray-700 dark:text-gray-300 uppercase">Check-in</th>
                                    <th class="px-6 py-3 text-left text-xs font-bold text-gray-700 dark:text-gray-300 uppercase">Keterlambatan</th>
                                    <th class="px-6 py-3 text-left text-xs font-bold text-gray-700 dark:text-gray-300 uppercase">Check-out</th>
                                    <th class="px-6 py-3 text-left text-xs font-bold text-gray-700 dark:text-gray-300 uppercase">Detail</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-100 dark:divide-gray-800">
                                @forelse ($dailyStatusesFreelance as $daily)
                                    <tr class="hover:bg-gray-50 dark:hover:bg-gray-700/50 transition-colors">
                                        <td class="px-6 py-4 whitespace-nowrap">
                                            <a href="{{ route('admin.absensi.user', $daily['user']->id) }}" class="block group">
                                                <div class="font-semibold text-gray-900 dark:text-gray-100 group-hover:text-indigo-600">
                                                    {{ $daily['user']->name }}
                                                </div>
                                                <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-blue-100 dark:bg-blue-900 text-blue-700 dark:text-blue-300 mt-1">
                                                    Freelance
                                                </span>
                                            </a>
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap">
                                            <span class="text-sm text-gray-600 dark:text-gray-300">{{ $daily['status'] ?? '-' }}</span>
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
            @else
                {{-- Tabel Single --}}
                <div class="bg-white/90 dark:bg-gray-800/90 backdrop-blur-xl rounded-2xl shadow-lg border border-gray-200/50 dark:border-gray-700/50 p-6">
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
                            <thead class="bg-gray-50 dark:bg-gray-900">
                                <tr>
                                    <th class="px-6 py-3 text-left text-xs font-bold text-gray-700 dark:text-gray-300 uppercase">Karyawan</th>
                                    <th class="px-6 py-3 text-left text-xs font-bold text-gray-700 dark:text-gray-300 uppercase">Status</th>
                                    <th class="px-6 py-3 text-left text-xs font-bold text-gray-700 dark:text-gray-300 uppercase">Check-in</th>
                                    <th class="px-6 py-3 text-left text-xs font-bold text-gray-700 dark:text-gray-300 uppercase">Keterlambatan</th>
                                    <th class="px-6 py-3 text-left text-xs font-bold text-gray-700 dark:text-gray-300 uppercase">Check-out</th>
                                    <th class="px-6 py-3 text-left text-xs font-bold text-gray-700 dark:text-gray-300 uppercase">Detail</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-100 dark:divide-gray-800">
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
                                                @if(str_contains($daily['status'], 'Belum')) bg-gray-100 text-gray-700
                                                @elseif(str_contains($daily['status'], 'Hadir')) bg-emerald-100 text-emerald-800
                                                @elseif(str_contains($daily['status'], 'Izin')) bg-yellow-100 text-yellow-800
                                                @elseif(str_contains($daily['status'], 'Sakit')) bg-red-100 text-red-800
                                                @elseif(str_contains($daily['status'], 'Pending')) bg-orange-100 text-orange-800
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

    {{-- MODAL EXPORT MASSAL --}}
    <div id="bulkModal" class="fixed inset-0 z-50 hidden overflow-y-auto" aria-labelledby="modal-title" role="dialog" aria-modal="true">
        <div class="flex items-end justify-center min-h-screen pt-4 px-4 pb-20 text-center sm:block sm:p-0">
            <div class="fixed inset-0 bg-gray-500 bg-opacity-75 transition-opacity" onclick="closeBulkModal()"></div>

            <div class="inline-block align-bottom bg-white dark:bg-gray-800 rounded-lg text-left overflow-hidden shadow-xl transform transition-all sm:my-8 sm:align-middle sm:max-w-lg sm:w-full">
                <form action="{{ route('admin.absensi.bulk-export-detail') }}" method="POST">
                    @csrf
                    <div class="bg-white dark:bg-gray-800 px-4 pt-5 pb-4 sm:p-6 sm:pb-4">
                        <div class="sm:flex sm:items-start">
                            <div class="mt-3 text-center sm:mt-0 sm:text-left w-full">
                                <h3 class="text-lg leading-6 font-medium text-gray-900 dark:text-white" id="modal-title">
                                    Export Detail Absensi Massal
                                </h3>

                                <div class="mt-4 space-y-4">
                                    <div>
                                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Rentang Tanggal</label>
                                        <div class="flex gap-2">
                                            <input type="date" name="start_date" required class="w-1/2 rounded-md border-gray-300 dark:bg-gray-700 dark:text-white dark:border-gray-600">
                                            <input type="date" name="end_date" required class="w-1/2 rounded-md border-gray-300 dark:bg-gray-700 dark:text-white dark:border-gray-600">
                                        </div>
                                    </div>

                                    <div>
                                        <div class="flex justify-between items-center mb-2">
                                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">Pilih Karyawan</label>
                                            <button type="button" onclick="toggleAllUsers()" class="text-xs text-indigo-600 hover:text-indigo-800 font-semibold">Pilih Semua</button>
                                        </div>

                                        <div class="h-48 overflow-y-auto border border-gray-200 dark:border-gray-700 rounded-md p-2 space-y-2">
                                            @foreach($users as $u)
                                                <div class="flex items-center">
                                                    <input type="checkbox" name="user_ids[]" value="{{ $u->id }}" class="user-checkbox h-4 w-4 text-indigo-600 focus:ring-indigo-500 border-gray-300 rounded">
                                                    <label class="ml-2 block text-sm text-gray-900 dark:text-gray-300">
                                                        {{ $u->name }} <span class="text-xs text-gray-500">({{ ucfirst($u->employment_type) }})</span>
                                                    </label>
                                                </div>
                                            @endforeach
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="bg-gray-50 dark:bg-gray-700 px-4 py-3 sm:px-6 sm:flex sm:flex-row-reverse">
                        <button type="submit" class="w-full inline-flex justify-center rounded-md border border-transparent shadow-sm px-4 py-2 bg-indigo-600 text-base font-medium text-white hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500 sm:ml-3 sm:w-auto sm:text-sm">
                            Download Excel
                        </button>
                        <button type="button" onclick="closeBulkModal()" class="mt-3 w-full inline-flex justify-center rounded-md border border-gray-300 shadow-sm px-4 py-2 bg-white text-base font-medium text-gray-700 hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500 sm:mt-0 sm:ml-3 sm:w-auto sm:text-sm">
                            Batal
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script>
        function openBulkModal() { document.getElementById('bulkModal').classList.remove('hidden'); }
        function closeBulkModal() { document.getElementById('bulkModal').classList.add('hidden'); }

        function toggleAllUsers() {
            const checkboxes = document.querySelectorAll('.user-checkbox');
            const allChecked = Array.from(checkboxes).every(c => c.checked);
            checkboxes.forEach(c => c.checked = !allChecked);
        }
    </script>

    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <script>
        document.addEventListener('DOMContentLoaded', () => {
            const isDark = document.documentElement.classList.contains('dark');

            Chart.defaults.color = isDark ? '#cbd5e0' : '#4b5563';
            Chart.defaults.borderColor = isDark ? '#4a5568' : '#e5e7eb';

            new Chart(document.getElementById('grafikBulananLine'), {
                type: 'line',
                data: {
                    labels: [@for ($i = 1; $i <= 12; $i++) "{{ \Carbon\Carbon::createFromFormat('!m', $i)->translatedFormat('M') }}", @endfor],
                    datasets: [
                        {
                            label: 'Organik',
                            data: [@for ($i = 1; $i <= 12; $i++) {{ $grafikBulananOrganik[$i] ?? 0 }}, @endfor],
                            borderColor: '#3b82f6',
                            backgroundColor: 'rgba(59,130,246,0.08)',
                            tension: 0.4,
                            pointBackgroundColor: '#3b82f6',
                            pointBorderColor: '#fff',
                            pointRadius: 7,
                            fill: true,
                        },
                        {
                            label: 'Freelance',
                            data: [@for ($i = 1; $i <= 12; $i++) {{ $grafikBulananFreelance[$i] ?? 0 }}, @endfor],
                            borderColor: '#fb923c',
                            backgroundColor: 'rgba(251,146,60,0.08)',
                            tension: 0.4,
                            pointBackgroundColor: '#fb923c',
                            pointBorderColor: '#fff',
                            pointRadius: 7,
                            fill: true,
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
                            grid: { color: isDark ? 'rgba(100,116,139,0.15)' : 'rgba(100,116,139,0.07)' },
                            ticks: { color: isDark ? '#cbd5e0' : '#4b5563' }
                        }
                    },
                    elements: {
                        line: { borderWidth: 3 },
                        point: { borderWidth: 2 }
                    }
                }
            });

            new Chart(document.getElementById('grafikStatus'), {
                type: 'doughnut',
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
                    cutout: '65%'
                }
            });
        });
    </script>
</x-app-layout>
