<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center justify-between">
            <div>
                <h2 class="font-bold text-2xl text-gray-900 dark:text-white leading-tight">
                    Manager Approval Dashboard
                </h2>
                <p class="mt-1 text-sm text-gray-600 dark:text-gray-400">Review dan approve pengajuan lembur karyawan</p>
            </div>
            <div class="flex items-center gap-3">
                <div class="text-right">
                    <p class="text-xs text-gray-500 dark:text-gray-400">Hari ini</p>
                    <p class="text-sm font-semibold text-gray-900 dark:text-white">{{ \Carbon\Carbon::now()->isoFormat('DD MMMM YYYY') }}</p>
                </div>
                <div class="relative">
                    <div class="h-10 w-10 rounded-full bg-gradient-to-br from-emerald-500 to-teal-600 flex items-center justify-center text-white font-bold text-sm shadow-lg ring-2 ring-emerald-500/20">
                        {{ strtoupper(substr(auth()->user()->name, 0, 2)) }}
                    </div>
                    <div class="absolute -bottom-1 -right-1 h-3 w-3 bg-green-500 rounded-full border-2 border-white dark:border-gray-900"></div>
                </div>
            </div>
        </div>
    </x-slot>

    <div class="py-8 bg-gray-50 dark:bg-gray-900 min-h-screen">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">

            {{-- OVERVIEW STATS --}}
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6 mb-8">
                <div class="group bg-gradient-to-br from-white to-gray-50 dark:from-gray-800 dark:to-gray-800/50 rounded-2xl shadow-lg border border-gray-200 dark:border-gray-700 p-6 hover:shadow-2xl hover:border-cyan-300 dark:hover:border-cyan-500/50 transition-all duration-300 hover:-translate-y-1 relative overflow-hidden">
                    <div class="absolute inset-0 bg-gradient-to-br from-cyan-500/5 to-transparent opacity-0 group-hover:opacity-100 transition-opacity"></div>
                    <div class="relative flex items-center justify-between">
                        <div>
                            <p class="text-sm font-medium text-gray-600 dark:text-gray-400 mb-1">Freelance Pending</p>
                            <p class="text-3xl font-bold text-gray-900 dark:text-white mb-1">{{ $freelanceManager->count() }}</p>
                            <p class="text-xs text-gray-500 flex items-center gap-1">⏱️ Menunggu approval</p>
                        </div>
                        <div class="p-4 bg-cyan-500/10 rounded-xl group-hover:bg-cyan-500/20 transition-colors">
                            <span class="text-3xl">👥</span>
                        </div>
                    </div>
                </div>

                <div class="group bg-gradient-to-br from-white to-gray-50 dark:from-gray-800 dark:to-gray-800/50 rounded-2xl shadow-lg border border-gray-200 dark:border-gray-700 p-6 hover:shadow-2xl hover:border-emerald-300 dark:hover:border-emerald-500/50 transition-all duration-300 hover:-translate-y-1 relative overflow-hidden">
                    <div class="absolute inset-0 bg-gradient-to-br from-emerald-500/5 to-transparent opacity-0 group-hover:opacity-100 transition-opacity"></div>
                    <div class="relative flex items-center justify-between">
                        <div>
                            <p class="text-sm font-medium text-gray-600 dark:text-gray-400 mb-1">Organik Pending</p>
                            <p class="text-3xl font-bold text-gray-900 dark:text-white mb-1">{{ $organikManager->count() }}</p>
                            <p class="text-xs text-gray-500 flex items-center gap-1">⏱️ Menunggu approval</p>
                        </div>
                        <div class="p-4 bg-emerald-500/10 rounded-xl group-hover:bg-emerald-500/20 transition-colors">
                            <span class="text-3xl">👨‍💼</span>
                        </div>
                    </div>
                </div>

                <div class="group bg-gradient-to-br from-white to-gray-50 dark:from-gray-800 dark:to-gray-800/50 rounded-2xl shadow-lg border border-gray-200 dark:border-gray-700 p-6 hover:shadow-2xl hover:border-amber-300 dark:hover:border-amber-500/50 transition-all duration-300 hover:-translate-y-1 relative overflow-hidden">
                    <div class="absolute inset-0 bg-gradient-to-br from-amber-500/5 to-transparent opacity-0 group-hover:opacity-100 transition-opacity"></div>
                    <div class="relative flex items-center justify-between">
                        <div>
                            <p class="text-sm font-medium text-gray-600 dark:text-gray-400 mb-1">Total Pending</p>
                            <p class="text-3xl font-bold text-gray-900 dark:text-white mb-1">{{ $freelanceManager->count() + $organikManager->count() }}</p>
                            <p class="text-xs text-gray-500 flex items-center gap-1">📋 Perlu di-review</p>
                        </div>
                        <div class="p-4 bg-amber-500/10 rounded-xl group-hover:bg-amber-500/20 transition-colors">
                            <span class="text-3xl">📊</span>
                        </div>
                    </div>
                </div>

                <div class="group bg-gradient-to-br from-emerald-600 to-teal-600 rounded-2xl shadow-lg p-6 hover:shadow-2xl transition-all duration-300 hover:-translate-y-1 hover:scale-105 relative overflow-hidden">
                    <div class="absolute inset-0 bg-gradient-to-br from-white/10 to-transparent opacity-0 group-hover:opacity-100 transition-opacity"></div>
                    <div class="relative flex items-center justify-between mb-3">
                        <div>
                            <p class="text-sm font-medium text-emerald-100 mb-1">Quick Actions</p>
                            <p class="text-2xl font-bold text-white">Manager Panel</p>
                        </div>
                        <span class="text-3xl group-hover:scale-110 transition-transform">⚡</span>
                    </div>
                    <div class="relative text-xs text-emerald-100/80">Akses cepat untuk approval</div>
                </div>
            </div>

            {{-- TABS NAVIGATION --}}
            <div class="bg-white dark:bg-gray-800 rounded-2xl shadow-lg border border-gray-200 dark:border-gray-700 mb-6 overflow-hidden">
                <div class="flex border-b border-gray-200 dark:border-gray-700">
                    <button onclick="showTab('freelance')" id="tab-freelance" class="flex-1 px-6 py-4 text-sm font-semibold text-cyan-600 dark:text-cyan-400 border-b-2 border-cyan-500 bg-cyan-50 dark:bg-cyan-500/10 transition-all">
                        <div class="flex items-center justify-center gap-2">
                            <span>👥</span>
                            <span>Freelance</span>
                            @if($freelanceManager->count() > 0)
                                <span class="ml-2 px-2 py-0.5 bg-cyan-500 text-white text-xs rounded-full">{{ $freelanceManager->count() }}</span>
                            @endif
                        </div>
                    </button>
                    <button onclick="showTab('organik')" id="tab-organik" class="flex-1 px-6 py-4 text-sm font-semibold text-gray-600 dark:text-gray-400 hover:text-gray-900 dark:hover:text-gray-300 hover:bg-gray-50 dark:hover:bg-gray-700/50 transition-all">
                        <div class="flex items-center justify-center gap-2">
                            <span>👨‍💼</span>
                            <span>Organik</span>
                            @if($organikManager->count() > 0)
                                <span class="ml-2 px-2 py-0.5 bg-emerald-500 text-white text-xs rounded-full">{{ $organikManager->count() }}</span>
                            @endif
                        </div>
                    </button>
                </div>
            </div>

            {{-- FREELANCE CONTENT --}}
            <div id="content-freelance" class="tab-content">
                <div class="bg-white dark:bg-gray-800 rounded-2xl shadow-lg border border-gray-200 dark:border-gray-700 overflow-hidden">
                    <div class="bg-gradient-to-r from-cyan-600 via-blue-600 to-indigo-600 dark:from-cyan-700 dark:via-blue-700 dark:to-indigo-700 px-6 py-5 border-b border-gray-200 dark:border-gray-700">
                        <div class="flex items-center gap-3">
                            <span class="text-3xl">👥</span>
                            <div>
                                <h2 class="text-xl font-bold text-white">Freelance - Level Manager</h2>
                                <p class="text-sm text-cyan-100 mt-0.5">Review pengajuan dari karyawan freelance</p>
                            </div>
                        </div>
                    </div>

                    @if ($freelanceManager->isEmpty())
                        <div class="px-6 py-16 text-center">
                            <div class="text-6xl mb-4">🎉</div>
                            <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-2">Semua Sudah Clear!</h3>
                            <p class="text-gray-600 dark:text-gray-400">Tidak ada pengajuan freelance yang menunggu approval manager.</p>
                        </div>
                    @else
                        <div class="p-6">
                            @include('admin.absensi.approval.table', ['submissions' => $freelanceManager])
                        </div>
                    @endif
                </div>
            </div>

            {{-- ORGANIK CONTENT --}}
            <div id="content-organik" class="tab-content hidden">
                <div class="bg-white dark:bg-gray-800 rounded-2xl shadow-lg border border-gray-200 dark:border-gray-700 overflow-hidden">
                    <div class="bg-gradient-to-r from-emerald-600 via-teal-600 to-green-600 dark:from-emerald-700 dark:via-teal-700 dark:to-green-700 px-6 py-5 border-b border-gray-200 dark:border-gray-700">
                        <div class="flex items-center gap-3">
                            <span class="text-3xl">👨‍💼</span>
                            <div>
                                <h2 class="text-xl font-bold text-white">Organik - Level Manager</h2>
                                <p class="text-sm text-emerald-100 mt-0.5">Review pengajuan dari karyawan organik</p>
                            </div>
                        </div>
                    </div>

                    @if ($organikManager->isEmpty())
                        <div class="px-6 py-16 text-center">
                            <div class="text-6xl mb-4">🎉</div>
                            <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-2">Semua Sudah Clear!</h3>
                            <p class="text-gray-600 dark:text-gray-400">Tidak ada pengajuan organik yang menunggu approval manager.</p>
                        </div>
                    @else
                        <div class="p-6">
                            @include('admin.absensi.approval.table', ['submissions' => $organikManager])
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
                    'text-cyan-600', 'dark:text-cyan-400', 'border-cyan-500', 'bg-cyan-50', 'dark:bg-cyan-500/10',
                    'text-emerald-600', 'dark:text-emerald-400', 'border-emerald-500', 'bg-emerald-50', 'dark:bg-emerald-500/10'
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
                    activeTab.classList.add('text-cyan-600', 'dark:text-cyan-400', 'border-cyan-500', 'bg-cyan-50', 'dark:bg-cyan-500/10');
                } else {
                    activeTab.classList.add('text-emerald-600', 'dark:text-emerald-400', 'border-emerald-500', 'bg-emerald-50', 'dark:bg-emerald-500/10');
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
