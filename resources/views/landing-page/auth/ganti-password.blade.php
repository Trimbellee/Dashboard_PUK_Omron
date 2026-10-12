@extends('landing-page.layouts.app')

@push('styles')
    @vite([
        'resources/css/landing-page/auth/login.css',
        'resources/css/landing-page/auth/ganti-password.css',
    ])
@endpush

@section('title', 'Ganti Password - PUK OMRON')

@section('content')
    <section class="lp-section lp-auth">
        <div class="lp-container lp-auth__container">
            <div class="lp-auth__card">
                <h1 class="lp-auth__title">Ganti Password</h1>
                <p class="lp-auth__desc">
                    Halo, {{ $anggota->nama_lengkap }}. Password Anda masih berupa password default,
                    buat password baru sebelum melanjutkan.
                </p>

                @if (session('info'))
                    <p class="lp-auth__alert lp-auth__alert--info">{{ session('info') }}</p>
                @endif
                @if (session('error'))
                    <p class="lp-form__error lp-auth__alert">{{ session('error') }}</p>
                @endif

                <form action="{{ route('anggota.ganti-password.update') }}"
                      method="POST"
                      class="lp-form lp-auth__form"
                      id="formGantiPassword"
                      data-default="{{ $passwordDefault }}"
                      data-nik="{{ $anggota->nik_karyawan }}"
                      autocomplete="off">
                    @csrf

                    @foreach ([
                        ['id' => 'password',              'label' => 'Password Baru',            'placeholder' => 'Buat password baru'],
                        ['id' => 'password_confirmation', 'label' => 'Konfirmasi Password Baru', 'placeholder' => 'Ulangi password baru'],
                    ] as $f)
                        <div class="lp-form__group">
                            <label for="{{ $f['id'] }}">{{ $f['label'] }}</label>

                            <div class="lp-password">
                                <input type="password"
                                       id="{{ $f['id'] }}"
                                       name="{{ $f['id'] }}"
                                       placeholder="{{ $f['placeholder'] }}"
                                       autocomplete="new-password"
                                       @if ($loop->first) autofocus @endif
                                       required>

                                <button type="button"
                                        class="lp-password__toggle"
                                        data-toggle-password
                                        aria-label="Lihat password">
                                    <svg class="eye-open" xmlns="http://www.w3.org/2000/svg" width="20" height="20"
                                         viewBox="0 0 24 24" fill="none" stroke="currentColor"
                                         stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                        <path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8S1 12 1 12z"/>
                                        <circle cx="12" cy="12" r="3"/>
                                    </svg>
                                    <svg class="eye-closed" xmlns="http://www.w3.org/2000/svg" width="20" height="20"
                                         viewBox="0 0 24 24" fill="none" stroke="currentColor"
                                         stroke-width="2" stroke-linecap="round" stroke-linejoin="round"
                                         style="display:none;">
                                        <path d="M17.94 17.94A10.94 10.94 0 0 1 12 20C5 20 1 12 1 12a21.77 21.77 0 0 1 5.06-6.94"/>
                                        <path d="M9.9 4.24A10.94 10.94 0 0 1 12 4c7 0 11 8 11 8a21.8 21.8 0 0 1-3.17 4.39"/>
                                        <path d="M1 1l22 22"/>
                                    </svg>
                                </button>
                            </div>

                            @if ($loop->first)
                                <div class="lp-pw-strength" aria-hidden="true">
                                    <div class="lp-pw-meter"><span></span><span></span><span></span><span></span></div>
                                    <small class="lp-pw-strength__label" id="pwStrengthLabel"></small>
                                </div>

                                <ul class="lp-pw-rules">
                                    <li data-rule="len">Minimal 8 karakter</li>
                                    <li data-rule="letter">Mengandung huruf</li>
                                    <li data-rule="number">Mengandung angka</li>
                                    <li data-rule="default">Bukan password default</li>
                                </ul>

                                @error('password') <span class="lp-form__error">{{ $message }}</span> @enderror
                            @else
                                <span class="lp-form__error" id="pwMatchMsg" hidden></span>
                            @endif
                        </div>
                    @endforeach

                    <button type="submit" class="lp-btn lp-btn--primary lp-form__submit" id="btnSimpanPassword" disabled>
                        Simpan Password
                    </button>
                </form>

                <form action="{{ route('anggota.logout') }}" method="POST">
                    @csrf
                    <button type="submit" class="lp-auth__back lp-auth__back--btn">Batal dan keluar</button>
                </form>
            </div>
        </div>
    </section>

    @push('scripts')
    <script>
    document.addEventListener('DOMContentLoaded', () => {
        const form      = document.getElementById('formGantiPassword');
        const pw        = document.getElementById('password');
        const confirmEl = document.getElementById('password_confirmation');
        const submit    = document.getElementById('btnSimpanPassword');
        const matchMsg  = document.getElementById('pwMatchMsg');
        const bars      = document.querySelectorAll('.lp-pw-meter span');
        const label     = document.getElementById('pwStrengthLabel');
        const levels    = ['', 'Lemah', 'Cukup', 'Baik', 'Kuat'];

        // Sama seperti normalisasi di server: abaikan huruf besar/kecil & spasi berlebih
        const norm = (s) => s.trim().replace(/\s+/g, ' ').toLowerCase();
        const passwordDefault = norm(form.dataset.default);
        const nik             = norm(form.dataset.nik);

        // Tombol lihat/sembunyikan password (berlaku untuk kedua field)
        document.querySelectorAll('[data-toggle-password]').forEach(btn => {
            btn.addEventListener('click', () => {
                const input = btn.parentElement.querySelector('input');
                const show  = input.type === 'password';

                input.type = show ? 'text' : 'password';
                btn.querySelector('.eye-open').style.display   = show ? 'none'  : 'block';
                btn.querySelector('.eye-closed').style.display = show ? 'block' : 'none';
                btn.setAttribute('aria-label', show ? 'Sembunyikan password' : 'Lihat password');
            });
        });

        function evaluasi() {
            const v = pw.value;
            const n = norm(v);

            const cek = {
                len:     v.length >= 8,
                letter:  /[A-Za-z]/.test(v),
                number:  /\d/.test(v),
                default: v.length > 0 && n !== passwordDefault && n !== nik,
            };

            Object.entries(cek).forEach(([k, ok]) => {
                form.querySelector(`[data-rule="${k}"]`).classList.toggle('is-ok', ok);
            });

            // Kekuatan 0-4
            let skor = 0;
            if (cek.len) skor++;
            if (cek.letter && cek.number) skor++;
            if (v.length >= 12) skor++;
            if (/[^A-Za-z0-9]/.test(v)) skor++;
            if (v.length && skor === 0) skor = 1;

            bars.forEach((bar, i) => {
                bar.className = i < skor ? 'is-on-' + skor : '';
            });
            label.textContent = v.length ? levels[skor] : '';

            // Konfirmasi
            const cocok = v.length > 0 && v === confirmEl.value;
            matchMsg.hidden = confirmEl.value.length === 0;
            matchMsg.textContent = cocok ? 'Password cocok.' : 'Konfirmasi password belum sama.';
            matchMsg.classList.toggle('is-ok', cocok);

            submit.disabled = !(cek.len && cek.letter && cek.number && cek.default && cocok);
        }

        pw.addEventListener('input', evaluasi);
        confirmEl.addEventListener('input', evaluasi);
        evaluasi();
    });
    </script>
    @endpush
@endsection