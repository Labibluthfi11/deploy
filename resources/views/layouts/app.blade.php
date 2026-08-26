<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}"
      x-data="{ darkMode: localStorage.getItem('theme') === 'dark' }"
      x-init="$watch('darkMode', val => localStorage.setItem('theme', val ? 'dark' : 'light'))"
      :class="{ 'dark': darkMode }">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>{{ config('app.name', 'Laravel') }}</title>


    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css"
          integrity="sha512-DTOQO9RWCH3ppGqcWaEA1BIZOC6xxalwEsw9c2QQeAIftl+Vegovlnee1c9QX4TctnWMn13TZye+giMm8e2LwA=="
          crossorigin="anonymous"
          referrerpolicy="no-referrer" />

    @vite(['resources/css/app.css', 'resources/js/app.js'])
    <style nonce="{{ config('app.csp_nonce') }}">
        .custom-scrollbar::-webkit-scrollbar {
            width: 4px;
        }
        .custom-scrollbar::-webkit-scrollbar-track {
            background: transparent;
        }
        .custom-scrollbar::-webkit-scrollbar-thumb {
            background: #e2e8f0;
            border-radius: 10px;
        }
        .dark .custom-scrollbar::-webkit-scrollbar-thumb {
            background: #334155;
        }
        @media (min-width: 1024px) {
            .lg\:ml-64 { margin-left: 16rem !important; }
            .lg\:ml-0 { margin-left: 0 !important; }
        }
    </style>
    @stack('styles')
</head>

