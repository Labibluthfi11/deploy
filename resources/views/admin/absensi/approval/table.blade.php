{{-- HEADER SECTION --}}
<div class="mb-6">
    <div class="flex items-center justify-between">
        <div>
            <h1 class="text-2xl font-bold text-gray-900 dark:text-white tracking-tight">Persetujuan Lembur / Izin</h1>
            <p class="mt-1 text-sm text-gray-600 dark:text-gray-400">Kelola dan review pengajuan absensi yang memerlukan persetujuan</p>
        </div>
    </div>

    {{-- STATS CARDS --}}
    <div class="grid grid-cols-1 md:grid-cols-4 gap-4 mt-6">
        <div class="bg-white dark:bg-gray-800 rounded-lg border border-gray-200 dark:border-gray-700 p-4 hover:shadow-lg hover:border-gray-300 dark:hover:border-gray-600 transition-all">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-sm font-medium text-gray-600 dark:text-gray-400">Pending</p>
                    <p class="text-2xl font-bold text-gray-900 dark:text-white mt-1">{{ $submissions->where('status_approval', 'pending')->count() }}</p>
                </div>
                <div class="p-3 bg-yellow-500/10 rounded-lg">
                    <svg class="w-6 h-6 text-yellow-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/>
                    </svg>
                </div>
            </div>
        </div>
        <div class="bg-white dark:bg-gray-800 rounded-lg border border-gray-200 dark:border-gray-700 p-4 hover:shadow-lg hover:border-gray-300 dark:hover:border-gray-600 transition-all">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-sm font-medium text-gray-600 dark:text-gray-400">Approved (Final)</p>
                    <p class="text-2xl font-bold text-gray-900 dark:text-white mt-1">{{ $submissions->where('status_approval', 'approved_hrga')->count() }}</p>
                </div>
                <div class="p-3 bg-green-500/10 rounded-lg">
                    <svg class="w-6 h-6 text-green-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                    </svg>
                </div>
            </div>
        </div>
        <div class="bg-white dark:bg-gray-800 rounded-lg border border-gray-200 dark:border-gray-700 p-4 hover:shadow-lg hover:border-gray-300 dark:hover:border-gray-600 transition-all">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-sm font-medium text-gray-600 dark:text-gray-400">Rejected</p>
                    <p class="text-2xl font-bold text-gray-900 dark:text-white mt-1">{{ $submissions->where('status_approval', 'rejected')->count() }}</p>
                </div>
                <div class="p-3 bg-red-500/10 rounded-lg">
                    <svg class="w-6 h-6 text-red-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 14l2-2m0 0l2-2m-2 2l-2-2m2 2l2 2m7-2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                    </svg>
                </div>
            </div>
        </div>
        <div class="bg-white dark:bg-gray-800 rounded-lg border border-gray-200 dark:border-gray-700 p-4 hover:shadow-lg hover:border-gray-300 dark:hover:border-gray-600 transition-all">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-sm font-medium text-gray-600 dark:text-gray-400">Total</p>
                    <p class="text-2xl font-bold text-gray-900 dark:text-white mt-1">{{ $submissions->count() }}</p>
                </div>
                <div class="p-3 bg-indigo-500/10 rounded-lg">
                    <svg class="w-6 h-6 text-indigo-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                    </svg>
                </div>
            </div>
        </div>
    </div>
</div>

