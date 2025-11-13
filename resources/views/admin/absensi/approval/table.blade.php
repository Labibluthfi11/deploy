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
                        // ... (Logika $statusLabel lo udah bener) ...
                      $statusLabel = match($statusApproval) {
                            'pending' => ['text' => 'Menunggu', 'color' => 'bg-yellow-50 text-yellow-700 border-yellow-200', 'icon' => '⏱️'],
                            'approved_supervisor' => ['text' => 'Approved SPV', 'color' => 'bg-blue-50 text-blue-700 border-blue-200', 'icon' => '✓'],
                            'approved_manager' => ['text' => 'Approved MGR', 'color' => 'bg-purple-50 text-purple-700 border-purple-200', 'icon' => '✓✓'],
                            'approved_hrga' => ['text' => 'Final Approved', 'color' => 'bg-green-50 text-green-700 border-green-200', 'icon' => '✓✓✓'],
                            'rejected' => ['text' => 'Ditolak', 'color' => 'bg-red-50 text-red-700 border-red-200', 'icon' => '✕'],
                            default => ['text' => ucfirst($statusApproval), 'color' => 'bg-gray-50 text-gray-700 border-gray-200', 'icon' => '•']
                        };
                        // ... (Logika $duration lo udah bener) ...
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
                         _           } elseif ($submission->tipe === 'sakit') {
                                        $mainKeterangan = $submission->keterangan_izin_sakit ?? 'Pengajuan Sakit';
       _                           } elseif ($submission->tipe === 'izin') {
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
           _              @if ($statusApproval === 'pending')
                                {{-- ... (Kode Aksi lo udah bener) ... --}}
                            @elseif (in_array($statusApproval, ['approved_supervisor', 'approved_manager']))
                                {{-- ... (Kode Aksi lo udah bener) ... --}}
                            @elseif ($statusApproval === 'approved_hrga')
                       A        {{-- ... (Kode Aksi lo udah bener) ... --}}
                            @elseif ($statusApproval === 'rejected')
                                {{-- ... (Kode Aksi lo udah bener) ... --}}
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
    </div>
</div>

{{-- Include Reject Modal --}}
@include('admin.absensi.approval.partials.reject-modal')
