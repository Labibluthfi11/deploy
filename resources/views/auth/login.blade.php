<x-guest-layout>
    <style>
        body {
            margin: 0;
            padding: 0;
            min-height: 100vh;
            overflow-x: hidden;
            background: linear-gradient(135deg, #fafbfc 0%, #f0f2f5 100%);
            position: relative;
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, 'Helvetica Neue', Arial, sans-serif;
        }

        .dark body {
            background: linear-gradient(135deg, #0f172a 0%, #1e293b 100%);
        }

        /* Elegant mesh gradient background */
        .mesh-bg {
            position: absolute;
            width: 100%;
            height: 100%;
            background:
                radial-gradient(at 20% 30%, rgba(156, 163, 175, 0.08) 0px, transparent 50%),
                radial-gradient(at 80% 70%, rgba(107, 114, 128, 0.06) 0px, transparent 50%),
                radial-gradient(at 50% 50%, rgba(148, 163, 184, 0.05) 0px, transparent 50%);
            animation: mesh-move 15s ease-in-out infinite;
        }

        .dark .mesh-bg {
            background:
                radial-gradient(at 20% 30%, rgba(71, 85, 105, 0.15) 0px, transparent 50%),
                radial-gradient(at 80% 70%, rgba(51, 65, 85, 0.12) 0px, transparent 50%),
                radial-gradient(at 50% 50%, rgba(100, 116, 139, 0.1) 0px, transparent 50%);
        }

        @keyframes mesh-move {
            0%, 100% { transform: scale(1) rotate(0deg); }
            50% { transform: scale(1.05) rotate(2deg); }
        }

        /* Premium fade in */
        @keyframes fadeInUp {
            from {
                opacity: 0;
                transform: translateY(30px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        .fade-in {
            animation: fadeInUp 0.8s cubic-bezier(0.16, 1, 0.3, 1) forwards;
        }

        /* Luxury card with glassmorphism */
        .luxury-card {
            background: rgba(255, 255, 255, 0.98);
            backdrop-filter: blur(20px);
            -webkit-backdrop-filter: blur(20px);
            box-shadow:
                0 0 0 1px rgba(0, 0, 0, 0.03),
                0 8px 32px rgba(0, 0, 0, 0.06),
                0 24px 64px rgba(0, 0, 0, 0.04);
            border: 1px solid rgba(255, 255, 255, 0.8);
            transition: all 0.4s cubic-bezier(0.16, 1, 0.3, 1);
        }

        .luxury-card:hover {
            box-shadow:
                0 0 0 1px rgba(0, 0, 0, 0.04),
                0 12px 48px rgba(0, 0, 0, 0.08),
                0 32px 80px rgba(0, 0, 0, 0.06);
            transform: translateY(-2px);
        }

        .dark .luxury-card {
            background: rgba(30, 41, 59, 0.98);
            border: 1px solid rgba(71, 85, 105, 0.3);
            box-shadow:
                0 0 0 1px rgba(71, 85, 105, 0.2),
                0 8px 32px rgba(0, 0, 0, 0.4),
                0 24px 64px rgba(0, 0, 0, 0.3);
        }

        .dark .luxury-card:hover {
            box-shadow:
                0 0 0 1px rgba(71, 85, 105, 0.3),
                0 12px 48px rgba(0, 0, 0, 0.5),
                0 32px 80px rgba(0, 0, 0, 0.4);
        }

        /* Premium input design */
        .premium-input {
            background: rgba(249, 250, 251, 0.8) !important;
            border: 1.5px solid rgba(229, 231, 235, 0.8) !important;
            color: #111827 !important;
            transition: all 0.3s cubic-bezier(0.16, 1, 0.3, 1);
            font-size: 0.9375rem;
            letter-spacing: -0.01em;
        }

        .premium-input:hover {
            border-color: rgba(156, 163, 175, 0.5) !important;
            background: rgba(255, 255, 255, 0.95) !important;
        }

        .premium-input:focus {
            background: #ffffff !important;
            border-color: #6b7280 !important;
            box-shadow: 0 0 0 4px rgba(107, 114, 128, 0.08), 0 1px 2px rgba(0, 0, 0, 0.05);
            transform: translateY(-1px);
        }

        .premium-input::placeholder {
            color: #9ca3af;
            font-weight: 400;
        }

        .dark .premium-input {
            background: rgba(15, 23, 42, 0.6) !important;
            border-color: rgba(71, 85, 105, 0.5) !important;
            color: #f1f5f9 !important;
        }

        .dark .premium-input:hover {
            border-color: rgba(100, 116, 139, 0.6) !important;
            background: rgba(15, 23, 42, 0.8) !important;
        }

        .dark .premium-input:focus {
            background: rgba(15, 23, 42, 0.9) !important;
            border-color: #64748b !important;
            box-shadow: 0 0 0 4px rgba(100, 116, 139, 0.15), 0 1px 2px rgba(0, 0, 0, 0.5);
        }

        /* Sophisticated button */
        .btn-sophisticated {
            background: linear-gradient(135deg, #1f2937 0%, #111827 100%);
            box-shadow:
                0 2px 8px rgba(0, 0, 0, 0.15),
                0 4px 16px rgba(0, 0, 0, 0.1),
                inset 0 1px 0 rgba(255, 255, 255, 0.1);
            transition: all 0.3s cubic-bezier(0.16, 1, 0.3, 1);
            position: relative;
            overflow: hidden;
        }

        .btn-sophisticated::before {
            content: '';
            position: absolute;
            top: 0;
            left: -100%;
            width: 100%;
            height: 100%;
            background: linear-gradient(90deg, transparent, rgba(255, 255, 255, 0.1), transparent);
            transition: left 0.5s;
        }

        .btn-sophisticated:hover {
            transform: translateY(-2px);
            box-shadow:
                0 4px 16px rgba(0, 0, 0, 0.2),
                0 8px 32px rgba(0, 0, 0, 0.15),
                inset 0 1px 0 rgba(255, 255, 255, 0.15);
            background: linear-gradient(135deg, #111827 0%, #000000 100%);
        }

        .btn-sophisticated:hover::before {
            left: 100%;
        }

        .btn-sophisticated:active {
            transform: translateY(0);
        }

        .dark .btn-sophisticated {
            background: linear-gradient(135deg, #475569 0%, #334155 100%);
        }

        .dark .btn-sophisticated:hover {
            background: linear-gradient(135deg, #334155 0%, #1e293b 100%);
        }

        /* Container */
        .login-container {
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 3rem 1rem;
            position: relative;
            z-index: 10;
        }

        /* Typography */
        .elegant-label {
            color: #1f2937 !important;
            font-weight: 600;
            font-size: 0.8125rem;
            letter-spacing: 0.02em;
            text-transform: uppercase;
        }

        .dark .elegant-label {
            color: #cbd5e1 !important;
        }

        /* Links */
        .elegant-link {
            color: #374151;
            font-weight: 600;
            transition: all 0.2s ease;
            text-decoration: none;
            position: relative;
        }

        .elegant-link::after {
            content: '';
            position: absolute;
            bottom: -2px;
            left: 0;
            width: 0;
            height: 1.5px;
            background: currentColor;
            transition: width 0.3s ease;
        }

        .elegant-link:hover {
            color: #111827;
        }

        .elegant-link:hover::after {
            width: 100%;
        }

        .dark .elegant-link {
            color: #94a3b8;
        }

        .dark .elegant-link:hover {
            color: #cbd5e1;
        }

        /* Checkbox */
        .elegant-checkbox {
            width: 1.125rem;
            height: 1.125rem;
            border: 2px solid #d1d5db;
            background: #f9fafb;
            transition: all 0.2s ease;
            cursor: pointer;
        }

        .elegant-checkbox:checked {
            background: linear-gradient(135deg, #1f2937, #111827);
            border-color: #1f2937;
        }

        .elegant-checkbox:hover {
            border-color: #9ca3af;
        }

        .dark .elegant-checkbox {
            background: rgba(15, 23, 42, 0.8);
            border-color: #475569;
        }

        .dark .elegant-checkbox:checked {
            background: linear-gradient(135deg, #475569, #334155);
            border-color: #475569;
        }

        /* Logo */
        .logo-wrapper {
            transition: all 0.3s cubic-bezier(0.16, 1, 0.3, 1);
            filter: drop-shadow(0 2px 8px rgba(0, 0, 0, 0.08));
        }

        .logo-wrapper:hover {
            transform: scale(1.03);
            filter: drop-shadow(0 4px 12px rgba(0, 0, 0, 0.12));
        }

        /* Divider */
        .elegant-divider {
            height: 1px;
            background: linear-gradient(90deg, transparent, rgba(229, 231, 235, 0.8), transparent);
        }

        .dark .elegant-divider {
            background: linear-gradient(90deg, transparent, rgba(71, 85, 105, 0.5), transparent);
        }

        /* Focus ring */
        .focus-ring:focus {
            outline: none;
            box-shadow: 0 0 0 3px rgba(107, 114, 128, 0.15);
        }

        /* Header text glow effect */
        .header-glow {
            text-shadow: 0 2px 20px rgba(0, 0, 0, 0.05);
        }

        .dark .header-glow {
            text-shadow: 0 2px 20px rgba(255, 255, 255, 0.05);
        }
    </style>

    {{-- Background --}}
    <div class="mesh-bg"></div>

    {{-- Main container --}}
    <div class="login-container">
        <div class="w-full sm:max-w-md fade-in">
            {{-- Header --}}
            <div class="text-center mb-10">
                <h1 class="text-4xl md:text-5xl font-bold text-gray-900 dark:text-gray-50 mb-3 tracking-tight header-glow">
                    Selamat Datang Kembali
                </h1>
                <p class="text-gray-600 dark:text-gray-400 text-base font-medium">
                    Silakan masuk ke akun Anda
                </p>
            </div>

            {{-- Login card --}}
            <div class="luxury-card px-10 py-12 rounded-3xl">
                {{-- Logo --}}
                <div class="flex justify-center mb-10">
                    <a href="/" class="logo-wrapper inline-block">
                        <img src="{{ asset('images/logo.png') }}" alt="Logo" class="h-20 w-auto">
                    </a>
                </div>

                {{-- Session status --}}
                <x-auth-session-status class="mb-6 text-green-600 dark:text-green-400 text-center font-semibold text-sm" :status="session('status')" />

                {{-- Form --}}
                <form method="POST" action="{{ route('login') }}" class="space-y-6">
                    @csrf

                    {{-- Email --}}
                    <div>
                        <x-input-label for="email" value="{{ __('Alamat Email') }}" class="elegant-label mb-3 block" />
                        <x-text-input
                            id="email"
                            class="premium-input block w-full rounded-xl px-4 py-3.5 focus-ring"
                            type="email"
                            name="email"
                            :value="old('email')"
                            required
                            autofocus
                            autocomplete="username"
                            placeholder="nama@perusahaan.com"
                        />
                        <x-input-error :messages="$errors->get('email')" class="mt-2.5 text-red-600 dark:text-red-400 text-sm font-medium" />
                    </div>

                    {{-- Password --}}
                    <div>
                        <x-input-label for="password" value="{{ __('Kata Sandi') }}" class="elegant-label mb-3 block" />
                        <x-text-input
                            id="password"
                            class="premium-input block w-full rounded-xl px-4 py-3.5 focus-ring"
                            type="password"
                            name="password"
                            required
                            autocomplete="current-password"
                            placeholder="Masukkan kata sandi Anda"
                        />
                        <x-input-error :messages="$errors->get('password')" class="mt-2.5 text-red-600 dark:text-red-400 text-sm font-medium" />
                    </div>

                    {{-- Remember & Forgot --}}
                    <div class="flex items-center justify-between pt-2">
                        <label for="remember_me" class="inline-flex items-center cursor-pointer group">
                            <input
                                id="remember_me"
                                type="checkbox"
                                class="elegant-checkbox rounded"
                                name="remember"
                            >
                            <span class="ms-3 text-sm text-gray-700 dark:text-gray-300 font-medium group-hover:text-gray-900 dark:group-hover:text-gray-100 transition-colors">
                                {{ __('Ingat saya') }}
                            </span>
                        </label>

                        @if (Route::has('password.request'))
                            <a class="elegant-link text-sm" href="{{ route('password.request') }}">
                                {{ __('Lupa kata sandi?') }}
                            </a>
                        @endif
                    </div>

                    {{-- Submit button --}}
                    <button type="submit" class="btn-sophisticated w-full text-white font-semibold py-3.5 rounded-xl focus:outline-none focus:ring-4 focus:ring-gray-400/20 mt-8 text-base">
                        {{ __('Masuk ke Dashboard') }}
                    </button>

                    {{-- Divider --}}
                    <div class="elegant-divider my-8"></div>

                    {{-- Register link --}}
                    <div class="text-center">
                        <p class="text-sm text-gray-600 dark:text-gray-400 font-medium">
                            Belum memiliki akun?
                            <a href="{{ route('register') }}" class="elegant-link ml-1.5 text-gray-900 dark:text-gray-200">
                                Daftar sekarang
                            </a>
                        </p>
                    </div>
                </form>
            </div>

            {{-- Footer --}}
            <p class="text-center text-gray-500 dark:text-gray-500 text-xs mt-8 font-medium">
                © {{ date('Y') }} Ansel. All rights reserved.
            </p>
        </div>
    </div>

    {{-- Dark mode toggle script --}}
    <script>
        document.addEventListener('DOMContentLoaded', function () {
            const themeToggleBtn = document.getElementById('theme-toggle');
            const htmlElement = document.documentElement;

            function setThemeToggleIcon() {
                if (!themeToggleBtn) return;

                if (htmlElement.classList.contains('dark')) {
                    themeToggleBtn.innerHTML = `
                        <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 20 20">
                            <path d="M10 2a1 1 0 011 1v1a1 1 0 11-2 0V3a1 1 0 011-1zm4 8a4 4 0 11-8 0 4 4 0 018 0zm-.464 4.95l.707.707a1 1 0 001.414-1.414l-.707-.707a1 1 0 00-1.414 1.414zm2.12-10.607a1 1 0 010 1.414l-.706.707a1 1 0 11-1.414-1.414l.707-.707a1 1 0 011.414 0zM17 11a1 1 0 100-2h-1a1 1 0 100 2h1zm-7 4a1 1 0 011 1v1a1 1 0 11-2 0v-1a1 1 0 011-1zM5.05 6.464A1 1 0 106.465 5.05l-.708-.707a1 1 0 00-1.414 1.414l.707.707zm1.414 8.486l-.707.707a1 1 0 01-1.414-1.414l.707-.707a1 1 0 011.414 1.414zM4 11a1 1 0 100-2H3a1 1 0 000 2h1z"></path>
                        </svg>
                    `;
                } else {
                    themeToggleBtn.innerHTML = `
                        <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 20 20">
                            <path d="M17.293 13.293A8 8 0 016.707 2.707a8.001 8.001 0 1010.586 10.586z"></path>
                        </svg>
                    `;
                }
            }

            if (themeToggleBtn) {
                setThemeToggleIcon();
                themeToggleBtn.addEventListener('click', () => {
                    htmlElement.classList.toggle('dark');
                    localStorage.setItem('theme', htmlElement.classList.contains('dark') ? 'dark' : 'light');
                    setThemeToggleIcon();
                });
            }
        });
    </script>
</x-guest-layout>
