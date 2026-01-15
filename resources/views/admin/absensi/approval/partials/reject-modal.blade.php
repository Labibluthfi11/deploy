{{-- MODERN REJECT MODAL - FORCE DISPLAY VERSION --}}
<div id="rejectModal" style="display: none;" class="fixed inset-0 z-[9999] overflow-y-auto">
    <div class="flex items-end justify-center min-h-screen pt-4 px-4 pb-20 text-center sm:block sm:p-0">
        {{-- Background overlay --}}
        <div class="fixed inset-0 bg-gray-900 bg-opacity-75 transition-opacity" onclick="closeRejectModal()"></div>

        {{-- Center modal --}}
        <span class="hidden sm:inline-block sm:align-middle sm:h-screen">&#8203;</span>

        <div class="inline-block align-bottom bg-white dark:bg-gray-800 rounded-xl text-left overflow-hidden shadow-2xl transform transition-all sm:my-8 sm:align-middle sm:max-w-lg sm:w-full relative z-[10000]">
            <form id="rejectForm" method="POST">
                @csrf
                <div class="bg-white dark:bg-gray-800 px-6 pt-6 pb-4">
                    {{-- Icon & Title --}}
                    <div class="flex items-start">
                        <div class="flex-shrink-0">
                            <div class="mx-auto flex h-12 w-12 items-center justify-center rounded-full bg-red-100">
                                <svg class="h-6 w-6 text-red-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/>
                                </svg>
                            </div>
                        </div>
                        <div class="ml-4 flex-1">
                            <h3 class="text-lg font-semibold text-gray-900 dark:text-white">Tolak Pengajuan</h3>
                            <div class="mt-2">
                                <p class="text-sm text-gray-500 dark:text-gray-400">Silakan berikan alasan penolakan untuk pengajuan ini.</p>
                            </div>
                        </div>
                    </div>

                    {{-- Form Content --}}
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
                            class="block w-full rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm"></textarea>
                        <p class="mt-2 text-xs text-gray-500 dark:text-gray-400">Minimal 10 karakter</p>
                    </div>
                </div>

                {{-- Actions --}}
                <div class="bg-gray-50 dark:bg-gray-700 px-6 py-4 flex flex-row-reverse gap-3">
                    <button
                        type="submit"
                        class="inline-flex justify-center items-center px-4 py-2 border border-transparent text-sm font-medium rounded-lg shadow-sm text-white bg-red-600 hover:bg-red-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-red-500">
                        <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                        </svg>
                        Tolak Pengajuan
                    </button>
                    <button
                        type="button"
                        onclick="closeRejectModal()"
                        class="inline-flex justify-center items-center px-4 py-2 border border-gray-300 dark:border-gray-600 text-sm font-medium rounded-lg text-gray-700 dark:text-gray-300 bg-white dark:bg-gray-800 hover:bg-gray-50 dark:hover:bg-gray-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500">
                        Batal
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

{{-- ✅ SCRIPT WITH FORCE DISPLAY --}}
<script>
    function openRejectModal(id, routeUrl) {
        console.log('🔥 [MODAL] Opening modal', { id, routeUrl });

        const modal = document.getElementById('rejectModal');
        const form = document.getElementById('rejectForm');

        if (!modal) {
            console.error('❌ [MODAL] Modal element not found!');
            alert('ERROR: Modal tidak ditemukan di DOM!');
            return;
        }

        if (!form) {
            console.error('❌ [MODAL] Form element not found!');
            alert('ERROR: Form tidak ditemukan di DOM!');
            return;
        }

        // ✅ SET FORM ACTION
        form.action = routeUrl;
        console.log('✅ [MODAL] Form action set:', form.action);

        // 🔥 FORCE DISPLAY - PAKE STYLE.DISPLAY (bukan class)
        modal.style.display = 'block';
        document.body.style.overflow = 'hidden';

        console.log('✅ [MODAL] Modal displayed, current style:', modal.style.display);

        // Focus textarea
        setTimeout(() => {
            const textarea = document.getElementById('catatan_admin');
            if (textarea) {
                textarea.focus();
                console.log('✅ [MODAL] Textarea focused');
            }
        }, 100);
    }

    function closeRejectModal() {
        console.log('🔥 [MODAL] Closing modal');

        const modal = document.getElementById('rejectModal');
        if (modal) {
            modal.style.display = 'none';
            document.body.style.overflow = 'auto';
            console.log('✅ [MODAL] Modal closed');
        }

        const textarea = document.getElementById('catatan_admin');
        if (textarea) {
            textarea.value = '';
        }
    }

    // ESC key handler
    document.addEventListener('keydown', function(e) {
        if (e.key === 'Escape') {
            const modal = document.getElementById('rejectModal');
            if (modal && modal.style.display === 'block') {
                closeRejectModal();
            }
        }
    });

    // Form validation
    document.addEventListener('DOMContentLoaded', function() {
        console.log('✅ [MODAL] DOM loaded, checking modal existence');

        const modal = document.getElementById('rejectModal');
        const form = document.getElementById('rejectForm');

        console.log('Modal exists:', !!modal);
        console.log('Form exists:', !!form);

        if (form) {
            form.addEventListener('submit', function(e) {
                const textarea = document.getElementById('catatan_admin');
                if (textarea && textarea.value.trim().length < 10) {
                    e.preventDefault();
                    alert('⚠️ Alasan penolakan minimal 10 karakter!');
                    textarea.focus();
                }
            });
        }
    });
</script>
