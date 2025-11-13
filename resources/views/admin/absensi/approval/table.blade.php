{{-- Pastikan Anda sudah mengimplementasikan Tailwind CSS di proyek Laravel Anda --}}

{{-- HEADER SECTION --}}
<div class="mb-6">
    <div class="flex items-center justify-between">
        <div>
            <h1 class="text-2xl font-bold text-gray-900 tracking-tight">Persetujuan Lembur / Izin</h1>
            <p class="mt-1 text-sm text-gray-500">Kelola dan review pengajuan absensi yang memerlukan persetujuan</p>
        </div>
        {{-- ❌ DIV TOMBOL SEARCH & EXPORT SUDAH DIHAPUS --}}
    </div>

    {{-- STATS CARDS (Contoh) --}}
    <div class="grid grid-cols-1 md:grid-cols-4 gap-4 mt-6">
        <div class="bg-white rounded-lg border border-gray-200 p-4 hover:shadow-md transition-shadow">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-sm font-medium text-gray-600">Pending</p>
                    <p class="text-2xl font-bold text-gray-900 mt-1">{{ $submissions->where('status_approval', 'pending')->count() }}</p>
                </div>
                <div class="p-3 bg-yellow-100 rounded-lg"><svg class="w-6 h-6 text-yellow-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/></svg></div>
            </div>
        </div>
        <div class="bg-white rounded-lg border border-gray-200 p-4 hover:shadow-md transition-shadow">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-sm font-medium text-gray-600">Approved (Final)</p>
                    <p class="text-2xl font-bold text-gray-900 mt-1">{{ $submissions->where('status_approval', 'approved_hrga')->count() }}</p>
                </div>
                <div class="p-3 bg-green-100 rounded-lg"><svg class="w-6 h-6 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/></svg></div>
            </div>
        </div>
        <div class="bg-white rounded-lg border border-gray-200 p-4 hover:shadow-md transition-shadow">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-sm font-medium text-gray-600">Rejected</p>
                    <p class="text-2xl font-bold text-gray-900 mt-1">{{ $submissions->where('status_approval', 'rejected')->count() }}</p>
                </div>
                <div class="p-3 bg-red-100 rounded-lg"><svg class="w-6 h-6 text-red-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 14l2-2m0 0l2-2m-2 2l-2-2m2 2l2 2m7-2a9 9 0 11-18 0 9 9 0 0118 0z"/></svg></div>
            </div>
        </div>
        <div class="bg-white rounded-lg border border-gray-200 p-4 hover:shadow-md transition-shadow">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-sm font-medium text-gray-600">Total</p>
                    <p class="text-2xl font-bold text-gray-900 mt-1">{{ $submissions->count() }}</p>
                </div>
                <div class="p-3 bg-indigo-100 rounded-lg"><svg class="w-6 h-6 text-indigo-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg></div>
            </div>
        </div>
    </div>
</div>

