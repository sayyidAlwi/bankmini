@extends('layouts.app')

@push('styles')
    <style>
        body.login-page {
            background: #f3f6f4;
            color: #172a25;
            font-family: 'Nunito', ui-sans-serif, system-ui, sans-serif;
        }

        body.login-page #app > nav {
            display: none;
        }

        body.login-page #app > main.py-4 {
            padding: 0 !important;
        }

        .bankmini-login {
            display: grid;
            grid-template-columns: minmax(280px, 0.9fr) minmax(420px, 1.1fr);
            min-height: 100vh;
            background: #fff;
        }

        .bankmini-login__intro {
            position: relative;
            display: flex;
            flex-direction: column;
            justify-content: space-between;
            gap: 3rem;
            overflow: hidden;
            padding: clamp(2rem, 6vw, 5.5rem);
            background:
                radial-gradient(ellipse at 100% 100%, rgb(91 160 128 / 22%), transparent 48%),
                #123d32;
            color: #fff;
        }

        .bankmini-login__intro::after {
            position: absolute;
            right: -7rem;
            bottom: 8%;
            width: 22rem;
            height: 22rem;
            border: 1px solid rgb(255 255 255 / 12%);
            border-radius: 50%;
            box-shadow: 0 0 0 2rem rgb(255 255 255 / 4%), 0 0 0 4rem rgb(255 255 255 / 3%);
            content: '';
            pointer-events: none;
        }

        .bankmini-login__brand {
            display: inline-flex;
            align-items: center;
            gap: 0.75rem;
            color: inherit;
            font-size: 1.15rem;
            font-weight: 800;
            letter-spacing: -0.03em;
            text-decoration: none;
        }

        .bankmini-login__brand-mark {
            display: grid;
            width: 2.5rem;
            height: 2.5rem;
            place-items: center;
            border: 1px solid rgb(255 255 255 / 24%);
            border-radius: 0.8rem;
            background: rgb(255 255 255 / 10%);
            color: #c9efda;
            font-size: 0.9rem;
            letter-spacing: 0;
        }

        .bankmini-login__message {
            position: relative;
            z-index: 1;
            max-width: 32rem;
            margin: auto 0;
            padding: 4rem 0;
        }

        .bankmini-login__eyebrow {
            margin-bottom: 1rem;
            color: #b7ddc9;
            font-size: 0.75rem;
            font-weight: 800;
            letter-spacing: 0.16em;
            text-transform: uppercase;
        }

        .bankmini-login__message h1 {
            max-width: 11ch;
            margin: 0;
            font-size: clamp(2.5rem, 5vw, 4.5rem);
            font-weight: 700;
            letter-spacing: -0.06em;
            line-height: 1.04;
        }

        .bankmini-login__message p {
            max-width: 27rem;
            margin: 1.5rem 0 0;
            color: rgb(255 255 255 / 72%);
            font-size: 1rem;
            line-height: 1.8;
        }

        .bankmini-login__footnote {
            position: relative;
            z-index: 1;
            margin: 0;
            color: rgb(255 255 255 / 56%);
            font-size: 0.8rem;
        }

        .bankmini-login__content {
            display: grid;
            place-items: center;
            padding: clamp(2rem, 7vw, 6rem);
        }

        .bankmini-login__form-wrap {
            width: min(100%, 26rem);
        }

        .bankmini-login__heading {
            margin-bottom: 2.25rem;
        }

        .bankmini-login__heading h2 {
            margin: 0;
            color: #172a25;
            font-size: 2rem;
            font-weight: 700;
            letter-spacing: -0.045em;
        }

        .bankmini-login__heading p {
            margin: 0.55rem 0 0;
            color: #718079;
            font-size: 0.95rem;
        }

        .bankmini-login__alert {
            margin-bottom: 1.25rem;
            padding: 0.85rem 1rem;
            border: 1px solid #f0caca;
            border-radius: 0.75rem;
            background: #fff6f5;
            color: #a23c36;
            font-size: 0.875rem;
        }

        .bankmini-login__field {
            margin-bottom: 1.25rem;
        }

        .bankmini-login__label {
            display: block;
            margin-bottom: 0.5rem;
            color: #344840;
            font-size: 0.85rem;
            font-weight: 700;
        }

        .bankmini-login__input {
            display: block;
            width: 100%;
            min-height: 3.25rem;
            padding: 0.75rem 0.95rem;
            border: 1px solid #dce5df;
            border-radius: 0.75rem;
            outline: none;
            background: #fff;
            color: #172a25;
            font: inherit;
            transition: border-color 150ms ease, box-shadow 150ms ease;
        }

        .bankmini-login__input::placeholder {
            color: #a4b0aa;
        }

        .bankmini-login__input:focus {
            border-color: #34795f;
            box-shadow: 0 0 0 4px rgb(52 121 95 / 12%);
        }

        .bankmini-login__input.is-invalid {
            border-color: #c7524b;
        }

        .bankmini-login__error {
            display: block;
            margin-top: 0.45rem;
            color: #b33f38;
            font-size: 0.8rem;
        }

        .bankmini-login__options {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 1rem;
            margin: 0.25rem 0 1.75rem;
            color: #66776f;
            font-size: 0.85rem;
        }

        .bankmini-login__remember {
            display: inline-flex;
            align-items: center;
            gap: 0.55rem;
            cursor: pointer;
        }

        .bankmini-login__remember input {
            width: 1rem;
            height: 1rem;
            accent-color: #24644d;
        }

        .bankmini-login__link {
            color: #24644d;
            font-weight: 700;
            text-decoration: none;
        }

        .bankmini-login__link:hover {
            color: #174a38;
            text-decoration: underline;
        }

        .bankmini-login__submit {
            width: 100%;
            min-height: 3.25rem;
            border: 0;
            border-radius: 0.75rem;
            background: #1c5a43;
            color: #fff;
            font: inherit;
            font-weight: 800;
            transition: background-color 150ms ease, transform 150ms ease;
        }

        .bankmini-login__submit:hover {
            transform: translateY(-1px);
            background: #164a37;
        }

        .bankmini-login__submit:focus-visible,
        .bankmini-login__link:focus-visible {
            outline: 3px solid #7ab89a;
            outline-offset: 3px;
        }

        @media (max-width: 760px) {
            .bankmini-login {
                grid-template-columns: 1fr;
            }

            .bankmini-login__intro {
                min-height: 16rem;
                gap: 2rem;
                padding: 1.5rem 1.75rem;
            }

            .bankmini-login__message {
                padding: 1rem 0;
            }

            .bankmini-login__message h1 {
                max-width: 15ch;
                font-size: clamp(2rem, 9vw, 3rem);
            }

            .bankmini-login__message p,
            .bankmini-login__footnote {
                display: none;
            }

            .bankmini-login__content {
                padding: 2.5rem 1.75rem 3rem;
            }
        }

        @media (max-width: 380px) {
            .bankmini-login__options {
                align-items: flex-start;
                flex-direction: column;
            }
        }
    </style>
