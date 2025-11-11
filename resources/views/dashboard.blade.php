<x-app-layout>
    <x-slot name="header">
        <h2 class="text-2xl font-bold text-gray-800 dark:text-white leading-tight" data-aos="fade-right">
            Selamat Datang di Dashboard
        </h2>
    </x-slot>

    <div class="py-12 bg-gray-50 dark:bg-gray-900 min-h-screen">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="bg-white dark:bg-gray-800 overflow-hidden shadow-xl sm:rounded-lg" data-aos="fade-up" data-aos-delay="150">
                <div class="p-8 text-gray-700 dark:text-gray-100 text-center space-y-4">
                    <div class="text-5xl">👋</div>
                    <h3 class="text-xl font-semibold">
                        Hai {{ Auth::user()->name }}! Kamu sudah berhasil login ke sistem 🎉
                    </h3>
                    <p class="text-gray-500 dark:text-gray-300">
                        Silakan pilih menu di sidebar untuk mulai menggunakan aplikasi.
                    </p>
                </div>
            </div>
        </div>
    </div>

    @push('styles')
        <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/aos/2.3.4/aos.css" />
    @endpush

    @push('scripts')
        <script src="https://cdnjs.cloudflare.com/ajax/libs/aos/2.3.4/aos.js"></script>
        <script>
            AOS.init();
        </script>
    @endpush
</x-app-layout>