{{-- TABLE SECTION --}}
<div class="bg-white rounded-lg border border-gray-200 shadow-sm overflow-hidden">
    {{-- Table Header with Filters --}}
    <div class="px-6 py-4 border-b border-gray-200 bg-gray-50">
        <div class="flex items-center justify-between">
            {{-- ❌ DIV FILTER DROPDOWN SUDAH DIHAPUS --}}
            <div class="text-sm text-gray-600">
                Menampilkan <span class="font-medium">{{ $submissions->count() }}</span> data
            </div>
        </div>
    </div>

    {{-- Table --}}
    <div class="overflow-x-auto">
        <table class="min-w-full divide-y divide-gray-200">
            <thead class="bg-gray-50">
                <tr>
                    <th scope="col" class="px-6 py-3 text-left text-xs font-semibold text-gray-700 uppercase tracking-wider"><input type="checkbox" class="rounded border-gray-300 text-indigo-600 focus:ring-indigo-500"></th>
                    <th scope="col" class="px-6 py-3 text-left text-xs font-semibold text-gray-700 uppercase tracking-wider">Karyawan</th>
                    <th scope="col" class="px-6 py-3 text-left text-xs font-semibold text-gray-700 uppercase tracking-wider">Jenis / Tanggal</th>
                    <th scope="col" class="px-6 py-3 text-left text-xs font-semibold text-gray-700 uppercase tracking-wider">Durasi</th>

                    {{-- 🆕 HEADER BUKTI DITAMBAH --}}
                    <th scope="col" class="px-6 py-3 text-left text-xs font-semibold text-gray-700 uppercase tracking-wider">Bukti</th>

                    <th scope="col" class="px-6 py-3 text-left text-xs font-semibold text-gray-700 uppercase tracking-wider">Keterangan</th>
                    <th scope="col" class="px-6 py-3 text-left text-xs font-semibold text-gray-700 uppercase tracking-wider">Status</th>
                    <th scope="col" class="px-6 py-3 text-center text-xs font-semibold text-gray-700 uppercase tracking-wider">Aksi</th>
                </tr>
            </thead>

            <tbody class="bg-white divide-y divide-gray-200">
                @forelse ($submissions as $index => $submission)
                    @php
                        $user = $submission->user ?? null;
                        $statusApproval = $submission->status_approval ?? 'pending';

                        // Status Label Styling
                        $statusLabel = match($statusApproval) {
                            'pending' => ['text' => 'Menunggu', 'color' => 'bg-yellow-50 text-yellow-700 border-yellow-200', 'icon' => '⏱️'],
                            'approved_supervisor' => ['text' => 'Approved SPV', 'color' => 'bg-blue-50 text-blue-700 border-blue-200', 'icon' => '✓'],
                            'approved_manager' => ['text' => 'Approved MGR', 'color' => 'bg-purple-50 text-purple-700 border-purple-200', 'icon' => '✓✓'],
                            'approved_hrga' => ['text' => 'Final Approved', 'color' => 'bg-green-50 text-green-700 border-green-200', 'icon' => '✓✓✓'],
                            'rejected' => ['text' => 'Ditolak', 'color' => 'bg-red-50 text-red-700 border-red-200', 'icon' => '✕'],
                            default => ['text' => ucfirst($statusApproval), 'color' => 'bg-gray-50 text-gray-700 border-gray-200', 'icon' => '•']
                        };

                        // --- LOGIKA DURASI LEMBUR ---
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

                    <tr class="hover:bg-gray-50 transition-colors">
                        <td class="px-6 py-4 whitespace-nowrap"><input type="checkbox" class="rounded border-gray-300 text-indigo-600 focus:ring-indigo-500"></td>

                        {{-- KARYAWAN --}}
                        <td class="px-6 py-4 whitespace-nowrap">
                            <div class="flex items-center">
                                <div class="flex-shrink-0 h-10 w-10">
                                    <div class="h-10 w-10 rounded-full bg-gradient-to-br from-indigo-400 to-indigo-600 flex items-center justify-center text-white font-semibold text-sm">
                                        {{ strtoupper(substr($user->name ?? 'U', 0, 2)) }}
                                    </div>
                                </div>
                                <div class="ml-4">
                                    <div class="text-sm font-semibold text-gray-900">{{ $user->name ?? '-' }}</div>
                                    <div class="text-xs text-gray-500">{{ $user->employee_id ?? 'N/A' }}</div>
                                </div>
                            </div>
                        </td>

                        {{-- JENIS / TANGGAL & WAKTU --}}
                        <td class="px-6 py-4">
                            <div class="text-xs font-semibold text-indigo-600 uppercase">
                                {{ $submission->tipe ? ucfirst($submission->tipe) : (ucfirst($submission->status) ?? 'Absensi') }}
                            </div>
                            <div class="text-sm text-gray-900 font-medium mt-1">
                                {{ $displayDate ? \Carbon\Carbon::parse($displayDate)->isoFormat('DD MMM YYYY') : '-' }}
                            </div>

                            @if($isOvertime)
                                <div class="text-xs text-gray-500 mt-1 flex items-center gap-2">
                                    <span>{{ $submission->lembur_start ? \Carbon\Carbon::parse($submission->lembur_start)->format('H:i') : '-' }}</span>
                                    <span class="text-gray-400">→</span>
                                    <span>{{ $submission->lembur_end ? \Carbon\Carbon::parse($submission->lembur_end)->format('H:i') : '-' }}</span>
                                    @if($submission->lembur_rest == 1)
                                        <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-gray-100 text-gray-600">
                                            <svg class="w-3 h-3 mr-1" fill="currentColor" viewBox="0 0 20 20"><path d="M10 2a6 6 0 00-6 6v3.586l-.707.707A1 1 104 14h12a1 1 0 00.707-1.707L16 11.586V8a6 6 0 00-6-6zM10 18a3 3 0 01-3-3h6a3 3 0 01-3 3z"/></svg>
                                            Istirahat
                                        </span>
                                    @endif
                                </div>
                            @endif
                        </td>

                        {{-- DURASI (Hanya terisi jika Lembur) --}}
                        <td class="px-6 py-4 whitespace-nowrap">
                            @if($isOvertime)
                                <div class="inline-flex items-center px-2.5 py-1 rounded-md text-xs font-semibold bg-indigo-50 text-indigo-700 border border-indigo-200">
                                    <svg class="w-3.5 h-3.5 mr-1.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                                    {{ $duration }}
                                </div>
                            @else
                                <span class="text-sm text-gray-500">-</span>
                            @endif
                        </td>

                        {{-- 🆕 DATA BUKTI DITAMBAH (Pake Icon Mata & asset()) --}}
                        <td class="px-6 py-4 whitespace-nowrap">
                            @if ($submission->tipe === 'sakit' || $submission->tipe === 'izin')
                                @if ($submission->file_bukti)
                                    {{-- ⬇️ INI DIA FIX-NYA. PAKE asset() ⬇️ --}}
                                    <a href="{{ asset('storage/' . $submission->file_bukti) }}" target="_blank"
                                       class="inline-flex items-center gap-2 px-3 py-1.5 bg-gray-100 text-gray-700 rounded-lg text-xs font-medium hover:bg-gray-200 transition-all">

                                        {{-- ICON MATA --}}
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" />
                                        </svg>

                                        Lihat Bukti
                                    </a>
                                @else
                                    <span class="text-xs text-gray-400 italic">Tidak ada</span>
                                @endif
                            @else
                                {{-- Kalo lembur, emang ga ada bukti file --}}
                                <span class="text-sm text-gray-500">-</span>
                            @endif
                        </td>

                        {{-- KETERANGAN + REJECTION HISTORY --}}
                        <td class="px-6 py-4">
                            <div class="text-sm text-gray-900 max-w-xs">
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

                                {{-- Keterangan utama --}}
                                <p class="truncate font-medium" title="{{ $mainKeterangan }}">
                                    {{ $mainKeterangan }}
                                </p>

                                {{-- ... (Sisa kode Keterangan lo udah bener) ... --}}
                                @if (!empty($submission->rejected_by) && !empty($submission->catatan_admin) && $submission->status_approval === 'pending')
                                <div class="mt-2 p-2.5 bg-amber-50 border-l-4 border-amber-400 rounded text-xs leading-relaxed">
                                    <div class="flex items-start gap-2">
                                        <svg class="w-4 h-4 text-amber-600 flex-shrink-0 mt-0.5" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M8.257 3.099c.765-1.36 2.722-1.36 3.486 0l5.58 9.92c.75 1.334-.213 2.98-1.742 2.98H4.42c-1.53 0-2.493-1.646-1.743-2.98l5.58-9.92zM11 13a1 1 0 11-2 0 1 1 0 012 0zm-1-8a1 1 0 00-1 1v3a1 1 0 002 0V6a1 1 0 00-1-1z" clip-rule="evenodd"/></svg>
                                        <div class="flex-1">
                                            <p class="font-bold text-amber-800 mb-1">⚠️ Pernah Ditolak oleh {{ strtoupper(str_replace(['_', '-'], ' ', $submission->rejected_by)) }}</p>
                                            <p class="text-amber-700 italic leading-tight">"{{ $submission->catatan_admin }}"</p>
                                            @if($submission->rejected_at)
                                                <p class="text-amber-600 mt-1.5 text-xs">📅 {{ \Carbon\Carbon::parse($submission->rejected_at)->isoFormat('DD MMM YYYY, HH:mm') }} <span class="text-amber-500">({{ \Carbon\Carbon::parse($submission->rejected_at)->diffForHumans() }})</span></p>
                                            @endif
                                        </div>
                                    </div>
                                </div>
                                @endif
                                @if ($submission->status_approval === 'rejected' && !empty($submission->catatan_admin))
                                <div class="mt-2 p-2 bg-red-50 border border-red-200 rounded-lg text-xs text-red-700 leading-snug">
                                    <strong class="block mb-1">❌ Ditolak Saat Ini</strong>
                                    <p class="italic">"{{ $submission->catatan_admin }}"</p>
                                </div>
                                @endif
                                @if ($submission->tipe !== 'lembur')
                                <p class="text-xs text-gray-500 mt-1.5">{{ $submission->status ? 'Pengajuan ' . ucfirst($submission->status) : 'Absensi Reguler' }}</p>
                                @else
                                <p class="text-xs text-gray-500 mt-1.5">Diajukan {{ $submission->created_at ? \Carbon\Carbon::parse($submission->created_at)->diffForHumans() : '-' }}</p>
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
                                    {{-- Tombol Approve --}}
                                    <form action="{{ route('admin.absensi.approval.action', ['absensi' => $submission->id, 'action' => 'approve']) }}"
                                        method="POST" class="inline">
                                        @csrf
                                        <input type="hidden" name="catatan_admin" value="Disetujui">
                                        <button type="submit"
                                            onclick="return confirm('Apakah Anda yakin ingin MENYETUJUI pengajuan ini?')"
                                            class="inline-flex items-center px-3 py-1.5 border border-transparent text-xs font-medium rounded-lg text-white bg-green-600 hover:bg-green-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-green-500 transition-all shadow-sm hover:shadow">
                                            <svg class="w-3.5 h-3.5 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
                                            Approve
                                        </button>
                                    </form>

                                    {{-- Tombol Reject --}}
                                    <button type="button"
                                        onclick="openRejectModal({{ $submission->id }})"
                                        class="inline-flex items-center px-3 py-1.5 border border-transparent text-xs font-medium rounded-lg text-white bg-red-600 hover:bg-red-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-red-500 transition-all shadow-sm hover:shadow">
                                        <svg class="w-3.5 h-3.5 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                                        Reject
                                    </button>
                                </div>
                            @elseif (in_array($statusApproval, ['approved_supervisor', 'approved_manager']))
                                <span class="inline-flex items-center text-xs text-gray-500">
                                    <svg class="animate-spin h-3.5 w-3.5 mr-1.5 text-gray-400" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path></svg>
                                    Pending approval
                                </span>
                            @elseif ($statusApproval === 'approved_hrga')
                                <span class="inline-flex items-center text-xs text-green-700 font-medium">
                                    <svg class="w-4 h-4 mr-1" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/></svg>
                                    Completed
                                </span>
                            @elseif ($statusApproval === 'rejected')
                                <button type="button" class="inline-flex items-center text-xs text-red-700 font-medium hover:text-red-800">
                                    <svg class="w-4 h-4 mr-1" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7 4a1 1 0 11-2 0 1 1 0 012 0zm-1-9a1 1 0 00-1 1v4a1 1 0 102 0V6a1 1 0 00-1-1z" clip-rule="evenodd"/></svg>
                                    View reason
                                </button>
                            @else
                                <span class="text-xs text-gray-400">-</span>
                            @endif
                        </td>
                    </tr>
                @empty
                    <tr>
                        {{-- 🆕 COLSPAN DIUBAH DARI 7 JADI 8 --}}
                        <td colspan="8" class="px-6 py-12 text-center">
                            <svg class="mx-auto h-12 w-12 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
                            <h3 class="mt-2 text-sm font-medium text-gray-900">Tidak ada data</h3>
                            <p class="mt-1 text-sm text-gray-500">Belum ada pengajuan yang perlu di-review.</p>
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    {{-- Pagination (Contoh) --}}
    <div class="px-6 py-4 border-t border-gray-200 bg-gray-50">
        {{-- ... (Sisa kode Pagination lo udah bener) ... --}}
        <div class="flex items-center justify-between">
            <div class="text-sm text-gray-700">
                Showing <span class="font-medium">1</span> to <span class="font-medium">{{ $submissions->count() }}</span> of <span class="font-medium">{{ $submissions->count() }}</span> results
            </div>
            <div class="flex items-center gap-2">
                <button class="px-3 py-1.5 border border-gray-300 rounded-lg text-sm font-medium text-gray-700 bg-white hover:bg-gray-50 disabled:opacity-50 disabled:cursor-not-allowed" disabled>
                    Previous
                </button>
                <button class="px-3 py-1.5 bg-indigo-600 text-white rounded-lg text-sm font-medium">
                    1
                </button>
                <button class="px-3 py-1.5 border border-gray-300 rounded-lg text-sm font-medium text-gray-700 bg-white hover:bg-gray-50">
                    2
                </button>
                <button class="px-3 py-1.5 border border-gray-300 rounded-lg text-sm font-medium text-gray-700 bg-white hover:bg-gray-50">
                    Next
                </button>
            </div>
        </div>
    </div>
</div>

{{-- Include Reject Modal --}}
@include('admin.absensi.approval.partials.reject-modal')
