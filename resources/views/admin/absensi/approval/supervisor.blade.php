<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center justify-between">
            <div>
                <h2 class="font-bold text-2xl text-gray-900 leading-tight">
                    Supervisor Approval Dashboard
                </h2>
                <p class="mt-1 text-sm text-gray-600">Review dan approve pengajuan lembur karyawan freelance</p>
            </div>
            <div class="flex items-center gap-3">
                <div class="text-right">
                    <p class="text-xs text-gray-500">Hari ini</p>
                    <p class="text-sm font-semibold text-gray-900">{{ \Carbon\Carbon::now()->isoFormat('DD MMMM YYYY') }}</p>
                </div>
                <div class="h-10 w-10 rounded-full bg-gradient-to-br from-orange-500 to-red-600 flex items-center justify-center text-white font-bold text-sm shadow-lg">
                    {{ strtoupper(substr(auth()->user()->name, 0, 2)) }}
                </div>
            </div>
        </div>
    </x-slot>

    <div class="py-8 bg-gradient-to-br from-gray-50 via-white to-orange-50/30 min-h-screen">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">

            {{-- ALERT NOTIFICATIONS --}}
            @if (session('success'))
                <div class="mb-6 bg-gradient-to-r from-green-500 to-emerald-600 text-white px-6 py-4 rounded-xl shadow-lg flex items-center gap-3 animate-fade-in">
                    <div class="flex-shrink-0">
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                        </svg>
                    </div>
                    <div class="flex-1">
                        <p class="font-semibold">{{ session('success') }}</p>
                    </div>
                    <button onclick="this.parentElement.remove()" class="flex-shrink-0 text-white/80 hover:text-white">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                        </svg>
                    </button>
                </div>
            @endif

            @if (session('error'))
                <div class="mb-6 bg-gradient-to-r from-red-500 to-pink-600 text-white px-6 py-4 rounded-xl shadow-lg flex items-center gap-3 animate-fade-in">
                    <div class="flex-shrink-0">
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                        </svg>
                    </div>
                    <div class="flex-1">
                        <p class="font-semibold">{{ session('error') }}</p>
                    </div>
                    <button onclick="this.parentElement.remove()" class="flex-shrink-0 text-white/80 hover:text-white">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                        </svg>
                    </button>
                </div>
            @endif

            {{-- OVERVIEW STATS --}}
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6 mb-8">
                {{-- Total Pending --}}
                <div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-6 hover:shadow-md transition-all duration-300 hover:-translate-y-0.5">
                    <div class="flex items-center justify-between">
                        <div>
                            <p class="text-sm font-medium text-gray-600 mb-1">Total Pending</p>
                            <p class="text-3xl font-bold text-gray-900">{{ $freelanceYuli->count() }}</p>
                            <p class="text-xs text-gray-500 mt-1">Menunggu approval</p>
                        </div>
                        <div class="p-4 bg-gradient-to-br from-orange-100 to-orange-50 rounded-xl">
                            <svg class="w-8 h-8 text-orange-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/>
                            </svg>
                        </div>
                    </div>
                </div>

                {{-- Freelance Workers --}}
                <div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-6 hover:shadow-md transition-all duration-300 hover:-translate-y-0.5">
                    <div class="flex items-center justify-between">
                        <div>
                            <p class="text-sm font-medium text-gray-600 mb-1">Freelance Team</p>
                            <p class="text-3xl font-bold text-gray-900">{{ $freelanceYuli->unique('user_id')->count() }}</p>
                            <p class="text-xs text-gray-500 mt-1">Karyawan aktif</p>
                        </div>
                        <div class="p-4 bg-gradient-to-br from-blue-100 to-blue-50 rounded-xl">
                            <svg class="w-8 h-8 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z"/>
                            </svg>
                        </div>
                    </div>
                </div>

                {{-- Today's Submissions --}}
                <div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-6 hover:shadow-md transition-all duration-300 hover:-translate-y-0.5">
                    <div class="flex items-center justify-between">
                        <div>
                            <p class="text-sm font-medium text-gray-600 mb-1">Hari Ini</p>
                            <p class="text-3xl font-bold text-gray-900">{{ $freelanceYuli->where('created_at', '>=', \Carbon\Carbon::today())->count() }}</p>
                            <p class="text-xs text-gray-500 mt-1">Pengajuan baru</p>
                        </div>
                        <div class="p-4 bg-gradient-to-br from-purple-100 to-purple-50 rounded-xl">
                            <svg class="w-8 h-8 text-purple-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/>
                            </svg>
                        </div>
                    </div>
                </div>

                {{-- Quick Actions --}}
                <div class="bg-gradient-to-br from-orange-600 to-red-600 rounded-2xl shadow-lg p-6 text-white hover:shadow-xl transition-all duration-300 hover:-translate-y-0.5">
                    <div class="flex items-center justify-between mb-3">
                        <div>
                            <p class="text-sm font-medium text-orange-100 mb-1">Quick Actions</p>
                            <p class="text-2xl font-bold">Supervisor Panel</p>
                        </div>
                        <svg class="w-8 h-8 text-orange-200" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-6 9l2 2 4-4"/>
                        </svg>
                    </div>
                    <div class="flex gap-2 mt-4">
                        <button class="flex-1 bg-white/20 hover:bg-white/30 backdrop-blur-sm text-white text-xs font-semibold py-2 px-3 rounded-lg transition-all">
                            Export
                        </button>
                        <button class="flex-1 bg-white/20 hover:bg-white/30 backdrop-blur-sm text-white text-xs font-semibold py-2 px-3 rounded-lg transition-all">
                            Settings
                        </button>
                    </div>
                </div>
            </div>

            {{-- MAIN CONTENT --}}
            <div class="bg-white rounded-2xl shadow-sm border border-gray-100 overflow-hidden">
                <div class="bg-gradient-to-r from-orange-600 to-red-600 px-6 py-5 border-b">
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
                        @if($freelanceYuli->count() > 0)
                            <div class="flex items-center gap-2">
                                <button class="px-4 py-2 bg-white/20 hover:bg-white/30 backdrop-blur-sm text-white text-sm font-medium rounded-lg transition-all flex items-center gap-2">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 4a1 1 0 011-1h16a1 1 0 011 1v2.586a1 1 0 01-.293.707l-6.414 6.414a1 1 0 00-.293.707V17l-4 4v-6.586a1 1 0 00-.293-.707L3.293 7.293A1 1 0 013 6.586V4z"/>
                                    </svg>
                                    Filter
                                </button>
                                <button class="px-4 py-2 bg-white text-orange-600 text-sm font-semibold rounded-lg hover:bg-orange-50 transition-all shadow-sm flex items-center gap-2">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"/>
                                    </svg>
                                    Export
                                </button>
                            </div>
                        @endif
                    </div>
                </div>

                @if ($freelanceYuli->isEmpty())
                    <div class="px-6 py-16 text-center">
                        <div class="inline-flex items-center justify-center w-20 h-20 rounded-full bg-orange-100 mb-4">
                            <svg class="w-10 h-10 text-orange-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                            </svg>
                        </div>
                        <h3 class="text-lg font-semibold text-gray-900 mb-2">Semua Sudah Clear! 🎉</h3>
                        <p class="text-gray-500">Tidak ada pengajuan freelance yang menunggu approval supervisor.</p>
                        <div class="mt-6">
                            <button class="inline-flex items-center px-4 py-2 bg-orange-600 text-white text-sm font-semibold rounded-lg hover:bg-orange-700 transition-all shadow-sm">
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
                <div class="mt-6 bg-gradient-to-r from-blue-50 to-indigo-50 rounded-2xl p-6 border border-blue-100">
                    <div class="flex items-start gap-4">
                        <div class="flex-shrink-0">
                            <div class="p-2 bg-blue-100 rounded-lg">
                                <svg class="w-5 h-5 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                                </svg>
                            </div>
                        </div>
                        <div class="flex-1">
                            <h3 class="text-sm font-semibold text-gray-900 mb-2">💡 Tips untuk Supervisor</h3>
                            <ul class="text-sm text-gray-700 space-y-1">
                                <li class="flex items-start gap-2">
                                    <span class="text-blue-600 font-bold">•</span>
                                    <span>Review keterangan lembur dengan detail sebelum approve</span>
                                </li>
                                <li class="flex items-start gap-2">
                                    <span class="text-blue-600 font-bold">•</span>
                                    <span>Pastikan durasi lembur sesuai dengan pekerjaan yang dilakukan</span>
                                </li>
                                <li class="flex items-start gap-2">
                                    <span class="text-blue-600 font-bold">•</span>
                                    <span>Berikan alasan yang jelas saat reject pengajuan</span>
                                </li>
                            </ul>
                        </div>
                    </div>
                </div>
            @endif

        </div>
    </div>

    @include('admin.absensi.approval.partials.reject-modal')

    <style>
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
</x-app-layout>