@endpush

@section('content')
    <div class="bankmini-login">
        <aside class="bankmini-login__intro">
            <a class="bankmini-login__brand" href="{{ url('/') }}">
                <span class="bankmini-login__brand-mark" aria-hidden="true">BM</span>
                <span>BankMini</span>
            </a>

            <div class="bankmini-login__message">
                <p class="bankmini-login__eyebrow">Perbankan lebih sederhana</p>
                <h1>Keuanganmu, lebih mudah dikelola.</h1>
                <p>Akses layanan BankMini dengan aman dan nyaman, kapan pun kamu membutuhkannya.</p>
            </div>

            <p class="bankmini-login__footnote">Aman, sederhana, dan dekat denganmu.</p>
        </aside>

        <section class="bankmini-login__content" aria-labelledby="login-heading">
            <div class="bankmini-login__form-wrap">
                <header class="bankmini-login__heading">
                    <h2 id="login-heading">Selamat datang</h2>
                    <p>Masuk ke akun BankMini untuk melanjutkan.</p>
                </header>

                @if (session('status'))
                    <div class="bankmini-login__alert" role="status">{{ session('status') }}</div>
                @endif

                <form method="POST" action="{{ route('login') }}">
                    @csrf

                    <div class="bankmini-login__field">
                        <label for="username" class="bankmini-login__label">{{ __('Username') }}</label>
                        <input
                            id="username"
                            type="text"
                            class="bankmini-login__input @error('username') is-invalid @enderror"
                            name="username"
                            value="{{ old('username') }}"
                            placeholder="Masukkan username"
                            required
                            autocomplete="username"
                            autofocus
                            @error('username') aria-describedby="username-error" @enderror
                        >

                        @error('username')
                            <span id="username-error" class="bankmini-login__error" role="alert">{{ $message }}</span>
                        @enderror
                    </div>

                    <div class="bankmini-login__field">
                        <label for="password" class="bankmini-login__label">{{ __('Password') }}</label>
                        <input
                            id="password"
                            type="password"
                            class="bankmini-login__input @error('password') is-invalid @enderror"
                            name="password"
                            placeholder="Masukkan password"
                            required
                            autocomplete="current-password"
                            @error('password') aria-describedby="password-error" @enderror
                        >

                        @error('password')
                            <span id="password-error" class="bankmini-login__error" role="alert">{{ $message }}</span>
                        @enderror
                    </div>

                    <div class="bankmini-login__options">
                        <label class="bankmini-login__remember" for="remember">
                            <input type="checkbox" name="remember" id="remember" {{ old('remember') ? 'checked' : '' }}>
                            <span>{{ __('Remember Me') }}</span>
                        </label>

                        @if (Route::has('password.request'))
                            <a class="bankmini-login__link" href="{{ route('password.request') }}">
                                {{ __('Forgot Your Password?') }}
                            </a>
                        @endif
                    </div>

                    <button type="submit" class="bankmini-login__submit">{{ __('Login') }}</button>
                </form>
            </div>
        </section>
    </div>
@endsection
