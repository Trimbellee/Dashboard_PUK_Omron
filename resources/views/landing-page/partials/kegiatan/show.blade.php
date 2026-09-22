{{-- resources/views/landing-page/kegiatan/show.blade.php --}}

@extends('landing-page.layouts.app')

@section('title', $kegiatan->judul . ' — PUK OMRON')
@section('meta_description', Str::limit(strip_tags($kegiatan->deskripsi ?? ''), 150))

@push('styles')
<style>
    /* ============================================================
       HERO HEADER
    ============================================================ */
    .lp-kgd__hero {
        position: relative;
        min-height: 320px;
        display: flex;
        align-items: flex-end;
        background: linear-gradient(135deg,
            color-mix(in srgb, var(--accent) 22%, var(--bg-surface-alt)),
            color-mix(in srgb, var(--accent) 8%, var(--bg-surface-alt)));
        overflow: hidden;
        border-bottom: 1px solid var(--border-color);
    }
    .lp-kgd__hero-img {
        position: absolute; inset: 0;
        width: 100%; height: 100%;
        object-fit: cover;
    }
    .lp-kgd__hero-overlay {
        position: absolute; inset: 0;
        background:
            linear-gradient(180deg,
                rgba(15, 23, 42, .15) 0%,
                rgba(15, 23, 42, .45) 55%,
                rgba(15, 23, 42, .82) 100%);
    }
    .lp-kgd__hero-inner {
        position: relative;
        width: 100%;
        max-width: 1080px;
        margin: 0 auto;
        padding: 40px 24px 32px;
        color: #fff;
    }

    .lp-kgd__back {
        display: inline-flex; align-items: center; gap: 6px;
        font-size: 13px; font-weight: 600;
        color: rgba(255,255,255,.85);
        text-decoration: none;
        padding: 6px 12px 6px 8px;
        border-radius: 999px;
        background: rgba(255,255,255,.12);
        border: 1px solid rgba(255,255,255,.18);
        backdrop-filter: blur(10px);
        -webkit-backdrop-filter: blur(10px);
        margin-bottom: 20px;
        transition: background .25s ease, transform .25s ease;
    }
    .lp-kgd__back:hover {
        background: rgba(255,255,255,.22);
        transform: translateX(-2px);
    }

    .lp-kgd__badges {
        display: flex; gap: 8px; flex-wrap: wrap;
        margin-bottom: 16px;
    }
    .lp-kgd__badge {
        display: inline-flex; align-items: center;
        font-size: 12px; font-weight: 700;
        padding: 5px 12px; border-radius: 999px;
        text-transform: capitalize;
        letter-spacing: .02em;
        color: #fff;
        background: rgba(99, 102, 241, .92);
        border: 1px solid rgba(255,255,255,.22);
        backdrop-filter: blur(10px);
        -webkit-backdrop-filter: blur(10px);
        box-shadow: 0 4px 14px rgba(0,0,0,.15);
    }
    .lp-kgd__badge--seminar { background: rgba(14, 116, 144, .92); }
    .lp-kgd__badge--aksi { background: rgba(194, 65, 12, .92); }
    .lp-kgd__badge--lainnya { background: rgba(75, 85, 99, .92); }
    .lp-kgd__badge--soft {
        background: rgba(255,255,255,.16);
        color: #fff;
        font-weight: 600;
    }
    .lp-kgd__badge--full {
        background: rgba(185, 28, 28, .92);
        text-transform: uppercase;
        letter-spacing: .06em;
    }

    .lp-kgd__title {
        font-family: var(--font-display);
        font-size: clamp(22px, 3.4vw, 38px);
        font-weight: 800;
        line-height: 1.2;
        letter-spacing: -0.02em;
        margin: 0;
        text-shadow: 0 2px 16px rgba(0,0,0,.35);
        max-width: 900px;
    }

    /* ============================================================
       BODY LAYOUT
    ============================================================ */
    .lp-kgd__body {
        max-width: 1080px;
        margin: 0 auto;
        padding: 36px 24px 80px;
        display: grid;
        grid-template-columns: minmax(0, 1fr) 340px;
        gap: 40px;
        align-items: start;
    }

    /* ============================================================
       INFO CHIPS
    ============================================================ */
    .lp-kgd__info {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
        gap: 14px;
        margin-bottom: 32px;
    }
    .lp-kgd__chip {
        display: flex; gap: 12px; align-items: flex-start;
        padding: 14px 16px;
        background: var(--bg-surface);
        border: 1px solid var(--border-color);
        border-radius: 14px;
        transition: border-color .25s ease, transform .25s ease;
    }
    .lp-kgd__chip:hover {
        border-color: color-mix(in srgb, var(--accent) 40%, var(--border-color));
        transform: translateY(-1px);
    }
    .lp-kgd__chip-icon {
        flex-shrink: 0;
        width: 38px; height: 38px;
        border-radius: 10px;
        display: flex; align-items: center; justify-content: center;
        background: color-mix(in srgb, var(--accent) 12%, transparent);
        color: var(--accent);
    }
    .lp-kgd__chip-text { min-width: 0; }
    .lp-kgd__chip-text strong {
        display: block;
        font-size: 11.5px; font-weight: 700;
        text-transform: uppercase; letter-spacing: .05em;
        color: var(--text-secondary);
        margin-bottom: 3px;
    }
    .lp-kgd__chip-text span {
        display: block;
        font-size: 14px; font-weight: 600;
        color: var(--text-primary);
        line-height: 1.35;
        word-break: break-word;
    }

    /* ============================================================
       DESCRIPTION
    ============================================================ */
    .lp-kgd__desc {
        font-size: 15.5px;
        line-height: 1.8;
        color: var(--text-primary);
    }
    .lp-kgd__desc > * + * { margin-top: 1em; }
    .lp-kgd__desc img {
        max-width: 100%;
        border-radius: 14px;
        box-shadow: var(--shadow-md);
    }
    .lp-kgd__desc h2, .lp-kgd__desc h3 {
        font-family: var(--font-display);
        font-weight: 700;
        line-height: 1.3;
        letter-spacing: -0.01em;
    }
    .lp-kgd__desc h2 { font-size: 22px; }
    .lp-kgd__desc h3 { font-size: 18px; }
    .lp-kgd__desc a { color: var(--accent); text-decoration: underline; text-underline-offset: 2px; }
    .lp-kgd__desc ul, .lp-kgd__desc ol { padding-left: 1.25em; }
    .lp-kgd__desc blockquote {
        border-left: 3px solid var(--accent);
        padding: 4px 0 4px 16px;
        color: var(--text-secondary);
        font-style: italic;
    }

    /* ============================================================
       SIDEBAR / PANEL
    ============================================================ */
    .lp-kgd__aside {
        position: sticky;
        top: 88px;
    }
    .lp-kgd__panel {
        background: var(--bg-surface);
        border: 1px solid var(--border-color);
        border-radius: 18px;
        padding: 24px;
        box-shadow: 0 12px 30px -20px rgba(15, 23, 42, .25);
    }
    .lp-kgd__panel-head {
        display: flex; align-items: center; gap: 10px;
        margin-bottom: 6px;
    }
    .lp-kgd__panel-head svg { color: var(--accent); }
    .lp-kgd__panel h2 {
        font-size: 17px; font-weight: 700;
        margin: 0;
        color: var(--text-primary);
        letter-spacing: -0.01em;
    }
    .lp-kgd__panel-sub {
        font-size: 13px;
        color: var(--text-secondary);
        margin: 0 0 20px;
        line-height: 1.5;
    }

    /* Quota bar in panel */
    .lp-kgd__quota {
        padding: 14px 16px;
        background: color-mix(in srgb, var(--accent) 6%, transparent);
        border: 1px solid color-mix(in srgb, var(--accent) 18%, transparent);
        border-radius: 12px;
        margin-bottom: 20px;
    }
    .lp-kgd__quota-row {
        display: flex; justify-content: space-between;
        align-items: baseline; gap: 8px;
        margin-bottom: 8px;
    }
    .lp-kgd__quota-row strong {
        font-size: 18px; font-weight: 800;
        color: var(--text-primary);
        letter-spacing: -0.01em;
    }
    .lp-kgd__quota-row span {
        font-size: 12px;
        color: var(--text-secondary);
        font-weight: 500;
    }
    .lp-kgd__quota-bar {
        width: 100%; height: 6px; border-radius: 999px;
        background: color-mix(in srgb, var(--border-color) 70%, transparent);
        overflow: hidden;
    }
    .lp-kgd__quota-fill {
        height: 100%; border-radius: 999px;
        background: linear-gradient(90deg,
            color-mix(in srgb, var(--accent) 70%, #22c55e),
            var(--accent));
        transition: width .6s cubic-bezier(.22,1,.36,1);
    }
    .lp-kgd__quota-fill.is-full {
        background: linear-gradient(90deg, #ef4444, #b91c1c);
    }

    /* Form fields */
    .lp-kgd__field { margin-bottom: 14px; }
    .lp-kgd__field label {
        display: block;
        font-weight: 600;
        font-size: 13px;
        color: var(--text-primary);
        margin-bottom: 6px;
        letter-spacing: .01em;
    }
    .lp-kgd__field label .req { color: #b91c1c; margin-left: 2px; }

    .lp-kgd__readonly {
        background: var(--bg-surface-alt);
        border: 1px solid var(--border-color);
        border-radius: 10px;
        padding: 11px 14px;
        font-size: 14px;
        color: var(--text-primary);
        font-weight: 500;
        display: flex; align-items: center; justify-content: space-between;
        gap: 8px;
    }
    .lp-kgd__readonly small {
        font-size: 11px;
        color: var(--text-secondary);
        font-weight: 500;
    }

    .lp-kgd__panel .lp-form__input,
    .lp-kgd__panel select,
    .lp-kgd__panel input[type="text"],
    .lp-kgd__panel input[type="number"] {
        width: 100%;
        padding: 11px 14px;
        border: 1px solid var(--border-color);
        border-radius: 10px;
        background: var(--bg-surface);
        color: var(--text-primary);
        font-size: 14px;
        font-family: inherit;
        transition: border-color .2s ease, box-shadow .2s ease;
    }
    .lp-kgd__panel .lp-form__input:focus,
    .lp-kgd__panel select:focus,
    .lp-kgd__panel input:focus {
        outline: none;
        border-color: var(--accent);
        box-shadow: 0 0 0 3px color-mix(in srgb, var(--accent) 18%, transparent);
    }
    .lp-kgd__check {
        display: flex; align-items: center; gap: 10px;
        font-size: 14px; color: var(--text-primary);
        padding: 10px 12px;
        border: 1px solid var(--border-color);
        border-radius: 10px;
        cursor: pointer;
        transition: border-color .2s ease;
    }
    .lp-kgd__check:hover { border-color: color-mix(in srgb, var(--accent) 40%, var(--border-color)); }
    .lp-kgd__check input { width: auto; margin: 0; accent-color: var(--accent); }

    /* Submit button: full width */
    .lp-kgd__submit {
        width: 100%;
        margin-top: 8px;
        padding: 13px 20px !important;
        font-size: 15px !important;
        font-weight: 700 !important;
        border-radius: 12px !important;
        letter-spacing: .01em;
    }

    /* States */
    .lp-kgd__state {
        text-align: center;
        padding: 12px 4px;
    }
    .lp-kgd__state-icon {
        width: 56px; height: 56px;
        border-radius: 50%;
        display: inline-flex; align-items: center; justify-content: center;
        margin-bottom: 14px;
    }
    .lp-kgd__state-title {
        font-size: 15px; font-weight: 700;
        margin: 0 0 6px;
        color: var(--text-primary);
    }
    .lp-kgd__state-desc {
        font-size: 13.5px;
        color: var(--text-secondary);
        margin: 0 0 16px;
        line-height: 1.55;
    }

    .lp-kgd__state--ok .lp-kgd__state-icon {
        background: color-mix(in srgb, #16a34a 14%, transparent);
        color: #15803d;
    }
    .lp-kgd__state--warn .lp-kgd__state-icon {
        background: color-mix(in srgb, #dc2626 14%, transparent);
        color: #b91c1c;
    }
    .lp-kgd__state--info .lp-kgd__state-icon {
        background: color-mix(in srgb, var(--accent) 14%, transparent);
        color: var(--accent);
    }

    /* Alert messages */
    .lp-kgd__alert {
        padding: 11px 14px;
        border-radius: 10px;
        font-size: 13.5px;
        margin-bottom: 16px;
        line-height: 1.5;
        font-weight: 500;
    }
    .lp-kgd__alert--error {
        background: color-mix(in srgb, #dc2626 10%, transparent);
        color: #b91c1c;
        border: 1px solid color-mix(in srgb, #dc2626 25%, transparent);
    }
    .lp-kgd__alert--success {
        background: color-mix(in srgb, #16a34a 10%, transparent);
        color: #15803d;
        border: 1px solid color-mix(in srgb, #16a34a 25%, transparent);
    }

    /* ============================================================
       RESPONSIVE
    ============================================================ */
    @media (max-width: 960px) {
        .lp-kgd__body {
            grid-template-columns: 1fr;
            gap: 32px;
            padding: 32px 20px 60px;
        }
        .lp-kgd__aside {
            position: static;
            top: auto;
        }
    }
    @media (max-width: 640px) {
        .lp-kgd__hero { min-height: 240px; }
        .lp-kgd__hero-inner { padding: 28px 18px 24px; }
        .lp-kgd__info { grid-template-columns: 1fr; gap: 10px; }
        .lp-kgd__chip { padding: 12px 14px; }
        .lp-kgd__panel { padding: 20px; border-radius: 16px; }
        .lp-kgd__desc { font-size: 15px; }
    }

    @media (prefers-reduced-motion: reduce) {
        .lp-kgd__chip,
        .lp-kgd__back,
        .lp-kgd__quota-fill { transition: none !important; }
    }
</style>
@endpush

@section('content')
<div class="lp-kg-detail">

    {{-- =========================
         HERO HEADER
    ========================== --}}
    <div class="lp-kgd__hero">
        @if ($kegiatan->thumbnail)
            <img src="{{ asset('storage/' . $kegiatan->thumbnail) }}"
                 alt="{{ $kegiatan->judul }}"
                 class="lp-kgd__hero-img">
        @endif
        <div class="lp-kgd__hero-overlay"></div>

        <div class="lp-kgd__hero-inner">
            <a href="{{ route('kegiatan.index') }}" class="lp-kgd__back">
                <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><path d="M19 12H5M12 19l-7-7 7-7"/></svg>
                Kembali ke Kegiatan
            </a>

            <div class="lp-kgd__badges">
                <span class="lp-kgd__badge lp-kgd__badge--{{ $kegiatan->jenis }}">
                    {{ ucfirst($kegiatan->jenis) }}
                </span>

                @if ($kegiatan->jenis === 'pendidikan' && $kegiatan->tingkat_kategori)
                    <span class="lp-kgd__badge lp-kgd__badge--soft">
                        {{ ucfirst($kegiatan->tingkat_kategori) }}
                        @if ($kegiatan->tingkat_keterangan) · {{ $kegiatan->tingkat_keterangan }} @endif
                    </span>
                @endif

                @if ($kegiatan->status !== 'dibuka')
                    <span class="lp-kgd__badge lp-kgd__badge--soft">{{ ucfirst($kegiatan->status) }}</span>
                @endif

                @if ($kegiatan->kuota_penuh && $kegiatan->status === 'dibuka')
                    <span class="lp-kgd__badge lp-kgd__badge--full">Kuota Penuh</span>
                @endif
            </div>

            <h1 class="lp-kgd__title">{{ $kegiatan->judul }}</h1>
        </div>
    </div>

    {{-- =========================
         BODY: 2 COLUMNS
    ========================== --}}
    <div class="lp-kgd__body">

        {{-- LEFT: CONTENT --}}
        <main>
            {{-- Info chips --}}
            <div class="lp-kgd__info">
                <div class="lp-kgd__chip">
                    <div class="lp-kgd__chip-icon">
                        <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="4" width="18" height="18" rx="2"/><path d="M16 2v4M8 2v4M3 10h18"/></svg>
                    </div>
                    <div class="lp-kgd__chip-text">
                        <strong>Tanggal</strong>
                        <span>
                            {{ $kegiatan->tanggal_mulai->translatedFormat('d F Y') }}
                            @if ($kegiatan->tanggal_selesai && !$kegiatan->tanggal_selesai->equalTo($kegiatan->tanggal_mulai))
                                <br>&ndash; {{ $kegiatan->tanggal_selesai->translatedFormat('d F Y') }}
                            @endif
                        </span>
                    </div>
                </div>

                @if ($kegiatan->lokasi)
                    <div class="lp-kgd__chip">
                        <div class="lp-kgd__chip-icon">
                            <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M21 10c0 7-9 13-9 13s-9-6-9-13a9 9 0 0 1 18 0z"/><circle cx="12" cy="10" r="3"/></svg>
                        </div>
                        <div class="lp-kgd__chip-text">
                            <strong>Lokasi</strong>
                            <span>{{ $kegiatan->lokasi }}</span>
                        </div>
                    </div>
                @endif

                <div class="lp-kgd__chip">
                    <div class="lp-kgd__chip-icon">
                        <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/><path d="M23 21v-2a4 4 0 0 0-3-3.87"/><path d="M16 3.13a4 4 0 0 1 0 7.75"/></svg>
                    </div>
                    <div class="lp-kgd__chip-text">
                        <strong>Kuota</strong>
                        <span>
                            @if ($kegiatan->kuota === null)
                                Tidak dibatasi<br><small>{{ $kegiatan->kuota_terisi }} terdaftar</small>
                            @elseif ($kegiatan->kuota_penuh)
                                Kuota penuh
                            @else
                                {{ $kegiatan->sisa_kuota }} dari {{ $kegiatan->kuota }} kursi tersisa
                            @endif
                        </span>
                    </div>
                </div>
            </div>

            {{-- Description --}}
            @if ($kegiatan->deskripsi)
                <div class="lp-kgd__desc">{!! $kegiatan->deskripsi !!}</div>
            @endif
        </main>

        {{-- RIGHT: STICKY PANEL --}}
        <aside class="lp-kgd__aside">
            <div class="lp-kgd__panel">
                <div class="lp-kgd__panel-head">
                    <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M9 11l3 3L22 4"/><path d="M21 12v7a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h11"/></svg>
                    <h2>Pendaftaran</h2>
                </div>
                <p class="lp-kgd__panel-sub">Isi data di bawah untuk mendaftar kegiatan ini.</p>

                {{-- Flash messages --}}
                @if (session('error'))
                    <div class="lp-kgd__alert lp-kgd__alert--error">{{ session('error') }}</div>
                @endif
                @if (session('success'))
                    <div class="lp-kgd__alert lp-kgd__alert--success">{{ session('success') }}</div>
                @endif

                {{-- Quota progress (only when kuota exists & status open) --}}
                @if ($kegiatan->kuota && $kegiatan->status === 'dibuka')
                    @php
                        $persen = $kegiatan->kuota > 0
                            ? min(100, round(($kegiatan->kuota_terisi / $kegiatan->kuota) * 100))
                            : 0;
                    @endphp
                    <div class="lp-kgd__quota">
                        <div class="lp-kgd__quota-row">
                            <strong>{{ $kegiatan->kuota_terisi }}<span style="font-weight:500; font-size:13px; color:var(--text-secondary);"> / {{ $kegiatan->kuota }}</span></strong>
                            <span>{{ $persen }}% terisi</span>
                        </div>
                        <div class="lp-kgd__quota-bar">
                            <div class="lp-kgd__quota-fill {{ $kegiatan->kuota_penuh ? 'is-full' : '' }}" style="width: {{ $persen }}%"></div>
                        </div>
                    </div>
                @endif

                {{-- =========================
                     STATE MACHINE
                ========================== --}}
                @if ($kegiatan->status !== 'dibuka')

                    <div class="lp-kgd__state lp-kgd__state--warn">
                        <div class="lp-kgd__state-icon">
                            <svg width="26" height="26" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"/><path d="M12 8v4M12 16h.01"/></svg>
                        </div>
                        <p class="lp-kgd__state-title">Pendaftaran Ditutup</p>
                        <p class="lp-kgd__state-desc">Pendaftaran untuk kegiatan ini sudah tidak dibuka lagi.</p>
                    </div>

                @elseif ($sudahDaftar)

                    <div class="lp-kgd__state lp-kgd__state--ok">
                        <div class="lp-kgd__state-icon">
                            <svg width="26" height="26" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><path d="M20 6L9 17l-5-5"/></svg>
                        </div>
                        <p class="lp-kgd__state-title">Anda Sudah Terdaftar</p>
                        <p class="lp-kgd__state-desc">Terima kasih! Pendaftaran Anda untuk kegiatan ini sudah tercatat.</p>
                    </div>

                @elseif ($kegiatan->kuota_penuh)

                    <div class="lp-kgd__state lp-kgd__state--warn">
                        <div class="lp-kgd__state-icon">
                            <svg width="26" height="26" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"/><path d="M12 8v4M12 16h.01"/></svg>
                        </div>
                        <p class="lp-kgd__state-title">Kuota Sudah Penuh</p>
                        <p class="lp-kgd__state-desc">Maaf, kuota kegiatan ini sudah terisi penuh.</p>
                    </div>

                @elseif (session('anggota'))

                    <form method="POST" action="{{ route('kegiatan.daftar', $kegiatan) }}">
                        @csrf

                        <div class="lp-kgd__field">
                            <label>Nama</label>
                            <div class="lp-kgd__readonly">
                                <span>{{ session('anggota.nama') }}</span>
                            </div>
                        </div>

                        <div class="lp-kgd__field">
                            <label>NIK</label>
                            <div class="lp-kgd__readonly">
                                <span>{{ session('anggota.nik_karyawan') }}</span>
                            </div>
                        </div>

                        @foreach ($kegiatan->customFields as $field)
                            <div class="lp-kgd__field">
                                <label for="cf-{{ $field->id }}">
                                    {{ $field->label }}
                                    @if ($field->wajib) <span class="req">*</span> @endif
                                </label>

                                @if ($field->tipe === 'select')
                                    <select name="custom_fields[{{ $field->id }}]" id="cf-{{ $field->id }}" class="lp-form__input">
                                        <option value="">— Pilih —</option>
                                        @foreach (($field->opsi ?? []) as $opsi)
                                            <option value="{{ $opsi }}" @selected(old("custom_fields.{$field->id}") === $opsi)>{{ $opsi }}</option>
                                        @endforeach
                                    </select>
                                @elseif ($field->tipe === 'number')
                                    <input type="number" name="custom_fields[{{ $field->id }}]" id="cf-{{ $field->id }}" class="lp-form__input" value="{{ old("custom_fields.{$field->id}") }}">
                                @elseif ($field->tipe === 'checkbox')
                                    <label class="lp-kgd__check">
                                        <input type="checkbox" name="custom_fields[{{ $field->id }}]" value="1" @checked(old("custom_fields.{$field->id}"))>
                                        <span>Ya</span>
                                    </label>
                                @else
                                    <input type="text" name="custom_fields[{{ $field->id }}]" id="cf-{{ $field->id }}" class="lp-form__input" value="{{ old("custom_fields.{$field->id}") }}">
                                @endif

                                @error("custom_fields.{$field->id}")
                                    <span class="lp-form__error" style="display:block; margin-top:6px; font-size:12.5px;">{{ $message }}</span>
                                @enderror
                            </div>
                        @endforeach

                        <button type="submit" class="lp-btn lp-btn--primary lp-kgd__submit">
                            Daftar Sekarang
                        </button>
                    </form>

                @else

                    <div class="lp-kgd__state lp-kgd__state--info">
                        <div class="lp-kgd__state-icon">
                            <svg width="26" height="26" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="11" width="18" height="10" rx="2"/><path d="M7 11V7a5 5 0 0 1 10 0v4"/></svg>
                        </div>
                        <p class="lp-kgd__state-title">Login Diperlukan</p>
                        <p class="lp-kgd__state-desc">Silakan login sebagai anggota untuk mendaftar kegiatan ini.</p>
                        <a href="{{ route('anggota.login') }}" class="lp-btn lp-btn--primary lp-kgd__submit" style="display:inline-flex; justify-content:center;">
                            Login Anggota
                        </a>
                    </div>

                @endif
            </div>
        </aside>
    </div>
</div>
@endsection