<x-guest-layout>
    <section class="min-h-screen flex items-center justify-center px-6 py-20">
        <div class="max-w-7xl w-full grid md:grid-cols-2 items-center gap-12">

            <!-- Left Content -->
            <div data-aos="fade-right" data-aos-duration="1500" class="space-y-6">
                <div class="flex items-center gap-4">
                    <img src="{{ asset('images/logo.png') }}" alt="Logo PT" class="h-20 w-auto animate__animated animate__fadeInLeft">
                    <div>
                        <h1 class="text-xl font-semibold text-gray-700 dark:text-gray-200">PT. Ansel Muda Berkarya</h1>
                        <p class="text-sm text-gray-500 dark:text-gray-400 tracking-wide">Sistem Absensi Digital Terintegrasi</p>
                    </div>
                </div>

                <h2 class="text-4xl md:text-5xl font-bold leading-tight text-gray-900 dark:text-white animate__animated animate__fadeInDown">
                    Presensi Online <br>
                    <span class="bg-gradient-to-r from-indigo-600 to-purple-600 bg-clip-text text-transparent">Cepat & Efisien</span>
                </h2>

                <p class="text-lg text-gray-600 dark:text-gray-300 animate__animated animate__fadeIn animate__delay-1s">
                    Kelola kehadiran karyawan dengan akurat, berbasis foto & lokasi realtime. Solusi modern untuk perusahaan profesional.
                </p>

                <div class="flex flex-wrap gap-4">
                    <a href="{{ route('login') }}"
                       class="group px-8 py-3.5 bg-gradient-to-r from-indigo-600 to-purple-600 hover:from-indigo-700 hover:to-purple-700 text-white rounded-xl font-semibold shadow-lg hover:shadow-xl transition-all transform hover:scale-105 animate__animated animate__fadeInUp animate__delay-2s flex items-center gap-2">
                        <span>Masuk Sekarang</span>
                        <i class="fas fa-arrow-right group-hover:translate-x-1 transition-transform"></i>
                    </a>

                    <a href="{{ route('register') }}"
                       class="px-8 py-3.5 bg-white dark:bg-gray-800 border-2 border-indigo-600 dark:border-indigo-500 text-indigo-600 dark:text-indigo-400 hover:bg-indigo-50 dark:hover:bg-gray-700 rounded-xl font-semibold shadow-md hover:shadow-lg transition-all transform hover:scale-105 animate__animated animate__fadeInUp animate__delay-3s">
                        Daftar Akun
                    </a>
                </div>

                <!-- Features List -->
                <div class="pt-8 space-y-3 animate__animated animate__fadeIn animate__delay-4s">
                    <div class="flex items-center gap-3 text-gray-700 dark:text-gray-300">
                        <div class="w-8 h-8 rounded-full bg-green-100 dark:bg-green-900/30 flex items-center justify-center">
                            <i class="fas fa-check text-green-600 dark:text-green-400 text-sm"></i>
                        </div>
                        <span class="font-medium">Absensi berbasis GPS & Foto</span>
                    </div>
                    <div class="flex items-center gap-3 text-gray-700 dark:text-gray-300">
                        <div class="w-8 h-8 rounded-full bg-green-100 dark:bg-green-900/30 flex items-center justify-center">
                            <i class="fas fa-check text-green-600 dark:text-green-400 text-sm"></i>
                        </div>
                        <span class="font-medium">Laporan Real-time & Analitik</span>
                    </div>
                    <div class="flex items-center gap-3 text-gray-700 dark:text-gray-300">
                        <div class="w-8 h-8 rounded-full bg-green-100 dark:bg-green-900/30 flex items-center justify-center">
                            <i class="fas fa-check text-green-600 dark:text-green-400 text-sm"></i>
                        </div>
                        <span class="font-medium">Mudah digunakan & Terintegrasi</span>
                    </div>
                </div>
            </div>

            <!-- Right Illustration (Optional) -->
            <div data-aos="fade-left" data-aos-duration="1500" class="hidden md:flex justify-center">
                <div class="relative">
                    <!-- Decorative Elements -->
                    <div class="absolute -top-4 -left-4 w-72 h-72 bg-purple-300 dark:bg-purple-900 rounded-full mix-blend-multiply dark:mix-blend-soft-light filter blur-xl opacity-70 animate-blob"></div>
                    <div class="absolute -bottom-8 right-4 w-72 h-72 bg-indigo-300 dark:bg-indigo-900 rounded-full mix-blend-multiply dark:mix-blend-soft-light filter blur-xl opacity-70 animate-blob animation-delay-2000"></div>
                    <div class="absolute -bottom-4 left-20 w-72 h-72 bg-pink-300 dark:bg-pink-900 rounded-full mix-blend-multiply dark:mix-blend-soft-light filter blur-xl opacity-70 animate-blob animation-delay-4000"></div>

                    <!-- Icon Grid -->
                    <div class="relative grid grid-cols-2 gap-6 p-8">
                        <div class="bg-white dark:bg-gray-800 p-8 rounded-2xl shadow-xl transform hover:scale-110 transition-all">
                            <i class="fas fa-users text-5xl text-indigo-600 dark:text-indigo-400"></i>
                        </div>
                        <div class="bg-white dark:bg-gray-800 p-8 rounded-2xl shadow-xl transform hover:scale-110 transition-all">
                            <i class="fas fa-clock text-5xl text-purple-600 dark:text-purple-400"></i>
                        </div>
                        <div class="bg-white dark:bg-gray-800 p-8 rounded-2xl shadow-xl transform hover:scale-110 transition-all">
                            <i class="fas fa-chart-line text-5xl text-pink-600 dark:text-pink-400"></i>
                        </div>
                        <div class="bg-white dark:bg-gray-800 p-8 rounded-2xl shadow-xl transform hover:scale-110 transition-all">
                            <i class="fas fa-mobile-alt text-5xl text-blue-600 dark:text-blue-400"></i>
                        </div>
                    </div>
                </div>
            </div>

        </div>
    </section>

    <!-- Footer -->
    <footer class="text-center text-sm py-6 text-gray-600 dark:text-gray-400 bg-white/50 dark:bg-gray-900/50 backdrop-blur-sm border-t border-gray-200 dark:border-gray-800">
        &copy; {{ date('Y') }} PT. Ansel Muda Berkarya. All rights reserved.
    </footer>

    @push('styles')
        <link href="https://cdnjs.cloudflare.com/ajax/libs/aos/2.3.4/aos.css" rel="stylesheet">
        <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/animate.css/4.1.1/animate.min.css"/>
        <style>
            @keyframes blob {
                0%, 100% {
                    transform: translate(0, 0) scale(1);
                }
                33% {
                    transform: translate(30px, -50px) scale(1.1);
                }
                66% {
                    transform: translate(-20px, 20px) scale(0.9);
                }
            }
            .animate-blob {
                animation: blob 7s infinite;
            }
            .animation-delay-2000 {
                animation-delay: 2s;
            }
            .animation-delay-4000 {
                animation-delay: 4s;
            }
        </style>
    @endpush

    @push('scripts')
        <script src="https://cdnjs.cloudflare.com/ajax/libs/aos/2.3.4/aos.js"></script>
        <script>
            AOS.init({
                once: true,
                duration: 1000
            });
        </script>
    @endpush
</x-guest-layout>
