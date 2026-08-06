<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-col md:flex-row justify-between items-start md:items-center gap-4">
            <div>
                <h2 class="font-bold text-3xl leading-tight text-gray-900 dark:text-white">
                    {{ __('Rekap Absensi Bulanan') }}
                </h2>
                <p class="text-sm text-gray-600 dark:text-gray-400 mt-1">Kelola dan export data absensi karyawan</p>
            </div>
            <a href="{{ route('admin.absensi.index') }}"
                class="inline-flex items-center px-4 py-2.5 bg-white dark:bg-gray-800 border border-gray-300 dark:border-gray-600 rounded-lg text-gray-700 dark:text-gray-300 hover:bg-gray-50 dark:hover:bg-gray-700 transition-all duration-200 shadow-sm hover:shadow">
                <i class="fas fa-arrow-left mr-2"></i> Kembali
            </a>
        </div>
    </x-slot>

    <div class="py-8 bg-gray-50 dark:bg-gray-900 min-h-screen">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 space-y-6">

            {{-- FILTER SECTION --}}
            <div class="bg-white dark:bg-gray-800 rounded-2xl shadow-sm border border-gray-200 dark:border-gray-700 overflow-hidden">
                <div class="p-6 border-b border-gray-200 dark:border-gray-700">
                    <h3 class="text-lg font-semibold text-gray-900 dark:text-white flex items-center">
                        <i class="fas fa-filter mr-2 text-blue-600"></i> Filter Periode
                    </h3>
                </div>

                @php
                    $year = $selectedYear;
                    $month = $selectedMonth;
                    $firstDay = \Carbon\Carbon::create($year, $month, 1);
                    $firstMonday = null;
                    for ($d = 0; $d < 7; $d++) {
                        $candidate = $firstDay->copy()->addDays($d);
                        if ($candidate->isMonday()) {
                            $firstMonday = $candidate;
                            break;
                        }
                    }
                    if (!$firstMonday) {
                        $firstMonday = $firstDay->copy();
                    }
                    $weeks = 0;
                    $tempDate = $firstMonday->copy();
                    while ($tempDate->month == $month) {
                        $weeks++;
                        $tempDate->addWeek();
                    }
                @endphp

                <form method="GET" action="{{ route('admin.absensi.recap') }}" class="p-6" id="filterForm">
                    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-6 gap-4">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Bulan</label>
                            <select name="month" id="monthSelect" class="w-full px-4 py-2.5 bg-gray-50 dark:bg-gray-700 border border-gray-300 dark:border-gray-600 rounded-lg text-gray-900 dark:text-white focus:ring-2 focus:ring-blue-500 focus:border-transparent transition">
                                @for ($m = 1; $m <= 12; $m++)
                                    <option value="{{ $m }}" {{ $selectedMonth == $m ? 'selected' : '' }}>
                                        {{ \Carbon\Carbon::createFromFormat('!m', $m)->translatedFormat('F') }}
                                    </option>
                                @endfor
                            </select>
                        </div>

                        <div>
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Tahun</label>
                            <select name="year" id="yearSelect" class="w-full px-4 py-2.5 bg-gray-50 dark:bg-gray-700 border border-gray-300 dark:border-gray-600 rounded-lg text-gray-900 dark:text-white focus:ring-2 focus:ring-blue-500 focus:border-transparent transition">
                                @for ($y = date('Y'); $y >= 2020; $y--)
                                    <option value="{{ $y }}" {{ $selectedYear == $y ? 'selected' : '' }}>{{ $y }}</option>
                                @endfor
                            </select>
                        </div>

                        <div>
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Tipe</label>
                            <select name="range" id="rangeSelect" class="w-full px-4 py-2.5 bg-gray-50 dark:bg-gray-700 border border-gray-300 dark:border-gray-600 rounded-lg text-gray-900 dark:text-white focus:ring-2 focus:ring-blue-500 focus:border-transparent transition">
                                <option value="monthly" {{ request('range') == 'monthly' ? 'selected' : '' }}>Bulanan</option>
                                <option value="weekly" {{ request('range') == 'weekly' ? 'selected' : '' }}>Mingguan</option>
                                <option value="custom" {{ request('range') == 'custom' ? 'selected' : '' }}>Custom</option>
                            </select>
                        </div>

                        <div>
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Minggu</label>
                            <select name="week" id="weekSelect" class="w-full px-4 py-2.5 bg-gray-50 dark:bg-gray-700 border border-gray-300 dark:border-gray-600 rounded-lg text-gray-900 dark:text-white focus:ring-2 focus:ring-blue-500 focus:border-transparent transition hidden">
                                <option value="">Semua</option>
                                @for ($i = 1; $i <= $weeks; $i++)
                                    <option value="{{ $i }}" {{ request('week') == $i ? 'selected' : '' }}>Minggu {{ $i }}</option>
                                @endfor
                            </select>
                        </div>

                        <div id="customDateStart" class="hidden">
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Dari</label>
                            <input type="date" name="start_date" value="{{ request('start_date') }}" class="w-full px-4 py-2.5 bg-gray-50 dark:bg-gray-700 border border-gray-300 dark:border-gray-600 rounded-lg text-gray-900 dark:text-white focus:ring-2 focus:ring-blue-500 focus:border-transparent transition">
                        </div>

                        <div id="customDateEnd" class="hidden">
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Sampai</label>
                            <input type="date" name="end_date" value="{{ request('end_date') }}" class="w-full px-4 py-2.5 bg-gray-50 dark:bg-gray-700 border border-gray-300 dark:border-gray-600 rounded-lg text-gray-900 dark:text-white focus:ring-2 focus:ring-blue-500 focus:border-transparent transition">
                        </div>

                        <div class="flex items-end">
                            <button type="submit" class="w-full px-4 py-2.5 bg-blue-600 hover:bg-blue-700 text-white font-medium rounded-lg shadow-sm hover:shadow transition-all duration-200">
                                <i class="fas fa-search mr-2"></i> Tampilkan
                            </button>
                        </div>
                    </div>
                </form>
            </div>

            {{-- TABEL ORGANIK --}}
            <div class="bg-white dark:bg-gray-800 rounded-2xl shadow-sm border border-gray-200 dark:border-gray-700 overflow-hidden">
                <form method="POST">
                    @csrf
                    <input type="hidden" name="start_date" value="{{ $startDate->format('Y-m-d H:i:s') }}">
                    <input type="hidden" name="end_date" value="{{ $endDate->format('Y-m-d H:i:s') }}">

                    <div class="p-6 border-b border-gray-200 dark:border-gray-700 flex flex-col md:flex-row justify-between items-start md:items-center gap-4">
                        <div>
                            <h3 class="text-lg font-semibold text-gray-900 dark:text-white flex items-center">
                                <span class="w-3 h-3 bg-green-500 rounded-full mr-3"></span> Karyawan Organik
                            </h3>
                            <p class="text-sm text-gray-600 dark:text-gray-400 mt-1">Data absensi karyawan tetap</p>
                        </div>
                        <div class="flex gap-2">

                                <button type="submit" formaction="{{ route('admin.absensi.bulk-export-simple') }}" class="inline-flex items-center px-4 py-2 bg-cyan-600 hover:bg-cyan-700 text-white font-medium rounded-lg shadow-sm hover:shadow transition-all duration-200">
                                <i class="fas fa-table mr-2"></i> Simple
                            </button>
                            @if(auth()->user()->role !== 'pkl')
                             <button type="submit" formaction="{{ route('admin.absensi.bulk-export-detail') }}" class="inline-flex items-center px-4 py-2 bg-green-600 hover:bg-green-700 text-white font-medium rounded-lg shadow-sm hover:shadow transition-all duration-200">
                                <i class="fas fa-file-excel mr-2"></i> Excel
                            </button>
                            <button type="submit" formaction="{{ route('admin.absensi.bulk-export-pdf') }}" class="inline-flex items-center px-4 py-2 bg-red-600 hover:bg-red-700 text-white font-medium rounded-lg shadow-sm hover:shadow transition-all duration-200">
                                <i class="fas fa-file-pdf mr-2"></i> PDF
                            </button>
                            @endif
                        </div>
                    </div>

                    <div class="overflow-x-auto">
                        @php
                            $organikData = array_filter($recapData, fn($data) => isset($data['kategori']) && $data['kategori'] === 'organik');
                            usort($organikData, fn($a, $b) => $a['user']->name <=> $b['user']->name);
                            $totalGajiOrganik = array_sum(array_column($organikData, 'total_gaji'));
                        @endphp
                        <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
                            <thead class="bg-gray-50 dark:bg-gray-900">
                                <tr>
                                    <th class="py-3 px-4 text-left">
                                        <input type="checkbox" class="toggle-table-checkbox w-4 h-4 text-blue-600 rounded focus:ring-2 focus:ring-blue-500" data-category="organik">
                                    </th>
                                    <th class="py-3 px-4 text-left text-xs font-semibold text-gray-700 dark:text-gray-300 uppercase tracking-wider">Nama</th>
                                    <th class="py-3 px-4 text-center text-xs font-semibold text-gray-700 dark:text-gray-300 uppercase tracking-wider">Hadir</th>
                                    <th class="py-3 px-4 text-center text-xs font-semibold text-gray-700 dark:text-gray-300 uppercase tracking-wider">Cuti Tahunan</th>
                                    <th class="py-3 px-4 text-center text-xs font-semibold text-gray-700 dark:text-gray-300 uppercase tracking-wider">Cuti Spesial</th>
                                    <th class="py-3 px-4 text-center text-xs font-semibold text-gray-700 dark:text-gray-300 uppercase tracking-wider">Sakit</th>
                                    <th class="py-3 px-4 text-center text-xs font-semibold text-gray-700 dark:text-gray-300 uppercase tracking-wider">Lembur</th>
                                    <th class="py-3 px-4 text-center text-xs font-semibold text-gray-700 dark:text-gray-300 uppercase tracking-wider">Sisa Cuti</th>
                                    <th class="py-3 px-4 text-center text-xs font-semibold text-gray-700 dark:text-gray-300 uppercase tracking-wider">Izin Keluar</th>
                                    <th class="py-3 px-4 text-center text-xs font-semibold text-gray-700 dark:text-gray-300 uppercase tracking-wider">Aksi</th>
                                </tr>
                            </thead>
                            <tbody class="bg-white dark:bg-gray-800 divide-y divide-gray-200 dark:divide-gray-700">
                                @forelse ($organikData as $data)
                                    <tr class="hover:bg-gray-50 dark:hover:bg-gray-700/50 transition-colors">
                                        <td class="py-3 px-4">
                                            <input type="checkbox" name="user_ids[]" value="{{ $data['user']->id }}" class="user-checkbox-organik w-4 h-4 text-blue-600 rounded focus:ring-2 focus:ring-blue-500">
                                        </td>
                                        <td class="py-3 px-4 text-sm font-medium text-gray-900 dark:text-white">{{ $data['user']->name }}</td>
                                        <td class="py-3 px-4 text-center">
                                            <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-green-100 text-green-800 dark:bg-green-900/30 dark:text-green-400">
                                                {{ $data['total_hadir'] }}
                                            </span>
                                        </td>
                                        <td class="py-3 px-4 text-center">
                                            <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-amber-100 text-amber-800 dark:bg-amber-900/30 dark:text-amber-400">
                                                {{ $data['total_cuti_tahunan'] ?? 0 }}
                                            </span>
                                        </td>
                                        <td class="py-3 px-4 text-center">
                                            <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-cyan-100 text-cyan-800 dark:bg-cyan-900/30 dark:text-cyan-400">
                                                {{ $data['total_cuti_spesial'] ?? 0 }}
                                            </span>
                                        </td>
                                        <td class="py-3 px-4 text-center">
                                            <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-red-100 text-red-800 dark:bg-red-900/30 dark:text-red-400">
                                                {{ $data['total_sakit'] }}
                                            </span>
                                        </td>
                                        <td class="py-3 px-4 text-center">
                                            <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-purple-100 text-purple-800 dark:bg-purple-900/30 dark:text-purple-400">
                                                {{ $data['total_lembur'] }}
                                            </span>
                                        </td>
                                        <td class="py-3 px-4 text-center">
                                            <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-teal-100 text-teal-800 dark:bg-teal-900/30 dark:text-teal-400">
                                                {{ $data['user']->sisa_cuti ?? 12 }} hari
                                            </span>
                                        </td>
                                        <td class="py-3 px-4 text-center">
                                            <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium {{ ($data['total_izin_keluar'] ?? 0) >= 3 ? 'bg-red-100 text-red-800 dark:bg-red-900/30 dark:text-red-400' : 'bg-indigo-100 text-indigo-800 dark:bg-indigo-900/30 dark:text-indigo-400' }}">
                                                {{ $data['total_izin_keluar'] ?? 0 }}x
                                            </span>
                                            @if(($data['total_izin_keluar_ditolak'] ?? 0) > 0)
                                                <div class="text-xs text-red-500 mt-1">{{ $data['total_izin_keluar_ditolak'] }} ditolak</div>
                                            @endif
                                        </td>
                                        <td class="py-3 px-4 text-center">
                                            <a href="{{ route('admin.absensi.user', $data['user']->id) }}" class="inline-flex items-center text-blue-600 hover:text-blue-800 dark:text-blue-400 dark:hover:text-blue-300 font-medium">
                                                <i class="fas fa-eye mr-1"></i>
                                            </a>
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="13" class="text-center py-8 text-gray-500 dark:text-gray-400">
                                            <i class="fas fa-inbox text-3xl mb-2"></i>
                                            <p>Tidak ada data</p>
                                        </td>
                                    </tr>
                                @endforelse
                                @if(count($organikData) > 0)
                                    <tr class="bg-green-50 dark:bg-green-900/20">
                                        <td colspan="10" class="py-4 px-4 text-right text-sm font-bold text-gray-900 dark:text-white uppercase">
                                            Total Gaji Organik
                                        </td>
                                        <td class="py-4 px-4 text-right text-base font-bold text-green-600 dark:text-green-400">
                                            Rp {{ number_format($totalGajiOrganik, 0, ',', '.') }}
                                        </td>
                                        <td></td>
                                    </tr>
                                @endif
                            </tbody>
                        </table>
                    </div>
                </form>
            </div>

            {{-- TABEL FREELANCE --}}
            <div class="bg-white dark:bg-gray-800 rounded-2xl shadow-sm border border-gray-200 dark:border-gray-700 overflow-hidden">
                <form method="POST">
                    @csrf
                    <input type="hidden" name="start_date" value="{{ $startDate->format('Y-m-d H:i:s') }}">
                    <input type="hidden" name="end_date" value="{{ $endDate->format('Y-m-d H:i:s') }}">

                    <div class="p-6 border-b border-gray-200 dark:border-gray-700 flex flex-col md:flex-row justify-between items-start md:items-center gap-4">
                        <div>
                            <h3 class="text-lg font-semibold text-gray-900 dark:text-white flex items-center">
                                <span class="w-3 h-3 bg-orange-500 rounded-full mr-3"></span> Karyawan Freelance
                            </h3>
                            <p class="text-sm text-gray-600 dark:text-gray-400 mt-1">Data absensi karyawan lepas</p>
                        </div>
                        <div class="flex gap-2">

                            <button type="submit" formaction="{{ route('admin.absensi.bulk-export-simple') }}" class="inline-flex items-center px-4 py-2 bg-cyan-600 hover:bg-cyan-700 text-white font-medium rounded-lg shadow-sm hover:shadow transition-all duration-200">
                                <i class="fas fa-table mr-2"></i> Simple
                            </button>
                            <button type="submit" formaction="{{ route('admin.absensi.bulk-export-detail') }}" class="inline-flex items-center px-4 py-2 bg-green-600 hover:bg-green-700 text-white font-medium rounded-lg shadow-sm hover:shadow transition-all duration-200">
                                <i class="fas fa-file-excel mr-2"></i> Excel
                            </button>
                            <button type="submit" formaction="{{ route('admin.absensi.bulk-export-pdf') }}" class="inline-flex items-center px-4 py-2 bg-red-600 hover:bg-red-700 text-white font-medium rounded-lg shadow-sm hover:shadow transition-all duration-200">
                                <i class="fas fa-file-pdf mr-2"></i> PDF
                            </button>
                        </div>
                    </div>

                    <div class="overflow-x-auto">
                        @php
                            $freelanceData = array_filter($recapData, fn($data) => isset($data['kategori']) && $data['kategori'] === 'freelance');
                            usort($freelanceData, fn($a, $b) => $a['user']->name <=> $b['user']->name);
                            $totalGajiFreelance = array_sum(array_column($freelanceData, 'total_gaji'));
                        @endphp
                        <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
                            <thead class="bg-gray-50 dark:bg-gray-900">
                                <tr>
                                    <th class="py-3 px-4 text-left">
                                        <input type="checkbox" class="toggle-table-checkbox w-4 h-4 text-blue-600 rounded focus:ring-2 focus:ring-blue-500" data-category="freelance">
                                    </th>
                                    <th class="py-3 px-4 text-left text-xs font-semibold text-gray-700 dark:text-gray-300 uppercase tracking-wider">Nama</th>
                                    <th class="py-3 px-4 text-center text-xs font-semibold text-gray-700 dark:text-gray-300 uppercase tracking-wider">Hadir</th>
                                    <th class="py-3 px-4 text-center text-xs font-semibold text-gray-700 dark:text-gray-300 uppercase tracking-wider">Izin</th>
                                    <th class="py-3 px-4 text-center text-xs font-semibold text-gray-700 dark:text-gray-300 uppercase tracking-wider">Sakit</th>
                                    <th class="py-3 px-4 text-center text-xs font-semibold text-gray-700 dark:text-gray-300 uppercase tracking-wider">Lembur</th>
                                    <th class="py-3 px-4 text-center text-xs font-semibold text-gray-700 dark:text-gray-300 uppercase tracking-wider">Telat</th>
                                    @if(auth()->user()->role !== 'pkl')
                                    <th class="py-3 px-4 text-right text-xs font-semibold text-gray-700 dark:text-gray-300 uppercase tracking-wider">Potongan</th>
                                    <th class="py-3 px-4 text-center text-xs font-semibold text-gray-700 dark:text-gray-300 uppercase tracking-wider">Menit OT</th>
                                    <th class="py-3 px-4 text-right text-xs font-semibold text-gray-700 dark:text-gray-300 uppercase tracking-wider">Gaji OT</th>
                                    <th class="py-3 px-4 text-right text-xs font-semibold text-gray-700 dark:text-gray-300 uppercase tracking-wider">Total Gaji</th>
                                    @endif
                                    <th class="py-3 px-4 text-center text-xs font-semibold text-gray-700 dark:text-gray-300 uppercase tracking-wider">Izin Keluar</th>
                                    <th class="py-3 px-4 text-center text-xs font-semibold text-gray-700 dark:text-gray-300 uppercase tracking-wider">Aksi</th>
                                </tr>
                            </thead>
                            <tbody class="bg-white dark:bg-gray-800 divide-y divide-gray-200 dark:divide-gray-700">
                                @forelse ($freelanceData as $data)
                                    <tr class="hover:bg-gray-50 dark:hover:bg-gray-700/50 transition-colors">
                                        <td class="py-3 px-4">
                                            <input type="checkbox" name="user_ids[]" value="{{ $data['user']->id }}" class="user-checkbox-freelance w-4 h-4 text-blue-600 rounded focus:ring-2 focus:ring-blue-500">
                                        </td>
                                        <td class="py-3 px-4 text-sm font-medium text-gray-900 dark:text-white">{{ $data['user']->name }}</td>
                                        <td class="py-3 px-4 text-center">
                                            <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-green-100 text-green-800 dark:bg-green-900/30 dark:text-green-400">
                                                {{ $data['total_hadir'] }}
                                            </span>
                                        </td>
                                        <td class="py-3 px-4 text-center">
                                            <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-yellow-100 text-yellow-800 dark:bg-yellow-900/30 dark:text-yellow-400">
                                                {{ $data['total_izin'] }}
                                            </span>
                                        </td>
                                        <td class="py-3 px-4 text-center">
                                            <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-red-100 text-red-800 dark:bg-red-900/30 dark:text-red-400">
                                                {{ $data['total_sakit'] }}
                                            </span>
                                        </td>
                                        <td class="py-3 px-4 text-center">
                                            <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-purple-100 text-purple-800 dark:bg-purple-900/30 dark:text-purple-400">
                                                {{ $data['total_lembur'] }}
                                            </span>
                                        </td>
                                        <td class="py-3 px-4 text-center">
                                            <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-orange-100 text-orange-800 dark:bg-orange-900/30 dark:text-orange-400">
                                                {{ $data['total_telat'] ?? 0 }}x
                                            </span>
                                        </td>
                                        @if(auth()->user()->role !== 'pkl')
                                        <td class="py-3 px-4 text-right text-sm font-medium text-red-600 dark:text-red-400">
                                            Rp {{ number_format($data['total_potongan'] ?? 0, 0, ',', '.') }}
                                        </td>
                                        <td class="py-3 px-4 text-center text-sm text-gray-900 dark:text-gray-300">
                                            {{ $data['total_menit_lembur'] ?? 0 }}'
                                        </td>
                                        <td class="py-3 px-4 text-right text-sm font-medium text-purple-600 dark:text-purple-400">
                                            Rp {{ number_format($data['total_gaji_lembur'] ?? 0, 0, ',', '.') }}
                                        </td>
                                        <td class="py-3 px-4 text-right text-sm font-bold text-green-600 dark:text-green-400">
                                            Rp {{ number_format($data['total_gaji'] ?? 0, 0, ',', '.') }}
                                        </td>
                                        @endif
                                        <td class="py-3 px-4 text-center">
                                            <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium {{ ($data['total_izin_keluar'] ?? 0) >= 3 ? 'bg-red-100 text-red-800 dark:bg-red-900/30 dark:text-red-400' : 'bg-indigo-100 text-indigo-800 dark:bg-indigo-900/30 dark:text-indigo-400' }}">
                                                {{ $data['total_izin_keluar'] ?? 0 }}x
                                            </span>
                                            @if(($data['total_izin_keluar_ditolak'] ?? 0) > 0)
                                                <div class="text-xs text-red-500 mt-1">{{ $data['total_izin_keluar_ditolak'] }} ditolak</div>
                                            @endif
                                        </td>
                                        <td class="py-3 px-4 text-center">
                                            <a href="{{ route('admin.absensi.user', $data['user']->id) }}" class="inline-flex items-center text-blue-600 hover:text-blue-800 dark:text-blue-400 dark:hover:text-blue-300 font-medium">
                                                <i class="fas fa-eye mr-1"></i>
                                            </a>
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="12" class="text-center py-8 text-gray-500 dark:text-gray-400">
                                            <i class="fas fa-inbox text-3xl mb-2"></i>
                                            <p>Tidak ada data</p>
                                        </td>
                                    </tr>
                                @endforelse
                                @if(count($freelanceData) > 0 && auth()->user()->role !== 'pkl')
                                    <tr class="bg-orange-50 dark:bg-orange-900/20">
                                        <td colspan="10" class="py-4 px-4 text-right text-sm font-bold text-gray-900 dark:text-white uppercase">
                                            Total Gaji Freelance
                                        </td>
                                        <td class="py-4 px-4 text-right text-base font-bold text-orange-600 dark:text-orange-400">
                                            Rp {{ number_format($totalGajiFreelance, 0, ',', '.') }}
                                        </td>
                                        <td></td>
                                    </tr>
                                @endif
                            </tbody>
                        </table>
                    </div>
                </form>
            </div>

            {{-- TABEL BORONGAN --}}
            <div class="bg-white dark:bg-gray-800 rounded-2xl shadow-sm border border-gray-200 dark:border-gray-700 overflow-hidden">
                <form method="POST">
                    @csrf
                    <input type="hidden" name="start_date" value="{{ $startDate->format('Y-m-d H:i:s') }}">
                    <input type="hidden" name="end_date" value="{{ $endDate->format('Y-m-d H:i:s') }}">

                    <div class="p-6 border-b border-gray-200 dark:border-gray-700 flex flex-col md:flex-row justify-between items-start md:items-center gap-4">
                        <div>
                            <h3 class="text-lg font-semibold text-gray-900 dark:text-white flex items-center">
                                <span class="w-3 h-3 bg-purple-500 rounded-full mr-3"></span> Karyawan Borongan
                            </h3>
                            <p class="text-sm text-gray-600 dark:text-gray-400 mt-1">Data absensi karyawan borongan</p>
                        </div>
                        <div class="flex gap-2">
                            <button type="submit" formaction="{{ route('admin.absensi.bulk-export-simple') }}" class="inline-flex items-center px-4 py-2 bg-cyan-600 hover:bg-cyan-700 text-white font-medium rounded-lg shadow-sm hover:shadow transition-all duration-200">
                                <i class="fas fa-table mr-2"></i> Simple
                            </button>
                            <button type="submit" formaction="{{ route('admin.absensi.bulk-export-detail') }}" class="inline-flex items-center px-4 py-2 bg-green-600 hover:bg-green-700 text-white font-medium rounded-lg shadow-sm hover:shadow transition-all duration-200">
                                <i class="fas fa-file-excel mr-2"></i> Excel
                            </button>
                            <button type="submit" formaction="{{ route('admin.absensi.bulk-export-pdf') }}" class="inline-flex items-center px-4 py-2 bg-red-600 hover:bg-red-700 text-white font-medium rounded-lg shadow-sm hover:shadow transition-all duration-200">
                                <i class="fas fa-file-pdf mr-2"></i> PDF
                            </button>
                        </div>
                    </div>

                    <div class="overflow-x-auto">
                        @php
                            $boronganData = array_filter($recapData, fn($data) => isset($data['kategori']) && $data['kategori'] === 'borongan');
                            usort($boronganData, fn($a, $b) => $a['user']->name <=> $b['user']->name);
                            $totalGajiBorongan = array_sum(array_column($boronganData, 'total_gaji'));
                        @endphp
                        <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
                            <thead class="bg-gray-50 dark:bg-gray-900">
                                <tr>
                                    <th class="py-3 px-4 text-left">
                                        <input type="checkbox" class="toggle-table-checkbox w-4 h-4 text-blue-600 rounded focus:ring-2 focus:ring-blue-500" data-category="borongan">
                                    </th>
                                    <th class="py-3 px-4 text-left text-xs font-semibold text-gray-700 dark:text-gray-300 uppercase tracking-wider">Nama</th>
                                    <th class="py-3 px-4 text-center text-xs font-semibold text-gray-700 dark:text-gray-300 uppercase tracking-wider">Hadir</th>
                                    <th class="py-3 px-4 text-center text-xs font-semibold text-gray-700 dark:text-gray-300 uppercase tracking-wider">Izin</th>
                                    <th class="py-3 px-4 text-center text-xs font-semibold text-gray-700 dark:text-gray-300 uppercase tracking-wider">Sakit</th>
                                    <th class="py-3 px-4 text-center text-xs font-semibold text-gray-700 dark:text-gray-300 uppercase tracking-wider">Lembur</th>
                                    <th class="py-3 px-4 text-center text-xs font-semibold text-gray-700 dark:text-gray-300 uppercase tracking-wider">Telat</th>
                                    @if(auth()->user()->role !== 'pkl')
                                    <th class="py-3 px-4 text-right text-xs font-semibold text-gray-700 dark:text-gray-300 uppercase tracking-wider">Potongan</th>
                                    <th class="py-3 px-4 text-center text-xs font-semibold text-gray-700 dark:text-gray-300 uppercase tracking-wider">Menit OT</th>
                                    <th class="py-3 px-4 text-right text-xs font-semibold text-gray-700 dark:text-gray-300 uppercase tracking-wider">Gaji OT</th>
                                    <th class="py-3 px-4 text-right text-xs font-semibold text-gray-700 dark:text-gray-300 uppercase tracking-wider">Total Gaji</th>
                                    @endif
                                    <th class="py-3 px-4 text-center text-xs font-semibold text-gray-700 dark:text-gray-300 uppercase tracking-wider">Izin Keluar</th>
                                    <th class="py-3 px-4 text-center text-xs font-semibold text-gray-700 dark:text-gray-300 uppercase tracking-wider">Aksi</th>
                                </tr>
                            </thead>
                            <tbody class="bg-white dark:bg-gray-800 divide-y divide-gray-200 dark:divide-gray-700">
                                @forelse ($boronganData as $data)
                                    <tr class="hover:bg-gray-50 dark:hover:bg-gray-700/50 transition-colors">
                                        <td class="py-3 px-4">
                                            <input type="checkbox" name="user_ids[]" value="{{ $data['user']->id }}" class="user-checkbox-borongan w-4 h-4 text-blue-600 rounded focus:ring-2 focus:ring-blue-500">
                                        </td>
                                        <td class="py-3 px-4 text-sm font-medium text-gray-900 dark:text-white">{{ $data['user']->name }}</td>
                                        <td class="py-3 px-4 text-center">
                                            <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-green-100 text-green-800 dark:bg-green-900/30 dark:text-green-400">
                                                {{ $data['total_hadir'] }}
                                            </span>
                                        </td>
                                        <td class="py-3 px-4 text-center">
                                            <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-yellow-100 text-yellow-800 dark:bg-yellow-900/30 dark:text-yellow-400">
                                                {{ $data['total_izin'] }}
                                            </span>
                                        </td>
                                        <td class="py-3 px-4 text-center">
                                            <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-red-100 text-red-800 dark:bg-red-900/30 dark:text-red-400">
                                                {{ $data['total_sakit'] }}
                                            </span>
                                        </td>
                                        <td class="py-3 px-4 text-center">
                                            <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-purple-100 text-purple-800 dark:bg-purple-900/30 dark:text-purple-400">
                                                {{ $data['total_lembur'] }}
                                            </span>
                                        </td>
                                        <td class="py-3 px-4 text-center">
                                            <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-orange-100 text-orange-800 dark:bg-orange-900/30 dark:text-orange-400">
                                                {{ $data['total_telat'] ?? 0 }}x
                                            </span>
                                        </td>
                                        @if(auth()->user()->role !== 'pkl')
                                        <td class="py-3 px-4 text-right text-sm font-medium text-red-600 dark:text-red-400">
                                            Rp {{ number_format($data['total_potongan'] ?? 0, 0, ',', '.') }}
                                        </td>
                                        <td class="py-3 px-4 text-center text-sm text-gray-900 dark:text-gray-300">
                                            {{ $data['total_menit_lembur'] ?? 0 }}'
                                        </td>
                                        <td class="py-3 px-4 text-right text-sm font-medium text-purple-600 dark:text-purple-400">
                                            Rp {{ number_format($data['total_gaji_lembur'] ?? 0, 0, ',', '.') }}
                                        </td>
                                        <td class="py-3 px-4 text-right text-sm font-bold text-green-600 dark:text-green-400">
                                            Rp {{ number_format($data['total_gaji'] ?? 0, 0, ',', '.') }}
                                        </td>
                                        @endif
                                        <td class="py-3 px-4 text-center">
                                            <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium {{ ($data['total_izin_keluar'] ?? 0) >= 3 ? 'bg-red-100 text-red-800 dark:bg-red-900/30 dark:text-red-400' : 'bg-indigo-100 text-indigo-800 dark:bg-indigo-900/30 dark:text-indigo-400' }}">
                                                {{ $data['total_izin_keluar'] ?? 0 }}x
                                            </span>
                                            @if(($data['total_izin_keluar_ditolak'] ?? 0) > 0)
                                                <div class="text-xs text-red-500 mt-1">{{ $data['total_izin_keluar_ditolak'] }} ditolak</div>
                                            @endif
                                        </td>
                                        <td class="py-3 px-4 text-center">
                                            <a href="{{ route('admin.absensi.user', $data['user']->id) }}" class="inline-flex items-center text-blue-600 hover:text-blue-800 dark:text-blue-400 dark:hover:text-blue-300 font-medium">
                                                <i class="fas fa-eye mr-1"></i>
                                            </a>
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="12" class="text-center py-8 text-gray-500 dark:text-gray-400">
                                            <i class="fas fa-inbox text-3xl mb-2"></i>
                                            <p>Tidak ada data</p>
                                        </td>
                                    </tr>
                                @endforelse
                                @if(count($boronganData) > 0 && auth()->user()->role !== 'pkl')
                                    <tr class="bg-purple-50 dark:bg-purple-900/20">
                                        <td colspan="10" class="py-4 px-4 text-right text-sm font-bold text-gray-900 dark:text-white uppercase">
                                            Total Gaji Borongan
                                        </td>
                                        <td class="py-4 px-4 text-right text-base font-bold text-purple-600 dark:text-purple-400">
                                            Rp {{ number_format($totalGajiBorongan, 0, ',', '.') }}
                                        </td>
                                        <td></td>
                                    </tr>
                                @endif
                            </tbody>
                        </table>
                    </div>
                </form>
            </div>

            {{-- TABEL MAGANG --}}
            <div class="bg-white dark:bg-gray-800 rounded-2xl shadow-sm border border-gray-200 dark:border-gray-700 overflow-hidden">
                <form method="POST">
                    @csrf
                    <input type="hidden" name="start_date" value="{{ $startDate->format('Y-m-d H:i:s') }}">
                    <input type="hidden" name="end_date" value="{{ $endDate->format('Y-m-d H:i:s') }}">

                    <div class="p-6 border-b border-gray-200 dark:border-gray-700 flex flex-col md:flex-row justify-between items-start md:items-center gap-4">
                        <div>
                            <h3 class="text-lg font-semibold text-gray-900 dark:text-white flex items-center">
                                <span class="w-3 h-3 bg-blue-500 rounded-full mr-3"></span> Karyawan Magang
                            </h3>
                            <p class="text-sm text-gray-600 dark:text-gray-400 mt-1">Data absensi karyawan magang</p>
                        </div>
                        <div class="flex gap-2">
                            <button type="submit" formaction="{{ route('admin.absensi.bulk-export-simple') }}" class="inline-flex items-center px-4 py-2 bg-cyan-600 hover:bg-cyan-700 text-white font-medium rounded-lg shadow-sm hover:shadow transition-all duration-200">
                                <i class="fas fa-table mr-2"></i> Simple
                            </button>
                            <button type="submit" formaction="{{ route('admin.absensi.bulk-export-detail') }}" class="inline-flex items-center px-4 py-2 bg-green-600 hover:bg-green-700 text-white font-medium rounded-lg shadow-sm hover:shadow transition-all duration-200">
                                <i class="fas fa-file-excel mr-2"></i> Excel
                            </button>
                            <button type="submit" formaction="{{ route('admin.absensi.bulk-export-pdf') }}" class="inline-flex items-center px-4 py-2 bg-red-600 hover:bg-red-700 text-white font-medium rounded-lg shadow-sm hover:shadow transition-all duration-200">
                                <i class="fas fa-file-pdf mr-2"></i> PDF
                            </button>
                        </div>
                    </div>

                    <div class="overflow-x-auto">
                        @php
                            $magangData = array_filter($recapData, fn($data) => isset($data['kategori']) && $data['kategori'] === 'magang');
                            usort($magangData, fn($a, $b) => $a['user']->name <=> $b['user']->name);
                            $totalGajiMagang = array_sum(array_column($magangData, 'total_gaji'));
                        @endphp
                        <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
                            <thead class="bg-gray-50 dark:bg-gray-900">
                                <tr>
                                    <th class="py-3 px-4 text-left">
                                        <input type="checkbox" class="toggle-table-checkbox w-4 h-4 text-blue-600 rounded focus:ring-2 focus:ring-blue-500" data-category="magang">
                                    </th>
                                    <th class="py-3 px-4 text-left text-xs font-semibold text-gray-700 dark:text-gray-300 uppercase tracking-wider">Nama</th>
                                    <th class="py-3 px-4 text-center text-xs font-semibold text-gray-700 dark:text-gray-300 uppercase tracking-wider">Hadir</th>
                                    <th class="py-3 px-4 text-center text-xs font-semibold text-gray-700 dark:text-gray-300 uppercase tracking-wider">Izin</th>
                                    <th class="py-3 px-4 text-center text-xs font-semibold text-gray-700 dark:text-gray-300 uppercase tracking-wider">Sakit</th>
                                    <th class="py-3 px-4 text-center text-xs font-semibold text-gray-700 dark:text-gray-300 uppercase tracking-wider">Lembur</th>
                                    <th class="py-3 px-4 text-center text-xs font-semibold text-gray-700 dark:text-gray-300 uppercase tracking-wider">Telat</th>
                                    <th class="py-3 px-4 text-right text-xs font-semibold text-gray-700 dark:text-gray-300 uppercase tracking-wider">Potongan</th>
                                    <th class="py-3 px-4 text-center text-xs font-semibold text-gray-700 dark:text-gray-300 uppercase tracking-wider">Menit OT</th>
                                    <th class="py-3 px-4 text-right text-xs font-semibold text-gray-700 dark:text-gray-300 uppercase tracking-wider">Gaji OT</th>
                                    <th class="py-3 px-4 text-right text-xs font-semibold text-gray-700 dark:text-gray-300 uppercase tracking-wider">Total Gaji</th>
                                    <th class="py-3 px-4 text-center text-xs font-semibold text-gray-700 dark:text-gray-300 uppercase tracking-wider">Izin Keluar</th>
                                    <th class="py-3 px-4 text-center text-xs font-semibold text-gray-700 dark:text-gray-300 uppercase tracking-wider">Aksi</th>
                                </tr>
                            </thead>
                            <tbody class="bg-white dark:bg-gray-800 divide-y divide-gray-200 dark:divide-gray-700">
                                @forelse ($magangData as $data)
                                    <tr class="hover:bg-gray-50 dark:hover:bg-gray-700/50 transition-colors">
                                        <td class="py-3 px-4">
                                            <input type="checkbox" name="user_ids[]" value="{{ $data['user']->id }}" class="user-checkbox-magang w-4 h-4 text-blue-600 rounded focus:ring-2 focus:ring-blue-500">
                                        </td>
                                        <td class="py-3 px-4 text-sm font-medium text-gray-900 dark:text-white">{{ $data['user']->name }}</td>
                                        <td class="py-3 px-4 text-center">
                                            <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-green-100 text-green-800 dark:bg-green-900/30 dark:text-green-400">
                                                {{ $data['total_hadir'] }}
                                            </span>
                                        </td>
                                        <td class="py-3 px-4 text-center">
                                            <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-yellow-100 text-yellow-800 dark:bg-yellow-900/30 dark:text-yellow-400">
                                                {{ $data['total_izin'] }}
                                            </span>
                                        </td>
                                        <td class="py-3 px-4 text-center">
                                            <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-red-100 text-red-800 dark:bg-red-900/30 dark:text-red-400">
                                                {{ $data['total_sakit'] }}
                                            </span>
                                        </td>
                                        <td class="py-3 px-4 text-center">
                                            <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-purple-100 text-purple-800 dark:bg-purple-900/30 dark:text-purple-400">
                                                {{ $data['total_lembur'] }}
                                            </span>
                                        </td>
                                        <td class="py-3 px-4 text-center">
                                            <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-orange-100 text-orange-800 dark:bg-orange-900/30 dark:text-orange-400">
                                                {{ $data['total_telat'] ?? 0 }}x
                                            </span>
                                        </td>
                                        <td class="py-3 px-4 text-right text-sm font-medium text-red-600 dark:text-red-400">
                                            Rp {{ number_format($data['total_potongan'] ?? 0, 0, ',', '.') }}
                                        </td>
                                        <td class="py-3 px-4 text-center text-sm text-gray-900 dark:text-gray-300">
                                            {{ $data['total_menit_lembur'] ?? 0 }}'
                                        </td>
                                        <td class="py-3 px-4 text-right text-sm font-medium text-purple-600 dark:text-purple-400">
                                            Rp {{ number_format($data['total_gaji_lembur'] ?? 0, 0, ',', '.') }}
                                        </td>
                                        <td class="py-3 px-4 text-right text-sm font-bold text-green-600 dark:text-green-400">
                                            Rp {{ number_format($data['total_gaji'] ?? 0, 0, ',', '.') }}
                                        </td>
                                        <td class="py-3 px-4 text-center">
                                            <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium {{ ($data['total_izin_keluar'] ?? 0) >= 3 ? 'bg-red-100 text-red-800 dark:bg-red-900/30 dark:text-red-400' : 'bg-indigo-100 text-indigo-800 dark:bg-indigo-900/30 dark:text-indigo-400' }}">
                                                {{ $data['total_izin_keluar'] ?? 0 }}x
                                            </span>
                                            @if(($data['total_izin_keluar_ditolak'] ?? 0) > 0)
                                                <div class="text-xs text-red-500 mt-1">{{ $data['total_izin_keluar_ditolak'] }} ditolak</div>
                                            @endif
                                        </td>
                                        <td class="py-3 px-4 text-center">
                                            <a href="{{ route('admin.absensi.user', $data['user']->id) }}" class="inline-flex items-center text-blue-600 hover:text-blue-800 dark:text-blue-400 dark:hover:text-blue-300 font-medium">
                                                <i class="fas fa-eye mr-1"></i>
                                            </a>
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="12" class="text-center py-8 text-gray-500 dark:text-gray-400">
                                            <i class="fas fa-inbox text-3xl mb-2"></i>
                                            <p>Tidak ada data</p>
                                        </td>
                                    </tr>
                                @endforelse
                                @if(count($magangData) > 0)
                                    <tr class="bg-blue-50 dark:bg-blue-900/20">
                                        <td colspan="10" class="py-4 px-4 text-right text-sm font-bold text-gray-900 dark:text-white uppercase">
                                            Total Gaji Magang
                                        </td>
                                        <td class="py-4 px-4 text-right text-base font-bold text-blue-600 dark:text-blue-400">
                                            Rp {{ number_format($totalGajiMagang, 0, ',', '.') }}
                                        </td>
                                        <td></td>
                                    </tr>
                                @endif
                            </tbody>
                        </table>
                    </div>
                </form>
            </div>

            {{-- GRAND TOTAL --}}
            @php
                $grandTotal = $totalGajiOrganik + $totalGajiFreelance + $totalGajiBorongan + $totalGajiMagang;
            @endphp
            <div class="relative overflow-hidden bg-gradient-to-br from-blue-600 via-blue-700 to-indigo-800 dark:from-blue-900 dark:via-indigo-900 dark:to-purple-900 rounded-2xl shadow-xl">
                <div class="absolute inset-0 bg-black/10"></div>
                <div class="relative p-8">
                    <div class="flex flex-col md:flex-row items-center justify-between gap-6">
                        <div class="flex items-center gap-5">
                            <div class="p-4 bg-white/20 backdrop-blur-sm rounded-xl">
                                <i class="fas fa-money-bill-wave text-4xl text-white"></i>
                            </div>
                            <div>
                                <p class="text-blue-100 text-sm font-medium uppercase tracking-wide mb-1">Grand Total Gaji</p>
                                <p class="text-white text-4xl font-bold tracking-tight">
                                    Rp {{ number_format($grandTotal, 0, ',', '.') }}
                                </p>
                                <p class="text-blue-200 text-xs mt-2">4 Kategori Karyawan</p>
                            </div>
                        </div>
                        <div class="text-center md:text-right">
                            <div class="inline-flex items-center gap-2 px-4 py-2 bg-white/20 backdrop-blur-sm rounded-lg">
                                <i class="fas fa-calendar text-blue-100"></i>
                                <div class="text-left">
                                    <p class="text-white text-sm font-semibold">
                                        @if(request('range') == 'weekly' && request('week'))
                                            Minggu ke-{{ request('week') }}
                                        @elseif(request('range') == 'custom')
                                            Custom
                                        @else
                                            Bulanan
                                        @endif
                                    </p>
                                    <p class="text-blue-100 text-xs">
                                        {{ \Carbon\Carbon::createFromFormat('!m', $selectedMonth)->translatedFormat('F') }} {{ $selectedYear }}
                                    </p>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

        </div>
    </div>

    <script nonce="{{ config('app.csp_nonce') }}">
        function toggleTable(source, category) {
            const className = 'user-checkbox-' + category;
            const checkboxes = document.getElementsByClassName(className);
            for(let i = 0; i < checkboxes.length; i++) {
                checkboxes[i].checked = source.checked;
            }
        }

        document.addEventListener('DOMContentLoaded', function() {
            const rangeSelect = document.getElementById('rangeSelect');
            const weekSelect = document.getElementById('weekSelect');
            const customDateStart = document.getElementById('customDateStart');
            const customDateEnd = document.getElementById('customDateEnd');
            const monthSelect = document.getElementById('monthSelect');
            const yearSelect = document.getElementById('yearSelect');

            function toggleFields() {
                const selectedRange = rangeSelect.value;

                if (selectedRange === 'weekly') {
                    weekSelect.classList.remove('hidden');
                    weekSelect.style.display = 'block';
                    customDateStart.classList.add('hidden');
                    customDateEnd.classList.add('hidden');
                    monthSelect.disabled = false;
                    yearSelect.disabled = false;
                } else if (selectedRange === 'custom') {
                    weekSelect.classList.add('hidden');
                    weekSelect.style.display = 'none';
                    customDateStart.classList.remove('hidden');
                    customDateEnd.classList.remove('hidden');
                    monthSelect.disabled = true;
                    yearSelect.disabled = true;
                } else {
                    weekSelect.classList.add('hidden');
                    weekSelect.style.display = 'none';
                    customDateStart.classList.add('hidden');
                    customDateEnd.classList.add('hidden');
                    monthSelect.disabled = false;
                    yearSelect.disabled = false;
                }
            }

            if(rangeSelect) {
                rangeSelect.addEventListener('change', toggleFields);
                toggleFields();
            }

            document.querySelectorAll('.toggle-table-checkbox').forEach(function(checkbox) {
                checkbox.addEventListener('change', function() {
                    toggleTable(this, this.dataset.category);
                });
            });
        });
    </script>

    <style nonce="{{ config('app.csp_nonce') }}">
        @media (max-width: 768px) {
            .overflow-x-auto {
                scrollbar-width: thin;
                scrollbar-color: #94a3b8 #f1f5f9;
            }

            .overflow-x-auto::-webkit-scrollbar {
                height: 8px;
            }

            .overflow-x-auto::-webkit-scrollbar-track {
                background: #f1f5f9;
                border-radius: 10px;
            }

            .overflow-x-auto::-webkit-scrollbar-thumb {
                background: #94a3b8;
                border-radius: 10px;
            }

            .overflow-x-auto::-webkit-scrollbar-thumb:hover {
                background: #64748b;
            }
        }
    </style>
</x-app-layout>
