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
                <div class="h-10 w-10 rounded-full bg-gradient-to-br from-indigo-500 to-purple-600 flex items-center justify-center text-white font-bold text-sm shadow-lg dark:text-gray-900">
                    {{ strtoupper(substr(auth()->user()->name, 0, 2)) }}
                </div>
            </div>
        </div>
    </x-slot>

    <div class="py-8 bg-gray-50 dark:bg-gray-900 min-h-screen">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">

            {{-- PERSISTENT STATUS FILTER --}}
            <div class="flex flex-wrap items-center gap-2 mb-8 bg-white dark:bg-gray-800 p-1.5 rounded-2xl shadow-sm border border-gray-200 dark:border-gray-700 w-fit">
                <a href="{{ request()->fullUrlWithQuery(['status' => 'pending']) }}" 
                   class="flex items-center gap-2 px-6 py-2.5 rounded-xl text-sm font-bold transition-all duration-300 {{ request('status', 'pending') === 'pending' ? 'bg-indigo-600 text-white shadow-lg shadow-indigo-500/30' : 'text-gray-500 hover:text-indigo-600 hover:bg-gray-50 dark:hover:bg-gray-700' }}">
                   <i class="fas fa-clock"></i>
                   Pending
                </a>
                <a href="{{ request()->fullUrlWithQuery(['status' => 'approved']) }}" 
                   class="flex items-center gap-2 px-6 py-2.5 rounded-xl text-sm font-bold transition-all duration-300 {{ request('status') === 'approved' ? 'bg-emerald-600 text-white shadow-lg shadow-emerald-500/30' : 'text-gray-500 hover:text-emerald-600 hover:bg-gray-50 dark:hover:bg-gray-700' }}">
                   <i class="fas fa-check-circle"></i>
                   Approved (History)
                </a>
                <a href="{{ request()->fullUrlWithQuery(['status' => 'rejected']) }}" 
                   class="flex items-center gap-2 px-6 py-2.5 rounded-xl text-sm font-bold transition-all duration-300 {{ request('status') === 'rejected' ? 'bg-red-600 text-white shadow-lg shadow-red-500/30' : 'text-gray-500 hover:text-red-600 hover:bg-gray-50 dark:hover:bg-gray-700' }}">
                   <i class="fas fa-times-circle"></i>
                   Rejected (History)
                </a>
            </div>

            {{-- OVERVIEW STATS --}}
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6 mb-8">
                {{-- Total Pending Freelance --}}
                <div class="bg-white dark:bg-gray-800 rounded-2xl shadow-lg border border-gray-200 dark:border-gray-700 p-6 hover:shadow-xl hover:border-gray-300 dark:hover:border-gray-600 transition-all duration-300 hover:-translate-y-0.5">
                    <div class="flex items-center justify-between">
                        <div>
                            <p class="text-sm font-medium text-gray-600 dark:text-gray-400 mb-1">Produksi Pending</p>
                            <p class="text-3xl font-bold text-gray-900 dark:text-white">{{ $freelanceHRGA->count() }}</p>
                            <p class="text-xs text-gray-500 mt-1">Menunggu approval</p>
                        </div>
                        <div class="p-4 bg-blue-500/10 rounded-xl">
                            <svg class="w-8 h-8 text-blue-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z"/>
                            </svg>
                        </div>
                    </div>
                </div>

                {{-- Total Pending Organik --}}
                <div class="bg-white dark:bg-gray-800 rounded-2xl shadow-lg border border-gray-200 dark:border-gray-700 p-6 hover:shadow-xl hover:border-gray-300 dark:hover:border-gray-600 transition-all duration-300 hover:-translate-y-0.5">
                    <div class="flex items-center justify-between">
                        <div>
                            <p class="text-sm font-medium text-gray-600 dark:text-gray-400 mb-1">Office Pending</p>
                            <p class="text-3xl font-bold text-gray-900 dark:text-white">{{ $organikHRGA->count() }}</p>
                            <p class="text-xs text-gray-500 mt-1">Menunggu approval</p>
                        </div>
                        <div class="p-4 bg-purple-500/10 rounded-xl">
                            <svg class="w-8 h-8 text-purple-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z"/>
                            </svg>
                        </div>
                    </div>
                </div>

                {{-- Total All Pending --}}
                <div class="bg-white dark:bg-gray-800 rounded-2xl shadow-lg border border-gray-200 dark:border-gray-700 p-6 hover:shadow-xl hover:border-gray-300 dark:hover:border-gray-600 transition-all duration-300 hover:-translate-y-0.5">
                    <div class="flex items-center justify-between">
                        <div>
                            <p class="text-sm font-medium text-gray-600 dark:text-gray-400 mb-1">Total Pending</p>
                            <p class="text-3xl font-bold text-gray-900 dark:text-white">{{ $freelanceHRGA->count() + $organikHRGA->count() + $scheduledLembur->count() }}</p>
                            <p class="text-xs text-gray-500 mt-1">Perlu di-review</p>
                        </div>
                        <div class="p-4 bg-yellow-500/10 rounded-xl">
                            <svg class="w-8 h-8 text-yellow-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/>
                            </svg>
                        </div>
                    </div>
                </div>

                {{-- Quick Actions --}}
                <div class="bg-gradient-to-br from-indigo-600 to-purple-600 rounded-2xl shadow-lg p-6 hover:shadow-xl transition-all duration-300 hover:-translate-y-0.5">
                    <div class="flex items-center justify-between mb-3">
                        <div>
                            <p class="text-sm font-medium text-indigo-100 mb-1">Quick Actions</p>
                            <p class="text-2xl font-bold text-white dark:text-gray-900">HRGA Panel</p>
                        </div>
                        <svg class="w-8 h-8 text-indigo-200" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"/>
                        </svg>
                    </div>
                </div>
            </div>

            {{-- TABS NAVIGATION --}}
            <div class="bg-white dark:bg-gray-800 rounded-2xl shadow-lg border border-gray-200 dark:border-gray-700 mb-6 overflow-hidden">
                <div class="flex border-b border-gray-200 dark:border-gray-700">
                    <button id="tab-freelance" data-tab="freelance" class="flex-1 px-6 py-4 text-sm font-semibold text-indigo-600 dark:text-indigo-400 border-b-2 border-indigo-500 bg-indigo-50 dark:bg-indigo-500/10 transition-all">
                        <div class="flex items-center justify-center gap-2">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z"/>
                            </svg>
                            <span>Produksi</span>
                            @if($freelanceHRGA->count() > 0)
                                <span class="ml-2 px-2 py-0.5 bg-indigo-500 text-white text-xs rounded-full dark:text-gray-900">{{ $freelanceHRGA->count() }}</span>
                            @endif
                        </div>
                    </button>
                    <button id="tab-organik" data-tab="organik" class="flex-1 px-6 py-4 text-sm font-semibold text-gray-600 dark:text-gray-400 hover:text-gray-900 dark:hover:text-gray-300 hover:bg-gray-50 dark:hover:bg-gray-700/50 transition-all">
                        <div class="flex items-center justify-center gap-2">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z"/>
                            </svg>
                            <span>Office</span>
                            @if($organikHRGA->count() > 0)
                                <span class="ml-2 px-2 py-0.5 bg-purple-500 text-white text-xs rounded-full dark:text-gray-900">{{ $organikHRGA->count() }}</span>
                            @endif
                        </div>
                    </button>
                </div>
            </div>

            {{-- FREELANCE CONTENT --}}
            <div id="content-freelance" class="tab-content">
                <div class="bg-white dark:bg-gray-800 rounded-2xl shadow-lg border border-gray-200 dark:border-gray-700 overflow-hidden">
                    <div class="bg-gradient-to-r from-blue-600 to-indigo-600 dark:from-blue-700 dark:to-indigo-700 px-6 py-5 border-b border-gray-200 dark:border-gray-700">
                        <div class="flex items-center justify-between">
                            <div class="flex items-center gap-3">
                                <div class="p-2 bg-white/20 backdrop-blur-sm rounded-lg">
                                    <svg class="w-6 h-6 text-white dark:text-gray-900" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z"/>
                                    </svg>
                                </div>
                                <div>
                                    <h2 class="text-xl font-bold text-white dark:text-gray-900">Produksi Final Approval</h2>
                                    <p class="text-sm text-blue-100 mt-0.5">Review pengajuan dari karyawan produksi</p>
                                </div>
                            </div>
                        </div>
                    </div>

                    @if ($freelanceHRGA->isEmpty())
                        <div class="px-6 py-16 text-center">
                            <div class="inline-flex items-center justify-center w-20 h-20 rounded-full bg-blue-100 dark:bg-blue-500/10 mb-4">
                                <svg class="w-10 h-10 text-blue-600 dark:text-blue-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                                </svg>
                            </div>
                            <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-2">Semua Sudah Clear! 🎉</h3>
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
                        <div class="flex items-center justify-between">
                            <div class="flex items-center gap-3">
                                <div class="p-2 bg-white/20 backdrop-blur-sm rounded-lg">
                                    <svg class="w-6 h-6 text-white dark:text-gray-900" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z"/>
                                    </svg>
                                </div>
                                <div>
                                    <h2 class="text-xl font-bold text-white dark:text-gray-900">Office Final Approval</h2>
                                    <p class="text-sm text-purple-100 mt-0.5">Review pengajuan dari karyawan office</p>
                                </div>
                            </div>
                        </div>
                    </div>

                    @if ($organikHRGA->isEmpty())
                        <div class="px-6 py-16 text-center">
                            <div class="inline-flex items-center justify-center w-20 h-20 rounded-full bg-purple-100 dark:bg-purple-500/10 mb-4">
                                <svg class="w-10 h-10 text-purple-600 dark:text-purple-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                                </svg>
                            </div>
                            <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-2">Semua Sudah Clear! 🎉</h3>
                            <p class="text-gray-600 dark:text-gray-400">Tidak ada pengajuan office yang menunggu final approval.</p>
                        </div>
                    @else
                        <div class="p-6">
                            @include('admin.absensi.approval.table', ['submissions' => $organikHRGA])
                        </div>
                    @endif
                </div>
            </div>

            @include('admin.absensi.approval.partials.scheduled-lembur-table')
            @include('admin.absensi.approval.partials.izin-keluar-table')

        </div>
    </div>

    @include('admin.absensi.approval.partials.reject-modal')

    {{-- TAB SWITCHING SCRIPT --}}
    <script nonce="{{ config('app.csp_nonce') }}">
    function showTab(tabName) {
        document.querySelectorAll('.tab-content').forEach(content => {
            content.classList.add('hidden');
        });

        document.querySelectorAll('[id^="tab-"]').forEach(tab => {
            tab.classList.remove('text-indigo-600', 'dark:text-indigo-400', 'border-indigo-500', 'bg-indigo-50', 'dark:bg-indigo-500/10', 'text-purple-600', 'dark:text-purple-400', 'border-purple-500', 'bg-purple-50', 'dark:bg-purple-500/10');
            tab.classList.add('text-gray-600', 'dark:text-gray-400', 'hover:text-gray-900', 'dark:hover:text-gray-300', 'hover:bg-gray-50', 'dark:hover:bg-gray-700/50');
            tab.classList.remove('border-b-2');
        });

        document.getElementById('content-' + tabName).classList.remove('hidden');

        const activeTab = document.getElementById('tab-' + tabName);
        activeTab.classList.remove('text-gray-600', 'dark:text-gray-400', 'hover:text-gray-900', 'dark:hover:text-gray-300', 'hover:bg-gray-50', 'dark:hover:bg-gray-700/50');

        if (tabName === 'freelance') {
            activeTab.classList.add('text-indigo-600', 'dark:text-indigo-400', 'border-indigo-500', 'bg-indigo-50', 'dark:bg-indigo-500/10');
        } else {
            activeTab.classList.add('text-purple-600', 'dark:text-purple-400', 'border-purple-500', 'bg-purple-50', 'dark:bg-purple-500/10');
        }
        activeTab.classList.add('border-b-2');
        window.scrollTo({ top: 0, behavior: 'smooth' });
    }

    document.addEventListener('DOMContentLoaded', function() {
        // Pasang event listener, bukan onclick
        document.querySelectorAll('[data-tab]').forEach(button => {
            button.addEventListener('click', function() {
                showTab(this.dataset.tab);
            });
        });

        @if($freelanceHRGA->count() == 0 && $organikHRGA->count() > 0)
            showTab('organik');
        @else
            showTab('freelance');
        @endif
    });
