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

        body {
            background-color: #edf4ff !important;
            color: #183153;
            font-family: 'Plus Jakarta Sans', -apple-system, BlinkMacSystemFont, sans-serif !important;
        }

        .reference-login-page {
            isolation: isolate;
            overflow: hidden;
            padding: clamp(1rem, 3vw, 2.75rem);
            background:
                radial-gradient(ellipse at 100% 0%, rgba(116, 164, 244, 0.27) 0 18%, transparent 18.2%),
                radial-gradient(ellipse at 0% 100%, rgba(83, 139, 227, 0.32) 0 21%, transparent 21.2%),
                linear-gradient(135deg, #f8fbff 0%, #edf4ff 100%);
        }

        .reference-login-frame {
            width: min(100%, 1440px);
            max-width: none;
            min-height: clamp(560px, 76vh, 760px);
            padding: 2rem;
            overflow: hidden;
            border: 5px solid #075da7;
            border-radius: 2.1rem;
            background: rgba(255, 255, 255, 0.94);
            box-shadow: 0 24px 70px rgba(34, 86, 150, 0.12);
        }

        .reference-brand {
            position: absolute;
            top: 1.65rem;
            left: 1.9rem;
            z-index: 2;
            display: flex;
            align-items: center;
            gap: 0.55rem;
            color: #183153;
            font-size: 0.7rem;
            font-weight: 800;
            letter-spacing: 0.08em;
        }

        .reference-brand-mark {
            display: grid;
            width: 1.75rem;
            height: 1.75rem;
            place-items: center;
            border-radius: 0.55rem;
            background: #0b5fa8;
            color: #fff;
            font-size: 0.65rem;
            letter-spacing: 0;
        }

        .reference-login-grid {
            display: grid;
            min-height: calc(clamp(560px, 76vh, 760px) - 4rem);
            grid-template-columns: minmax(270px, 0.78fr) minmax(0, 1.5fr);
            align-items: center;
            gap: clamp(1.5rem, 4vw, 4rem);
            padding-top: 1.25rem;
        }

        .reference-login-form {
            width: 100%;
            max-width: 340px;
            margin: 2rem auto 0;
            text-align: left;
        }

        .reference-login-title {
            margin: 0;
            color: #183153;
            font-size: clamp(2rem, 3vw, 2.6rem);
            font-weight: 800;
            letter-spacing: -0.045em;
            line-height: 1.1;
        }

        .reference-login-description {
            max-width: 18rem;
            margin: 0.65rem 0 1.8rem;
            color: #8493a8;
            font-size: 0.78rem;
            line-height: 1.55;
        }

        .reference-login-form .input-group {
            margin-bottom: 1.1rem;
        }

        .reference-login-form .input-label {
            margin-bottom: 0.2rem;
            color: #49617e;
            font-size: 0.72rem;
            font-weight: 700;
        }

        .reference-login-form .input-field {
            padding: 0.62rem 0;
            border: 0 !important;
            border-bottom: 1px solid #d9e3f0 !important;
            border-radius: 0;
            background: transparent !important;
            color: #193757 !important;
            font-size: 0.78rem;
            box-shadow: none !important;
        }

        .reference-login-form .input-field:focus {
            border-bottom-color: #3489e8 !important;
            box-shadow: 0 1px 0 #3489e8 !important;
        }

        .reference-login-form .input-field::placeholder {
            color: #a4afbf;
        }

        .reference-login-form .flex-between {
            margin-top: 0.3rem;
            margin-bottom: 1.35rem;
        }

        .reference-login-form .checkbox-label {
            color: #63758d;
            font-size: 0.72rem;
        }

        .reference-login-form .checkbox-input {
            width: 0.9rem;
            height: 0.9rem;
            border-color: #b7c8dc;
        }

        .reference-login-form .checkbox-input:checked {
            border-color: #3489e8;
            background-color: #3489e8;
        }

        .reference-login-form .forgot-link {
            color: #287bd3;
            font-size: 0.72rem;
            font-weight: 700;
        }

        .reference-login-form .submit-btn {
            width: auto;
            min-width: 9.5rem;
            padding: 0.72rem 1.5rem;
            border-radius: 0.35rem;
            background: #3489e8;
            box-shadow: 0 8px 18px rgba(52, 137, 232, 0.22);
            font-size: 0.75rem;
            letter-spacing: 0.04em;
        }

        .reference-login-form .submit-btn:hover {
            background: #2479d8;
            box-shadow: 0 10px 22px rgba(52, 137, 232, 0.3);
        }

        .reference-login-form .status-message {
            padding: 0.65rem 0.75rem;
            border-radius: 0.55rem;
            font-size: 0.75rem;
        }

        .reference-login-art {
            display: flex;
            min-width: 0;
            align-items: center;
            justify-content: center;
        }

        .reference-login-art svg {
            display: block;
            width: 100%;
            max-height: 520px;
        }

        .reference-footer {
            position: relative;
            bottom: auto;
            margin-top: 0.9rem;
            color: #8293aa;
        }

        .reference-art-float {
            animation: reference-float 5s ease-in-out infinite;
        }

        .reference-art-float-delay {
            animation: reference-float 6s ease-in-out 0.8s infinite;
        }

        @keyframes reference-float {
            0%, 100% { transform: translateY(0); }
            50% { transform: translateY(-8px); }
        }

        .dark .reference-login-page {
            background:
                radial-gradient(ellipse at 100% 0%, rgba(37, 99, 185, 0.24) 0 18%, transparent 18.2%),
                radial-gradient(ellipse at 0% 100%, rgba(37, 99, 185, 0.3) 0 21%, transparent 21.2%),
                linear-gradient(135deg, #0d1728 0%, #111f35 100%);
        }

        .dark .reference-login-frame {
            border-color: #2875b7;
            background: rgba(15, 29, 49, 0.97);
        }

        .dark .reference-brand,
        .dark .reference-login-title {
            color: #edf5ff;
        }

        .dark .reference-login-form .input-label,
        .dark .reference-login-form .checkbox-label {
            color: #bac9dc;
        }

        .dark .reference-login-form .input-field {
            border-bottom-color: #3a506d !important;
            color: #edf5ff !important;
        }

        .dark .reference-login-form .input-field::placeholder {
            color: #8293aa;
        }

        .dark .reference-footer {
            color: #8094af;
        }

        @media (max-width: 900px) {
            .reference-login-frame {
                min-height: auto;
                padding: 2rem 1.75rem;
            }

            .reference-login-grid {
                min-height: auto;
                grid-template-columns: minmax(240px, 0.9fr) minmax(0, 1.1fr);
                gap: 1.25rem;
                padding-top: 2.5rem;
            }

            .reference-login-form {
                margin-top: 1.5rem;
            }
        }

        @media (max-width: 680px) {
            .reference-login-page {
                justify-content: flex-start;
                padding: 1rem;
            }

            .reference-login-frame {
                min-height: auto;
                padding: 1.5rem 1.25rem;
                border-width: 3px;
                border-radius: 1.5rem;
            }

            .reference-brand {
                top: 1rem;
                left: 1.25rem;
            }

            .reference-login-grid {
                display: block;
                padding-top: 2.8rem;
            }

            .reference-login-form {
                max-width: 420px;
                margin: 1rem auto 0;
            }

            .reference-login-art {
                display: none;
            }

            .reference-login-description {
                margin-bottom: 1.4rem;
            }

            .reference-footer {
                margin-top: 0.7rem;
            }
        }
    </style>
    @endpush

    <div class="login-wrapper reference-login-page">
        <main class="login-card reference-login-frame">
            <div class="reference-brand">
                <span class="reference-brand-mark">AMB</span>
                <span>PT. ANSEL MUDA BERKARYA</span>
            </div>

            <div class="reference-login-grid">
                <section class="reference-login-form" aria-labelledby="login-title">
                    <h1 id="login-title" class="reference-login-title">Login</h1>
                    <p class="reference-login-description">Welcome to your integrated attendance management system.</p>

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

                        <button type="submit" class="submit-btn">MASUK</button>
                    </form>
                </section>

                <div class="reference-login-art" aria-hidden="true">
                    <svg viewBox="0 0 760 560" role="presentation" xmlns="http://www.w3.org/2000/svg">
                        <defs>
                            <linearGradient id="sceneWash" x1="0" y1="0" x2="1" y2="1">
                                <stop offset="0" stop-color="#dceaff"/>
                                <stop offset="1" stop-color="#8eb5f4"/>
                            </linearGradient>
                            <linearGradient id="screenWash" x1="0" y1="0" x2="0" y2="1">
                                <stop offset="0" stop-color="#ffffff"/>
                                <stop offset="1" stop-color="#edf5ff"/>
                            </linearGradient>
                            <linearGradient id="blueBar" x1="0" y1="0" x2="1" y2="1">
                                <stop offset="0" stop-color="#8db7f7"/>
                                <stop offset="1" stop-color="#397bd5"/>
                            </linearGradient>
                            <filter id="screenShadow" x="-20%" y="-20%" width="140%" height="150%">
                                <feDropShadow dx="0" dy="14" stdDeviation="13" flood-color="#547eb8" flood-opacity=".18"/>
                            </filter>
                        </defs>

                        <path d="M90 190C104 106 174 85 247 105c52 14 67-35 132-28 60 7 82 48 137 29 62-22 118 12 128 79 9 57 60 77 44 153-15 69-85 98-157 75-58-18-101 34-164 18-61-16-93-46-151-21-66 28-139-3-145-68-5-51-53-83-41-152Z" fill="url(#sceneWash)" opacity=".72"/>
                        <path d="M108 432H681" stroke="#92acd0" stroke-width="2" opacity=".8"/>
                        <ellipse cx="437" cy="447" rx="211" ry="20" fill="#c6d9f4" opacity=".45"/>

                        <g class="reference-art-float" opacity=".75">
                            <ellipse cx="116" cy="96" rx="34" ry="17" transform="rotate(42 116 96)" fill="#b9d1f6"/>
                            <path d="M93 73c18 4 41 22 48 39l-34-9-14-30Z" fill="#6593d9"/>
                            <circle cx="664" cy="100" r="13" fill="#8db4ef"/>
                            <circle cx="641" cy="80" r="4" fill="#dbe9ff"/>
                        </g>

                        <g filter="url(#screenShadow)">
                            <rect x="176" y="146" width="424" height="255" rx="19" fill="url(#screenWash)" stroke="#5279b1" stroke-width="2"/>
                            <rect x="194" y="163" width="27" height="220" rx="13" fill="#d9e8fc"/>
                            <circle cx="207.5" cy="184" r="8" fill="#8fb1e4"/>
                            <circle cx="207.5" cy="214" r="8" fill="#8fb1e4"/>
                            <circle cx="207.5" cy="244" r="8" fill="#8fb1e4"/>
                            <circle cx="207.5" cy="274" r="8" fill="#8fb1e4"/>
                            <circle cx="207.5" cy="304" r="8" fill="#8fb1e4"/>
                            <circle cx="207.5" cy="334" r="8" fill="#8fb1e4"/>
                            <rect x="241" y="173" width="113" height="9" rx="4.5" fill="#80a8e7"/>
                            <rect x="241" y="191" width="83" height="7" rx="3.5" fill="#b7cef3"/>
                            <rect x="241" y="207" width="96" height="7" rx="3.5" fill="#b7cef3"/>
                            <rect x="241" y="237" width="15" height="13" rx="4" fill="#8bb1ed"/>
                            <rect x="263" y="237" width="15" height="13" rx="4" fill="#adc8f1"/>
                            <rect x="285" y="237" width="15" height="13" rx="4" fill="#adc8f1"/>
                            <rect x="241" y="265" width="111" height="15" rx="5" fill="#d5e4fa"/>
                            <rect x="241" y="288" width="76" height="14" rx="5" fill="#6d9de4"/>
                            <rect x="241" y="311" width="103" height="14" rx="5" fill="#c1d6f5"/>
                            <rect x="241" y="334" width="87" height="14" rx="5" fill="#d5e4fa"/>

                            <circle cx="437" cy="273" r="57" fill="#dce9fc"/>
                            <path d="M437 273V216a57 57 0 0 1 54 39l-54 18Z" fill="#91b5ed"/>
                            <path d="M437 273l54-18a57 57 0 0 1-57 75v-57Z" fill="#548bdc"/>
                            <path d="M437 273v57a57 57 0 0 1-57-57h57Z" fill="#b5cdf2"/>
                            <path d="M437 273l-57 0a57 57 0 0 1 57-57v57Z" fill="#d1e0f8"/>

                            <rect x="516" y="231" width="13" height="13" rx="4" fill="#729de0"/>
                            <rect x="536" y="231" width="39" height="8" rx="4" fill="#8eafe8"/>
                            <rect x="516" y="250" width="13" height="13" rx="4" fill="#9db9ea"/>
                            <rect x="536" y="250" width="33" height="8" rx="4" fill="#bfd2f1"/>
                            <rect x="516" y="269" width="13" height="13" rx="4" fill="#b8cdf0"/>
                            <rect x="536" y="269" width="37" height="8" rx="4" fill="#bfd2f1"/>
                            <rect x="506" y="315" width="70" height="39" rx="7" fill="#e1ecfb"/>
                            <rect x="516" y="325" width="49" height="5" rx="2.5" fill="#90b0e6"/>
                            <rect x="516" y="336" width="38" height="5" rx="2.5" fill="#b3c9ed"/>

                            <rect x="354" y="365" width="187" height="23" rx="7" fill="#f3f7fd"/>
                            <circle cx="370" cy="376" r="7" fill="#a8c3ee"/>
                            <rect x="383" y="372" width="51" height="5" rx="2.5" fill="#aec7ed"/>
                            <rect x="383" y="381" width="38" height="4" rx="2" fill="#d0def3"/>
                            <circle cx="451" cy="376" r="7" fill="#8aaee7"/>
                            <rect x="464" y="372" width="51" height="5" rx="2.5" fill="#aec7ed"/>
                            <rect x="464" y="381" width="39" height="4" rx="2" fill="#d0def3"/>
                        </g>

                        <path d="M357 401v33m64-33v33m-81 0h98" stroke="#6688bb" stroke-width="8" stroke-linecap="round"/>
                        <path d="M358 435h101" stroke="#6688bb" stroke-width="7" stroke-linecap="round"/>
                        <rect x="405" y="405" width="17" height="42" rx="4" fill="#3c74c1"/>
                        <rect x="427" y="390" width="18" height="57" rx="4" fill="#6395dc"/>
                        <rect x="449" y="416" width="18" height="31" rx="4" fill="#8ab0e8"/>

                        <g class="reference-art-float-delay">
                            <rect x="96" y="356" width="104" height="78" rx="8" fill="#f8fbff" stroke="#688bbd" stroke-width="2"/>
                            <rect x="108" y="398" width="11" height="25" rx="4" fill="#9ebdf0"/>
                            <rect x="125" y="386" width="11" height="37" rx="4" fill="#5c8fd8"/>
                            <rect x="142" y="391" width="11" height="32" rx="4" fill="#82a8e5"/>
                            <rect x="159" y="377" width="11" height="46" rx="4" fill="#aec8f1"/>
                            <rect x="176" y="386" width="11" height="37" rx="4" fill="#6e9ce1"/>
                        </g>

                        <g>
                            <circle cx="170" cy="278" r="17" fill="#f4c5a8"/>
                            <path d="M153 278c-3-20 12-29 28-22 7 3 10 10 8 20-5-4-10-8-16-9-5 7-11 10-20 11Z" fill="#263d5a"/>
                            <path d="M158 298h26l14 72h-55l15-72Z" fill="#4a80ce"/>
                            <path d="M156 305l-19 39 7 4 25-32m19-8 18 34-7 4-24-25" fill="none" stroke="#f0b999" stroke-width="8" stroke-linecap="round" stroke-linejoin="round"/>
                            <path d="M151 366h24l-5 54h-18l-1-54Zm25 0h22l14 51h-18l-18-51Z" fill="#355f9c"/>
                            <path d="m151 419-2 9h20l3-9m20-1 4 10h19l-4-10" fill="#273f62"/>
                        </g>

                        <g>
                            <circle cx="618" cy="276" r="17" fill="#f1c2a2"/>
                            <path d="M600 276c-3-20 11-29 28-22 7 3 10 10 8 20-5-4-11-8-17-9-5 7-11 10-19 11Z" fill="#263d5a"/>
                            <path d="M606 297h25l12 71h-53l16-71Z" fill="#376fb9"/>
                            <path d="m610 306-22 34 6 5 27-27m11-9 24 31-6 6-29-24" fill="none" stroke="#edb797" stroke-width="8" stroke-linecap="round" stroke-linejoin="round"/>
                            <path d="M597 365h23l-2 55h-18l-3-55Zm23 0h21l10 53h-18l-13-53Z" fill="#345b92"/>
                            <path d="m598 418-3 10h20l4-10m23 0 2 10h18l-2-10" fill="#273f62"/>
                        </g>
                    </svg>
                </div>
            </div>
        </main>

        <div class="footer-text reference-footer">
            © 2026 PT. Ansel Muda Berkarya. All rights reserved.
        </div>
    </div>
</x-guest-layout>