<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center justify-between">
            <div>
                <h2 class="font-bold text-2xl text-gray-900 dark:text-white leading-tight">
                    Supervisor Approval Dashboard
                </h2>
                <p class="mt-1 text-sm text-gray-600 dark:text-gray-400">Review dan approve pengajuan lembur karyawan freelance</p>
            </div>
            <div class="flex items-center gap-3">
                <div class="text-right">
                    <p class="text-xs text-gray-500 dark:text-gray-400">Hari ini</p>
                    <p class="text-sm font-semibold text-gray-900 dark:text-white">{{ \Carbon\Carbon::now()->isoFormat('DD MMMM YYYY') }}</p>
                </div>
                <div class="h-10 w-10 rounded-full bg-gradient-to-br from-orange-500 to-red-600 flex items-center justify-center text-white font-bold text-sm shadow-lg">
                    {{ strtoupper(substr(auth()->user()->name, 0, 2)) }}
                </div>
            </div>
        </div>
    </x-slot>

    <div class="py-8 bg-gray-50 dark:bg-gray-900 min-h-screen">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">

            {{-- ALERT NOTIFICATIONS --}}
            @if (session('success'))
                <div class="mb-6 bg-gradient-to-r from-green-500 to-emerald-600 dark:from-green-600 dark:to-emerald-700 text-white px-6 py-4 rounded-xl shadow-lg flex items-center gap-3 animate-fade-in">
                    <div class="flex-shrink-0">
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                        </svg>
                    </div>
                    <div class="flex-1">
                        <p class="font-semibold">{{ session('success') }}</p>
                    </div>
                    <button data-action="dismiss-alert" class="flex-shrink-0 text-white/80 hover:text-white transition-colors">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                        </svg>
                    </button>
                </div>
            @endif

            @if (session('error'))
                <div class="mb-6 bg-gradient-to-r from-red-500 to-pink-600 dark:from-red-600 dark:to-pink-700 text-white px-6 py-4 rounded-xl shadow-lg flex items-center gap-3 animate-fade-in">
                    <div class="flex-shrink-0">
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                        </svg>
                    </div>
                    <div class="flex-1">
                        <p class="font-semibold">{{ session('error') }}</p>
                    </div>
                    <button data-action="dismiss-alert" class="flex-shrink-0 text-white/80 hover:text-white transition-colors">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                        </svg>
                    </button>
                </div>
            @endif

            {{-- OVERVIEW STATS --}}
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6 mb-8">
                {{-- Total Pending --}}
                <div class="bg-white dark:bg-gray-800 rounded-2xl shadow-lg border border-gray-200 dark:border-gray-700 p-6 hover:shadow-xl hover:border-gray-300 dark:hover:border-gray-600 transition-all duration-300 hover:-translate-y-0.5">
                    <div class="flex items-center justify-between">
                        <div>
                            <p class="text-sm font-medium text-gray-600 dark:text-gray-400 mb-1">Total Pending</p>
                            <p class="text-3xl font-bold text-gray-900 dark:text-white">{{ $freelanceYuli->count() }}</p>
                            <p class="text-xs text-gray-500 mt-1">Menunggu approval</p>
                        </div>
                        <div class="p-4 bg-orange-500/10 rounded-xl">
                            <svg class="w-8 h-8 text-orange-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/>
                            </svg>
                        </div>
                    </div>
                </div>

                {{-- Freelance Workers --}}
                <div class="bg-white dark:bg-gray-800 rounded-2xl shadow-lg border border-gray-200 dark:border-gray-700 p-6 hover:shadow-xl hover:border-gray-300 dark:hover:border-gray-600 transition-all duration-300 hover:-translate-y-0.5">
                    <div class="flex items-center justify-between">
                        <div>
                            <p class="text-sm font-medium text-gray-600 dark:text-gray-400 mb-1">Freelance Team</p>
                            <p class="text-3xl font-bold text-gray-900 dark:text-white">{{ $freelanceYuli->unique('user_id')->count() }}</p>
                            <p class="text-xs text-gray-500 mt-1">Karyawan aktif</p>
                        </div>
                        <div class="p-4 bg-blue-500/10 rounded-xl">
                            <svg class="w-8 h-8 text-blue-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z"/>
                            </svg>
                        </div>
                    </div>
                </div>

                {{-- Today's Submissions --}}
                <div class="bg-white dark:bg-gray-800 rounded-2xl shadow-lg border border-gray-200 dark:border-gray-700 p-6 hover:shadow-xl hover:border-gray-300 dark:hover:border-gray-600 transition-all duration-300 hover:-translate-y-0.5">
                    <div class="flex items-center justify-between">
                        <div>
                            <p class="text-sm font-medium text-gray-600 dark:text-gray-400 mb-1">Hari Ini</p>
                            <p class="text-3xl font-bold text-gray-900 dark:text-white">{{ $freelanceYuli->where('created_at', '>=', \Carbon\Carbon::today())->count() }}</p>
                            <p class="text-xs text-gray-500 mt-1">Pengajuan baru</p>
                        </div>
                        <div class="p-4 bg-purple-500/10 rounded-xl">
                            <svg class="w-8 h-8 text-purple-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/>
                            </svg>
                        </div>
                    </div>
                </div>

                {{-- Quick Actions --}}
                <div class="bg-gradient-to-br from-orange-600 to-red-600 rounded-2xl shadow-lg p-6 hover:shadow-xl transition-all duration-300 hover:-translate-y-0.5">
                    <div class="flex items-center justify-between mb-3">
                        <div>
                            <p class="text-sm font-medium text-orange-100 mb-1">Quick Actions</p>
                            <p class="text-2xl font-bold text-white">Supervisor Panel</p>
                        </div>
                        <svg class="w-8 h-8 text-orange-200" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-6 9l2 2 4-4"/>
                        </svg>
                    </div>
                </div>
            </div>

            {{-- MAIN CONTENT --}}
            <div class="bg-white dark:bg-gray-800 rounded-2xl shadow-lg border border-gray-200 dark:border-gray-700 overflow-hidden">
                <div class="bg-gradient-to-r from-orange-600 to-red-600 dark:from-orange-700 dark:to-red-700 px-6 py-5 border-b border-gray-200 dark:border-gray-700">
                    <div class="flex items-center justify-between">
                        <div class="flex items-center gap-3">
                            <div class="p-2 bg-white/20 backdrop-blur-sm rounded-lg">
                                <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-3 7h3m-3 4h3m-6-4h.01M9 16h.01"/>
                                </svg>
                            </div>
                            <div>
                                <h2 class="text-xl font-bold text-white">Freelance - Level Supervisor</h2>
                                <p class="text-sm text-orange-100 mt-0.5">Review dan approve pengajuan lembur karyawan freelance</p>
                            </div>
                        </div>
                    </div>
                </div>

                @if ($freelanceYuli->isEmpty())
                    <div class="px-6 py-16 text-center">
                        <div class="inline-flex items-center justify-center w-20 h-20 rounded-full bg-orange-100 dark:bg-orange-500/10 mb-4">
                            <svg class="w-10 h-10 text-orange-600 dark:text-orange-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                            </svg>
                        </div>
                        <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-2">Semua Sudah Clear! 🎉</h3>
                        <p class="text-gray-600 dark:text-gray-400">Tidak ada pengajuan freelance yang menunggu approval supervisor.</p>
                        <div class="mt-6">
                            <button class="inline-flex items-center px-4 py-2 bg-orange-600 hover:bg-orange-700 text-white text-sm font-semibold rounded-lg transition-all shadow-sm">
                                <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/>
                                </svg>
                                Refresh Data
                            </button>
                        </div>
                    </div>
                @else
                    <div class="p-6">
                        @include('admin.absensi.approval.table', ['submissions' => $freelanceYuli])
                    </div>
                @endif
            </div>

            {{-- TIPS SECTION --}}
            @if($freelanceYuli->count() > 0)
                <div class="mt-6 bg-gradient-to-r from-blue-50 to-indigo-50 dark:from-blue-500/10 dark:to-indigo-500/10 rounded-2xl p-6 border border-blue-200 dark:border-blue-500/20">
                    <div class="flex items-start gap-4">
                        <div class="flex-shrink-0">
                            <div class="p-2 bg-blue-100 dark:bg-blue-500/10 rounded-lg">
                                <svg class="w-5 h-5 text-blue-600 dark:text-blue-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                                </svg>
                            </div>
                        </div>
                        <div class="flex-1">
                            <h3 class="text-sm font-semibold text-gray-900 dark:text-white mb-2">💡 Tips untuk Supervisor</h3>
                            <ul class="text-sm text-gray-700 dark:text-gray-300 space-y-1">
                                <li class="flex items-start gap-2">
                                    <span class="text-blue-600 dark:text-blue-400 font-bold">•</span>
                                    <span>Review keterangan lembur dengan detail sebelum approve</span>
                                </li>
                                <li class="flex items-start gap-2">
                                    <span class="text-blue-600 dark:text-blue-400 font-bold">•</span>
                                    <span>Pastikan durasi lembur sesuai dengan pekerjaan yang dilakukan</span>
                                </li>
                                <li class="flex items-start gap-2">
                                    <span class="text-blue-600 dark:text-blue-400 font-bold">•</span>
                                    <span>Berikan alasan yang jelas saat reject pengajuan</span>
                                </li>
                            </ul>
                        </div>
                    </div>
                </div>
            @endif

        </div>
    </div>

    @include('admin.absensi.approval.partials.izin-keluar-table')
    @include('admin.absensi.approval.partials.reject-modal')

    <style nonce="{{ config('app.csp_nonce') }}">
        @keyframes fade-in {
            from {
                opacity: 0;
                transform: translateY(-10px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }
        .animate-fade-in {
            animation: fade-in 0.3s ease-out;
        }
    </style>

    <script nonce="{{ config('app.csp_nonce') }}">
        document.addEventListener('click', function(e) {
            var target = e.target.closest('[data-action="dismiss-alert"]');
            if (target) {
                var alert = target.closest('.animate-fade-in');
                if (alert) alert.remove();
            }
        });
    </script>

@if(session('success'))
<div id="flashToast" class="fixed bottom-6 right-6 z-[99999] flex items-center gap-3 px-5 py-4 bg-green-600 text-white rounded-2xl shadow-2xl transform transition-all duration-500 translate-y-0 opacity-100 max-w-[380px]">
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
            toast.classList.add('opacity-0', 'translate-y-5');
            setTimeout(() => toast.remove(), 500);
        }
    }, 4000);
    const closeBtn = document.getElementById('flashToastClose');
    if (closeBtn) closeBtn.addEventListener('click', function() { document.getElementById('flashToast').remove(); });
</script>
@endif

@if(session('error'))
<div id="flashToastError" class="fixed bottom-6 right-6 z-[99999] flex items-center gap-3 px-5 py-4 bg-red-600 text-white rounded-2xl shadow-2xl max-w-[380px] transition-all duration-500 opacity-100">
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
            toast.classList.add('opacity-0');
            setTimeout(() => toast.remove(), 500);
        }
    }, 4000);
</script>
@endif
</x-app-layout>
