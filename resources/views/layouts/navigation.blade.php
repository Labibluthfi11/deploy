<nav x-data="{ open: false }" class="bg-dustyLatte-400 border-b border-dustyLatte-500 shadow-sm  transition-colors duration-500"> {{-- Ganti dengan dustyLatte --}}
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="flex justify-between h-16">
            <div class="flex">
                <div class="flex items-center gap-2">
                    <a href="{{ route('dashboard') }}">
                        {{-- Sesuaikan logo agar terlihat di background dustyLatte --}}
                        <img src="{{ asset('images/logo.png') }}" class="block h-12 w-auto fill-current text-gray-800 dark:text-gray-200 filter contrast-125 dark:filter-none" alt="Logo PT Ansel Muda Berkarya">
                    </a>
                    {{-- Sesuaikan warna teks jika logo aslinya gelap dan background nav jadi terang --}}
                    <span class="font-bold text-lg text-black">PT. Ansel Muda Berkarya</span>
                </div>
            </div>

            <div class="hidden space-x-8 sm:-my-px sm:ml-10 sm:flex">
                <x-nav-link :href="route('dashboard')" :active="request()->routeIs('dashboard')" class=" dark:text-black  ">
                    {{ __('Dashboard') }}
                </x-nav-link>
                {{-- Tambahkan link navigasi lain di sini jika ada --}}
            </div>

            <div class="hidden sm:flex sm:items-center sm:ml-6">
                {{-- Pastikan ini konsisten dengan script dark mode di app.blade.php atau guest.blade.php --}}
                <button id="theme-toggle" type="button" class="text-gray-500 dark:text-gray-400 focus:outline-none focus:ring-4 focus:ring-gray-200 rounded-lg text-sm p-2.5 mr-4 transition-colors duration-300">
                    {{-- Icons will be set by JS --}}
                </button>

                @auth
                    <x-dropdown align="right" width="48">
                        <x-slot name="trigger">
                            {{-- Sesuaikan warna teks dropdown trigger --}}
                            <button class="flex items-center text-sm font-medium text-gray-700 hover:text-gray-900 focus:outline-none dark:text-gray-300 dark:hover:text-gray-100 dark:focus:text-gray-100 transition duration-150 ease-in-out">
                                <div>{{ Auth::user()->name }}</div>
                                <div class="ml-1">
                                    <svg class="fill-current h-4 w-4" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20">
                                        <path fill-rule="evenodd" d="M5.293 7.293a1 1 0 011.414 0L10 10.586l3.293-3.293a1 1 0 111.414 1.414l-4 4a1 1 0 01-1.414 0l-4-4a1 1 0 010-1.414z" clip-rule="evenodd" />
                                    </svg>
                                </div>
                            </button>
                        </x-slot>

                        <x-slot name="content">
                            <x-dropdown-link :href="route('profile.edit')" class="text-gray-700 hover:bg-gray-100 dark:text-gray-300 dark:hover:bg-gray-700">
                                {{ __('Profile') }}
                            </x-dropdown-link>

                            <form method="POST" action="{{ route('logout') }}">
                                @csrf
                                <x-dropdown-link :href="route('logout')"
                                        onclick="event.preventDefault(); this.closest('form').submit();"
                                        class="text-gray-700 hover:bg-gray-100 dark:text-gray-300 dark:hover:bg-gray-700">
                                    {{ __('Log Out') }}
                                </x-dropdown-link>
                            </form>
                        </x-slot>
                    </x-dropdown>
                @endauth
            </div>

            <div class="-mr-2 flex items-center sm:hidden">
                <button @click="open = ! open" class="inline-flex items-center justify-center p-2 rounded-md text-gray-500 hover:text-gray-600 hover:bg-gray-100 focus:outline-none focus:bg-gray-100 focus:text-gray-600 dark:text-gray-400 dark:hover:text-gray-300 dark:hover:bg-gray-700 dark:focus:bg-gray-700 dark:focus:text-gray-300 transition duration-150 ease-in-out">
                    <svg class="h-6 w-6" stroke="currentColor" fill="none" viewBox="0 0 24 24">
                        <path :class="{'hidden': open, 'inline-flex': ! open }" class="inline-flex" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16" />
                        <path :class="{'hidden': ! open, 'inline-flex': open }" class="hidden" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                    </svg>
                </button>
            </div>
        </div>
    </div>

    <div :class="{'block': open, 'hidden': ! open}" class="hidden sm:hidden bg-dustyLatte-300 border-t border-dustyLatte-400 dark:bg-gray-900 dark:border-gray-700"> {{-- Ganti dengan dustyLatte --}}
        <div class="pt-2 pb-3 space-y-1">
            <x-responsive-nav-link :href="route('dashboard')" :active="request()->routeIs('dashboard')" class="text-gray-700 hover:bg-dustyLatte-400 dark:text-gray-300 dark:hover:bg-gray-800"> {{-- Sesuaikan warna hover --}}
                {{ __('Dashboard') }}
            </x-responsive-nav-link>
            {{-- Tambahkan responsive link lain di sini --}}
        </div>

        @auth
            <div class="pt-4 pb-1 border-t border-dustyLatte-400 dark:border-gray-700"> {{-- Ganti dengan dustyLatte --}}
                <div class="px-4">
                    <div class="font-medium text-base text-gray-800 dark:text-gray-200">{{ Auth::user()->name }}</div>
                    <div class="font-medium text-sm text-gray-500 dark:text-gray-400">{{ Auth::user()->email }}</div>
                </div>

                <div class="mt-3 space-y-1">
                    <x-responsive-nav-link :href="route('profile.edit')" class="text-gray-700 hover:bg-dustyLatte-400 dark:text-gray-300 dark:hover:bg-gray-800">
                        {{ __('Profile') }}
                    </x-responsive-nav-link>

                    <form method="POST" action="{{ route('logout') }}">
                        @csrf
                        <x-responsive-nav-link :href="route('logout')"
                                onclick="event.preventDefault(); this.closest('form').submit();"
                                class="text-gray-700 hover:bg-dustyLatte-400 dark:text-gray-300 dark:hover:bg-gray-800">
                            {{ __('Log Out') }}
                        </x-responsive-nav-link>
                    </form>
                </div>
            </div>
        @endauth
    </div>
