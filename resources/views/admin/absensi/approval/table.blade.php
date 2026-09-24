{{-- HEADER SECTION --}}
<div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4 mb-8">
    <div>
        <h1 class="text-2xl font-bold tracking-tight text-gray-900 dark:text-white">Persetujuan Absensi</h1>
        <p class="text-sm text-gray-500 dark:text-gray-400 mt-1">Review dan kelola pengajuan lembur, izin, atau sakit karyawan.</p>
    </div>
</div>

{{-- GRID SECTION --}}
<div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
    @forelse ($submissions as $submission)
        @php
            $user = $submission->user ?? null;
            $isOvertime = ($submission->tipe === 'lembur');
            $status = $submission->status_approval ?? 'pending';

            // Durasi lembur
            $durationStr = '-';
            if ($isOvertime && $submission->overtime_minutes > 0) {
                $diffInMinutes = $submission->overtime_minutes;
                $hours = floor($diffInMinutes / 60);
                $minutes = $diffInMinutes % 60;
                $durationStr = $hours . ' jam ' . $minutes . ' menit';
            } elseif ($isOvertime && $submission->lembur_start && $submission->lembur_end) {
                $start = \Carbon\Carbon::parse($submission->lembur_start);
                $end = \Carbon\Carbon::parse($submission->lembur_end);
                $diffInMinutes = $start->diffInMinutes($end);
                if ($submission->lembur_rest == 1) {
                    $diffInMinutes = max(0, $diffInMinutes - 30);
                }
                $hours = floor($diffInMinutes / 60);
                $minutes = $diffInMinutes % 60;
                $durationStr = $hours . ' jam ' . $minutes . ' menit';
            }

            // Warna Badge Status
            $statusColors = [
                'pending' => 'bg-amber-50 text-amber-700 border border-amber-200/60 dark:bg-amber-500/10 dark:text-amber-400 dark:border-amber-500/20',
                'approved_hrga' => 'bg-emerald-50 text-emerald-700 border border-emerald-200/60 dark:bg-emerald-500/10 dark:text-emerald-400 dark:border-emerald-500/20',
                'rejected' => 'bg-rose-50 text-rose-700 border border-rose-200/60 dark:bg-rose-500/10 dark:text-rose-400 dark:border-rose-500/20',
                'default' => 'bg-gray-50 text-gray-700 border border-gray-200 dark:bg-gray-800 dark:text-gray-300 dark:border-gray-700'
            ];
            $statusClass = $statusColors[$status] ?? $statusColors['default'];

            // Border warna di sisi kiri card sesuai tipe
            $typeBorder = [
                'lembur' => 'border-l-indigo-500',
                'izin' => 'border-l-amber-500',
                'sakit' => 'border-l-rose-500',
                'telat' => 'border-l-orange-500',
                'default' => 'border-l-gray-300 dark:border-l-gray-600',
            ];
            $borderClass = $typeBorder[$submission->tipe] ?? $typeBorder['default'];

            $initial = $user->name ? strtoupper(substr($user->name, 0, 2)) : '??';
        @endphp

        <div class="bg-white dark:bg-gray-900 rounded-2xl border border-gray-200/80 dark:border-gray-800 border-l-4 {{ $borderClass }} p-5 shadow-sm hover:shadow-md transition-all duration-200 flex flex-col justify-between">
            <div>
                {{-- CARD HEADER --}}
                <div class="flex justify-between items-start gap-3 mb-4">
                    <div class="flex items-center gap-3 min-w-0">
                        <div class="w-10 h-10 rounded-xl bg-gray-100 dark:bg-gray-800 flex items-center justify-center text-gray-700 dark:text-gray-200 text-xs font-semibold tracking-wider flex-shrink-0">
                            {{ $initial }}
                        </div>
                        <div class="min-w-0">
                            <h3 class="text-sm font-semibold text-gray-900 dark:text-white truncate">{{ $user->name ?? '-' }}</h3>
                            <span class="inline-block text-[11px] font-medium text-gray-400 dark:text-gray-500 uppercase tracking-wider mt-0.5">{{ $submission->tipe }}</span>
                        </div>
                    </div>
                    <span class="px-2.5 py-1 rounded-full text-[10px] font-semibold tracking-wide uppercase flex-shrink-0 {{ $statusClass }}">
                        {{ str_replace('_', ' ', $status) }}
                    </span>
                </div>

                {{-- DETAILS METADATA --}}
                <div class="space-y-2 mb-4 bg-gray-50/50 dark:bg-gray-800/50 p-3 rounded-xl border border-gray-100 dark:border-gray-800 text-xs">
                    <div class="flex items-center justify-between">
                        <span class="text-gray-400 dark:text-gray-500 font-medium">Tanggal</span>
                        <span class="text-gray-700 dark:text-gray-200 font-semibold">
                            {{ $submission->check_in_at ? \Carbon\Carbon::parse($submission->check_in_at)->isoFormat('DD MMM YYYY') : '-' }}
                        </span>
                    </div>

                    @if($isOvertime)
                        @if($submission->lembur_start && $submission->lembur_end)
                            <div class="flex items-center justify-between">
                                <span class="text-gray-400 dark:text-gray-500 font-medium">Jam Lembur</span>
                                <span class="text-gray-700 dark:text-gray-200 font-semibold">
                                    {{ \Carbon\Carbon::parse($submission->lembur_start)->format('H:i') }} - {{ \Carbon\Carbon::parse($submission->lembur_end)->format('H:i') }}
                                </span>
                            </div>
                        @endif
                        <div class="flex items-center justify-between">
                            <span class="text-gray-400 dark:text-gray-500 font-medium">Durasi</span>
                            <span class="text-gray-700 dark:text-gray-200 font-semibold">{{ $durationStr }}</span>
                        </div>
                        <div class="flex items-center justify-between">
                            <span class="text-gray-400 dark:text-gray-500 font-medium">Istirahat</span>
                            <span class="inline-flex items-center gap-1.5 text-gray-700 dark:text-gray-200 font-semibold">
                                <span class="w-1.5 h-1.5 rounded-full {{ $submission->lembur_rest == 1 ? 'bg-teal-500' : 'bg-gray-300 dark:bg-gray-600' }}"></span>
                                {{ $submission->lembur_rest == 1 ? '30 menit' : 'Tidak' }}
                            </span>
                        </div>
                    @endif
                </div>

                {{-- KETERANGAN --}}
                <div class="mb-4">
                    <p class="text-[11px] font-semibold text-gray-400 dark:text-gray-500 uppercase tracking-wider mb-1">Keterangan</p>
                    <p class="text-xs text-gray-600 dark:text-gray-300 leading-relaxed line-clamp-2">
                        {{ $isOvertime ? ($submission->lembur_keterangan ?? '-') : ($submission->keterangan_izin_sakit ?? '-') }}
                    </p>
                </div>

                {{-- BUKTI LAMPIRAN --}}
                <div class="mb-4">
                    <p class="text-[11px] font-semibold text-gray-400 dark:text-gray-500 uppercase tracking-wider mb-1.5">Bukti Lampiran</p>
                    <div class="flex flex-wrap gap-2">
                        @if ($submission->tipe === 'sakit' || $submission->tipe === 'izin' || $submission->tipe === 'telat')
                            @if ($submission->file_bukti)
                                <a href="{{ asset('storage/' . $submission->file_bukti) }}" target="_blank" class="inline-flex items-center gap-1 text-xs font-medium text-indigo-600 dark:text-indigo-400 hover:text-indigo-700 bg-indigo-50 dark:bg-indigo-950/50 px-2.5 py-1 rounded-lg border border-indigo-100 dark:border-indigo-900/50 transition-colors">
                                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/></svg>
                                    Lihat File
                                </a>
                            @else
                                <span class="text-xs text-gray-400 dark:text-gray-600 italic">Tidak ada lampiran</span>
                            @endif
                        @elseif ($submission->tipe === 'lembur')
                            @php
                                $allFotos = array_values(array_filter([
                                    $submission->foto_pulang, $submission->foto_pulang_2, $submission->foto_pulang_3,
                                    $submission->foto_pulang_4, $submission->foto_pulang_5, $submission->foto_pulang_6,
                                ]));
                            @endphp
                            @forelse($allFotos as $i => $foto)
                                <a href="{{ asset('storage/' . $foto) }}" target="_blank" class="inline-flex items-center gap-1 text-xs font-medium text-indigo-600 dark:text-indigo-400 hover:text-indigo-700 bg-indigo-50 dark:bg-indigo-950/50 px-2.5 py-1 rounded-lg border border-indigo-100 dark:border-indigo-900/50 transition-colors">
                                    Foto {{ $i + 1 }}
                                </a>
                            @empty
                                <span class="text-xs text-gray-400 dark:text-gray-600 italic">Tidak ada foto</span>
                            @endforelse
                        @endif
                    </div>
                </div>
            </div>

            {{-- ACTION BUTTONS (Tombol Setuju Tetap Hijau Konsisten) --}}
            @if ($status === 'pending')
                <div class="pt-4 border-t border-gray-100 dark:border-gray-800 grid grid-cols-2 gap-2 mt-auto">
                    <form action="{{ route('admin.absensi.approval.action', ['absensi' => $submission->id, 'action' => 'approve']) }}" method="POST">
                        @csrf
                        <input type="hidden" name="catatan_admin" value="Disetujui">
                        <button type="submit" class="w-full py-2.5 px-3 bg-emerald-600 hover:bg-emerald-700 text-white text-xs font-semibold rounded-xl shadow-sm transition-all active:scale-[0.98]">
                            Setujui
                        </button>
                    </form>
                    
                    <form action="{{ route('admin.absensi.approval.action', ['absensi' => $submission->id, 'action' => 'reject']) }}" method="POST" class="flex gap-1">
                        @csrf
                        <input type="text" name="catatan_admin" placeholder="Alasan..." class="w-full bg-gray-50 dark:bg-gray-800 border border-gray-200 dark:border-gray-700 text-gray-900 dark:text-white rounded-xl px-2.5 text-xs focus:ring-2 focus:ring-rose-500/20 focus:border-rose-500 outline-none transition-all" required>
                        <button type="submit" class="flex-shrink-0 px-3 py-2.5 bg-rose-50 hover:bg-rose-100 dark:bg-rose-950/40 dark:hover:bg-rose-900/50 border border-rose-200 dark:border-rose-900/50 text-rose-600 dark:text-rose-400 text-xs font-semibold rounded-xl transition-all active:scale-[0.98]">
                            Tolak
                        </button>
                    </form>
                </div>
            @endif
        </div>
    @empty
        <div class="col-span-full py-20 text-center bg-white dark:bg-gray-900 rounded-2xl border border-dashed border-gray-200 dark:border-gray-800">
            <svg class="w-12 h-12 mx-auto text-gray-300 dark:text-gray-600 mb-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012-2m-6 9l2 2 4-4"/></svg>
            <p class="text-sm font-medium text-gray-500 dark:text-gray-400">Tidak ada data pengajuan absensi saat ini.</p>
        </div>
    @endforelse
</div>