@props(['daily', 'type' => 'organik'])

@php
    $userId = $daily['user']->id;
    $belumAbsen = !$daily['check_in_time'] && str_contains($daily['status'] ?? '', 'Belum');

    // 🔥 HITUNG TOTAL TELAT + ALPHA BULAN INI (Kecuali Organik jika memang flexible, tapi sesuaikan dengan logic asli per tabel)
    $telatCount = \App\Models\Absensi::where('user_id', $userId)
        ->whereMonth('check_in_at', now()->month)
        ->whereYear('check_in_at', now()->year)
        ->where(function($q) {
            $q->where('late_minutes', '>', 0)  // Telat
              ->orWhereNull('check_in_at');     // Alpha (ga masuk)
        })
        ->count();

    // 🔥 TENTUKAN LEVEL PERINGATAN
    $warningLevel = 0;
    $warningText = '';
    $warningColor = '';
    $warningIcon = '';

    if ($type !== 'organik') {
        if ($telatCount >= 5) {
            $warningLevel = 3;
            $warningText = 'PERINGATAN 3 - Akan Dipanggil HRD!';
            $warningColor = 'bg-red-600 text-white animate-pulse';
            $warningIcon = 'fa-exclamation-circle';
        } elseif ($telatCount >= 3) {
            $warningLevel = 2;
            $warningText = 'Peringatan 2 - Perhatian Serius';
            $warningColor = 'bg-orange-500 text-white';
            $warningIcon = 'fa-exclamation-triangle';
        } elseif ($telatCount >= 1) {
            $warningLevel = 1;
            $warningText = 'Peringatan 1';
            $warningColor = 'bg-yellow-500 text-white';
            $warningIcon = 'fa-info-circle';
        }
    }

    $badgeTypeLabel = match($type) {
        'organik' => 'Organik',
        'freelance' => 'Freelance',
        'borongan' => 'Borongan',
        'magang' => 'Magang',
        default => ucfirst($type)
    };
@endphp

<tr class="transition-colors {{ $belumAbsen ? 'bg-red-50 dark:bg-red-900/20 hover:bg-red-100 dark:hover:bg-red-900/30 border-l-4 border-red-500' : 'hover:bg-gray-50 dark:hover:bg-gray-700/50' }}">
    <td class="px-6 py-4 whitespace-nowrap">
        <a href="{{ route('admin.absensi.user', $daily['user']->id) }}" class="block group">
            <div class="flex items-center gap-2">
                <div class="font-semibold {{ $belumAbsen ? 'text-red-700 dark:text-red-400' : 'text-gray-900 dark:text-gray-100' }} group-hover:text-indigo-600">
                    {{ $daily['user']->name }}
                    @if($belumAbsen)
                        <i class="fas fa-exclamation-triangle text-red-600 ml-2 animate-pulse"></i>
                    @endif
                </div>

                {{-- 🔥 BADGE PERINGATAN --}}
                @if($warningLevel > 0)
                    <span class="inline-flex items-center gap-1 px-2 py-1 rounded-full text-xs font-bold {{ $warningColor }}" title="{{ $telatCount }}x telat/alpha bulan ini">
                        <i class="fas {{ $warningIcon }}"></i>
                        P{{ $warningLevel }}
                    </span>
                @endif
            </div>

            <div class="flex items-center gap-2 mt-1">
                <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-emerald-100 dark:bg-emerald-900 text-emerald-700 dark:text-emerald-300">
                    {{ $badgeTypeLabel }}
                </span>

                {{-- 🔥 TEXT PERINGATAN DETAIL --}}
                @if($warningLevel > 0)
                    <span class="text-xs text-gray-600 dark:text-gray-400">
                        {{ $telatCount }}x telat bulan ini
                    </span>
                @endif
            </div>

            {{-- 🔥 ALERT MERAH BESAR UNTUK LEVEL 3 --}}
            @if($warningLevel === 3)
                <div class="mt-2 p-2 bg-red-100 dark:bg-red-900/30 border-l-4 border-red-600 rounded">
                    <p class="text-xs font-bold text-red-800 dark:text-red-300 flex items-center gap-2">
                        <i class="fas fa-bell animate-bounce"></i>
                        {{ $warningText }}
                    </p>
                </div>
            @endif
        </a>
    </td>

    {{-- Status --}}
    <td class="px-6 py-4 whitespace-nowrap">
        @if($belumAbsen)
            <span class="inline-flex items-center gap-2 px-3 py-1.5 rounded-lg text-sm font-bold bg-red-100 dark:bg-red-900/50 text-red-700 dark:text-red-300 border-2 border-red-300 dark:border-red-700">
                <i class="fas fa-times-circle animate-pulse"></i>
                Belum Absen
            </span>
        @else
            <span class="text-sm text-gray-600 dark:text-gray-300">{{ $daily['status'] ?? '-' }}</span>
        @endif
    </td>

    {{-- Check-in --}}
    <td class="px-6 py-4 whitespace-nowrap">
        @if($daily['check_in_time'])
            <div class="flex items-center gap-2">
                <i class="fas fa-sign-in-alt text-green-600 text-xs"></i>
                <span class="text-sm font-medium text-gray-700 dark:text-gray-300">
                    {{ \Carbon\Carbon::parse($daily['check_in_time'])->format('H:i') }}
                </span>
            </div>
        @else
            <span class="{{ $belumAbsen ? 'text-red-500 dark:text-red-400 font-bold' : 'text-gray-400' }} text-sm">
                @if($belumAbsen)
                    <i class="fas fa-ban"></i> Tidak ada
                @else
                    -
                @endif
            </span>
        @endif
    </td>

    {{-- Keterlambatan --}}
    <td class="px-6 py-4 whitespace-nowrap">
        @php
            $lateMinutes = $daily['late_minutes'] ?? 0;
        @endphp
        @if($type === 'organik')
            <span class="text-gray-400 text-sm">-</span>
        @elseif($belumAbsen)
            <span class="text-red-600 dark:text-red-400 text-sm font-bold">
                <i class="fas fa-exclamation-circle"></i> -
            </span>
        @elseif($lateMinutes > 0)
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

    {{-- Check-out --}}
    <td class="px-6 py-4 whitespace-nowrap">
        @if($daily['check_out_time'])
            <div class="flex items-center gap-2">
                <i class="fas fa-sign-out-alt text-red-600 text-xs"></i>
                <span class="text-sm font-medium text-gray-700 dark:text-gray-300">
                    {{ \Carbon\Carbon::parse($daily['check_out_time'])->format('H:i') }}
                </span>
            </div>
        @else
            <span class="{{ $belumAbsen ? 'text-red-500 dark:text-red-400 font-bold' : 'text-gray-400' }} text-sm">
                @if($belumAbsen)
                    <i class="fas fa-ban"></i> Tidak ada
                @else
                    -
                @endif
            </span>
        @endif
    </td>

    {{-- Detail --}}
    <td class="px-6 py-4 whitespace-nowrap">
        <a href="{{ route('admin.absensi.user', $daily['user']->id) }}" class="inline-flex items-center gap-2 px-4 py-2 {{ $belumAbsen ? 'bg-red-100 text-red-700 hover:bg-red-200 dark:bg-red-900/50 dark:text-red-300' : 'bg-indigo-100 text-indigo-700 hover:bg-indigo-200' }} rounded-lg text-sm font-medium transition-all">
            <i class="fas fa-eye"></i>
            <span>Detail</span>
        </a>
    </td>
</tr>
