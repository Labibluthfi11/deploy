<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center justify-between">
            <div>
                <h2 class="font-bold text-2xl text-gray-900 dark:text-white leading-tight">
                    Final Approval Dashboard
                </h2>
                <p class="mt-1 text-sm text-gray-600 dark:text-gray-400">Review dan approve pengajuan lembur karyawan</p>
            </div>
            <div class="flex items-center gap-3">
                <div class="text-right">
                    <p class="text-xs text-gray-500 dark:text-gray-400">Hari ini</p>
                    <p class="text-sm font-semibold text-gray-900 dark:text-white">{{ \Carbon\Carbon::now()->isoFormat('DD MMMM YYYY') }}</p>
                </div>
                <div class="h-10 w-10 rounded-full bg-gradient-to-br from-indigo-500 to-purple-600 flex items-center justify-center text-white font-bold text-sm shadow-lg">
                    {{ strtoupper(substr(auth()->user()->name, 0, 2)) }}
                </div>
            </div>
        </div>
    </x-slot>

    <div class="py-8 bg-gray-50 dark:bg-gray-900 min-h-screen">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">

            {{-- OVERVIEW STATS --}}
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6 mb-8">
                <div class="bg-white dark:bg-gray-800 rounded-2xl shadow-lg border border-gray-200 dark:border-gray-700 p-6 hover:shadow-xl hover:border-gray-300 dark:hover:border-gray-600 transition-all duration-300 hover:-translate-y-0.5">
                    <div class="flex items-center justify-between">
                        <div>
                            <p class="text-sm font-medium text-gray-600 dark:text-gray-400 mb-1">Freelance Pending</p>
                            <p class="text-3xl font-bold text-gray-900 dark:text-white">{{ $freelanceHRGA->count() }}</p>
                            <p class="text-xs text-gray-500 mt-1">Menunggu approval</p>
                        </div>
                        <div class="p-4 bg-blue-500/10 rounded-xl">
                            <span class="text-3xl">👥</span>
                        </div>
                    </div>
                </div>

                <div class="bg-white dark:bg-gray-800 rounded-2xl shadow-lg border border-gray-200 dark:border-gray-700 p-6 hover:shadow-xl hover:border-gray-300 dark:hover:border-gray-600 transition-all duration-300 hover:-translate-y-0.5">
                    <div class="flex items-center justify-between">
                        <div>
                            <p class="text-sm font-medium text-gray-600 dark:text-gray-400 mb-1">Organik Pending</p>
                            <p class="text-3xl font-bold text-gray-900 dark:text-white">{{ $organikHRGA->count() }}</p>
                            <p class="text-xs text-gray-500 mt-1">Menunggu approval</p>
                        </div>
                        <div class="p-4 bg-purple-500/10 rounded-xl">
                            <span class="text-3xl">👨‍💼</span>
                        </div>
                    </div>
                </div>

                <div class="bg-white dark:bg-gray-800 rounded-2xl shadow-lg border border-gray-200 dark:border-gray-700 p-6 hover:shadow-xl hover:border-gray-300 dark:hover:border-gray-600 transition-all duration-300 hover:-translate-y-0.5">
                    <div class="flex items-center justify-between">
                        <div>
                            <p class="text-sm font-medium text-gray-600 dark:text-gray-400 mb-1">Total Pending</p>
                            <p class="text-3xl font-bold text-gray-900 dark:text-white">{{ $freelanceHRGA->count() + $organikHRGA->count() }}</p>
                            <p class="text-xs text-gray-500 mt-1">Perlu di-review</p>
                        </div>
                        <div class="p-4 bg-yellow-500/10 rounded-xl">
                            <span class="text-3xl">⏱️</span>
                        </div>
                    </div>
                </div>

                <div class="bg-gradient-to-br from-indigo-600 to-purple-600 rounded-2xl shadow-lg p-6 hover:shadow-xl transition-all duration-300 hover:-translate-y-0.5">
                    <div class="flex items-center justify-between mb-3">
                        <div>
                            <p class="text-sm font-medium text-indigo-100 mb-1">Quick Actions</p>
                            <p class="text-2xl font-bold text-white">HRGA Panel</p>
                        </div>
                        <span class="text-3xl">⚡</span>
                    </div>
                </div>
            </div>

            {{-- TABS NAVIGATION --}}
            <div class="bg-white dark:bg-gray-800 rounded-2xl shadow-lg border border-gray-200 dark:border-gray-700 mb-6 overflow-hidden">
                <div class="flex border-b border-gray-200 dark:border-gray-700">
                    <button onclick="showTab('freelance')" id="tab-freelance" class="flex-1 px-6 py-4 text-sm font-semibold text-indigo-600 dark:text-indigo-400 border-b-2 border-indigo-500 bg-indigo-50 dark:bg-indigo-500/10 transition-all">
                        <div class="flex items-center justify-center gap-2">
                            <span>👥</span>
                            <span>Freelance</span>
                            @if($freelanceHRGA->count() > 0)
                                <span class="ml-2 px-2 py-0.5 bg-indigo-500 text-white text-xs rounded-full">{{ $freelanceHRGA->count() }}</span>
                            @endif
                        </div>
                    </button>
                    <button onclick="showTab('organik')" id="tab-organik" class="flex-1 px-6 py-4 text-sm font-semibold text-gray-600 dark:text-gray-400 hover:text-gray-900 dark:hover:text-gray-300 hover:bg-gray-50 dark:hover:bg-gray-700/50 transition-all">
                        <div class="flex items-center justify-center gap-2">
                            <span>👨‍💼</span>
                            <span>Organik</span>
                            @if($organikHRGA->count() > 0)
                                <span class="ml-2 px-2 py-0.5 bg-purple-500 text-white text-xs rounded-full">{{ $organikHRGA->count() }}</span>
                            @endif
                        </div>
                    </button>
                </div>
            </div>

            {{-- FREELANCE CONTENT --}}
            <div id="content-freelance" class="tab-content">
                <div class="bg-white dark:bg-gray-800 rounded-2xl shadow-lg border border-gray-200 dark:border-gray-700 overflow-hidden">
                    <div class="bg-gradient-to-r from-blue-600 to-indigo-600 dark:from-blue-700 dark:to-indigo-700 px-6 py-5 border-b border-gray-200 dark:border-gray-700">
                        <div class="flex items-center gap-3">
                            <span class="text-3xl">👥</span>
                            <div>
                                <h2 class="text-xl font-bold text-white">Freelance Final Approval</h2>
                                <p class="text-sm text-blue-100 mt-0.5">Review pengajuan dari karyawan freelance</p>
                            </div>
                        </div>
                    </div>

                    @if ($freelanceHRGA->isEmpty())
                        <div class="px-6 py-16 text-center">
                            <div class="text-6xl mb-4">🎉</div>
                            <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-2">Semua Sudah Clear!</h3>
                            <p class="text-gray-600 dark:text-gray-400">Tidak ada pengajuan freelance yang menunggu final approval.</p>
                        </div>
                    @else
                        <div class="p-6">
                            @include('admin.absensi.approval.table', ['submissions' => $freelanceHRGA])
                        </div>
                    @endif
                </div>
            </div>

            {{-- ORGANIK CONTENT --}}
            <div id="content-organik" class="tab-content hidden">
                <div class="bg-white dark:bg-gray-800 rounded-2xl shadow-lg border border-gray-200 dark:border-gray-700 overflow-hidden">
                    <div class="bg-gradient-to-r from-purple-600 to-pink-600 dark:from-purple-700 dark:to-pink-700 px-6 py-5 border-b border-gray-200 dark:border-gray-700">
                        <div class="flex items-center gap-3">
                            <span class="text-3xl">👨‍💼</span>
                            <div>
                                <h2 class="text-xl font-bold text-white">Organik Final Approval</h2>
                                <p class="text-sm text-purple-100 mt-0.5">Review pengajuan dari karyawan organik</p>
                            </div>
                        </div>
                    </div>

                    @if ($organikHRGA->isEmpty())
                        <div class="px-6 py-16 text-center">
                            <div class="text-6xl mb-4">🎉</div>
                            <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-2">Semua Sudah Clear!</h3>
                            <p class="text-gray-600 dark:text-gray-400">Tidak ada pengajuan organik yang menunggu final approval.</p>
                        </div>
                    @else
                        <div class="p-6">
                            @include('admin.absensi.approval.table', ['submissions' => $organikHRGA])
                        </div>
                    @endif
                </div>
            </div>

        </div>
    </div>

    @include('admin.absensi.approval.partials.reject-modal')

    {{-- 🔥 TAB SWITCHING SCRIPT - FINAL FIX --}}
    <script>
        function showTab(tabName) {
            console.log('🔄 [TAB] Switching to:', tabName);

            // Hide all content
            document.querySelectorAll('.tab-content').forEach(content => {
                content.classList.add('hidden');
            });

            // Remove active state from all tabs
            document.querySelectorAll('[id^="tab-"]').forEach(tab => {
                tab.classList.remove(
                    'text-indigo-600', 'dark:text-indigo-400', 'border-indigo-500', 'bg-indigo-50', 'dark:bg-indigo-500/10',
                    'text-purple-600', 'dark:text-purple-400', 'border-purple-500', 'bg-purple-50', 'dark:bg-purple-500/10'
                );
                tab.classList.add('text-gray-600', 'dark:text-gray-400');
                tab.classList.remove('border-b-2');
            });

            // Show selected content
            const contentElement = document.getElementById('content-' + tabName);
            if (contentElement) {
                contentElement.classList.remove('hidden');
                console.log('✅ [TAB] Content shown');
            }

            // Add active state to selected tab
            const activeTab = document.getElementById('tab-' + tabName);
            if (activeTab) {
                activeTab.classList.remove('text-gray-600', 'dark:text-gray-400');

                if (tabName === 'freelance') {
                    activeTab.classList.add('text-indigo-600', 'dark:text-indigo-400', 'border-indigo-500', 'bg-indigo-50', 'dark:bg-indigo-500/10');
                } else {
                    activeTab.classList.add('text-purple-600', 'dark:text-purple-400', 'border-purple-500', 'bg-purple-50', 'dark:bg-purple-500/10');
                }
                activeTab.classList.add('border-b-2');
            }

            // 🔥 CRITICAL: Re-attach event listeners after tab switch
            setTimeout(() => {
                const activeContent = document.getElementById('content-' + tabName);
                if (activeContent) {
                    const rejectButtons = activeContent.querySelectorAll('button[onclick*="openRejectModal"]');
                    console.log(`🔍 [TAB] Found ${rejectButtons.length} reject buttons in ${tabName}`);

                    // Force re-attach onclick to each button
                    rejectButtons.forEach((btn, idx) => {
                        const onclickAttr = btn.getAttribute('onclick');
                        const match = onclickAttr ? onclickAttr.match(/openRejectModal\((\d+)\)/) : null;

                        if (match) {
                            const id = parseInt(match[1]);
                            console.log(`  ✅ Button #${idx + 1} ready for ID: ${id}`);

                            // FORCE onclick re-attachment
                            btn.onclick = function(e) {
                                e.preventDefault();
                                e.stopPropagation();
                                console.log('🔴 [CLICK] Button clicked for ID:', id);

                                // Call the global function
                                if (typeof window.openRejectModal === 'function') {
                                    window.openRejectModal(id);
                                } else {
                                    console.error('❌ openRejectModal function not found!');
                                    alert('Error: Modal function tidak ditemukan!');
                                }
                            };
                        }
                    });
                }

                // Verify modal exists
                const modal = document.getElementById('rejectModal');
                console.log('🔍 [TAB] Modal exists:', !!modal);
                console.log('🔍 [TAB] openRejectModal function:', typeof window.openRejectModal);
            }, 150);

            window.scrollTo({ top: 0, behavior: 'smooth' });
        }

        // Initialize
        document.addEventListener('DOMContentLoaded', function() {
            console.log('📦 [INIT] Page loaded');
            showTab('freelance');
        });
    </script>
</x-app-layout>
