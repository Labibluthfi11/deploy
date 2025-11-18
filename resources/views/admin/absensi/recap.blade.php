<x-app-layout>
    <x-slot name="header">
        <div class="flex justify-between items-center">
            <h2 class="font-bold text-2xl leading-tight text-gray-800 dark:text-gray-100">
                {{ __('Rekap Absensi Bulanan Seluruh Karyawan') }}
            </h2>
            <div class="flex items-center space-x-4">
                <a href="{{ route('admin.absensi.index') }}"
                    class="bg-blue-600 hover:bg-blue-700 text-white font-semibold py-2 px-5 rounded-lg shadow-md transition duration-300 ease-in-out transform hover:scale-105 flex items-center">
                    <i class="fas fa-arrow-left mr-2"></i> Kembali ke Dashboard
                </a>

            </div>
        </div>
    </x-slot>

    <div class="py-10 bg-blue-50 dark:bg-gray-950 min-h-screen">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8 space-y-8">

            {{-- Filter --}}
            <div class="bg-white dark:bg-indigo-900 p-6 rounded-xl shadow-lg border border-blue-100 dark:border-indigo-800">
                <h3 class="text-xl font-semibold text-gray-800 dark:text-gray-100 mb-5">Pilih Periode Rekap</h3>

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

                <form method="GET" action="{{ route('admin.absensi.recap') }}" class="grid grid-cols-1 md:grid-cols-5 gap-4">
                    <select name="month" class="form-select block w-full px-4 py-2 text-base bg-blue-50 dark:bg-indigo-800 border border-blue-200 dark:border-indigo-700 rounded-lg">
                        @for ($m = 1; $m <= 12; $m++)
                            <option value="{{ $m }}" {{ $selectedMonth == $m ? 'selected' : '' }}>
                                {{ \Carbon\Carbon::createFromFormat('!m', $m)->translatedFormat('F') }}
                            </option>
                        @endfor
                    </select>

                    <select name="year" class="form-select block w-full px-4 py-2 text-base bg-blue-50 dark:bg-indigo-800 border border-blue-200 dark:border-indigo-700 rounded-lg">
                        @for ($y = date('Y'); $y >= 2020; $y--)
                            <option value="{{ $y }}" {{ $selectedYear == $y ? 'selected' : '' }}>{{ $y }}</option>
                        @endfor
                    </select>

                    <select name="range" class="form-select block w-full px-4 py-2 text-base bg-blue-50 dark:bg-indigo-800 border border-blue-200 dark:border-indigo-700 rounded-lg">
                        <option value="monthly" {{ request('range') == 'monthly' ? 'selected' : '' }}>Bulanan</option>
                        <option value="weekly" {{ request('range') == 'weekly' ? 'selected' : '' }}>Mingguan</option>
                    </select>

                    <select name="week" class="form-select block w-full px-4 py-2 text-base bg-blue-50 dark:bg-indigo-800 border border-blue-200 dark:border-indigo-700 rounded-lg">
                        <option value="">Semua Minggu</option>
                        @for ($i = 1; $i <= $weeks; $i++)
                            <option value="{{ $i }}" {{ request('week') == $i ? 'selected' : '' }}>Minggu ke-{{ $i }}</option>
                        @endfor
                    </select>

                    <button type="submit" class="bg-blue-600 hover:bg-blue-700 text-white font-bold py-3 px-6 rounded-lg shadow-md transition transform hover:scale-105">
                        <i class="fas fa-search mr-2"></i> Tampilkan Rekap
                    </button>
                </form>
            </div>

            {{-- 🔥 TABEL 1: ORGANIK (DENGAN FORM + CHECKBOX) --}}
            <div class="bg-white dark:bg-indigo-900 p-6 rounded-xl shadow-lg overflow-hidden border border-blue-100 dark:border-indigo-800">
                {{-- ⬇️ ⬇️ ⬇️ MULAI FORM DI SINI ⬇️ ⬇️ ⬇️ --}}
                <form action="{{ route('admin.absensi.bulk-export-detail') }}" method="POST">
                    @csrf
                    {{-- Ini input rahasia buat ngirim tanggal --}}
                    <input type="hidden" name="start_date" value="{{ $startDate->format('Y-m-d H:i:s') }}">
                    <input type="hidden" name="end_date" value="{{ $endDate->format('Y-m-d H:i:s') }}">

                    <div class="flex justify-between items-center mb-4">
                        <h3 class="text-xl font-semibold text-gray-800 dark:text-gray-100 flex items-center">
                            <i class="fas fa-calendar-alt mr-3 text-green-500"></i> Rekap Karyawan Organik
                        </h3>
                        {{-- Tombol Submit-nya nempel di sini --}}
                        <button type="submit" class="bg-green-600 hover:bg-green-700 text-white font-semibold py-2 px-4 rounded-lg shadow-md transition duration-300 ease-in-out transform hover:scale-105 flex items-center">
                            <i class="fas fa-download mr-2"></i> Download (Ceklis)
                        </button>
                    </div>

                    <div class="overflow-x-auto custom-scrollbar">
                        @php
                            $organikData = array_filter($recapData, fn($data) => isset($data['kategori']) && $data['kategori'] === 'organik');
                            usort($organikData, fn($a, $b) => $a['user']->name <=> $b['user']->name);
                            $totalGajiOrganik = array_sum(array_column($organikData, 'total_gaji'));
                        @endphp
                        <table class="min-w-full divide-y divide-gray-200 dark:divide-indigo-700">
                            <thead class="bg-blue-50 dark:bg-indigo-800">
                                <tr>
                                    {{-- ⬅️ HEADER BARU (Checkbox "Pilih Semua") --}}
                                    <th class="py-3 px-4">
                                        <input type="checkbox" onclick="toggleTable(this, 'organik')" class="w-4 h-4 text-blue-600 rounded">
                                    </th>
                                    <th class="py-3 px-4 text-left text-xs font-medium text-blue-700 uppercase">Nama</th>
                                    <th class="py-3 px-4 text-left text-xs font-medium text-blue-700 uppercase">Hadir</th>
                                    <th class="py-3 px-4 text-left text-xs font-medium text-blue-700 uppercase">Izin</th>
                                    <th class="py-3 px-4 text-left text-xs font-medium text-blue-700 uppercase">Sakit</th>
                                    <th class="py-3 px-4 text-left text-xs font-medium text-blue-700 uppercase">Lembur</th>
                                    <th class="py-3 px-4 text-left text-xs font-medium text-orange-700 uppercase">Telat (x)</th>
                                    <th class="py-3 px-4 text-left text-xs font-medium text-red-700 uppercase">Total Potongan</th>
                                    <th class="py-3 px-4 text-left text-xs font-medium text-purple-700 uppercase">Total Menit Lembur</th>
                                    <th class="py-3 px-4 text-left text-xs font-medium text-purple-700 uppercase">Total Gaji Lembur</th>
                                    <th class="py-3 px-4 text-left text-xs font-medium text-green-700 uppercase">Total Gaji</th>
                                    <th class="py-3 px-4 text-left text-xs font-medium text-blue-700 uppercase">Aksi</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-blue-100 dark:divide-indigo-700">
                                @forelse ($organikData as $data)
                                    <tr class="hover:bg-blue-50 dark:hover:bg-indigo-800 transition">
                                        {{-- ⬅️ DATA BARU (Checkbox per baris) --}}
                                        <td class="py-3 px-4">
                                            <input type="checkbox" name="user_ids[]" value="{{ $data['user']->id }}" class="user-checkbox-organik w-4 h-4 text-blue-600 rounded">
                                        </td>
                                        <td class="py-3 px-4 font-semibold">{{ $data['user']->name }}</td>
                                        <td class="py-3 px-4 text-green-700 font-semibold">{{ $data['total_hadir'] }}</td>
                                        <td class="py-3 px-4 text-yellow-700">{{ $data['total_izin'] }}</td>
                                        <td class="py-3 px-4 text-red-700">{{ $data['total_sakit'] }}</td>
                                        <td class="py-3 px-4 text-purple-700">{{ $data['total_lembur'] }}</td>
                                        <td class="py-3 px-4 text-orange-700 font-semibold">{{ $data['total_telat'] ?? 0 }}</td>
                                        <td class="py-3 px-4 text-red-700 font-semibold">
                                            Rp {{ number_format($data['total_potongan'] ?? 0, 0, ',', '.') }}
                                        </td>
                                        <td class="py-3 px-4 text-purple-700">
                                            {{ $data['total_menit_lembur'] ?? 0 }} Menit
                                        </td>
                                        <td class="py-3 px-4 text-purple-700 font-semibold">
                                            Rp {{ number_format($data['total_gaji_lembur'] ?? 0, 0, ',', '.') }}
                                        </td>
                                        <td class="py-3 px-4 text-green-700 font-bold">
                                            Rp {{ number_format($data['total_gaji'] ?? 0, 0, ',', '.') }}
                                        </td>
                                        <td class="py-3 px-4">
                                            <a href="{{ route('admin.absensi.user', $data['user']->id) }}" class="text-indigo-600 hover:text-indigo-800 font-semibold">
                                                <i class="fas fa-eye mr-2"></i>Detail
                                            </a>
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="12" class="text-center py-6 text-gray-500">Tidak ada data.</td>
                                    </tr>
                                @endforelse
                                @if(count($organikData) > 0)
                                    <tr class="bg-green-50 dark:bg-green-900/20 font-bold">
                                        <td></td>
                                        <td class="py-3 px-4" colspan="9">TOTAL GAJI ORGANIK</td>
                                        <td class="py-3 px-4 text-green-700 text-lg">
                                            Rp {{ number_format($totalGajiOrganik, 0, ',', '.') }}
                                        </td>
                                        <td></td>
                                    </tr>
                                @endif
                            </tbody>
                        </table>
                    </div>
                </form> {{-- ⬅️ ⬇️ ⬇️ TUTUP FORM DI SINI ⬇️ ⬇️ ⬇️ --}}
            </div>

            {{-- 🔥 TABEL 2: FREELANCE (DENGAN FORM + CHECKBOX) --}}
            <div class="bg-white dark:bg-indigo-900 p-6 rounded-xl shadow-lg overflow-hidden border border-blue-100 dark:border-indigo-800">
                {{-- ⬇️ ⬇️ ⬇️ MULAI FORM DI SINI ⬇️ ⬇️ ⬇️ --}}
                <form action="{{ route('admin.absensi.bulk-export-detail') }}" method="POST">
                    @csrf
                    <input type="hidden" name="start_date" value="{{ $startDate->format('Y-m-d H:i:s') }}">
                    <input type="hidden" name="end_date" value="{{ $endDate->format('Y-m-d H:i:s') }}">

                    <div class="flex justify-between items-center mb-4">
                        <h3 class="text-xl font-semibold text-gray-800 dark:text-gray-100 flex items-center">
                            <i class="fas fa-calendar-alt mr-3 text-orange-500"></i> Rekap Karyawan Freelance
                        </h3>
                        <button type="submit" class="bg-green-600 hover:bg-green-700 text-white font-semibold py-2 px-4 rounded-lg shadow-md transition duration-300 ease-in-out transform hover:scale-105 flex items-center">
                            <i class="fas fa-download mr-2"></i> Download (Ceklis)
                        </button>
                    </div>

                    <div class="overflow-x-auto custom-scrollbar">
                        @php
                            $freelanceData = array_filter($recapData, fn($data) => isset($data['kategori']) && $data['kategori'] === 'freelance');
                            usort($freelanceData, fn($a, $b) => $a['user']->name <=> $b['user']->name);
                            $totalGajiFreelance = array_sum(array_column($freelanceData, 'total_gaji'));
                        @endphp
                        <table class="min-w-full divide-y divide-gray-200 dark:divide-indigo-700">
                            <thead class="bg-blue-50 dark:bg-indigo-800">
                                <tr>
                                    <th class="py-3 px-4">
                                        <input type="checkbox" onclick="toggleTable(this, 'freelance')" class="w-4 h-4 text-blue-600 rounded">
                                    </th>
                                    <th class="py-3 px-4 text-left text-xs font-medium text-blue-700 uppercase">Nama</th>
                                    <th class="py-3 px-4 text-left text-xs font-medium text-blue-700 uppercase">Hadir</th>
                                    <th class="py-3 px-4 text-left text-xs font-medium text-blue-700 uppercase">Izin</th>
                                    <th class="py-3 px-4 text-left text-xs font-medium text-blue-700 uppercase">Sakit</th>
                                    <th class="py-3 px-4 text-left text-xs font-medium text-blue-700 uppercase">Lembur</th>
                                    <th class="py-3 px-4 text-left text-xs font-medium text-orange-700 uppercase">Telat (x)</th>
                                    <th class="py-3 px-4 text-left text-xs font-medium text-red-700 uppercase">Total Potongan</th>
                                    <th class="py-3 px-4 text-left text-xs font-medium text-purple-700 uppercase">Total Menit Lembur</th>
                                    <th class="py-3 px-4 text-left text-xs font-medium text-purple-700 uppercase">Total Gaji Lembur</th>
                                    <th class="py-3 px-4 text-left text-xs font-medium text-green-700 uppercase">Total Gaji</th>
                                    <th class="py-3 px-4 text-left text-xs font-medium text-blue-700 uppercase">Aksi</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-blue-100 dark:divide-indigo-700">
                                @forelse ($freelanceData as $data)
                                    <tr class="hover:bg-blue-50 dark:hover:bg-indigo-800 transition">
                                        <td class="py-3 px-4">
                                            <input type="checkbox" name="user_ids[]" value="{{ $data['user']->id }}" class="user-checkbox-freelance w-4 h-4 text-blue-600 rounded">
                                        </td>
                                        <td class="py-3 px-4 font-semibold">{{ $data['user']->name }}</td>
                                        <td class="py-3 px-4 text-green-700 font-semibold">{{ $data['total_hadir'] }}</td>
                                        <td class="py-3 px-4 text-yellow-700">{{ $data['total_izin'] }}</td>
                                        <td class="py-3 px-4 text-red-700">{{ $data['total_sakit'] }}</td>
                                        <td class="py-3 px-4 text-purple-700">{{ $data['total_lembur'] }}</td>
                                        <td class="py-3 px-4 text-orange-700 font-semibold">{{ $data['total_telat'] ?? 0 }}</td>
                                        <td class="py-3 px-4 text-red-700 font-semibold">
                                            Rp {{ number_format($data['total_potongan'] ?? 0, 0, ',', '.') }}
                                        </td>
                                        <td class="py-3 px-4 text-purple-700">
                                            {{ $data['total_menit_lembur'] ?? 0 }} Menit
                                        </td>
                                        <td class="py-3 px-4 text-purple-700 font-semibold">
                                            Rp {{ number_format($data['total_gaji_lembur'] ?? 0, 0, ',', '.') }}
                                        </td>
                                        <td class="py-3 px-4 text-green-700 font-bold">
                                            Rp {{ number_format($data['total_gaji'] ?? 0, 0, ',', '.') }}
                                        </td>
                                        <td class="py-3 px-4">
                                            <a href="{{ route('admin.absensi.user', $data['user']->id) }}" class="text-indigo-600 hover:text-indigo-800 font-semibold">
                                                <i class="fas fa-eye mr-2"></i>Detail
                                            </a>
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="12" class="text-center py-6 text-gray-500">Tidak ada data.</td>
                                    </tr>
                                @endforelse
                                @if(count($freelanceData) > 0)
                                    <tr class="bg-orange-50 dark:bg-orange-900/20 font-bold">
                                        <td></td>
                                        <td class="py-3 px-4" colspan="9">TOTAL GAJI FREELANCE</td>
                                        <td class="py-3 px-4 text-orange-700 text-lg">
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

            {{-- 🔥 TABEL 3: BORONGAN (DENGAN FORM + CHECKBOX) --}}
            <div class="bg-white dark:bg-indigo-900 p-6 rounded-xl shadow-lg overflow-hidden border border-blue-100 dark:border-indigo-800">
                <form action="{{ route('admin.absensi.bulk-export-detail') }}" method="POST">
                    @csrf
                    <input type="hidden" name="start_date" value="{{ $startDate->format('Y-m-d H:i:s') }}">
                    <input type="hidden" name="end_date" value="{{ $endDate->format('Y-m-d H:i:s') }}">

                    <div class="flex justify-between items-center mb-4">
                        <h3 class="text-xl font-semibold text-gray-800 dark:text-gray-100 flex items-center">
                            <i class="fas fa-hammer mr-3 text-purple-500"></i> Rekap Karyawan Borongan
                        </h3>
                        <button type="submit" class="bg-green-600 hover:bg-green-700 text-white font-semibold py-2 px-4 rounded-lg shadow-md transition duration-300 ease-in-out transform hover:scale-105 flex items-center">
                            <i class="fas fa-download mr-2"></i> Download (Ceklis)
                        </button>
                    </div>

                    <div class="overflow-x-auto custom-scrollbar">
                        @php
                            $boronganData = array_filter($recapData, fn($data) => isset($data['kategori']) && $data['kategori'] === 'borongan');
                            usort($boronganData, fn($a, $b) => $a['user']->name <=> $b['user']->name);
                            $totalGajiBorongan = array_sum(array_column($boronganData, 'total_gaji'));
                        @endphp
                        <table class="min-w-full divide-y divide-gray-200 dark:divide-indigo-700">
                            <thead class="bg-blue-50 dark:bg-indigo-800">
                                <tr>
                                    <th class="py-3 px-4">
                                        <input type="checkbox" onclick="toggleTable(this, 'borongan')" class="w-4 h-4 text-blue-600 rounded">
                                    </th>
                                    <th class="py-3 px-4 text-left text-xs font-medium text-blue-700 uppercase">Nama</th>
                                    <th class="py-3 px-4 text-left text-xs font-medium text-blue-700 uppercase">Hadir</th>
                                    <th class="py-3 px-4 text-left text-xs font-medium text-blue-700 uppercase">Izin</th>
                                    <th class="py-3 px-4 text-left text-xs font-medium text-blue-700 uppercase">Sakit</th>
                                    <th class="py-3 px-4 text-left text-xs font-medium text-blue-700 uppercase">Lembur</th>
                                    <th class="py-3 px-4 text-left text-xs font-medium text-orange-700 uppercase">Telat (x)</th>
                                    <th class="py-3 px-4 text-left text-xs font-medium text-red-700 uppercase">Total Potongan</th>
                                    <th class="py-3 px-4 text-left text-xs font-medium text-purple-700 uppercase">Total Menit Lembur</th>
                                    <th class="py-3 px-4 text-left text-xs font-medium text-purple-700 uppercase">Total Gaji Lembur</th>
                                    <th class="py-3 px-4 text-left text-xs font-medium text-green-700 uppercase">Total Gaji</th>
                                    <th class="py-3 px-4 text-left text-xs font-medium text-blue-700 uppercase">Aksi</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-blue-100 dark:divide-indigo-700">
                                @forelse ($boronganData as $data)
                                    <tr class="hover:bg-blue-50 dark:hover:bg-indigo-800 transition">
                                        <td class="py-3 px-4">
                                            <input type="checkbox" name="user_ids[]" value="{{ $data['user']->id }}" class="user-checkbox-borongan w-4 h-4 text-blue-600 rounded">
                                        </td>
                                        <td class="py-3 px-4 font-semibold">{{ $data['user']->name }}</td>
                                        <td class="py-3 px-4 text-green-700 font-semibold">{{ $data['total_hadir'] }}</td>
                                        <td class="py-3 px-4 text-yellow-700">{{ $data['total_izin'] }}</td>
                                        <td class="py-3 px-4 text-red-700">{{ $data['total_sakit'] }}</td>
                                        <td class="py-3 px-4 text-purple-700">{{ $data['total_lembur'] }}</td>
                                        <td class="py-3 px-4 text-orange-700 font-semibold">{{ $data['total_telat'] ?? 0 }}</td>
                                        <td class="py-3 px-4 text-red-700 font-semibold">
                                            Rp {{ number_format($data['total_potongan'] ?? 0, 0, ',', '.') }}
                                        </td>
                                        <td class="py-3 px-4 text-purple-700">
                                            {{ $data['total_menit_lembur'] ?? 0 }} Menit
                                        </td>
                                        <td class="py-3 px-4 text-purple-700 font-semibold">
                                            Rp {{ number_format($data['total_gaji_lembur'] ?? 0, 0, ',', '.') }}
                                        </td>
                                        <td class="py-3 px-4 text-green-700 font-bold">
                                            Rp {{ number_format($data['total_gaji'] ?? 0, 0, ',', '.') }}
                                        </td>
                                        <td class="py-3 px-4">
                                            <a href="{{ route('admin.absensi.user', $data['user']->id) }}" class="text-indigo-600 hover:text-indigo-800 font-semibold">
                                                <i class="fas fa-eye mr-2"></i>Detail
                                            </a>
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="12" class="text-center py-6 text-gray-500">Tidak ada data.</td>
                                    </tr>
                                @endforelse
                                @if(count($boronganData) > 0)
                                    <tr class="bg-purple-50 dark:bg-purple-900/20 font-bold">
                                        <td></td>
                                        <td class="py-3 px-4" colspan="9">TOTAL GAJI BORONGAN</td>
                                        <td class="py-3 px-4 text-purple-700 text-lg">
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

            {{-- 🔥 TABEL 4: MAGANG (DENGAN FORM + CHECKBOX) --}}
            <div class="bg-white dark:bg-indigo-900 p-6 rounded-xl shadow-lg overflow-hidden border border-blue-100 dark:border-indigo-800">
                <form action="{{ route('admin.absensi.bulk-export-detail') }}" method="POST">
                    @csrf
                    <input type="hidden" name="start_date" value="{{ $startDate->format('Y-m-d H:i:s') }}">
                    <input type="hidden" name="end_date" value="{{ $endDate->format('Y-m-d H:i:s') }}">
                    <div class="flex justify-between items-center mb-4">
                        <h3 class="text-xl font-semibold text-gray-800 dark:text-gray-100 flex items-center">
                            <i class="fas fa-graduation-cap mr-3 text-blue-500"></i> Rekap Karyawan Magang
                        </h3>
                        <button type="submit" class="bg-green-600 hover:bg-green-700 text-white font-semibold py-2 px-4 rounded-lg shadow-md transition duration-300 ease-in-out transform hover:scale-105 flex items-center">
                            <i class="fas fa-download mr-2"></i> Download (Ceklis)
                        </button>
                    </div>

                    <div class="overflow-x-auto custom-scrollbar">
                        @php
                            $magangData = array_filter($recapData, fn($data) => isset($data['kategori']) && $data['kategori'] === 'magang');
                            usort($magangData, fn($a, $b) => $a['user']->name <=> $b['user']->name);
                            $totalGajiMagang = array_sum(array_column($magangData, 'total_gaji'));
                        @endphp
                        <table class="min-w-full divide-y divide-gray-200 dark:divide-indigo-700">
                            <thead class="bg-blue-50 dark:bg-indigo-800">
                                <tr>
                                    <th class="py-3 px-4">
                                        <input type="checkbox" onclick="toggleTable(this, 'magang')" class="w-4 h-4 text-blue-600 rounded">
                                    </th>
                                    <th class="py-3 px-4 text-left text-xs font-medium text-blue-700 uppercase">Nama</th>
                                    <th class="py-3 px-4 text-left text-xs font-medium text-blue-700 uppercase">Hadir</th>
                                    <th class="py-3 px-4 text-left text-xs font-medium text-blue-700 uppercase">Izin</th>
                                    <th class="py-3 px-4 text-left text-xs font-medium text-blue-700 uppercase">Sakit</th>
                                    <th class="py-3 px-4 text-left text-xs font-medium text-blue-700 uppercase">Lembur</th>
                                    <th class="py-3 px-4 text-left text-xs font-medium text-orange-700 uppercase">Telat (x)</th>
                                    <th class="py-3 px-4 text-left text-xs font-medium text-red-700 uppercase">Total Potongan</th>
                                    <th class="py-3 px-4 text-left text-xs font-medium text-purple-700 uppercase">Total Menit Lembur</th>
                                    <th class="py-3 px-4 text-left text-xs font-medium text-purple-700 uppercase">Total Gaji Lembur</th>
                                    <th class="py-3 px-4 text-left text-xs font-medium text-green-700 uppercase">Total Gaji</th>
                                    <th class="py-3 px-4 text-left text-xs font-medium text-blue-700 uppercase">Aksi</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-blue-100 dark:divide-indigo-700">
                                @forelse ($magangData as $data)
                                    <tr class="hover:bg-blue-50 dark:hover:bg-indigo-800 transition">
                                        <td class="py-3 px-4">
                                            <input type="checkbox" name="user_ids[]" value="{{ $data['user']->id }}" class="user-checkbox-magang w-4 h-4 text-blue-600 rounded">
                                        </td>
                                        <td class="py-3 px-4 font-semibold">{{ $data['user']->name }}</td>
                                        <td class="py-3 px-4 text-green-700 font-semibold">{{ $data['total_hadir'] }}</td>
                                        <td class="py-3 px-4 text-yellow-700">{{ $data['total_izin'] }}</td>
                                        <td class="py-3 px-4 text-red-700">{{ $data['total_sakit'] }}</td>
                                        <td class="py-3 px-4 text-purple-700">{{ $data['total_lembur'] }}</td>
                                        <td class="py-3 px-4 text-orange-700 font-semibold">{{ $data['total_telat'] ?? 0 }}</td>
                                        <td class="py-3 px-4 text-red-700 font-semibold">
                                            Rp {{ number_format($data['total_potongan'] ?? 0, 0, ',', '.') }}
                                        </td>
                                        <td class="py-3 px-4 text-purple-700">
                                            {{ $data['total_menit_lembur'] ?? 0 }} Menit
                                        </td>
                                        <td class="py-3 px-4 text-purple-700 font-semibold">
                                            Rp {{ number_format($data['total_gaji_lembur'] ?? 0, 0, ',', '.') }}
                                        </td>
                                        <td class="py-3 px-4 text-green-700 font-bold">
                                            Rp {{ number_format($data['total_gaji'] ?? 0, 0, ',', '.') }}
                                        </td>
                                        <td class="py-3 px-4">
                                            <a href="{{ route('admin.absensi.user', $data['user']->id) }}" class="text-indigo-600 hover:text-indigo-800 font-semibold">
                                                <i class="fas fa-eye mr-2"></i>Detail
                                            </a>
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="12" class="text-center py-6 text-gray-500">Tidak ada data.</td>
                                    </tr>
                                @endforelse
                                @if(count($magangData) > 0)
                                    <tr class="bg-blue-50 dark:bg-blue-900/20 font-bold">
                                        <td></td>
                                        <td class="py-3 px-4" colspan="9">TOTAL GAJI MAGANG</td>
                                        <td class="py-3 px-4 text-blue-700 text-lg">
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

            {{-- 🔥 GRAND TOTAL (4 KATEGORI) --}}
            @php
                $grandTotal = $totalGajiOrganik + $totalGajiFreelance + $totalGajiBorongan + $totalGajiMagang;
            @endphp
            <div class="bg-gradient-to-r from-blue-600 to-indigo-600 dark:from-indigo-800 dark:to-indigo-900 p-6 rounded-xl shadow-2xl border-2 border-blue-300 dark:border-indigo-600">
                <div class="flex items-center justify-between">
                    <div class="flex items-center gap-4">
                        <div class="bg-white/20 p-4 rounded-full">
                            <i class="fas fa-money-bill-wave text-3xl text-white"></i>
                        </div>
                        <div>
                            <p class="text-white/80 text-sm font-semibold uppercase tracking-wide">Grand Total Gaji (4 Kategori)</p>
                            <p class="text-white text-3xl font-bold mt-1">
                                Rp {{ number_format($grandTotal, 0, ',', '.') }}
                            </p>
                        </div>
                    </div>
                    <div class="text-right text-white/90">
                        <p class="text-sm">Periode:
                            @if(request('range') == 'weekly' && request('week'))
                                Minggu ke-{{ request('week') }}
                            @else
                                Bulanan
                            @endif
                        </p>
                        <p class="text-sm">
                            {{ \Carbon\Carbon::createFromFormat('!m', $selectedMonth)->translatedFormat('F') }} {{ $selectedYear }}
                        </p>
                    </div>
                </div>
            </div>

        </div>
    </div>

    {{-- 🔥 MODAL UNTUK TOMBOL "Export Detail Massal" (PLACEHOLDER) --}}
    <div id="bulkModal" class="hidden fixed inset-0 bg-gray-900 bg-opacity-50 z-50 flex items-center justify-center">
        <div class="bg-white dark:bg-gray-800 rounded-lg shadow-xl p-6 max-w-md w-full mx-4">
            <h3 class="text-xl font-bold text-gray-800 dark:text-gray-100 mb-4">
                <i class="fas fa-info-circle text-indigo-600 mr-2"></i>
                Export Detail Massal
            </h3>
            <p class="text-gray-600 dark:text-gray-300 mb-6">
                Fitur ini sedang dalam pengembangan. Silakan gunakan checkbox di setiap tabel untuk export per kategori.
            </p>
            <button onclick="closeBulkModal()" class="w-full bg-indigo-600 hover:bg-indigo-700 text-white font-semibold py-2 px-4 rounded-lg transition">
                Tutup
            </button>
        </div>
    </div>

    <script src="https://kit.fontawesome.com/a076d05399.js" crossorigin="anonymous"></script>
    <script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js"></script>

    {{-- 🔥 JAVASCRIPT: Toggle Checkbox "Pilih Semua" --}}
    <script>
        // Fungsi untuk toggle semua checkbox dalam satu kategori
        function toggleTable(source, type) {
            const checkboxes = document.querySelectorAll('.user-checkbox-' + type);
            for (var i = 0, n = checkboxes.length; i < n; i++) {
                checkboxes[i].checked = source.checked;
            }
        }

        // Fungsi untuk modal (placeholder)
        function openBulkModal() {
            document.getElementById('bulkModal').classList.remove('hidden');
        }

        function closeBulkModal() {
            document.getElementById('bulkModal').classList.add('hidden');
        }
    </script>

    <style>
        .custom-scrollbar::-webkit-scrollbar { height: 8px; }
        .custom-scrollbar::-webkit-scrollbar-thumb { background-color: #94a3b8; border-radius: 10px; }
    </style>
</x-app-layout>
