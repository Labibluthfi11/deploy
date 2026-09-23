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
    {{-- Grid View --}}
    <div class="p-6 grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
        @forelse ($submissions as $submission)
            @php
                $user = $submission->user ?? null;
                $statusApproval = $submission->status_approval ?? 'pending';
                $isOvertime = ($submission->tipe === 'lembur');
                
                // Menentukan warna kartu berdasarkan tipe
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
                    @if($isOvertime)
                        <p>⏱️ Durasi: {{ $submission->lembur_start ? \Carbon\Carbon::parse($submission->lembur_start)->format('H:i') : '-' }} - {{ $submission->lembur_end ? \Carbon\Carbon::parse($submission->lembur_end)->format('H:i') : '-' }}</p>
                    @endif
                </div>
                
                <div class="mt-4 pt-4 border-t border-gray-100 dark:border-gray-700 flex justify-end">
                    <button type="button"
                        class="view-detail-btn text-sm text-indigo-600 dark:text-indigo-400 font-medium hover:underline"
                        data-submission='@json($submission)'>
                        Lihat Detail
                    </button>
                </div>
            </div>
        @empty
            <div class="col-span-full py-12 text-center text-gray-500">
                Tidak ada pengajuan yang perlu di-review.
            </div>
        @endforelse
    </div>

</div>

{{-- MODAL DETAIL --}}
<div id="modalDetail" class="hidden fixed inset-0 z-[9999] overflow-y-auto">
    <div class="flex items-center justify-center min-h-screen px-4">
        <div id="detailOverlay" class="fixed inset-0 bg-gray-900 bg-opacity-75"></div>
        <div class="relative bg-white dark:bg-gray-800 rounded-xl shadow-2xl w-full max-w-lg z-[10000]">
            <div class="p-6">
                <h3 class="text-xl font-bold text-gray-900 dark:text-white" id="modalTitle">Detail Pengajuan</h3>
                <div id="modalContent" class="mt-4 space-y-3 text-sm text-gray-600 dark:text-gray-300">
                    {{-- Data akan diisi oleh JS --}}
                </div>
                
                <div class="mt-6 flex justify-end gap-3" id="modalActions">
                    {{-- Tombol Aksi akan diisi oleh JS --}}
                </div>
            </div>
        </div>
    </div>
</div>

<div id="modalViewReason" class="hidden fixed inset-0 z-[9999] overflow-y-auto">
...
    <div class="flex items-center justify-center min-h-screen px-4">
        <div id="reasonModalOverlay" class="fixed inset-0 bg-gray-900 bg-opacity-75"></div>
        <div class="relative bg-white dark:bg-gray-800 rounded-xl shadow-2xl w-full max-w-md z-[10000]">
            <div class="bg-gradient-to-r from-red-600 to-red-500 rounded-t-xl p-5">
                <div class="flex items-center justify-between">
                    <div class="flex items-center gap-3">
                        <div class="w-10 h-10 bg-white/20 rounded-xl flex items-center justify-center">
                            <svg class="w-5 h-5 text-white" fill="currentColor" viewBox="0 0 20 20">
                                <path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7 4a1 1 0 11-2 0 1 1 0 012 0zm-1-9a1 1 0 00-1 1v4a1 1 0 102 0V6a1 1 0 00-1-1z" clip-rule="evenodd"/>
                            </svg>
                        </div>
                        <div>
                            <h3 class="text-white font-bold text-lg">Alasan Penolakan</h3>
                            <p id="reasonModalName" class="text-red-100 text-xs mt-0.5"></p>
                        </div>
                    </div>
                    <button id="reasonModalCloseBtn" class="w-8 h-8 bg-white/20 hover:bg-white/30 rounded-lg flex items-center justify-center transition-all">
                        <svg class="w-4 h-4 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                        </svg>
                    </button>
                </div>
            </div>
            <div class="p-6">
                <div class="bg-red-50 dark:bg-red-900/20 border border-red-200 dark:border-red-800 rounded-xl p-4">
                    <p id="reasonModalText" class="text-sm text-red-800 dark:text-red-300 leading-relaxed italic"></p>
                </div>
                <div class="mt-4 flex justify-end">
                    <button id="reasonModalCloseBtnBottom" class="px-4 py-2 bg-gray-100 hover:bg-gray-200 dark:bg-gray-700 dark:hover:bg-gray-600 text-gray-700 dark:text-gray-300 rounded-lg text-sm font-medium transition-all">
                        Tutup
                    </button>
                </div>
            </div>
        </div>
    </div>
</div>

