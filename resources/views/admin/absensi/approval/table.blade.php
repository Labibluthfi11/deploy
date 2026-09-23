{{-- HEADER SECTION --}}
<div class="mb-8">
    <h1 class="text-2xl font-semibold text-gray-900 dark:text-white">Persetujuan Absensi</h1>
    <p class="text-sm text-gray-500 mt-1">Review dan kelola pengajuan karyawan.</p>
</div>

{{-- GRID SECTION --}}
<div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
    @forelse ($submissions as $submission)
        @php
            $user = $submission->user ?? null;
            $isOvertime = ($submission->tipe === 'lembur');
            $status = $submission->status_approval ?? 'pending';

            // Perbaikan perhitungan durasi
            $durationStr = '-';
            if ($isOvertime && $submission->lembur_start && $submission->lembur_end) {
                $start = \Carbon\Carbon::parse($submission->lembur_start);
                $end = \Carbon\Carbon::parse($submission->lembur_end);
                $diffInMinutes = $start->diffInMinutes($end);

                // Logika potong istirahat 30 menit
                if ($submission->lembur_rest == 1) {
                    $diffInMinutes = max(0, $diffInMinutes - 30);
                }

                $hours = floor($diffInMinutes / 60);
                $minutes = $diffInMinutes % 60;
                $durationStr = $hours . ' jam ' . $minutes . ' menit';
            }

            // Minimalist status colors
            $statusColors = [
                'pending' => 'text-amber-600 bg-amber-50',
                'approved_hrga' => 'text-emerald-600 bg-emerald-50',
                'rejected' => 'text-rose-600 bg-rose-50',
                'default' => 'text-gray-600 bg-gray-50'
            ];
            $statusClass = $statusColors[$status] ?? $statusColors['default'];
        @endphp
        
        <div class="bg-white dark:bg-gray-800 rounded-xl border border-gray-100 dark:border-gray-700 p-6 shadow-sm hover:shadow-md transition-shadow">
            <div class="flex justify-between items-start mb-4">
                <div>
                    <h3 class="font-semibold text-gray-900 dark:text-white">{{ $user->name ?? '-' }}</h3>
                    <p class="text-xs text-gray-400 uppercase tracking-wider font-medium">{{ $submission->tipe }}</p>
                </div>
                <span class="px-2.5 py-0.5 rounded-full text-[10px] font-bold uppercase {{ $statusClass }}">
                    {{ str_replace('_', ' ', $status) }}
                </span>
            </div>
            
            <div class="space-y-3 mb-6">
                <div class="text-sm">
                    <p class="text-gray-400 text-xs">Tanggal</p>
                    <p class="text-gray-900 dark:text-gray-200">{{ $submission->check_in_at ? \Carbon\Carbon::parse($submission->check_in_at)->isoFormat('DD MMM YYYY') : '-' }}</p>
                </div>
                
                @if($isOvertime)
                    <div class="text-sm">
                        <p class="text-gray-400 text-xs">Durasi</p>
                        <p class="text-gray-900 dark:text-gray-200">
                            {{ $durationStr }}
                        </p>
                    </div>
                @endif

                <div class="text-sm">
                    <p class="text-gray-400 text-xs">Keterangan</p>
                    <p class="text-gray-900 dark:text-gray-200 line-clamp-2 text-sm italic">"{{ $isOvertime ? ($submission->lembur_keterangan ?? '-') : ($submission->keterangan_izin_sakit ?? '-') }}"</p>
                </div>
            </div>

            <div class="pt-4 border-t border-gray-50">
                <div class="text-xs text-gray-400 font-semibold mb-2 uppercase">Bukti Lampiran</div>
                <div class="flex flex-wrap gap-2">
                    @if ($submission->tipe === 'sakit' || $submission->tipe === 'izin' || $submission->tipe === 'telat')
                        @if ($submission->file_bukti)
                            <a href="{{ asset('storage/' . $submission->file_bukti) }}" target="_blank" class="text-indigo-600 hover:text-indigo-800 text-xs font-medium">Lihat File</a>
                        @else
                            <span class="text-xs text-gray-300">Tidak ada</span>
                        @endif
                    @elseif ($submission->tipe === 'lembur')
                        @php
                            $allFotos = array_values(array_filter([
                                $submission->foto_pulang, $submission->foto_pulang_2, $submission->foto_pulang_3,
                                $submission->foto_pulang_4, $submission->foto_pulang_5, $submission->foto_pulang_6,
                            ]));
                        @endphp
                        @forelse($allFotos as $i => $foto)
                            <a href="{{ asset('storage/' . $foto) }}" target="_blank" class="text-indigo-600 hover:text-indigo-800 text-xs font-medium">Bukti {{ $i + 1 }}</a>
                        @empty
                            <span class="text-xs text-gray-300">Tidak ada</span>
                        @endforelse
                    @endif
                </div>
            </div>

            @if ($status === 'pending')
                <div class="mt-6 pt-4 border-t border-gray-50 flex gap-3">
                    <form action="{{ route('admin.absensi.approval.action', ['absensi' => $submission->id, 'action' => 'approve']) }}" method="POST" class="flex-1">
                        @csrf
                        <input type="hidden" name="catatan_admin" value="Disetujui">
                        <button type="submit" class="w-full py-2 bg-emerald-600 hover:bg-emerald-700 text-white text-xs font-semibold rounded-lg transition-colors">
                            SETUJUI
                        </button>
                    </form>
                    <form action="{{ route('admin.absensi.approval.action', ['absensi' => $submission->id, 'action' => 'reject']) }}" method="POST" class="flex-1 flex gap-2">
                        @csrf
                        <input type="text" name="catatan_admin" placeholder="Alasan..." class="w-full border-gray-200 border rounded-lg px-3 text-xs" required>
                        <button type="submit" class="py-2 px-3 bg-rose-600 hover:bg-rose-700 text-white text-xs font-semibold rounded-lg transition-colors">
                            TOLAK
                        </button>
                    </form>
                </div>
            @endif
        </div>
    @empty
        <div class="col-span-full py-12 text-center text-gray-500">Tidak ada data pengajuan.</div>
    @endforelse
</div>