</script>

@if(session('success'))
<div id="flashToast" class="fixed bottom-6 right-6 z-[99999] flex items-center gap-3 px-5 py-4 bg-green-600 text-white rounded-2xl shadow-2xl transform transition-all duration-500 translate-y-0 opacity-100" style="max-width:380px">
    <div class="w-8 h-8 bg-white/20 rounded-xl flex items-center justify-center flex-shrink-0">
        <svg class="w-5 h-5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
        </svg>
    </div>
    <p class="text-sm font-semibold flex-1">{{ session('success') }}</p>
    <button id="flashToastClose" class="w-6 h-6 bg-white/20 hover:bg-white/30 rounded-lg flex items-center justify-center flex-shrink-0">
        <svg class="w-3 h-3 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
        </svg>
    </button>
</div>
<script nonce="{{ config('app.csp_nonce') }}">
    setTimeout(function() {
        const toast = document.getElementById('flashToast');
        if (toast) {
            toast.style.opacity = '0';
            toast.style.transform = 'translateY(20px)';
            setTimeout(() => toast.remove(), 500);
        }
    }, 4000);
    const closeBtn = document.getElementById('flashToastClose');
    if (closeBtn) closeBtn.addEventListener('click', function() { document.getElementById('flashToast').remove(); });
</script>
@endif

@if(session('error'))
<div id="flashToastError" class="fixed bottom-6 right-6 z-[99999] flex items-center gap-3 px-5 py-4 bg-red-600 text-white rounded-2xl shadow-2xl" style="max-width:380px">
    <div class="w-8 h-8 bg-white/20 rounded-xl flex items-center justify-center flex-shrink-0">
        <svg class="w-5 h-5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
        </svg>
    </div>
    <p class="text-sm font-semibold flex-1">{{ session('error') }}</p>
</div>
<script nonce="{{ config('app.csp_nonce') }}">
    setTimeout(function() {
        const toast = document.getElementById('flashToastError');
        if (toast) {
            toast.style.opacity = '0';
            setTimeout(() => toast.remove(), 500);
        }
    }, 4000);
</script>
@endif
</x-app-layout>
