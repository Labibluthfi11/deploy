{{-- File: resources/views/admin/absensi/approval/table.blade.php --}}

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
                    <span class="text-2xl">⏱️</span>
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
                    <span class="text-2xl">✅</span>
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
                    <span class="text-2xl">❌</span>
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
                    <span class="text-2xl">📋</span>
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
                    <th scope="col" class="px-6 py-3 text-left text-xs font-semibold text-gray-700 dark:text-gray-300 uppercase tracking-wider">
                        <input type="checkbox" class="rounded border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700 text-indigo-500 focus:ring-indigo-500">
                    </th>
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

                        $duration = '-';
                        $isOvertime = ($submission->tipe === 'lembur');
                        if ($isOvertime && $submission->lembur_start && $submission->lembur_end) {
                            $start = \Carbon\Carbon::parse($submission->lembur_start);
                            $end = \Carbon\Carbon::parse($submission->lembur_end);
                            $diff = $start->diffInMinutes($end);
                            if ($submission->lembur_rest == 1) {
                                $diff = max(0, $diff - 30);
                            }
                            $hours = floor($diff / 60);
                            $minutes = $diff % 60;
                            $duration = $hours . 'j ' . $minutes . 'm';
                        }

                        $displayDate = $submission->check_in_at;
                    @endphp

                    <tr class="hover:bg-gray-50 dark:hover:bg-gray-700/50 transition-colors">
                        <td class="px-6 py-4 whitespace-nowrap">
                            <input type="checkbox" class="rounded border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700 text-indigo-500 focus:ring-indigo-500">
                        </td>

                        {{-- KARYAWAN --}}
                        <td class="px-6 py-4 whitespace-nowrap">
                            <div class="flex items-center">
                                <div class="flex-shrink-0 h-10 w-10">
                                    <div class="h-10 w-10 rounded-full bg-gradient-to-br from-indigo-500 to-indigo-700 flex items-center justify-center text-white font-semibold text-sm shadow-lg">
                                        {{ strtoupper(substr($user->name ?? 'U', 0, 2)) }}
                                    </div>
                                </div>
                                <div class="ml-4">
                                    <div class="text-sm font-semibold text-gray-900 dark:text-white">{{ $user->name ?? '-' }}</div>
                                    <div class="text-xs text-gray-500 dark:text-gray-400">{{ $user->employee_id ?? 'N/A' }}</div>
                                </div>
                            </div>
                        </td>

                        {{-- JENIS / TANGGAL & WAKTU --}}
                        <td class="px-6 py-4">
                            <div class="text-xs font-semibold text-indigo-600 dark:text-indigo-400 uppercase">
                                {{ $submission->tipe ? ucfirst($submission->tipe) : (ucfirst($submission->status) ?? 'Absensi') }}
                            </div>
                            <div class="text-sm text-gray-900 dark:text-white font-medium mt-1">
                                {{ $displayDate ? \Carbon\Carbon::parse($displayDate)->isoFormat('DD MMM YYYY') : '-' }}
                            </div>

                            @if($isOvertime)
                                <div class="text-xs text-gray-500 dark:text-gray-400 mt-1 flex items-center gap-2">
                                    <span>{{ $submission->lembur_start ? \Carbon\Carbon::parse($submission->lembur_start)->format('H:i') : '-' }}</span>
                                    <span class="text-gray-400 dark:text-gray-500">→</span>
                                    <span>{{ $submission->lembur_end ? \Carbon\Carbon::parse($submission->lembur_end)->format('H:i') : '-' }}</span>
                                    @if($submission->lembur_rest == 1)
                                        <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-gray-100 dark:bg-gray-700 text-gray-700 dark:text-gray-300">
                                            ⏸️ Istirahat
                                        </span>
                                    @endif
                                </div>
                            @endif
                        </td>

                        {{-- DURASI --}}
                        <td class="px-6 py-4 whitespace-nowrap">
                            @if($isOvertime)
                                <div class="inline-flex items-center px-2.5 py-1 rounded-md text-xs font-semibold bg-indigo-500/10 text-indigo-400 border border-indigo-500/20">
                                    ⏱️ {{ $duration }}
                                </div>
                            @else
                                <span class="text-sm text-gray-400 dark:text-gray-500">-</span>
                            @endif
                        </td>

                        {{-- BUKTI --}}
                        <td class="px-6 py-4 whitespace-nowrap">
                            @if ($submission->tipe === 'sakit' || $submission->tipe === 'izin')
                                @if ($submission->file_bukti)
                                    <a href="{{ asset('storage/' . $submission->file_bukti) }}" target="_blank"
                                       class="inline-flex items-center gap-2 px-3 py-1.5 bg-gray-100 dark:bg-gray-700 text-gray-700 dark:text-gray-300 rounded-lg text-xs font-medium hover:bg-gray-200 dark:hover:bg-gray-600 transition-all">
                                        👁️ Lihat Bukti
                                    </a>
                                @else
                                    <span class="text-xs text-gray-400 dark:text-gray-500 italic">Tidak ada</span>
                                @endif
                            @else
                                <span class="text-sm text-gray-400 dark:text-gray-500">-</span>
                            @endif
                        </td>

                        {{-- KETERANGAN --}}
                        <td class="px-6 py-4">
                            <div class="text-sm text-gray-700 dark:text-gray-300 max-w-xs">
                                @php
                                    $mainKeterangan = '-';
                                    if ($submission->tipe === 'lembur') {
                                        $mainKeterangan = $submission->lembur_keterangan ?? 'Lembur';
                                    } elseif ($submission->tipe === 'sakit') {
                                        $mainKeterangan = $submission->keterangan_izin_sakit ?? 'Pengajuan Sakit';
                                    } elseif ($submission->tipe === 'izin') {
                                        $mainKeterangan = $submission->keterangan_izin ?? $submission->keterangan_izin_sakit ?? 'Pengajuan Izin';
                                    } else {
                                        $mainKeterangan = 'Absensi Reguler';
                                    }
                                @endphp

                                <p class="truncate font-medium text-gray-900 dark:text-white" title="{{ $mainKeterangan }}">
                                    {{ $mainKeterangan }}
                                </p>

                                @if (!empty($submission->rejected_by) && !empty($submission->catatan_admin) && $submission->status_approval === 'pending')
                                <div class="mt-2 p-2.5 bg-amber-500/10 border-l-4 border-amber-500 rounded text-xs leading-relaxed">
                                    <div class="flex items-start gap-2">
                                        <span class="text-amber-400">⚠️</span>
                                        <div class="flex-1">
                                            <p class="font-bold text-amber-300 mb-1">Pernah Ditolak oleh {{ strtoupper(str_replace(['_', '-'], ' ', $submission->rejected_by)) }}</p>
                                            <p class="text-amber-200 italic leading-tight">"{{ $submission->catatan_admin }}"</p>
                                            @if($submission->rejected_at)
                                                <p class="text-amber-400 mt-1.5 text-xs">📅 {{ \Carbon\Carbon::parse($submission->rejected_at)->isoFormat('DD MMM YYYY, HH:mm') }} <span class="text-amber-500">({{ \Carbon\Carbon::parse($submission->rejected_at)->diffForHumans() }})</span></p>
                                            @endif
                                        </div>
                                    </div>
                                </div>
                                @endif

                                @if ($submission->status_approval === 'rejected' && !empty($submission->catatan_admin))
                                <div class="mt-2 p-2 bg-red-500/10 border border-red-500/20 rounded-lg text-xs text-red-400 leading-snug">
                                    <strong class="block mb-1">❌ Ditolak Saat Ini</strong>
                                    <p class="italic">"{{ $submission->catatan_admin }}"</p>
                                </div>
                                @endif

                                @if ($submission->tipe !== 'lembur')
                                <p class="text-xs text-gray-400 dark:text-gray-500 mt-1.5">{{ $submission->status ? 'Pengajuan ' . ucfirst($submission->status) : 'Absensi Reguler' }}</p>
                                @else
                                <p class="text-xs text-gray-400 dark:text-gray-500 mt-1.5">Diajukan {{ $submission->created_at ? \Carbon\Carbon::parse($submission->created_at)->diffForHumans() : '-' }}</p>
                                @endif
                            </div>
                        </td>

                        {{-- STATUS --}}
                        <td class="px-6 py-4 whitespace-nowrap">
                            <span class="inline-flex items-center px-2.5 py-1 rounded-full text-xs font-semibold border {{ $statusLabel['color'] }}">
                                <span class="mr-1">{{ $statusLabel['icon'] }}</span>
                                {{ $statusLabel['text'] }}
                            </span>
                        </td>

                        {{-- AKSI --}}
                        <td class="px-6 py-4 whitespace-nowrap text-center">
                            @if ($statusApproval === 'pending')
                                <div class="flex items-center justify-center gap-2">
                                    {{-- 🔥 TOMBOL APPROVE (FIXED) --}}
                                    <form action="{{ route('admin.absensi.approval.action', ['absensi' => $submission->id, 'action' => 'approve']) }}"
                                        method="POST" class="inline">
                                        @csrf
                                        <input type="hidden" name="catatan_admin" value="Disetujui">
                                        <button type="submit"
                                            onclick="return confirm('Apakah Anda yakin ingin MENYETUJUI pengajuan ini?')"
                                            class="inline-flex items-center px-3 py-1.5 border border-transparent text-xs font-medium rounded-lg text-white bg-green-600 hover:bg-green-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-green-500 transition-all shadow-sm hover:shadow">
                                            ✅ Approve
                                        </button>
                                    </form>

                                    {{-- 🔥 TOMBOL REJECT (FIXED) --}}
                                    <button type="button"
                                        onclick="openRejectModal({{ $submission->id }})"
                                        class="inline-flex items-center px-3 py-1.5 border border-transparent text-xs font-medium rounded-lg text-white bg-red-600 hover:bg-red-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-red-500 transition-all shadow-sm hover:shadow">
                                        ❌ Reject
                                    </button>
                                </div>
                            @elseif (in_array($statusApproval, ['approved_supervisor', 'approved_manager']))
                                <span class="inline-flex items-center text-xs text-gray-500 dark:text-gray-400">
                                    ⏳ Pending approval
                                </span>
                            @elseif ($statusApproval === 'approved_hrga')
                                <span class="inline-flex items-center text-xs text-green-600 dark:text-green-400 font-medium">
                                    ✅ Completed
                                </span>
                            @elseif ($statusApproval === 'rejected')
                                <button type="button" class="inline-flex items-center text-xs text-red-600 dark:text-red-400 font-medium hover:text-red-500 dark:hover:text-red-300">
                                    ℹ️ View reason
                                </button>
                            @else
                                <span class="text-xs text-gray-400 dark:text-gray-500">-</span>
                            @endif
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="8" class="px-6 py-12 text-center">
                            <div class="text-4xl mb-4">📋</div>
                            <h3 class="mt-2 text-sm font-medium text-gray-700 dark:text-gray-300">Tidak ada data</h3>
                            <p class="mt-1 text-sm text-gray-500 dark:text-gray-500">Belum ada pengajuan yang perlu di-review.</p>
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    {{-- Pagination --}}
    <div class="px-6 py-4 border-t border-gray-200 dark:border-gray-700 bg-gray-50 dark:bg-gray-800/50">
        <div class="flex items-center justify-between">
            <div class="text-sm text-gray-600 dark:text-gray-400">
                 Showing <span class="font-medium text-gray-900 dark:text-white">1</span> to <span class="font-medium text-gray-900 dark:text-white">{{ $submissions->count() }}</span> of <span class="font-medium text-gray-900 dark:text-white">{{ $submissions->count() }}</span> results
            </div>
            <div class="flex items-center gap-2">
                <button class="px-3 py-1.5 border border-gray-300 dark:border-gray-600 rounded-lg text-sm font-medium text-gray-700 dark:text-gray-400 bg-white dark:bg-gray-700 hover:bg-gray-50 dark:hover:bg-gray-600 disabled:opacity-50 disabled:cursor-not-allowed" disabled>
                    Previous
                </button>
                <button class="px-3 py-1.5 bg-indigo-600 text-white rounded-lg text-sm font-medium hover:bg-indigo-700">
                    1
                </button>
                <button class="px-3 py-1.5 border border-gray-300 dark:border-gray-600 rounded-lg text-sm font-medium text-gray-700 dark:text-gray-400 bg-white dark:bg-gray-700 hover:bg-gray-50 dark:hover:bg-gray-600">
                    2
                </button>
                <button class="px-3 py-1.5 border border-gray-300 dark:border-gray-600 rounded-lg text-sm font-medium text-gray-700 dark:text-gray-400 bg-white dark:bg-gray-700 hover:bg-gray-50 dark:hover:bg-gray-600">
                    Next
                </button>
            </div>
        </div>
    </div>
</div>

@include('admin.absensi.approval.partials.reject-modal')