<div id="modalGallery" class="hidden fixed inset-0 z-[9999] overflow-y-auto">
    <div class="flex items-center justify-center min-h-screen px-4 py-8">
        <div id="galleryOverlay" class="fixed inset-0 bg-gray-900 bg-opacity-90"></div>
        <div class="relative bg-white dark:bg-gray-800 rounded-xl shadow-2xl w-full max-w-2xl z-[10000]">
            <div class="bg-gradient-to-r from-purple-600 to-purple-500 rounded-t-xl p-5">
                <div class="flex items-center justify-between">
                    <div>
                        <h3 class="text-white font-bold text-lg">Foto Bukti Lembur</h3>
                        <p id="galleryName" class="text-purple-100 text-xs mt-0.5"></p>
                    </div>
                    <button id="galleryCloseBtn" class="w-8 h-8 bg-white/20 hover:bg-white/30 rounded-lg flex items-center justify-center transition-all">
                        <svg class="w-4 h-4 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                        </svg>
                    </button>
                </div>
            </div>
            <div class="p-4">
                <div id="galleryMain" class="w-full h-80 bg-gray-100 dark:bg-gray-900 rounded-xl overflow-hidden mb-3 flex items-center justify-center">
                    <img id="galleryMainImg" src="" alt="Foto lembur" class="max-h-full max-w-full object-contain rounded-xl">
                </div>
                <div id="galleryThumbs" class="flex gap-2 overflow-x-auto pb-1"></div>
                <div class="flex items-center justify-between mt-3">
                    <button id="galleryPrev" class="px-3 py-1.5 bg-gray-100 hover:bg-gray-200 dark:bg-gray-700 dark:hover:bg-gray-600 text-gray-700 dark:text-gray-300 rounded-lg text-sm font-medium transition-all">← Prev</button>
                    <span id="galleryCounter" class="text-sm text-gray-500 dark:text-gray-400"></span>
                    <button id="galleryNext" class="px-3 py-1.5 bg-gray-100 hover:bg-gray-200 dark:bg-gray-700 dark:hover:bg-gray-600 text-gray-700 dark:text-gray-300 rounded-lg text-sm font-medium transition-all">Next →</button>
                </div>
            </div>
        </div>
    </div>
</div>

<script nonce="{{ config('app.csp_nonce') }}">
    document.addEventListener('DOMContentLoaded', function() {
        const modalDetail = document.getElementById('modalDetail');
        const modalContent = document.getElementById('modalContent');
        const modalActions = document.getElementById('modalActions');
        const overlay = document.getElementById('detailOverlay');

        function openDetailModal(submission) {
            // Update Title
            document.getElementById('modalTitle').textContent = 'Detail ' + submission.tipe.toUpperCase();
            
            // Siapkan Data Foto
            let fotos = [];
            if (submission.tipe === 'lembur') {
                ['foto_pulang', 'foto_pulang_2', 'foto_pulang_3', 'foto_pulang_4', 'foto_pulang_5', 'foto_pulang_6'].forEach(key => {
                    if (submission[key]) fotos.push('/storage/' + submission[key]);
                });
            } else if (submission.file_bukti) {
                fotos.push('/storage/' + submission.file_bukti);
            }

            // Fill Content
            modalContent.innerHTML = `
                <p><strong>Nama:</strong> ${submission.user ? submission.user.name : '-'}</p>
                <p><strong>Jenis:</strong> ${submission.tipe.toUpperCase()}</p>
                <p><strong>Tanggal:</strong> ${submission.check_in_at}</p>
                <p><strong>Keterangan:</strong> ${submission.keterangan_goals || submission.keterangan_izin_sakit || '-'}</p>
                
                ${fotos.length > 0 ? `
                    <div class="mt-4">
                        <p class="font-bold mb-2">Bukti (${fotos.length} Foto):</p>
                        <div class="flex gap-2 overflow-x-auto pb-2">
                            ${fotos.map(f => `<img src="${f}" class="w-20 h-20 object-cover rounded-lg cursor-pointer" onclick="window.open('${f}', '_blank')">`).join('')}
                        </div>
                    </div>
                ` : ''}
            `;

            // Setup Actions
            if (submission.status_approval === 'pending') {
                modalActions.innerHTML = `
                    <form action="/admin/absensi/approval/${submission.id}/approve" method="POST">
                        <input type="hidden" name="_token" value="{{ csrf_token() }}">
                        <input type="hidden" name="catatan_admin" value="Disetujui">
                        <button type="submit" class="px-4 py-2 bg-green-600 text-white rounded-lg">Approve</button>
                    </form>
                    <form action="/admin/absensi/approval/${submission.id}/reject" method="POST" class="flex gap-2">
                        <input type="hidden" name="_token" value="{{ csrf_token() }}">
                        <input type="text" name="catatan_admin" placeholder="Alasan reject..." required class="border rounded-lg px-2 text-sm dark:bg-gray-700 dark:border-gray-600">
                        <button type="submit" class="px-4 py-2 bg-red-600 text-white rounded-lg">Reject</button>
                    </form>
                `;
            } else {
                modalActions.innerHTML = `<button type="button" class="px-4 py-2 bg-gray-500 text-white rounded-lg" onclick="closeDetailModal()">Tutup</button>`;
            }

            modalDetail.classList.remove('hidden');
            document.body.classList.add('overflow-hidden');
        }

        window.closeDetailModal = function() {
            modalDetail.classList.add('hidden');
            document.body.classList.remove('overflow-hidden');
        };

        overlay.addEventListener('click', closeDetailModal);

        document.querySelectorAll('.view-detail-btn').forEach(function(btn) {
            btn.addEventListener('click', function() {
                console.log("DEBUG: Tombol diklik.");
                try {
                    const submission = JSON.parse(this.dataset.submission);
                    window.openDetailModal(submission);
                } catch (e) {
                    console.error("DEBUG ERROR: Error parsing JSON:", e);
                }
            });
        });
    });
