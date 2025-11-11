<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center justify-between">
            <div>
                <h2 class="font-bold text-2xl text-gray-900 leading-tight">
                    Manager Approval Dashboard
                </h2>
                <p class="mt-1 text-sm text-gray-600">Review dan approve pengajuan lembur karyawan</p>
            </div>
            <div class="flex items-center gap-3">
                <div class="text-right">
                    <p class="text-xs text-gray-500">Hari ini</p>
                    <p class="text-sm font-semibold text-gray-900">{{ \Carbon\Carbon::now()->isoFormat('DD MMMM YYYY') }}</p>
                </div>
                <div class="h-10 w-10 rounded-full bg-gradient-to-br from-emerald-500 to-teal-600 flex items-center justify-center text-white font-bold text-sm shadow-lg">
                    {{ strtoupper(substr(auth()->user()->name, 0, 2)) }}
                </div>
            </div>
        </div>
    </x-slot>

    <div class="py-8 bg-gradient-to-br from-gray-50 via-white to-emerald-50/30 min-h-screen">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">

            {{-- OVERVIEW STATS --}}
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6 mb-8">
                {{-- Total Pending Freelance --}}
                <div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-6 hover:shadow-md transition-all duration-300 hover:-translate-y-0.5">
                    <div class="flex items-center justify-between">
                        <div>
                            <p class="text-sm font-medium text-gray-600 mb-1">Freelance Pending</p>
                            <p class="text-3xl font-bold text-gray-900">{{ $freelanceManager->count() }}</p>
                            <p class="text-xs text-gray-500 mt-1">Menunggu approval</p>
                        </div>
                        <div class="p-4 bg-gradient-to-br from-cyan-100 to-cyan-50 rounded-xl">
                            <svg class="w-8 h-8 text-cyan-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z"/>
                            </svg>
                        </div>
                    </div>
                </div>

                {{-- Total Pending Organik --}}
                <div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-6 hover:shadow-md transition-all duration-300 hover:-translate-y-0.5">
                    <div class="flex items-center justify-between">
                        <div>
                            <p class="text-sm font-medium text-gray-600 mb-1">Organik Pending</p>
                            <p class="text-3xl font-bold text-gray-900">{{ $organikManager->count() }}</p>
                            <p class="text-xs text-gray-500 mt-1">Menunggu approval</p>
                        </div>
                        <div class="p-4 bg-gradient-to-br from-emerald-100 to-emerald-50 rounded-xl">
                            <svg class="w-8 h-8 text-emerald-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z"/>
                            </svg>
                        </div>
                    </div>
                </div>

                {{-- Total All Pending --}}
                <div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-6 hover:shadow-md transition-all duration-300 hover:-translate-y-0.5">
                    <div class="flex items-center justify-between">
                        <div>
                            <p class="text-sm font-medium text-gray-600 mb-1">Total Pending</p>
                            <p class="text-3xl font-bold text-gray-900">{{ $freelanceManager->count() + $organikManager->count() }}</p>
                            <p class="text-xs text-gray-500 mt-1">Perlu di-review</p>
                        </div>
                        <div class="p-4 bg-gradient-to-br from-amber-100 to-amber-50 rounded-xl">
                            <svg class="w-8 h-8 text-amber-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/>
                            </svg>
                        </div>
                    </div>
                </div>

                {{-- Quick Actions --}}
                <div class="bg-gradient-to-br from-emerald-600 to-teal-600 rounded-2xl shadow-lg p-6 text-white hover:shadow-xl transition-all duration-300 hover:-translate-y-0.5">
                    <div class="flex items-center justify-between mb-3">
                        <div>
                            <p class="text-sm font-medium text-emerald-100 mb-1">Quick Actions</p>
                            <p class="text-2xl font-bold">Manager Panel</p>
                        </div>
                        <svg class="w-8 h-8 text-emerald-200" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4M7.835 4.697a3.42 3.42 0 001.946-.806 3.42 3.42 0 014.438 0 3.42 3.42 0 001.946.806 3.42 3.42 0 013.138 3.138 3.42 3.42 0 00.806 1.946 3.42 3.42 0 010 4.438 3.42 3.42 0 00-.806 1.946 3.42 3.42 0 01-3.138 3.138 3.42 3.42 0 00-1.946.806 3.42 3.42 0 01-4.438 0 3.42 3.42 0 00-1.946-.806 3.42 3.42 0 01-3.138-3.138 3.42 3.42 0 00-.806-1.946 3.42 3.42 0 010-4.438 3.42 3.42 0 00.806-1.946 3.42 3.42 0 013.138-3.138z"/>
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

            {{-- TABS NAVIGATION --}}
            <div class="bg-white rounded-2xl shadow-sm border border-gray-100 mb-6 overflow-hidden">
                <div class="flex border-b border-gray-200">
                    <button onclick="showTab('freelance')" id="tab-freelance" class="flex-1 px-6 py-4 text-sm font-semibold text-cyan-600 border-b-2 border-cyan-600 bg-cyan-50/50 transition-all">
                        <div class="flex items-center justify-center gap-2">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z"/>
                            </svg>
                            <span>Freelance</span>
                            @if($freelanceManager->count() > 0)
                                <span class="ml-2 px-2 py-0.5 bg-cyan-600 text-white text-xs rounded-full">{{ $freelanceManager->count() }}</span>
                            @endif
                        </div>
                    </button>
                    <button onclick="showTab('organik')" id="tab-organik" class="flex-1 px-6 py-4 text-sm font-semibold text-gray-600 hover:text-gray-900 hover:bg-gray-50 transition-all">
                        <div class="flex items-center justify-center gap-2">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z"/>
                            </svg>
                            <span>Organik</span>
                            @if($organikManager->count() > 0)
                                <span class="ml-2 px-2 py-0.5 bg-emerald-600 text-white text-xs rounded-full">{{ $organikManager->count() }}</span>
                            @endif
                        </div>
                    </button>
                </div>
            </div>

            {{-- FREELANCE CONTENT --}}
            <div id="content-freelance" class="tab-content">
                <div class="bg-white rounded-2xl shadow-sm border border-gray-100 overflow-hidden">
                    <div class="bg-gradient-to-r from-cyan-600 to-blue-600 px-6 py-5 border-b">
                        <div class="flex items-center justify-between">
                            <div class="flex items-center gap-3">
                                <div class="p-2 bg-white/20 backdrop-blur-sm rounded-lg">
                                    <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z"/>
                                    </svg>
                                </div>
                                <div>
                                    <h2 class="text-xl font-bold text-white">Freelance - Level Manager</h2>
                                    <p class="text-sm text-cyan-100 mt-0.5">Review pengajuan dari karyawan freelance</p>
                                </div>
                            </div>
                            @if($freelanceManager->count() > 0)
                                <div class="flex items-center gap-2">
                                    <button class="px-4 py-2 bg-white/20 hover:bg-white/30 backdrop-blur-sm text-white text-sm font-medium rounded-lg transition-all flex items-center gap-2">
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 4a1 1 0 011-1h16a1 1 0 011 1v2.586a1 1 0 01-.293.707l-6.414 6.414a1 1 0 00-.293.707V17l-4 4v-6.586a1 1 0 00-.293-.707L3.293 7.293A1 1 0 013 6.586V4z"/>
                                        </svg>
                                        Filter
                                    </button>
                                    <button class="px-4 py-2 bg-white text-cyan-600 text-sm font-semibold rounded-lg hover:bg-cyan-50 transition-all shadow-sm flex items-center gap-2">
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"/>
                                        </svg>
                                        Export
                                    </button>
                                </div>
                            @endif
                        </div>
                    </div>

                    @if ($freelanceManager->isEmpty())
                        <div class="px-6 py-16 text-center">
                            <div class="inline-flex items-center justify-center w-20 h-20 rounded-full bg-cyan-100 mb-4">
                                <svg class="w-10 h-10 text-cyan-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                                </svg>
                            </div>
                            <h3 class="text-lg font-semibold text-gray-900 mb-2">Semua Sudah Clear! 🎉</h3>
                            <p class="text-gray-500">Tidak ada pengajuan freelance yang menunggu approval manager.</p>
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
                <div class="bg-white rounded-2xl shadow-sm border border-gray-100 overflow-hidden">
                    <div class="bg-gradient-to-r from-emerald-600 to-teal-600 px-6 py-5 border-b">
                        <div class="flex items-center justify-between">
                            <div class="flex items-center gap-3">
                                <div class="p-2 bg-white/20 backdrop-blur-sm rounded-lg">
                                    <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z"/>
                                    </svg>
                                </div>
                                <div>
                                    <h2 class="text-xl font-bold text-white">Organik - Level Manager</h2>
                                    <p class="text-sm text-emerald-100 mt-0.5">Review pengajuan dari karyawan organik</p>
                                </div>
                            </div>
                            @if($organikManager->count() > 0)
                                <div class="flex items-center gap-2">
                                    <button class="px-4 py-2 bg-white/20 hover:bg-white/30 backdrop-blur-sm text-white text-sm font-medium rounded-lg transition-all flex items-center gap-2">
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 4a1 1 0 011-1h16a1 1 0 011 1v2.586a1 1 0 01-.293.707l-6.414 6.414a1 1 0 00-.293.707V17l-4 4v-6.586a1 1 0 00-.293-.707L3.293 7.293A1 1 0 013 6.586V4z"/>
                                        </svg>
                                        Filter
                                    </button>
                                    <button class="px-4 py-2 bg-white text-emerald-600 text-sm font-semibold rounded-lg hover:bg-emerald-50 transition-all shadow-sm flex items-center gap-2">
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"/>
                                        </svg>
                                        Export
                                    </button>
                                </div>
                            @endif
                        </div>
                    </div>

                    @if ($organikManager->isEmpty())
                        <div class="px-6 py-16 text-center">
                            <div class="inline-flex items-center justify-center w-20 h-20 rounded-full bg-emerald-100 mb-4">
                                <svg class="w-10 h-10 text-emerald-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                                </svg>
                            </div>
                            <h3 class="text-lg font-semibold text-gray-900 mb-2">Semua Sudah Clear! 🎉</h3>
                            <p class="text-gray-500">Tidak ada pengajuan organik yang menunggu approval manager.</p>
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

    {{-- TAB SWITCHING SCRIPT --}}
    <script>
        function showTab(tabName) {
            // Hide all content
            document.querySelectorAll('.tab-content').forEach(content => {
                content.classList.add('hidden');
            });

            // Remove active state from all tabs
            document.querySelectorAll('[id^="tab-"]').forEach(tab => {
                tab.classList.remove('text-cyan-600', 'border-cyan-600', 'bg-cyan-50/50', 'text-emerald-600', 'border-emerald-600', 'bg-emerald-50/50');
                tab.classList.add('text-gray-600', 'hover:text-gray-900', 'hover:bg-gray-50');
                tab.classList.remove('border-b-2');
            });

            // Show selected content
            document.getElementById('content-' + tabName).classList.remove('hidden');

            // Add active state to selected tab
            const activeTab = document.getElementById('tab-' + tabName);
            activeTab.classList.remove('text-gray-600', 'hover:text-gray-900', 'hover:bg-gray-50');

            if (tabName === 'freelance') {
                activeTab.classList.add('text-cyan-600', 'border-cyan-600', 'bg-cyan-50/50');
            } else {
                activeTab.classList.add('text-emerald-600', 'border-emerald-600', 'bg-emerald-50/50');
            }
            activeTab.classList.add('border-b-2');

            // Add smooth scroll animation
            window.scrollTo({ top: 0, behavior: 'smooth' });
        }

        // Initialize first tab as active
        document.addEventListener('DOMContentLoaded', function() {
            showTab('freelance');
        });
    </script>
</x-app-layout>
