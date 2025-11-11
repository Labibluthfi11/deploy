{{-- MODERN REJECT MODAL --}}
<div id="rejectModal" class="hidden fixed inset-0 z-50 overflow-y-auto" aria-labelledby="modal-title" role="dialog" aria-modal="true">
    <div class="flex items-end justify-center min-h-screen pt-4 px-4 pb-20 text-center sm:block sm:p-0">
        {{-- Background overlay --}}
        <div class="fixed inset-0 bg-gray-900 bg-opacity-75 transition-opacity" aria-hidden="true" onclick="closeRejectModal()"></div>

        {{-- Center modal --}}
        <span class="hidden sm:inline-block sm:align-middle sm:h-screen" aria-hidden="true">&#8203;</span>

        <div class="inline-block align-bottom bg-white rounded-xl text-left overflow-hidden shadow-2xl transform transition-all sm:my-8 sm:align-middle sm:max-w-lg sm:w-full">
            <form id="rejectForm" method="POST">
                @csrf
                <div class="bg-white px-6 pt-6 pb-4">
                    {{-- Icon & Title --}}
                    <div class="flex items-start">
                        <div class="flex-shrink-0">
                            <div class="mx-auto flex h-12 w-12 items-center justify-center rounded-full bg-red-100">
                                <svg class="h-6 w-6 text-red-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/></svg>
                            </div>
                        </div>
                        <div class="ml-4 flex-1">
                            <h3 class="text-lg font-semibold text-gray-900" id="modal-title">Tolak Pengajuan</h3>
                            <div class="mt-2">
                                <p class="text-sm text-gray-500">Silakan berikan alasan penolakan untuk pengajuan ini.</p>
                            </div>
                        </div>
                    </div>

                    {{-- Form Content --}}
                    <div class="mt-6">
                        <label for="catatan_admin" class="block text-sm font-medium text-gray-700 mb-2">
                            Alasan Penolakan <span class="text-red-500">*</span>
                        </label>
                        <textarea
                            name="catatan_admin"
                            id="catatan_admin"
                            rows="4"
                            required
                            placeholder="Contoh: Bukti surat sakit tidak valid atau lembur tidak disetujui karena tidak ada urgensi..."
                            class="block w-full rounded-lg border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm transition-colors"></textarea>
                        <p class="mt-2 text-xs text-gray-500">Minimal 10 karakter</p>
                    </div>
                </div>

                {{-- Actions --}}
                <div class="bg-gray-50 px-6 py-4 flex flex-row-reverse gap-3">
                    <button
                        type="submit"
                        class="inline-flex justify-center items-center px-4 py-2 border border-transparent text-sm font-medium rounded-lg shadow-sm text-white bg-red-600 hover:bg-red-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-red-500 transition-colors">
                        <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                        Tolak Pengajuan
                    </button>
                    <button
                        type="button"
                        onclick="closeRejectModal()"
                        class="inline-flex justify-center items-center px-4 py-2 border border-gray-300 text-sm font-medium rounded-lg text-gray-700 bg-white hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500 transition-colors">
                        Batal
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

{{-- ENHANCED SCRIPT --}}
<script>
    function openRejectModal(id) {
        const modal = document.getElementById('rejectModal');
        const form = document.getElementById('rejectForm');
        // PASTIKAN URL INI SESUAI DENGAN ROUTE REJECT ANDA
        form.action = `/admin/approval/${id}/reject`;

        modal.classList.remove('hidden');
        document.body.style.overflow = 'hidden';

        setTimeout(() => {
            document.getElementById('catatan_admin').focus();
        }, 100);
    }

    function closeRejectModal() {
        const modal = document.getElementById('rejectModal');
        modal.classList.add('hidden');
        document.body.style.overflow = 'auto';
        document.getElementById('catatan_admin').value = '';
    }

    document.addEventListener('keydown', function(e) {
        if (e.key === 'Escape') {
            closeRejectModal();
        }
    });

    document.getElementById('rejectForm').addEventListener('submit', function(e) {
        const textarea = document.getElementById('catatan_admin');
        if (textarea.value.trim().length < 10) {
            e.preventDefault();
            console.error('Alasan penolakan minimal 10 karakter');
            textarea.focus();
        }
    });
</script>
