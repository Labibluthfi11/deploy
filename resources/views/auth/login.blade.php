<x-guest-layout>
    @push('styles')
    <style nonce="{{ config('app.csp_nonce') }}">
        @import url('https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap');

        body {
            margin: 0;
            padding: 0;
            min-height: 100vh;
            background-color: #f9fafb !important;
            font-family: 'Inter', -apple-system, BlinkMacSystemFont, sans-serif !important;
            color: #111827;
        }

        .login-wrapper {
            min-height: 100vh;
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            padding: 1.5rem;
            position: relative;
        }

        .login-card {
            width: 100%;
            max-width: 420px;
            background: #ffffff;
            border-radius: 1rem;
            box-shadow: 0 10px 25px -5px rgba(0, 0, 0, 0.05), 0 8px 10px -6px rgba(0, 0, 0, 0.01);
            padding: 2.5rem 2.5rem;
            position: relative;
            z-index: 10;
        }

        .header-section {
            text-align: center;
            margin-bottom: 2rem;
        }

        .logo {
            height: 48px;
            width: auto;
            margin: 0 auto 1.25rem auto;
            display: block;
        }

        .company-name {
            font-size: 1.125rem;
            font-weight: 700;
            color: #111827;
            margin: 0 0 0.25rem 0;
            line-height: 1.2;
        }

        .company-subheading {
            font-size: 0.875rem;
            color: #6b7280;
            margin: 0 0 1.5rem 0;
            font-weight: 400;
        }

        .login-title {
            font-size: 1.125rem;
            font-weight: 600;
            color: #374151;
            margin: 0;
        }

        .input-group {
            margin-bottom: 1.25rem;
            text-align: left;
        }

        .input-label {
            display: block;
            font-size: 0.875rem;
            font-weight: 500;
            color: #4b5563;
            margin-bottom: 0.5rem;
        }

        .input-field {
            width: 100%;
            padding: 0.75rem 1rem;
            border: 1px solid #d1d5db !important;
            border-radius: 0.5rem;
            font-size: 0.9375rem;
            color: #111827 !important;
            background-color: #ffffff !important;
            box-sizing: border-box;
            transition: all 0.2s ease;
            font-family: inherit;
        }

        .input-field:focus {
            outline: none;
            border-color: #7c3aed !important;
            box-shadow: 0 0 0 4px rgba(124, 58, 237, 0.15) !important;
        }

        .input-field::placeholder {
            color: #9ca3af;
        }

        .flex-between {
            display: flex;
            align-items: center;
            justify-content: space-between;
            margin-bottom: 1.5rem;
            margin-top: 0.5rem;
        }

        .checkbox-label {
            display: flex;
            align-items: center;
            font-size: 0.875rem;
            color: #4b5563;
            cursor: pointer;
            font-weight: 400;
        }

        .checkbox-input {
            appearance: none;
            width: 1rem;
            height: 1rem;
            border: 1px solid #d1d5db;
            border-radius: 0.25rem;
            margin-right: 0.5rem;
            cursor: pointer;
            position: relative;
            background-color: #fff;
            transition: all 0.2s;
        }

        .checkbox-input:checked {
            background-color: #7c3aed;
            border-color: #7c3aed;
        }

        .checkbox-input:checked::after {
            content: '';
            position: absolute;
            left: 4px;
            top: 1px;
            width: 4px;
            height: 8px;
            border: solid white;
            border-width: 0 2px 2px 0;
            transform: rotate(45deg);
        }

        .forgot-link {
            font-size: 0.875rem;
            color: #7c3aed;
            font-weight: 500;
            text-decoration: none;
            transition: color 0.2s;
        }

        .forgot-link:hover {
            color: #6d28d9;
            text-decoration: underline;
        }

        .submit-btn {
            width: 100%;
            padding: 0.75rem 1.5rem;
            background-color: #7c3aed;
            color: #ffffff;
            font-weight: 600;
            font-size: 1rem;
            border: none;
            border-radius: 0.5rem;
            cursor: pointer;
            box-shadow: 0 4px 14px 0 rgba(124, 58, 237, 0.39);
            transition: all 0.2s ease;
            font-family: inherit;
        }

        .submit-btn:hover {
            background-color: #6d28d9;
            box-shadow: 0 6px 20px 0 rgba(124, 58, 237, 0.39);
            transform: translateY(-1px);
        }

        .submit-btn:active {
            transform: translateY(1px);
            box-shadow: 0 2px 8px 0 rgba(124, 58, 237, 0.39);
        }

        .error-message {
            color: #ef4444;
            font-size: 0.75rem;
            margin-top: 0.375rem;
            display: block;
            text-align: left;
        }

        .status-message {
            background: #f0fdf4;
            color: #16a34a;
            padding: 0.75rem;
            border: 1px solid #bbf7d0;
            border-radius: 0.5rem;
            font-size: 0.875rem;
            margin-bottom: 1.5rem;
            text-align: center;
            font-weight: 500;
        }

        .footer-text {
            position: absolute;
            bottom: 2rem;
            left: 0;
            right: 0;
            text-align: center;
            font-size: 0.75rem;
            color: #9ca3af;
        }
    </style>
    @endpush

    <div class="login-wrapper">
        <div class="login-card">
            
            <div class="header-section">
                <a href="/">
                    <img src="{{ asset('images/logo.png') }}" alt="AMB Logo" class="logo">
                </a>
                <h1 class="company-name">PT. Ansel Muda Berkarya</h1>
                <p class="company-subheading">Sistem Absensi Digital Terintegrasi</p>
                <h2 class="login-title">Silakan masuk ke akun Anda</h2>
            </div>

            @if(session('status'))
                <div class="status-message">
                    {{ session('status') }}
                </div>
            @endif

            <form method="POST" action="{{ route('login') }}">
                @csrf

                <div class="input-group">
                    <label for="email" class="input-label">Alamat Email</label>
                    <input 
                        id="email" 
                        type="email" 
                        name="email" 
                        class="input-field" 
                        value="{{ old('email') }}" 
                        required 
                        autofocus 
                        autocomplete="username" 
                        placeholder="nama@perusahaan.com"
                    >
                    @error('email')
                        <span class="error-message">{{ $message }}</span>
                    @enderror
                </div>

                <div class="input-group">
                    <label for="password" class="input-label">Kata Sandi</label>
                    <input 
                        id="password" 
                        type="password" 
                        name="password" 
                        class="input-field" 
                        required 
                        autocomplete="current-password" 
                        placeholder="••••••••"
                    >
                    @error('password')
                        <span class="error-message">{{ $message }}</span>
                    @enderror
                </div>

                <div class="flex-between">
                    <label class="checkbox-label" for="remember_me">
                        <input id="remember_me" type="checkbox" class="checkbox-input" name="remember">
                        Ingat saya
                    </label>

                    @if (Route::has('password.request'))
                        <a class="forgot-link" href="{{ route('password.request') }}">
                            Lupa Kata Sandi?
                        </a>
                    @endif
                </div>

                <button type="submit" class="submit-btn">
                    Masuk
                </button>
            </form>
        </div>

        <div class="footer-text">
            © 2026 PT. Ansel Muda Berkarya. All rights reserved.
        </div>
    </div>
</x-guest-layout>