@extends('landing-page.layouts.app')

@push('styles')
    @vite(['resources/css/landing-page/auth/login.css'])
@endpush

@section('title', 'Login Anggota - PUK OMRON')

@section('content')
    <section class="lp-section lp-auth">
        <div class="lp-container lp-auth__container">
            <div class="lp-auth__card">
                <h1 class="lp-auth__title">Login Anggota</h1>
                <p class="lp-auth__desc">Masuk menggunakan NIK Karyawan dan password akun PUK OMRON Anda.</p>

                @if (session('error'))
                    <p class="lp-form__error lp-auth__alert">{{ session('error') }}</p>
                @endif
                @if (session('success'))
                    <p class="lp-form__success lp-auth__alert">{{ session('success') }}</p>
                @endif

                <form action="{{ route('anggota.login.process') }}" method="POST" class="lp-form lp-auth__form">
                    @csrf

                    <div class="lp-form__group">
                        <label for="nik_karyawan">NIK Karyawan</label>
                        <input type="text"
                               id="nik_karyawan"
                               name="nik_karyawan"
                               value="{{ old('nik_karyawan') }}"
                               placeholder="Masukkan NIK Anda"
                               maxlength="12"
                               inputmode="numeric"
                               autofocus
                               required>
                        @error('nik_karyawan') <span class="lp-form__error">{{ $message }}</span> @enderror
                    </div>

                    <div class="lp-form__group">
                    <label for="password">Password</label>

                    <div class="lp-password">
                        <input
                            type="password"
                            id="password"
                            name="password"
                            placeholder="Password Anda"
                            required
                        >

                        <button
                            type="button"
                            class="lp-password__toggle"
                            id="togglePassword"
                            aria-label="Lihat password"
                        >
                            <svg id="eyeOpen" xmlns="http://www.w3.org/2000/svg" width="20" height="20"
                                viewBox="0 0 24 24" fill="none" stroke="currentColor"
                                stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                <path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8S1 12 1 12z"/>
                                <circle cx="12" cy="12" r="3"/>
                            </svg>

                            <svg id="eyeClosed" xmlns="http://www.w3.org/2000/svg" width="20" height="20"
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

                    <button type="submit" class="lp-btn lp-btn--primary lp-form__submit">Masuk</button>
                </form>

                <a href="{{ route('anggota.password.request') }}" class="lp-auth__back">Lupa password?</a>
            </div>
        </div>
    </section>

    @push('scripts')
<script>
document.addEventListener('DOMContentLoaded', () => {
    const password = document.getElementById('password');
    const toggle = document.getElementById('togglePassword');
    const eyeOpen = document.getElementById('eyeOpen');
    const eyeClosed = document.getElementById('eyeClosed');

    toggle.addEventListener('click', () => {
        const show = password.type === 'password';

        password.type = show ? 'text' : 'password';

        eyeOpen.style.display = show ? 'none' : 'block';
        eyeClosed.style.display = show ? 'block' : 'none';
    });
});
</script>
@endpush
@endsection