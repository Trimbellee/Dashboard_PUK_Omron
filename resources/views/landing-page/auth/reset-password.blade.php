@extends('landing-page.layouts.app')

@push('styles')
    @vite(['resources/css/landing-page/auth/login.css', 'resources/css/landing-page/auth/reset-password.css'])
@endpush

@section('title', 'Set Password Baru - PUK OMRON')

@section('content')
    <section class="lp-section lp-auth">
        <div class="lp-container lp-auth__container">
            <div class="lp-auth__card">

                <div class="lp-auth__steps">
                    <span class="lp-auth__step lp-auth__step--done">1</span>
                    <span class="lp-auth__step-line lp-auth__step-line--done"></span>
                    <span class="lp-auth__step lp-auth__step--done">2</span>
                    <span class="lp-auth__step-line lp-auth__step-line--done"></span>
                    <span class="lp-auth__step lp-auth__step--active">3</span>
                </div>

                <h1 class="lp-auth__title">Buat Password Baru</h1>
                <p class="lp-auth__desc">Identitas Anda telah terverifikasi. Silakan buat password baru untuk akun Anda.</p>

                @if (session('error'))
                    <p class="lp-form__error lp-auth__alert">{{ session('error') }}</p>
                @endif

                <form action="{{ route('anggota.password.update') }}" method="POST" class="lp-form lp-auth__form">
                    @csrf

                    <div class="lp-form__group">
                    <label for="password">Password Baru</label>

                    <div class="lp-password">
                        <input
                            type="password"
                            id="password"
                            name="password"
                            placeholder="Minimal 8 karakter"
                            required
                            autofocus>

                        <button
                            type="button"
                            class="lp-password__toggle"
                            data-target="password"
                            aria-label="Lihat password">
                            <svg class="eye-open" xmlns="http://www.w3.org/2000/svg" width="20" height="20"
                                viewBox="0 0 24 24" fill="none" stroke="currentColor"
                                stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                <path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8S1 12 1 12z"/>
                                <circle cx="12" cy="12" r="3"/>
                            </svg>

                            <svg class="eye-close" xmlns="http://www.w3.org/2000/svg" width="20" height="20"
                                viewBox="0 0 24 24" fill="none" stroke="currentColor"
                                stroke-width="2" stroke-linecap="round" stroke-linejoin="round"
                                style="display:none;">
                                <path d="M17.94 17.94A10.94 10.94 0 0 1 12 20C5 20 1 12 1 12a21.77 21.77 0 0 1 5.06-6.94"/>
                                <path d="M9.9 4.24A10.94 10.94 0 0 1 12 4c7 0 11 8 11 8a21.8 21.8 0 0 1-3.17 4.39"/>
                                <path d="M1 1l22 22"/>
                            </svg>
                        </button>
                    </div>

                    @error('password')
                        <span class="lp-form__error">{{ $message }}</span>
                    @enderror
                </div>

                <div class="lp-form__group">
                    <label for="password_confirmation">Konfirmasi Password</label>

                    <div class="lp-password">
                        <input
                            type="password"
                            id="password_confirmation"
                            name="password_confirmation"
                            placeholder="Ulangi password baru"
                            required>

                        <button
                            type="button"
                            class="lp-password__toggle"
                            data-target="password_confirmation"
                            aria-label="Lihat password">
                            <svg class="eye-open" xmlns="http://www.w3.org/2000/svg" width="20" height="20"
                                viewBox="0 0 24 24" fill="none" stroke="currentColor"
                                stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                <path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8S1 12 1 12z"/>
                                <circle cx="12" cy="12" r="3"/>
                            </svg>

                            <svg class="eye-close" xmlns="http://www.w3.org/2000/svg" width="20" height="20"
                                viewBox="0 0 24 24" fill="none" stroke="currentColor"
                                stroke-width="2" stroke-linecap="round" stroke-linejoin="round"
                                style="display:none;">
                                <path d="M17.94 17.94A10.94 10.94 0 0 1 12 20C5 20 1 12 1 12a21.77 21.77 0 0 1 5.06-6.94"/>
                                <path d="M9.9 4.24A10.94 10.94 0 0 1 12 4c7 0 11 8 11 8a21.8 21.8 0 0 1-3.17 4.39"/>
                                <path d="M1 1l22 22"/>
                            </svg>
                        </button>
                    </div>
                </div>

                    <button type="submit" class="lp-btn lp-btn--primary lp-form__submit">Simpan Password</button>
                </form>
            </div>
        </div>
    </section>

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', () => {

    document.querySelectorAll('.lp-password__toggle').forEach(button => {

        button.addEventListener('click', () => {

            const input = document.getElementById(button.dataset.target);

            const eyeOpen = button.querySelector('.eye-open');
            const eyeClose = button.querySelector('.eye-close');

            const show = input.type === 'password';

            input.type = show ? 'text' : 'password';

            eyeOpen.style.display = show ? 'none' : 'block';
            eyeClose.style.display = show ? 'block' : 'none';
        });

    });

});
</script>
@endpush
@endsection