</nav>

{{-- Pastikan script untuk dark mode toggle ada di app.blade.php atau guest.blade.php --}}
{{-- Atau tambahkan di bawah sini jika navbar ini berdiri sendiri tanpa layout parent --}}
<script>
    document.addEventListener('DOMContentLoaded', function () {
        const themeToggleBtn = document.getElementById('theme-toggle');
        if (themeToggleBtn) {
            const htmlElement = document.documentElement;

            // Set initial icon based on current theme
            if (htmlElement.classList.contains('dark')) {
                themeToggleBtn.innerHTML = `
                    <svg id="theme-toggle-light-icon" class="w-5 h-5" fill="currentColor" viewBox="0 0 20 20" xmlns="http://www.w3.org/2000/svg"><path d="M10 2a1 1 0 011 1v1a1 1 0 11-2 0V3a1 1 0 011-1zm4 11a1 1 0 01-.707 1.707l-1 1a1 1 0 01-1.414-1.414l1-1A1 1 0 0114 13zM4 13a1 1 0 011 1v1a1 1 0 01-2 0v-1a1 1 0 011-1zm3.293-1.707a1 1 0 011.414 0l1 1a1 1 0 01-1.414 1.414l-1-1a1 1 0 010-1.414zM10 16a1 1 0 011 1v1a1 1 0 11-2 0v-1a1 1 0 011-1zm-4-1a1 1 0 01-.707 1.707l-1 1a1 1 0 01-1.414-1.414l1-1A1 1 0 016 14zM16 10a1 1 0 011 1v1a1 1 0 11-2 0v-1a1 1 0 011-1zM3 10a1 1 0 011 1v1a1 1 0 11-2 0v-1a1 1 0 011-1zM13.293 4.293a1 1 0 011.414 0l1 1a1 1 0 01-1.414 1.414l-1-1a1 1 0 010-1.414zM7 4a1 1 0 011 1v1a1 1 0 11-2 0V5a1 1 0 011-1z"></path></svg>
                `; // Sun icon
            } else {
                themeToggleBtn.innerHTML = `
                    <svg id="theme-toggle-dark-icon" class="w-5 h-5" fill="currentColor" viewBox="0 0 20 20" xmlns="http://www.w3.org/2000/svg"><path d="M17.292 8.614a5.955 5.955 0 01-8.23-5.228 5.955 5.955 0 00-7.394 6.786 5.956 5.956 0 006.786 7.394 5.955 5.955 0 005.228-8.23z"></path></svg>
                `; // Moon icon
            }

            themeToggleBtn.addEventListener('click', () => {
                htmlElement.classList.toggle('dark');
                if (htmlElement.classList.contains('dark')) {
                    localStorage.setItem('theme', 'dark');
                    themeToggleBtn.innerHTML = `
                        <svg id="theme-toggle-light-icon" class="w-5 h-5" fill="currentColor" viewBox="0 0 20 20" xmlns="http://www.w3.org/2000/svg"><path d="M10 2a1 1 0 011 1v1a1 1 0 11-2 0V3a1 1 0 011-1zm4 11a1 1 0 01-.707 1.707l-1 1a1 1 0 01-1.414-1.414l1-1A1 1 0 0114 13zM4 13a1 1 0 011 1v1a1 1 0 01-2 0v-1a1 1 0 011-1zm3.293-1.707a1 1 0 011.414 0l1 1a1 1 0 01-1.414 1.414l-1-1a1 1 0 010-1.414zM10 16a1 1 0 011 1v1a1 1 0 11-2 0v-1a1 1 0 011-1zm-4-1a1 1 0 01-.707 1.707l-1 1a1 1 0 01-1.414-1.414l1-1A1 1 0 016 14zM16 10a1 1 0 011 1v1a1 1 0 11-2 0v-1a1 1 0 011-1zM3 10a1 1 0 011 1v1a1 1 0 11-2 0v-1a1 1 0 011-1zM13.293 4.293a1 1 0 011.414 0l1 1a1 1 0 01-1.414 1.414l-1-1a1 1 0 010-1.414zM7 4a1 1 0 011 1v1a1 1 0 11-2 0V5a1 1 0 011-1z"></path></svg>
                    `; // Sun icon
                } else {
                    localStorage.setItem('theme', 'light');
                    themeToggleBtn.innerHTML = `
                        <svg id="theme-toggle-dark-icon" class="w-5 h-5" fill="currentColor" viewBox="0 0 20 20" xmlns="http://www.w3.org/2000/svg"><path d="M17.292 8.614a5.955 5.955 0 01-8.23-5.228 5.955 5.955 0 00-7.394 6.786 5.956 5.956 0 006.786 7.394 5.955 5.955 0 005.228-8.23z"></path></svg>
                    `; // Moon icon
                }
            });
        }
    });
</script>
