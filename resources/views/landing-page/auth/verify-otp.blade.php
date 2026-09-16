@extends('landing-page.layouts.app')

@push('styles')
    @vite(['resources/css/landing-page/auth/login.css', 'resources/css/landing-page/auth/reset-password.css'])
@endpush

@section('title', 'Verifikasi OTP - PUK OMRON')

@section('content')
    <section class="lp-section lp-auth">
        <div class="lp-container lp-auth__container">
            <div class="lp-auth__card"
                 x-data="{
                    digits: ['', '', '', '', '', ''],
                    cooldown: 0,
                    timer: null,
                    showSuccess: {{ session('success') ? 'true' : 'false' }},
                    startCooldown() {
                        this.cooldown = 60;
                        clearInterval(this.timer);
                        this.timer = setInterval(() => {
                            if (this.cooldown > 0) this.cooldown--;
                            else clearInterval(this.timer);
                        }, 1000);
                    },
                    handleInput(index, event) {
                        const val = event.target.value.replace(/[^0-9]/g, '');
                        this.digits[index] = val.slice(-1);
                        if (val && index < 5) {
                            this.$refs['digit' + (index + 1)].focus();
                        }
                        this.syncHidden();
                    },
                    handleKeydown(index, event) {
                        if (event.key === 'Backspace' && !this.digits[index] && index > 0) {
                            this.$refs['digit' + (index - 1)].focus();
                        }
                    },
                    handlePaste(event) {
                        const pasted = (event.clipboardData || window.clipboardData).getData('text').replace(/[^0-9]/g, '').slice(0, 6);
                        pasted.split('').forEach((d, i) => this.digits[i] = d);
                        this.syncHidden();
                        this.$nextTick(() => {
                            const lastIndex = Math.min(pasted.length, 6) - 1;
                            if (lastIndex >= 0) this.$refs['digit' + lastIndex].focus();
                        });
                    },
                    syncHidden() {
                        this.$refs.tokenHidden.value = this.digits.join('');
                    }
                 }"
                 x-init="
                    if (showSuccess) {
                        startCooldown();
                        setTimeout(() => showSuccess = false, 3000);
                    }
                 ">

                <div class="lp-auth__steps">
                    <span class="lp-auth__step lp-auth__step--done">1</span>
                    <span class="lp-auth__step-line lp-auth__step-line--done"></span>
                    <span class="lp-auth__step lp-auth__step--active">2</span>
                    <span class="lp-auth__step-line"></span>
                    <span class="lp-auth__step">3</span>
                </div>

                <h1 class="lp-auth__title">Verifikasi Kode OTP</h1>
                <p class="lp-auth__desc">Kode 6 digit telah dikirim ke <strong>{{ $emailMasked }}</strong>. Kode berlaku selama 10 menit.</p>

                @if (session('error'))
                    <p class="lp-form__error lp-auth__alert">{{ session('error') }}</p>
                @endif

                @if (session('success'))
                    <p class="lp-form__success lp-auth__alert" x-show="showSuccess" x-transition:leave.duration.400ms>
                        {{ session('success') }}
                    </p>
                @endif

                @error('token') <span class="lp-form__error lp-auth__alert">{{ $message }}</span> @enderror

                <form action="{{ route('anggota.password.otp.check') }}" method="POST" class="lp-form lp-auth__form">
                    @csrf
                    <input type="hidden" name="token" x-ref="tokenHidden">

                    <div class="lp-otp__group" @paste="handlePaste">
                        @for ($i = 0; $i < 6; $i++)
                            <input type="text"
                                   inputmode="numeric"
                                   maxlength="1"
                                   class="lp-otp__box"
                                   x-ref="digit{{ $i }}"
                                   x-model="digits[{{ $i }}]"
                                   @input="handleInput({{ $i }}, $event)"
                                   @keydown="handleKeydown({{ $i }}, $event)"
                                   @if($i === 0) autofocus @endif>
                        @endfor
                    </div>

                    <button type="submit" class="lp-btn lp-btn--primary lp-form__submit">Verifikasi</button>
                </form>

                <div class="lp-otp__resend">
                    <template x-if="cooldown > 0">
                        <span>Kirim ulang kode dalam <span x-text="cooldown"></span> detik</span>
                    </template>
                    <template x-if="cooldown === 0">
                        <form action="{{ route('anggota.password.otp.resend') }}" method="POST">
                            @csrf
                            <button type="submit" class="lp-otp__resend-btn">Kirim ulang kode</button>
                        </form>
                    </template>
                </div>

                <a href="{{ route('anggota.password.request') }}" class="lp-auth__back">← Kembali</a>
            </div>
        </div>
    </section>
@endsection