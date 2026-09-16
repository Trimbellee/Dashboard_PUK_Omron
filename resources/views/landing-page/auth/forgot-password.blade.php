@extends('landing-page.layouts.app')

@push('styles')
    @vite(['resources/css/landing-page/auth/login.css', 'resources/css/landing-page/auth/reset-password.css'])
@endpush

@section('title', 'Lupa Password - PUK OMRON')

@section('content')
    <section class="lp-section lp-auth">
        <div class="lp-container lp-auth__container">
            <div class="lp-auth__card">

                <div class="lp-auth__steps">
                    <span class="lp-auth__step lp-auth__step--active">1</span>
                    <span class="lp-auth__step-line"></span>
                    <span class="lp-auth__step">2</span>
                    <span class="lp-auth__step-line"></span>
                    <span class="lp-auth__step">3</span>
                </div>

                <h1 class="lp-auth__title">Lupa Password</h1>
                <p class="lp-auth__desc">Masukkan NIK Karyawan dan Email yang terdaftar untuk melanjutkan proses reset password.</p>

                @if (session('error'))
                    <p class="lp-form__error lp-auth__alert">{{ session('error') }}</p>
                @endif
                @if (session('success'))
                    <p class="lp-form__success lp-auth__alert">{{ session('success') }}</p>
                @endif

                <form action="{{ route('anggota.password.identity.check') }}" method="POST" class="lp-form lp-auth__form">
                    @csrf

                    <div class="lp-form__group">
                        <label for="nik_karyawan">NIK Karyawan</label>
                        <input type="text"
                               id="nik_karyawan"
                               name="nik_karyawan"
                               value="{{ old('nik_karyawan') }}"
                               placeholder="Masukkan NIK Anda"
                               maxlength="8"
                               inputmode="numeric"
                               autofocus
                               required>
                        @error('nik_karyawan') <span class="lp-form__error">{{ $message }}</span> @enderror
                    </div>

                    <div class="lp-form__group">
                        <label for="email">Email</label>
                        <input type="email"
                               id="email"
                               name="email"
                               value="{{ old('email') }}"
                               placeholder="Email terdaftar Anda"
                               required>
                        @error('email') <span class="lp-form__error">{{ $message }}</span> @enderror
                    </div>

                    <button type="submit" class="lp-btn lp-btn--primary lp-form__submit">Lanjutkan</button>
                </form>

                <a href="{{ route('anggota.login') }}" class="lp-auth__back">← Kembali ke Login</a>
            </div>
        </div>
    </section>
@endsection