<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" x-data="{ darkMode: localStorage.getItem('theme') === 'dark' }" x-init="$watch('darkMode', val => localStorage.setItem('theme', val ? 'dark' : 'light'))" :class="{ 'dark': darkMode }">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>{{ config('app.name', 'Laravel') }}</title>

    @vite(['resources/css/app.css', 'resources/js/app.js'])
    @stack('styles')

    <style>
        /* Efek animasi latar belakang */
        body::before {
            content: "";
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: radial-gradient(circle at top left, rgba(99, 102, 241, 0.1), transparent 70%),
                        radial-gradient(circle at bottom right, rgba(168, 85, 247, 0.1), transparent 70%);
            animation: pulseBG 10s ease-in-out infinite;
            z-index: -1;
        }

        @keyframes pulseBG {
            0%, 100% {
                opacity: 0.8;
                transform: scale(1);
            }
            50% {
                opacity: 1;
                transform: scale(1.05);
            }
        }
    </style>
</head>
<body class="font-sans antialiased bg-gradient-to-br from-gray-50 via-blue-50 to-indigo-50 dark:from-gray-900 dark:via-gray-800 dark:to-indigo-950 text-gray-900 dark:text-gray-100">

    <!-- Simple Header (Optional) -->
    <header class="absolute top-0 right-0 p-6 z-10">
        <button @click="darkMode = !darkMode" class="p-2.5 bg-white/80 dark:bg-gray-800/80 backdrop-blur-sm text-gray-600 dark:text-gray-300 hover:bg-white dark:hover:bg-gray-800 rounded-xl shadow-md transition-all">
            <i x-show="!darkMode" class="fas fa-moon text-lg"></i>
            <i x-show="darkMode" class="fas fa-sun text-lg"></i>
        </button>
    </header>

    <!-- Page Content -->
    <main>
        {{ $slot }}
    </main>

    @stack('scripts')

    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css">
</body>
</html>
