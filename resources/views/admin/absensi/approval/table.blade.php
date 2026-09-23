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

{{-- GRID SECTION --}}
<div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
    @forelse ($submissions as $submission)
        @php
            $user = $submission->user ?? null;
            $statusApproval = $submission->status_approval ?? 'pending';
            $typeColor = match($submission->tipe) {
                'lembur' => 'border-indigo-500',
                'izin' => 'border-yellow-500',
                'sakit' => 'border-red-500',
                default => 'border-gray-200',
            };
        @endphp
        
        <div class="bg-white dark:bg-gray-800 rounded-xl shadow-md border-l-4 {{ $typeColor }} p-5 hover:shadow-lg transition-all">
            <div class="flex justify-between items-start mb-4">
                <div>
                    <h3 class="text-lg font-bold text-gray-900 dark:text-white">{{ $user->name ?? '-' }}</h3>
                    <p class="text-sm text-gray-500">{{ $user->employee_id ?? 'ID: -' }}</p>
                </div>
                <span class="px-2 py-1 text-xs font-semibold rounded-full bg-gray-100 dark:bg-gray-700 text-gray-600 dark:text-gray-300 capitalize">
                    {{ $submission->tipe }}
                </span>
            </div>
            
            <div class="space-y-2 text-sm text-gray-600 dark:text-gray-400">
                <p>📅 {{ $submission->check_in_at ? \Carbon\Carbon::parse($submission->check_in_at)->isoFormat('DD MMM YYYY') : '-' }}</p>
                @if($submission->tipe === 'lembur')
                    <p>⏱️ Durasi: {{ floor($submission->lembur_start ? \Carbon\Carbon::parse($submission->lembur_start)->diffInMinutes(\Carbon\Carbon::parse($submission->lembur_end))/60 : 0) }}j {{ ($submission->lembur_start ? \Carbon\Carbon::parse($submission->lembur_start)->diffInMinutes(\Carbon\Carbon::parse($submission->lembur_end))%60 : 0) }}m</p>
                @endif
            </div>

            <div class="mt-4 pt-4 border-t border-gray-100 dark:border-gray-700">
                <p class="text-sm text-gray-700 dark:text-gray-300 truncate italic">"{{ $submission->tipe === 'lembur' ? ($submission->lembur_keterangan ?? '-') : ($submission->keterangan_izin_sakit ?? '-') }}"</p>
            </div>
            
            <div class="mt-4 pt-4 border-t border-gray-100 dark:border-gray-700">
                <div class="font-semibold text-xs text-gray-500 mb-2">BUKTI:</div>
                <div class="flex flex-wrap gap-2">
                    @if ($submission->tipe === 'sakit' || $submission->tipe === 'izin' || $submission->tipe === 'telat')
                        @if ($submission->file_bukti)
                            <a href="{{ asset('storage/' . $submission->file_bukti) }}" target="_blank" class="text-indigo-600 hover:underline text-xs">Lihat Bukti</a>
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
                            @foreach($allFotos as $i => $foto)
                                <a href="{{ asset('storage/' . $foto) }}" target="_blank" class="text-purple-600 hover:underline text-xs">Bukti {{ $i + 1 }}</a>
                            @endforeach
                        @else
                            <span class="text-xs text-gray-400 italic">Tidak ada foto</span>
                        @endif
                    @endif
                </div>
            </div>

            <div class="mt-4 pt-4 border-t border-gray-100 dark:border-gray-700 flex justify-between items-center">
                <span class="inline-flex items-center px-2.5 py-1 rounded-full text-xs font-semibold border {{ match($statusApproval) {
                    'pending' => 'bg-yellow-500/10 text-yellow-400 border-yellow-500/20',
                    'approved_hrga' => 'bg-green-500/10 text-green-400 border-green-500/20',
                    'rejected' => 'bg-red-500/10 text-red-400 border-red-500/20',
                    default => 'bg-gray-100 text-gray-600'
                } }}">
                    {{ ucfirst(str_replace('_', ' ', $statusApproval)) }}
                </span>
                
                @if ($statusApproval === 'pending')
                    <div class="flex gap-2">
                        <form action="{{ route('admin.absensi.approval.action', ['absensi' => $submission->id, 'action' => 'approve']) }}" method="POST">
                            @csrf
                            <input type="hidden" name="catatan_admin" value="Disetujui">
                            <button type="submit" class="text-green-600 font-bold hover:text-green-800">✓</button>
                        </form>
                        <form action="{{ route('admin.absensi.approval.action', ['absensi' => $submission->id, 'action' => 'reject']) }}" method="POST" class="flex items-center">
                            @csrf
                            <input type="text" name="catatan_admin" placeholder="Alasan..." class="border rounded px-1 text-xs w-20" required>
                            <button type="submit" class="text-red-600 font-bold hover:text-red-800 ml-1">✕</button>
                        </form>
                    </div>
                @endif
            </div>
        </div>
    @empty
        <div class="col-span-full py-12 text-center text-gray-500">Tidak ada data pengajuan.</div>
    @endforelse
</div>
