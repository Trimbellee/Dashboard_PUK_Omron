@extends('landing-page.admin.layouts.app')

@section('title', 'Selamat Datang — Dashboad admin')

@section('content')
@php
    use Carbon\Carbon;
    $now = Carbon::now();
    $jam = (int) $now->format('H');
    if ($jam < 11)      { $sapaan = 'Selamat Pagi'; $emoji = '🌅'; }
    elseif ($jam < 15)  { $sapaan = 'Selamat Siang'; $emoji = '☀️'; }
    elseif ($jam < 19)  { $sapaan = 'Selamat Sore'; $emoji = '🌇'; }
    else                { $sapaan = 'Selamat Malam'; $emoji = '🌙'; }

    $hari = ['Sunday'=>'Minggu','Monday'=>'Senin','Tuesday'=>'Selasa','Wednesday'=>'Rabu',
             'Thursday'=>'Kamis','Friday'=>'Jumat','Saturday'=>'Sabtu'][$now->format('l')];
    $bulan = ['January'=>'Januari','February'=>'Februari','March'=>'Maret','April'=>'April',
              'May'=>'Mei','June'=>'Juni','July'=>'Juli','August'=>'Agustus',
              'September'=>'September','October'=>'Oktober','November'=>'November','December'=>'Desember'][$now->format('F')];
    $tanggal = "$hari, " . $now->format('j') . " $bulan " . $now->format('Y');

    $nama    = session('anggota.nama', 'Admin');
    $jabatan = session('anggota.jabatan_puk', 'Pengelola Konten');
    $inisial = collect(explode(' ', $nama))->map(fn($w) => mb_substr($w, 0, 1))->take(2)->implode('');
    $fotoUrl = session('anggota.foto_url');
@endphp

<div class="lw">

    {{-- ===== HERO CARD ===== --}}
    <section class="lw-hero">

        {{-- Background dekoratif --}}
        <div class="lw-hero__bg" aria-hidden="true">
            <span class="lw-hero__blob lw-hero__blob--1"></span>
            <span class="lw-hero__blob lw-hero__blob--2"></span>
            <span class="lw-hero__grid"></span>
        </div>

        {{-- ===== TOP: sapaan + tanggal ===== --}}
        <header class="lw-hero__top">
            <span class="lw-hero__eyebrow">
                <span class="lw-hero__dot"></span>
                {{ $emoji }} {{ $sapaan }}
            </span>
            <time class="lw-hero__date" datetime="{{ $now->toIso8601String() }}">
                {{ $tanggal }}
            </time>
        </header>

        {{-- ===== BODY: judul + deskripsi ===== --}}
        <div class="lw-hero__body">
            <h1 class="lw-hero__title">
                Halo, <span class="lw-hero__accent">{{ explode(' ', $nama)[0] }}</span>
            </h1>
            <p class="lw-hero__subtitle">
                Selamat datang di <strong>Dashboard Admin PUK OMI</strong>.
                Silakan pilih menu di samping untuk mulai mengelola konten
                website organisasi.
            </p>
        </div>

        {{-- ===== USER CARD ===== --}}
        <div class="lw-hero__user">
            <div class="lw-hero__avatar">
                @if ($fotoUrl)
                    <img src="{{ $fotoUrl }}" alt="{{ $nama }}">
                @else
                    <span>{{ $inisial }}</span>
                @endif
                <span class="lw-hero__status" aria-label="Online"></span>
            </div>

            <div class="lw-hero__user-info">
                <div class="lw-hero__user-label">Masuk sebagai</div>
                <div class="lw-hero__user-name">{{ $nama }}</div>
                <div class="lw-hero__user-role">{{ $jabatan }}</div>
            </div>
        </div>

        {{-- ===== FOOT NOTE ===== --}}
        <footer class="lw-hero__foot">
            <span class="lw-hero__foot-icon" aria-hidden="true">✨</span>
            Semoga hari Anda menyenangkan.
        </footer>

    </section>

</div>
@endsection

