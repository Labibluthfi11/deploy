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

    @vite(['resources/css/app.css', 'resources/js/app.js'])
    @stack('styles')
</head>

<body class="font-sans antialiased bg-gray-50 dark:bg-gray-900 text-gray-900 dark:text-gray-100">
<div class="min-h-screen flex" x-data="{ sidebarOpen: true }">
    <!-- ========================================================= -->
    <!-- SIDEBAR -->
    <!-- ========================================================= -->
    <aside :class="sidebarOpen ? 'translate-x-0' : '-translate-x-full'"
           class="fixed lg:static inset-y-0 left-0 z-50 w-64 bg-white dark:bg-gray-800 border-r border-gray-200 dark:border-gray-700 transform transition-transform duration-300 ease-in-out">

        <!-- Logo -->
        <div class="flex items-center gap-3 px-6 py-5 border-b border-gray-200 dark:border-gray-700">
            <img src="{{ asset('images/logo.png') }}" class="h-10 w-auto" alt="Logo">
            <div class="flex-1 min-w-0">
                <h1 class="text-sm font-bold text-gray-900 dark:text-white truncate">PT. Ansel Muda Berkarya</h1>
            </div>
        </div>

        <!-- Navigation Menu -->
        <nav class="px-4 py-6 space-y-2">
            <!-- Dashboard -->
            <a href="{{ route('dashboard') }}"
               class="flex items-center gap-3 px-4 py-3 text-sm font-medium rounded-xl transition-all
               {{ request()->routeIs('dashboard') ? 'bg-gradient-to-r from-indigo-600 to-purple-600 text-white shadow-md' : 'text-gray-700 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-700' }}">
                <i class="fas fa-home text-lg w-5"></i>
                <span>Dashboard</span>
            </a>

            <!-- ABSENSI SECTION -->
            <div class="space-y-1">
                <div class="flex items-center gap-3 px-4 py-2 text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wider">
                    <i class="fas fa-clipboard-list w-5"></i>
                    <span>Absensi</span>
                </div>

                <a href="{{ route('admin.absensi.index') }}"
                   class="flex items-center gap-3 px-4 py-2.5 ml-4 text-sm font-medium rounded-lg transition-all
                   {{ request()->routeIs('admin.absensi.index') ? 'bg-indigo-50 dark:bg-indigo-900/20 text-indigo-700 dark:text-indigo-400 border-l-4 border-indigo-600' : 'text-gray-600 dark:text-gray-400 hover:bg-gray-100 dark:hover:bg-gray-700' }}">
                    <i class="fas fa-circle text-xs w-5"></i>
                    <span>Semua</span>
                </a>

                <a href="{{ route('admin.absensi.organik') }}"
                   class="flex items-center gap-3 px-4 py-2.5 ml-4 text-sm font-medium rounded-lg transition-all
                   {{ request()->routeIs('admin.absensi.organik') ? 'bg-indigo-50 dark:bg-indigo-900/20 text-indigo-700 dark:text-indigo-400 border-l-4 border-indigo-600' : 'text-gray-600 dark:text-gray-400 hover:bg-gray-100 dark:hover:bg-gray-700' }}">
                    <i class="fas fa-circle text-xs w-5"></i>
                    <span>Organik</span>
                </a>

                <a href="{{ route('admin.absensi.freelance') }}"
                   class="flex items-center gap-3 px-4 py-2.5 ml-4 text-sm font-medium rounded-lg transition-all
                   {{ request()->routeIs('admin.absensi.freelance') ? 'bg-indigo-50 dark:bg-indigo-900/20 text-indigo-700 dark:text-indigo-400 border-l-4 border-indigo-600' : 'text-gray-600 dark:text-gray-400 hover:bg-gray-100 dark:hover:bg-gray-700' }}">
                    <i class="fas fa-circle text-xs w-5"></i>
                    <span>Freelance</span>
                </a>
            </div>

            <!-- ===================================================== -->
            <!-- APPROVAL SECTION (TIGA ROLE BARU) -->
            <!-- ===================================================== -->
            @auth
            @if (Auth::user()->is_admin)
            <div class="pt-4 space-y-1 border-t border-gray-200 dark:border-gray-700">
                <div class="flex items-center gap-3 px-4 py-2 text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wider">
                    <i class="fas fa-user-shield w-5"></i>
                    <span>Approval Absensi</span>
                </div>

                <!-- Supervisor -->
                <a href="{{ route('admin.absensi.approval.supervisor') }}"
                   class="flex items-center gap-3 px-4 py-2.5 ml-6 text-sm font-medium rounded-lg transition-all
                   {{ request()->routeIs('admin.absensi.approval.supervisor') ? 'bg-green-50 dark:bg-green-900/20 text-green-700 dark:text-green-400 border-l-4 border-green-600' : 'text-gray-600 dark:text-gray-400 hover:bg-gray-100 dark:hover:bg-gray-700' }}">
                    <i class="fas fa-user-tie text-xs w-5"></i>
                    <span>Supervisor (Freelance)</span>
                </a>

                <!-- Manager -->
                <a href="{{ route('admin.absensi.approval.manager') }}"
                   class="flex items-center gap-3 px-4 py-2.5 ml-6 text-sm font-medium rounded-lg transition-all
                   {{ request()->routeIs('admin.absensi.approval.manager') ? 'bg-yellow-50 dark:bg-yellow-900/20 text-yellow-700 dark:text-yellow-400 border-l-4 border-yellow-500' : 'text-gray-600 dark:text-gray-400 hover:bg-gray-100 dark:hover:bg-gray-700' }}">
                    <i class="fas fa-user-gear text-xs w-5"></i>
                    <span>Manager (Freelance & Organik)</span>
                </a>

                <!-- HRGA -->
                <a href="{{ route('admin.absensi.approval.hrga') }}"
                   class="flex items-center gap-3 px-4 py-2.5 ml-6 text-sm font-medium rounded-lg transition-all
                   {{ request()->routeIs('admin.absensi.approval.hrga') ? 'bg-blue-50 dark:bg-blue-900/20 text-blue-700 dark:text-blue-400 border-l-4 border-blue-500' : 'text-gray-600 dark:text-gray-400 hover:bg-gray-100 dark:hover:bg-gray-700' }}">
                    <i class="fas fa-user-check text-xs w-5"></i>
                    <span>HRGA (Final Approval)</span>
                </a>
            </div>
            @endif
            @endauth
            <!-- ===================================================== -->

            <!-- Export & Recap -->
            <div class="pt-4 space-y-1">
                <button class="flex items-center gap-3 px-4 py-3 w-full text-sm font-medium text-gray-700 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-700 rounded-xl transition-all">
                    <i class="fas fa-file-excel text-lg w-5"></i>
                    <span>Export Data</span>
                </button>

                <a href="{{ route('admin.absensi.recap') }}"
                   class="flex items-center gap-3 px-4 py-3 text-sm font-medium rounded-xl transition-all
                   {{ request()->routeIs('admin.absensi.recap') ? 'bg-gradient-to-r from-purple-600 to-pink-600 text-white shadow-md' : 'text-gray-700 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-700' }}">
                    <i class="fas fa-calendar-check text-lg w-5"></i>
                    <span>Rekap Bulanan</span>
                </a>
            </div>
        </nav>

        <!-- User Info -->
        @auth
        <div class="absolute bottom-0 left-0 right-0 p-4 border-t border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-800">
            <div class="flex items-center gap-3">
                <div class="w-10 h-10 rounded-full bg-gradient-to-br from-indigo-500 to-purple-600 flex items-center justify-center text-white font-bold">
                    {{ substr(Auth::user()->name ?? 'U', 0, 1) }}
                </div>
                <div class="flex-1 min-w-0">
                    <p class="text-sm font-semibold text-gray-900 dark:text-white truncate">{{ Auth::user()->name ?? 'User' }}</p>
                    <p class="text-xs text-gray-500 dark:text-gray-400 truncate">{{ Auth::user()->email ?? '' }}</p>
                </div>
                <form method="POST" action="{{ route('logout') }}">
                    @csrf
                    <button type="submit" class="p-2 text-gray-400 hover:text-red-600 dark:hover:text-red-400 transition-colors" title="Logout">
                        <i class="fas fa-sign-out-alt"></i>
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
    <div class="flex-1 flex flex-col min-w-0">
        <!-- Top Nav -->
        <header class="sticky top-0 z-30 bg-white dark:bg-gray-800 border-b border-gray-200 dark:border-gray-700 shadow-sm">
            <div class="flex items-center justify-between px-6 py-4">
                <button @click="sidebarOpen = !sidebarOpen" class="lg:hidden p-2 text-gray-600 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-700 rounded-lg">
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
        <main class="flex-1 overflow-y-auto bg-gradient-to-br from-gray-50 via-blue-50 to-indigo-50 dark:from-gray-950 dark:via-gray-900 dark:to-indigo-950">
            {{ $slot }}
        </main>
    </div>
</div>

@stack('scripts')
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css">
</body>
</html>
