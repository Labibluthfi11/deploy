{{-- File: resources/views/admin/absensi/approval/partials/reject-modal.blade.php --}}

<div id="rejectModal" class="hidden fixed inset-0 z-50 overflow-y-auto" aria-labelledby="modal-title" role="dialog" aria-modal="true">
    <div class="flex items-end justify-center min-h-screen pt-4 px-4 pb-20 text-center sm:block sm:p-0">
        <div class="fixed inset-0 bg-gray-900 bg-opacity-75 transition-opacity" aria-hidden="true" onclick="closeRejectModal()"></div>
        <span class="hidden sm:inline-block sm:align-middle sm:h-screen" aria-hidden="true">&#8203;</span>

        <div class="inline-block align-bottom bg-white dark:bg-gray-800 rounded-xl text-left overflow-hidden shadow-2xl transform transition-all sm:my-8 sm:align-middle sm:max-w-lg sm:w-full">
            <form id="rejectForm" method="POST">
                @csrf
                <div class="bg-white dark:bg-gray-800 px-6 pt-6 pb-4">
                    <div class="flex items-start">
                        <div class="flex-shrink-0">
                            <div class="mx-auto flex h-12 w-12 items-center justify-center rounded-full bg-red-100 dark:bg-red-900/30">
                                <svg class="h-6 w-6 text-red-600 dark:text-red-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/>
                                </svg>
                            </div>
                        </div>
                        <div class="ml-4 flex-1">
                            <h3 class="text-lg font-semibold text-gray-900 dark:text-white" id="modal-title">Tolak Pengajuan</h3>
                            <div class="mt-2">
                                <p class="text-sm text-gray-500 dark:text-gray-400">Silakan berikan alasan penolakan untuk pengajuan ini.</p>
                            </div>
                        </div>
                    </div>

                    <div class="mt-6">
                        <label for="catatan_admin" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                            Alasan Penolakan <span class="text-red-500">*</span>
                        </label>
                        <textarea
                            name="catatan_admin"
                            id="catatan_admin"
                            rows="4"
                            required
                            placeholder="Contoh: Bukti surat sakit tidak valid atau lembur tidak disetujui karena tidak ada urgensi..."
                            class="block w-full rounded-lg border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700 text-gray-900 dark:text-white shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm"></textarea>
                        <p class="mt-2 text-xs text-gray-500 dark:text-gray-400">Minimal 10 karakter</p>
                    </div>
                </div>

                <div class="bg-gray-50 dark:bg-gray-700/50 px-6 py-4 flex flex-row-reverse gap-3">
                    <button type="submit" class="inline-flex justify-center items-center px-4 py-2 border border-transparent text-sm font-medium rounded-lg shadow-sm text-white bg-red-600 hover:bg-red-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-red-500 transition-colors">
                        <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                        </svg>
                        Tolak Pengajuan
                    </button>
                    <button type="button" onclick="closeRejectModal()" class="inline-flex justify-center items-center px-4 py-2 border border-gray-300 dark:border-gray-600 text-sm font-medium rounded-lg text-gray-700 dark:text-gray-300 bg-white dark:bg-gray-800 hover:bg-gray-50 dark:hover:bg-gray-700 transition-colors">
                        Batal
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
    console.log('🔴 [REJECT MODAL] Script loaded');

    window.openRejectModal = function(absensiId) {
        console.log('🔴 Opening modal for ID:', absensiId);

        const modal = document.getElementById('rejectModal');
        const form = document.getElementById('rejectForm');

        if (!modal || !form) {
            alert('ERROR: Modal tidak ditemukan!');
            console.error('Modal atau Form tidak ada!');
            return;
        }

        form.action = `/admin/approval/${absensiId}/reject`;
        console.log('Form action set to:', form.action);

        modal.classList.remove('hidden');
        document.body.style.overflow = 'hidden';

        setTimeout(() => {
            const textarea = document.getElementById('catatan_admin');
            if (textarea) textarea.focus();
        }, 100);
    };

    window.closeRejectModal = function() {
        const modal = document.getElementById('rejectModal');
        const textarea = document.getElementById('catatan_admin');

        if (modal) {
            modal.classList.add('hidden');
            document.body.style.overflow = 'auto';
        }

        if (textarea) textarea.value = '';
    };

    document.addEventListener('DOMContentLoaded', function() {
        console.log('✅ Modal ready');

        document.addEventListener('keydown', function(e) {
            if (e.key === 'Escape') {
                closeRejectModal();
            }
        });

        const form = document.getElementById('rejectForm');
        if (form) {
            form.addEventListener('submit', function(e) {
                const textarea = document.getElementById('catatan_admin');
                const value = textarea.value.trim();

                if (value.length < 10) {
                    e.preventDefault();
                    alert('⚠️ Alasan penolakan minimal 10 karakter!');
                    textarea.focus();
                    return false;
                }
            });
        }
    });
</script>

<style>
    #rejectModal {
        z-index: 99999 !important;
    }
</style>