</script>
<script nonce="{{ config('app.csp_nonce') }}">
    // Fungsi agar bisa dipanggil dari HTML/Event listener
    window.closeDetailModal = function() {
        document.getElementById('modalDetail').classList.add('hidden');
        document.body.classList.remove('overflow-hidden');
    };

    window.openDetailModal = function(submission) {
        console.log("Membuka modal:", submission);
        const modalDetail = document.getElementById('modalDetail');
        const modalContent = document.getElementById('modalContent');
        const modalActions = document.getElementById('modalActions');
        
        try {
            document.getElementById('modalTitle').textContent = 'Detail ' + (submission.tipe ? submission.tipe.toUpperCase() : 'Pengajuan');
            
            let fotos = [];
            if (submission.tipe === 'lembur') {
                ['foto_pulang', 'foto_pulang_2', 'foto_pulang_3', 'foto_pulang_4', 'foto_pulang_5', 'foto_pulang_6'].forEach(key => {
                    if (submission[key]) fotos.push('/storage/' + submission[key]);
                });
            } else if (submission.file_bukti) {
                fotos.push('/storage/' + submission.file_bukti);
            }

            modalContent.innerHTML = `
                <p><strong>Nama:</strong> ${submission.user ? submission.user.name : '-'}</p>
                <p><strong>Jenis:</strong> ${submission.tipe ? submission.tipe.toUpperCase() : '-'}</p>
                <p><strong>Tanggal:</strong> ${submission.check_in_at || '-'}</p>
                <p><strong>Keterangan:</strong> ${submission.keterangan_goals || submission.keterangan_izin_sakit || '-'}</p>
                
                ${fotos.length > 0 ? `
                    <div class="mt-4">
                        <p class="font-bold mb-2">Bukti (${fotos.length} Foto):</p>
                        <div class="flex gap-2 overflow-x-auto pb-2">
                            ${fotos.map(f => `<img src="${f}" class="w-20 h-20 object-cover rounded-lg cursor-pointer" onclick="window.open('${f}', '_blank')">`).join('')}
                        </div>
                    </div>
                ` : ''}
            `;

            if (submission.status_approval === 'pending') {
                modalActions.innerHTML = `
                    <form action="/admin/absensi/approval/${submission.id}/approve" method="POST">
                        <input type="hidden" name="_token" value="{{ csrf_token() }}">
                        <input type="hidden" name="catatan_admin" value="Disetujui">
                        <button type="submit" class="px-4 py-2 bg-green-600 text-white rounded-lg">Approve</button>
                    </form>
                    <form action="/admin/absensi/approval/${submission.id}/reject" method="POST" class="flex gap-2">
                        <input type="hidden" name="_token" value="{{ csrf_token() }}">
                        <input type="text" name="catatan_admin" placeholder="Alasan reject..." required class="border rounded-lg px-2 text-sm dark:bg-gray-700 dark:border-gray-600">
                        <button type="submit" class="px-4 py-2 bg-red-600 text-white rounded-lg">Reject</button>
                    </form>
                `;
            } else {
                modalActions.innerHTML = `<button type="button" class="px-4 py-2 bg-gray-500 text-white rounded-lg" onclick="closeDetailModal()">Tutup</button>`;
            }

            modalDetail.classList.remove('hidden');
            document.body.classList.add('overflow-hidden');
        } catch (e) {
            console.error("Error:", e);
            alert("Terjadi kesalahan.");
        }
    };
    
    // Pasang listener langsung ke dokumen
    document.addEventListener('click', function(e) {
        if (e.target.classList.contains('view-detail-btn')) {
            console.log("Tombol Detail diklik!");
            try {
                const submission = JSON.parse(e.target.dataset.submission);
                window.openDetailModal(submission);
            } catch (err) {
                console.error("Error parsing data:", err);
            }
        }
        if (e.target.id === 'detailOverlay') {
            window.closeDetailModal();
        }
    });
</script>