{{-- TABLE SECTION --}}
<div class="bg-white dark:bg-gray-800 rounded-2xl shadow-lg border border-gray-200 dark:border-gray-700 overflow-hidden">
    {{-- Table Header --}}
    <div class="px-6 py-4 border-b border-gray-200 dark:border-gray-700 bg-gray-50 dark:bg-gray-800/50">
        <div class="flex items-center justify-between">
            <div class="text-sm text-gray-600 dark:text-gray-400">
                Menampilkan <span class="font-medium text-gray-900 dark:text-white">{{ $submissions->count() }}</span> data
            </div>
        </div>
    </div>

    {{-- Table --}}
    <div class="overflow-x-auto">
        <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
            <thead class="bg-gray-50 dark:bg-gray-800/50">
                <tr>
                    <th scope="col" class="px-6 py-3 text-left text-xs font-semibold text-gray-700 dark:text-gray-300 uppercase tracking-wider">Karyawan</th>
                    <th scope="col" class="px-6 py-3 text-left text-xs font-semibold text-gray-700 dark:text-gray-300 uppercase tracking-wider">Jenis / Tanggal</th>
                    <th scope="col" class="px-6 py-3 text-left text-xs font-semibold text-gray-700 dark:text-gray-300 uppercase tracking-wider">Durasi</th>
                    <th scope="col" class="px-6 py-3 text-left text-xs font-semibold text-gray-700 dark:text-gray-300 uppercase tracking-wider">Bukti</th>
                    <th scope="col" class="px-6 py-3 text-left text-xs font-semibold text-gray-700 dark:text-gray-300 uppercase tracking-wider">Keterangan</th>
                    <th scope="col" class="px-6 py-3 text-left text-xs font-semibold text-gray-700 dark:text-gray-300 uppercase tracking-wider">Status</th>
                    <th scope="col" class="px-6 py-3 text-center text-xs font-semibold text-gray-700 dark:text-gray-300 uppercase tracking-wider">Aksi</th>
                </tr>
            </thead>

            <tbody class="bg-white dark:bg-gray-800 divide-y divide-gray-200 dark:divide-gray-700">
                @forelse ($submissions as $index => $submission)
                    @php
                        $user = $submission->user ?? null;
                        $statusApproval = $submission->status_approval ?? 'pending';

                        $statusLabel = match($statusApproval) {
                            'pending' => ['text' => 'Menunggu', 'color' => 'bg-yellow-500/10 text-yellow-400 border-yellow-500/20', 'icon' => '⏱️'],
                            'approved_supervisor' => ['text' => 'Approved SPV', 'color' => 'bg-blue-500/10 text-blue-400 border-blue-500/20', 'icon' => '✓'],
                            'approved_manager' => ['text' => 'Approved MGR', 'color' => 'bg-purple-500/10 text-purple-400 border-purple-500/20', 'icon' => '✓✓'],
                            'approved_hrga' => ['text' => 'Final Approved', 'color' => 'bg-green-500/10 text-green-400 border-green-500/20', 'icon' => '✓✓✓'],
                            'rejected' => ['text' => 'Ditolak', 'color' => 'bg-red-500/10 text-red-400 border-red-500/20', 'icon' => '✕'],
                            default => ['text' => ucfirst($statusApproval), 'color' => 'bg-gray-100 dark:bg-gray-700 text-gray-600 dark:text-gray-400 border-gray-300 dark:border-gray-600', 'icon' => '•']
                        };

                        $isOvertime = ($submission->tipe === 'lembur');
                        $duration = '-';
                        if ($isOvertime && $submission->lembur_start && $submission->lembur_end) {
                            $start = \Carbon\Carbon::parse($submission->lembur_start);
                            $end = \Carbon\Carbon::parse($submission->lembur_end);
                            $diff = $start->diffInMinutes($end);
                            if ($submission->lembur_rest == 1) $diff = max(0, $diff - 30);
                            $duration = floor($diff / 60) . 'j ' . ($diff % 60) . 'm';
                        }
                    @endphp

                    <tr class="hover:bg-gray-50 dark:hover:bg-gray-700/50 transition-colors">
                        <td class="px-6 py-4 whitespace-nowrap">
                            <div class="flex items-center">
                                <div class="h-10 w-10 rounded-full bg-gradient-to-br from-indigo-500 to-indigo-700 flex items-center justify-center text-white font-semibold text-sm shadow-lg">
                                    {{ strtoupper(substr($user->name ?? 'U', 0, 2)) }}
                                </div>
                                <div class="ml-4">
                                    <div class="text-sm font-semibold text-gray-900 dark:text-white">{{ $user->name ?? '-' }}</div>
                                    <div class="text-xs text-gray-500 dark:text-gray-400">{{ $user->employee_id ?? 'N/A' }}</div>
                                </div>
                            </div>
                        </td>

                        <td class="px-6 py-4">
                            <div class="text-xs font-semibold text-indigo-600 dark:text-indigo-400 uppercase">
                                {{ $submission->submission_type ?? ucfirst($submission->tipe) }}
                            </div>
                            <div class="text-sm text-gray-900 dark:text-white font-medium mt-1">
                                {{ $submission->check_in_at ? \Carbon\Carbon::parse($submission->check_in_at)->isoFormat('DD MMM YYYY') : '-' }}
                            </div>
                        </td>

                        <td class="px-6 py-4 whitespace-nowrap">
                            {{ $isOvertime ? $duration : '-' }}
                        </td>

                        <td class="px-6 py-4">
                            @if ($submission->tipe === 'sakit' || $submission->tipe === 'izin' || $submission->tipe === 'telat')
                                @if ($submission->file_bukti)
                                    <a href="{{ asset('storage/' . $submission->file_bukti) }}" target="_blank" class="text-indigo-600 hover:underline">Lihat Bukti</a>
                                @else
                                    <span class="text-xs text-gray-400 italic">Tidak ada</span>
                                @endif
                            @elseif ($submission->tipe === 'lembur')
                                @php
                                    $allFotos = array_values(array_filter([
                                        $submission->foto_pulang, $submission->foto_pulang_2, $submission->foto_pulang_3,
                                        $submission->foto_pulang_4, $submission->foto_pulang_5, $submission->foto_pulang_6,
                                    ]));
                                @endphp
                                @if (count($allFotos) > 0)
                                    <div class="flex flex-col gap-1">
                                        @foreach($allFotos as $i => $foto)
                                            <a href="{{ asset('storage/' . $foto) }}" target="_blank" class="inline-flex items-center gap-2 px-3 py-1.5 bg-purple-100 dark:bg-purple-900/30 text-purple-700 dark:text-purple-300 rounded-lg text-xs font-medium hover:bg-purple-200 dark:hover:bg-purple-900/50 transition-all">
                                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" />
                                                </svg>
                                                Lihat Bukti {{ $i + 1 }}
                                            </a>
                                        @endforeach
                                    </div>
                                @else
                                    <span class="text-xs text-gray-400 italic">Tidak ada foto</span>
                                @endif
                            @endif
                        </td>

                        <td class="px-6 py-4">
                            <p class="text-sm text-gray-700 dark:text-gray-300 max-w-[200px] truncate">
                                {{ $isOvertime ? ($submission->lembur_keterangan ?? '-') : ($submission->keterangan_izin_sakit ?? '-') }}
                            </p>
                        </td>

                        <td class="px-6 py-4 whitespace-nowrap">
                            <span class="inline-flex items-center px-2.5 py-1 rounded-full text-xs font-semibold border {{ $statusLabel['color'] }}">
                                {{ $statusLabel['text'] }}
                            </span>
                        </td>

                        <td class="px-6 py-4 whitespace-nowrap text-center">
                            @if ($statusApproval === 'pending')
                                <form action="{{ route('admin.absensi.approval.action', ['absensi' => $submission->id, 'action' => 'approve']) }}" method="POST" class="inline">
                                    @csrf
                                    <button type="submit" class="text-green-600 hover:text-green-900 font-bold">✓</button>
                                </form>
                                <form action="{{ route('admin.absensi.approval.action', ['absensi' => $submission->id, 'action' => 'reject']) }}" method="POST" class="inline ml-2">
                                    @csrf
                                    <input type="text" name="catatan_admin" placeholder="Alasan..." class="border rounded px-1 text-xs" required>
                                    <button type="submit" class="text-red-600 hover:text-red-900 font-bold">✕</button>
                                </form>
                            @endif
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="7" class="px-6 py-12 text-center text-gray-500">Tidak ada data</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>