@push('page-styles')
<style>
    /* ============================================================
       WELCOME PAGE — Minimalis Modern
       ============================================================ */

    .lw {
        max-width: 820px;
        margin: 0 auto;
        padding: 8px 4px 40px;
    }

    /* ---------- HERO CARD ---------- */
    .lw-hero {
        position: relative;
        overflow: hidden;
        background: var(--bg-surface);
        border: 1px solid var(--border-color);
        border-radius: var(--radius-lg);
        padding: 32px 28px;
        display: flex;
        flex-direction: column;
        gap: 24px;
        box-shadow: var(--shadow-sm);
    }

    /* ---------- Background dekoratif ---------- */
    .lw-hero__bg {
        position: absolute;
        inset: 0;
        pointer-events: none;
        overflow: hidden;
    }

    .lw-hero__blob {
        position: absolute;
        border-radius: 50%;
        filter: blur(70px);
        opacity: 0.35;
    }

    .lw-hero__blob--1 {
        width: 260px;
        height: 260px;
        background: var(--accent);
        top: -100px;
        right: -80px;
    }

    .lw-hero__blob--2 {
        width: 200px;
        height: 200px;
        background: #7bb0f5;
        bottom: -80px;
        left: -60px;
        opacity: 0.25;
    }

    /* Grid halus di belakang */
    .lw-hero__grid {
        position: absolute;
        inset: 0;
        background-image:
            linear-gradient(to right, var(--border-color) 1px, transparent 1px),
            linear-gradient(to bottom, var(--border-color) 1px, transparent 1px);
        background-size: 32px 32px;
        mask-image: radial-gradient(ellipse at top right, black 0%, transparent 70%);
        -webkit-mask-image: radial-gradient(ellipse at top right, black 0%, transparent 70%);
        opacity: 0.35;
    }

    [data-theme="dark"] .lw-hero__blob { opacity: 0.22; }
    [data-theme="dark"] .lw-hero__grid { opacity: 0.15; }

    /* ---------- Top: sapaan + tanggal ---------- */
    .lw-hero__top {
        position: relative;
        z-index: 1;
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: 12px;
        flex-wrap: wrap;
    }

    .lw-hero__eyebrow {
        display: inline-flex;
        align-items: center;
        gap: 8px;
        font-size: 12px;
        font-weight: 700;
        letter-spacing: 0.04em;
        text-transform: uppercase;
        color: var(--accent);
        background: var(--accent-soft);
        padding: 5px 12px;
        border-radius: 999px;
    }

    .lw-hero__dot {
        width: 6px;
        height: 6px;
        border-radius: 50%;
        background: var(--accent);
        box-shadow: 0 0 0 0 var(--accent);
        animation: lw-pulse 2s ease-in-out infinite;
    }

    @keyframes lw-pulse {
        0%   { box-shadow: 0 0 0 0 rgba(26, 95, 180, 0.5); }
        70%  { box-shadow: 0 0 0 8px rgba(26, 95, 180, 0); }
        100% { box-shadow: 0 0 0 0 rgba(26, 95, 180, 0); }
    }

    .lw-hero__date {
        font-size: 12.5px;
        color: var(--text-muted);
        font-weight: 500;
    }

    /* ---------- Body: judul + subtitle ---------- */
    .lw-hero__body {
        position: relative;
        z-index: 1;
        max-width: 520px;
    }

    .lw-hero__title {
        font-family: var(--font-display);
        font-size: 28px;
        font-weight: 800;
        line-height: 1.2;
        color: var(--text-primary);
        margin: 0 0 12px;
        letter-spacing: -0.015em;
    }

    .lw-hero__accent {
        background: var(--accent-gradient);
        -webkit-background-clip: text;
        background-clip: text;
        -webkit-text-fill-color: transparent;
        color: transparent;
    }

    .lw-hero__subtitle {
        font-size: 14.5px;
        line-height: 1.7;
        color: var(--text-secondary);
        margin: 0;
    }

    .lw-hero__subtitle strong {
        color: var(--text-primary);
        font-weight: 700;
    }

    /* ---------- User Card ---------- */
    .lw-hero__user {
        position: relative;
        z-index: 1;
        display: flex;
        align-items: center;
        gap: 14px;
        padding: 16px 18px;
        background: var(--bg-surface-alt);
        border: 1px solid var(--border-color);
        border-radius: var(--radius-md);
        align-self: flex-start;
        max-width: 100%;
    }

    .lw-hero__avatar {
        position: relative;
        width: 48px;
        height: 48px;
        border-radius: 50%;
        overflow: hidden;
        flex-shrink: 0;
        background: var(--accent-gradient);
        color: #fff;
        display: flex;
        align-items: center;
        justify-content: center;
        font-weight: 700;
        font-size: 16px;
        box-shadow: 0 4px 14px rgba(26, 95, 180, 0.28);
    }

    .lw-hero__avatar img {
        width: 100%;
        height: 100%;
        object-fit: cover;
        display: block;
    }

    .lw-hero__status {
        position: absolute;
        bottom: 1px;
        right: 1px;
        width: 11px;
        height: 11px;
        border-radius: 50%;
        background: #22c55e;
        border: 2px solid var(--bg-surface-alt);
    }

    .lw-hero__user-info {
        min-width: 0;
    }

    .lw-hero__user-label {
        font-size: 11px;
        font-weight: 700;
        letter-spacing: 0.05em;
        text-transform: uppercase;
        color: var(--text-muted);
        margin-bottom: 3px;
    }

    .lw-hero__user-name {
        font-size: 14.5px;
        font-weight: 700;
        color: var(--text-primary);
        line-height: 1.3;
        white-space: nowrap;
        overflow: hidden;
        text-overflow: ellipsis;
        max-width: 260px;
    }

    .lw-hero__user-role {
        font-size: 12.5px;
        color: var(--text-secondary);
        margin-top: 2px;
    }

    /* ---------- Foot note ---------- */
    .lw-hero__foot {
        position: relative;
        z-index: 1;
        display: flex;
        align-items: center;
        gap: 8px;
        padding-top: 20px;
        border-top: 1px dashed var(--border-color);
        font-size: 13px;
        color: var(--text-secondary);
    }

    .lw-hero__foot-icon {
        font-size: 15px;
        line-height: 1;
    }

    /* ============================================================
       RESPONSIF
       ============================================================ */

    /* Tablet & Desktop */
    @media (min-width: 640px) {
        .lw {
            padding: 12px 0 48px;
        }

        .lw-hero {
            padding: 40px 40px;
            gap: 28px;
        }

        .lw-hero__title {
            font-size: 32px;
        }

        .lw-hero__subtitle {
            font-size: 15px;
        }

        .lw-hero__user {
            padding: 18px 22px;
            gap: 16px;
        }

        .lw-hero__avatar {
            width: 54px;
            height: 54px;
            font-size: 17px;
        }

        .lw-hero__user-name {
            font-size: 15.5px;
            max-width: 320px;
        }
    }

    /* Mobile kecil */
    @media (max-width: 480px) {
        .lw-hero {
            padding: 22px 18px;
            border-radius: var(--radius-md);
            gap: 18px;
        }

        .lw-hero__top {
            gap: 8px;
        }

        .lw-hero__eyebrow {
            font-size: 10.5px;
            padding: 4px 10px;
            gap: 6px;
        }

        .lw-hero__date {
            font-size: 11.5px;
        }

        .lw-hero__title {
            font-size: 22px;
        }

        .lw-hero__subtitle {
            font-size: 13.5px;
            line-height: 1.65;
        }

        .lw-hero__user {
            padding: 13px 15px;
            gap: 12px;
            width: 100%;
        }

        .lw-hero__avatar {
            width: 42px;
            height: 42px;
            font-size: 14px;
        }

        .lw-hero__status {
            width: 10px;
            height: 10px;
        }

        .lw-hero__user-name {
            font-size: 13.5px;
            max-width: 100%;
        }

        .lw-hero__user-role {
            font-size: 12px;
        }

        .lw-hero__foot {
            font-size: 12.5px;
            padding-top: 16px;
        }
    }

    /* Mobile super kecil */
    @media (max-width: 360px) {
        .lw-hero__title {
            font-size: 20px;
        }

        .lw-hero__eyebrow {
            font-size: 10px;
            padding: 3px 9px;
        }

        .lw-hero__user-label {
            font-size: 10px;
        }
    }

    /* ---------- Reduced motion ---------- */
    @media (prefers-reduced-motion: reduce) {
        .lw-hero__dot {
            animation: none;
        }
    }
</style>
@endpush