<body class="font-sans antialiased bg-gray-50 dark:bg-gray-900 text-gray-900 dark:text-gray-100">
<div class="min-h-screen w-full bg-gray-50 dark:bg-gray-900" x-data="{ sidebarOpen: true }">
    <!-- ========================================================= -->
    <!-- SIDEBAR -->
    <!-- ========================================================= -->
    <aside :class="sidebarOpen ? 'translate-x-0' : '-translate-x-full'"
           class="fixed top-0 left-0 bottom-0 z-50 w-64 bg-white dark:bg-gray-800 border-r border-gray-200 dark:border-gray-700 transition-all duration-300 ease-in-out shadow-xl lg:shadow-none flex flex-col overflow-y-auto custom-scrollbar">

        <!-- Logo (Kecil) -->
        <div class="sticky top-0 shrink-0 flex items-center gap-3 px-5 py-3 border-b border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-800 z-20">
            <img src="{{ asset('images/logo.png') }}" class="h-8 w-auto" alt="Logo">
            <div class="flex-1 min-w-0">
                <h1 class="text-sm font-bold text-gray-900 dark:text-white truncate">PT. Ansel Muda</h1>
            </div>
        </div>

        <!-- Navigation Menu (Super Rapat, Tanpa Scroll) -->
        <div class="flex-1 w-full">
            <nav class="px-3 py-3 space-y-0.5">
            <!-- Dashboard -->
            <a href="{{ route('dashboard') }}"
               class="flex items-center gap-3 px-3 py-2 text-sm font-medium rounded-lg transition-all
               {{ request()->routeIs('dashboard') ? 'bg-gradient-to-r from-indigo-600 to-purple-600 text-white shadow-sm' : 'text-gray-700 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-700' }}">
                <i class="fas fa-home text-base w-5"></i>
                <span>Dashboard</span>
            </a>

            <!-- ABSENSI SECTION -->
            <div class="pt-2 space-y-0.5">
                <div class="flex items-center gap-2 px-3 py-1.5 text-[11px] font-bold text-gray-400 dark:text-gray-500 uppercase tracking-wider">
                    <i class="fas fa-calendar-check w-4 text-indigo-400"></i>
                    <span>Absensi</span>
                </div>

                <a href="{{ route('admin.absensi.index') }}"
                   class="flex items-center gap-3 px-3 py-1.5 ml-3 text-sm font-medium rounded-md transition-all
                   {{ request()->routeIs('admin.absensi.index') ? 'bg-indigo-50 dark:bg-indigo-900/20 text-indigo-700 dark:text-indigo-400 border-l-4 border-indigo-600' : 'text-gray-600 dark:text-gray-400 hover:bg-gray-100 dark:hover:bg-gray-700' }}">
                    <i class="fas fa-circle text-[8px] w-4"></i>
                    <span>Semua</span>
                </a>

                <a href="{{ route('admin.absensi.organik') }}"
                   class="flex items-center gap-3 px-3 py-1.5 ml-3 text-sm font-medium rounded-md transition-all
                   {{ request()->routeIs('admin.absensi.organik') ? 'bg-indigo-50 dark:bg-indigo-900/20 text-indigo-700 dark:text-indigo-400 border-l-4 border-indigo-600' : 'text-gray-600 dark:text-gray-400 hover:bg-gray-100 dark:hover:bg-gray-700' }}">
                    <i class="fas fa-circle text-[8px] w-4"></i>
                    <span>Organik</span>
                </a>

                <a href="{{ route('admin.absensi.freelance') }}"
                   class="flex items-center gap-3 px-3 py-1.5 ml-3 text-sm font-medium rounded-md transition-all
                   {{ request()->routeIs('admin.absensi.freelance') ? 'bg-indigo-50 dark:bg-indigo-900/20 text-indigo-700 dark:text-indigo-400 border-l-4 border-indigo-600' : 'text-gray-600 dark:text-gray-400 hover:bg-gray-100 dark:hover:bg-gray-700' }}">
                    <i class="fas fa-circle text-[8px] w-4"></i>
                    <span>Freelance</span>
                </a>

                <a href="{{ route('admin.absensi.koreksi') }}"
                   class="flex items-center gap-3 px-3 py-1.5 ml-3 text-sm font-medium rounded-md transition-all
                   {{ request()->routeIs('admin.absensi.koreksi') ? 'bg-indigo-50 dark:bg-indigo-900/20 text-indigo-700 dark:text-indigo-400 border-l-4 border-indigo-600' : 'text-gray-600 dark:text-gray-400 hover:bg-gray-100 dark:hover:bg-gray-700' }}">
                    <i class="fas fa-pen-nib text-[8px] w-4"></i>
                    <span>Input Manual</span>
                </a>
            </div>

            <!-- APPROVAL SECTION -->
            @auth
            @if (Auth::user()->isAnyAdmin())
            <div class="pt-2 space-y-0.5 border-t border-gray-100 dark:border-gray-700/50 mt-2">
                <div class="flex items-center gap-2 px-3 py-1.5 text-[11px] font-bold text-gray-400 dark:text-gray-500 uppercase tracking-wider">
                    <i class="fas fa-user-shield w-4"></i>
                    <span>Approval</span>
                </div>

                <a href="{{ route('admin.absensi.approval.supervisor') }}"
                   class="flex items-center gap-3 px-3 py-1.5 ml-3 text-sm font-medium rounded-md transition-all
                   {{ request()->routeIs('admin.absensi.approval.supervisor') ? 'bg-green-50 dark:bg-green-900/20 text-green-700 dark:text-green-400 border-l-4 border-green-600' : 'text-gray-600 dark:text-gray-400 hover:bg-gray-100 dark:hover:bg-gray-700' }}">
                    <i class="fas fa-user-tie text-[10px] w-4"></i>
                    <span class="truncate">Supervisor (Freelance)</span>
                </a>

                <a href="{{ route('admin.absensi.approval.manager') }}"
                   class="flex items-center gap-3 px-3 py-1.5 ml-3 text-sm font-medium rounded-md transition-all
                   {{ request()->routeIs('admin.absensi.approval.manager') ? 'bg-yellow-50 dark:bg-yellow-900/20 text-yellow-700 dark:text-yellow-400 border-l-4 border-yellow-500' : 'text-gray-600 dark:text-gray-400 hover:bg-gray-100 dark:hover:bg-gray-700' }}">
                    <i class="fas fa-user-gear text-[10px] w-4"></i>
                    <span class="truncate">Manager (Freelance/Org)</span>
                </a>

                <a href="{{ route('admin.absensi.approval.hrga') }}"
                   class="flex items-center gap-3 px-3 py-1.5 ml-3 text-sm font-medium rounded-md transition-all
                   {{ request()->routeIs('admin.absensi.approval.hrga') ? 'bg-blue-50 dark:bg-blue-900/20 text-blue-700 dark:text-blue-400 border-l-4 border-blue-500' : 'text-gray-600 dark:text-gray-400 hover:bg-gray-100 dark:hover:bg-gray-700' }}">
                    <i class="fas fa-user-check text-[10px] w-4"></i>
                    <span class="truncate">HRGA (Final)</span>
                </a>
            </div>
            @endif
            @endauth

            <!-- MANAGEMENT -->
            @auth
            @if (Auth::user()->isAnyAdmin())
            <div class="pt-2 space-y-0.5 border-t border-gray-100 dark:border-gray-700/50 mt-2">
                <div class="flex items-center gap-2 px-3 py-1.5 text-[11px] font-bold text-gray-400 dark:text-gray-500 uppercase tracking-wider">
                    <i class="fas fa-toolbox w-4"></i>
                    <span>Manage</span>
                </div>
                <a href="{{ route('admin.holidays.index') }}"
                   class="flex items-center gap-3 px-3 py-1.5 ml-3 text-sm font-medium rounded-md transition-all
                   {{ request()->routeIs('admin.holidays.*') ? 'bg-indigo-50 dark:bg-indigo-900/20 text-indigo-700 dark:text-indigo-400 border-l-4 border-indigo-600' : 'text-gray-600 dark:text-gray-400 hover:bg-gray-100 dark:hover:bg-gray-700' }}">
                    <i class="fas fa-calendar-day text-[10px] w-4"></i>
                    <span>Hari Libur</span>
                </a>

                @if(Auth::user()->isSuperAdmin())
                <a href="{{ route('admin.activity_logs.index') }}"
                   class="flex items-center gap-3 px-3 py-1.5 ml-3 text-sm font-medium rounded-md transition-all
                   {{ request()->routeIs('admin.activity_logs.*') ? 'bg-rose-50 dark:bg-rose-900/20 text-rose-700 dark:text-rose-400 border-l-4 border-rose-600' : 'text-gray-600 dark:text-gray-400 hover:bg-gray-100 dark:hover:bg-gray-700' }}">
                    <i class="fas fa-history text-[10px] w-4"></i>
                    <span>Log Aktivitas</span>
                </a>
                @endif
            </div>
            @endif
            @endauth

            <!-- Export & Recap -->
            <div class="pt-2 space-y-0.5 border-t border-gray-100 dark:border-gray-700/50 mt-2">
                <a href="{{ route('admin.absensi.recap') }}"
                   class="flex items-center gap-3 px-3 py-2 text-sm font-medium rounded-lg transition-all
                   {{ request()->routeIs('admin.absensi.recap') ? 'bg-gradient-to-r from-purple-600 to-pink-600 text-white shadow-sm' : 'text-gray-700 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-700' }}">
                    <i class="fas fa-calendar-check text-base w-5"></i>
                    <span>Rekap Bulanan</span>
                </a>
            </div>
            </nav>
        </div>

        <!-- User Info (Kecil di Bawah) -->
        @auth
        <div class="sticky bottom-0 shrink-0 mt-auto p-3 border-t border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-800 z-20">
            <div class="flex items-center gap-2">
                <div class="w-8 h-8 rounded-full bg-gradient-to-br from-indigo-500 to-purple-600 flex items-center justify-center text-white text-xs font-bold shrink-0 shadow-sm">
                    {{ substr(Auth::user()->name ?? 'U', 0, 1) }}
                </div>
                <div class="flex-1 min-w-0">
                    <p class="text-xs font-bold text-gray-900 dark:text-white truncate">{{ Auth::user()->name ?? 'User' }}</p>
                    <p class="text-[10px] text-gray-500 dark:text-gray-400 truncate">{{ Auth::user()->email ?? '' }}</p>
                </div>
                <form method="POST" action="{{ route('logout') }}" class="shrink-0">
                    @csrf
                    <button type="submit" class="p-1.5 text-gray-400 hover:text-red-600 dark:hover:text-red-400 hover:bg-red-50 dark:hover:bg-red-900/20 rounded-md transition-all" title="Logout">
                        <i class="fas fa-sign-out-alt text-sm"></i>
                    </button>
                </form>
            </div>
        </div>
        @endauth
    </aside>

    <!-- Overlay (mobile) -->
    <div x-show="sidebarOpen"
         @click="sidebarOpen = false"
         x-transition:enter="transition-opacity ease-linear duration-300"
         x-transition:enter-start="opacity-0"
         x-transition:enter-end="opacity-100"
         x-transition:leave="transition-opacity ease-linear duration-300"
         x-transition:leave-start="opacity-100"
         x-transition:leave-end="opacity-0"
         class="fixed inset-0 bg-gray-900/50 lg:hidden z-40"></div>

    <!-- ========================================================= -->
    <!-- MAIN CONTENT -->
    <!-- ========================================================= -->
    <div :class="sidebarOpen ? 'lg:ml-64' : 'lg:ml-0'"
         class="flex flex-col min-h-screen transition-all duration-300 ease-in-out">
        <!-- Top Nav -->
        <header class="sticky top-0 z-30 bg-white dark:bg-gray-800 border-b border-gray-200 dark:border-gray-700 shadow-sm shrink-0">
            <div class="flex items-center justify-between px-6 py-4">
                <button @click="sidebarOpen = !sidebarOpen" class="p-2 mr-4 text-gray-600 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-700 rounded-lg transition-colors focus:outline-none">
                    <i class="fas fa-bars text-xl"></i>
                </button>

                <div class="flex-1">
                    @if (isset($header))
                        {{ $header }}
                    @endif
                </div>

                <!-- Dark Mode Toggle -->
                <button @click="darkMode = !darkMode"
                        class="p-2.5 text-gray-600 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-700 rounded-lg transition-all">
                    <i x-show="!darkMode" class="fas fa-moon text-lg"></i>
                    <i x-show="darkMode" class="fas fa-sun text-lg"></i>
                </button>
            </div>
        </header>

        <!-- Page Content -->
        <main class="flex-1 bg-gradient-to-br from-gray-50 via-blue-50 to-indigo-50 dark:from-gray-950 dark:via-gray-900 dark:to-indigo-950">
            {{ $slot }}
        </main>
    </div>
</div>

@stack('scripts')
</body>
</html>