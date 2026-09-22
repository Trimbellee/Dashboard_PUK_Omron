{{-- resources/views/landing-page/kegiatan/index.blade.php --}}

@extends('landing-page.layouts.app')

@section('title', 'Kegiatan — PUK OMRON')
@section('meta_description', 'Daftar kegiatan pendidikan, seminar, dan aksi PUK OMRON yang sedang dibuka pendaftarannya.')

@push('styles')
<style>
    /* ============================================================
       HERO / HEADER
    ============================================================ */
    .lp-kgi__hero {
        max-width: 1100px;
        margin: 0 auto;
        padding: 48px 24px 8px;
    }
    .lp-kgi__eyebrow {
        display: inline-flex; align-items: center; gap: 8px;
        padding: 6px 14px; border-radius: 999px;
        background: color-mix(in srgb, var(--accent) 10%, transparent);
        color: var(--accent);
        font-size: 12.5px; font-weight: 700;
        letter-spacing: .04em; text-transform: uppercase;
        border: 1px solid color-mix(in srgb, var(--accent) 22%, transparent);
        margin-bottom: 14px;
    }
    .lp-kgi__eyebrow-dot {
        width: 7px; height: 7px; border-radius: 50%;
        background: var(--accent);
        box-shadow: 0 0 0 3px color-mix(in srgb, var(--accent) 22%, transparent);
    }
    .lp-kgi__title {
        font-family: var(--font-display);
        font-size: clamp(26px, 3.6vw, 40px);
        font-weight: 800;
        letter-spacing: -0.02em;
        line-height: 1.15;
        margin: 0 0 10px;
        color: var(--text-primary);
    }
    .lp-kgi__desc {
        font-size: 16px;
        line-height: 1.6;
        color: var(--text-secondary);
        max-width: 620px;
        margin: 0;
    }

    /* ============================================================
       BODY
    ============================================================ */
    .lp-kgi__body {
        max-width: 1100px;
        margin: 0 auto;
        padding: 32px 24px 80px;
    }

    /* ============================================================
       FILTER TABS
    ============================================================ */
    .lp-kgi__filters {
        display: flex; gap: 8px; flex-wrap: wrap;
        margin-bottom: 28px;
    }
    .lp-kgi__filter {
        display: inline-flex; align-items: center; gap: 8px;
        font-size: 13.5px; font-weight: 600;
        padding: 9px 16px;
        border-radius: 999px;
        border: 1px solid var(--border-color);
        background: var(--bg-surface);
        color: var(--text-primary);
        text-decoration: none;
        transition: all .25s ease;
        white-space: nowrap;
    }
    .lp-kgi__filter:hover {
        border-color: color-mix(in srgb, var(--accent) 45%, var(--border-color));
        color: var(--accent);
        transform: translateY(-1px);
    }
    .lp-kgi__filter.is-active {
        background: var(--accent);
        color: #fff;
        border-color: var(--accent);
        box-shadow: 0 6px 18px -8px color-mix(in srgb, var(--accent) 60%, transparent);
    }
    .lp-kgi__filter-count {
        display: inline-flex; align-items: center; justify-content: center;
        min-width: 20px; height: 20px;
        padding: 0 6px;
        border-radius: 999px;
        font-size: 11px; font-weight: 700;
        background: color-mix(in srgb, var(--text-secondary) 15%, transparent);
        color: inherit;
    }
    .lp-kgi__filter.is-active .lp-kgi__filter-count {
        background: rgba(255,255,255,.22);
    }

    /* ============================================================
       GRID
    ============================================================ */
    .lp-kgi__grid {
        display: grid;
        grid-template-columns: repeat(auto-fill, minmax(280px, 1fr));
        gap: 22px;
    }

    /* ============================================================
       CARD
    ============================================================ */
    .lp-kgi__card {
        display: flex; flex-direction: column;
        background: var(--bg-surface);
        border: 1px solid var(--border-color);
        border-radius: 18px;
        overflow: hidden;
        text-decoration: none; color: inherit;
        position: relative;
        transition: transform .35s cubic-bezier(.22,1,.36,1),
                    box-shadow .35s ease,
                    border-color .25s ease;
    }
    .lp-kgi__card:hover {
        transform: translateY(-6px);
        box-shadow: 0 20px 40px -18px rgba(15, 23, 42, .28),
                    0 8px 20px -12px rgba(15, 23, 42, .18);
        border-color: color-mix(in srgb, var(--accent) 35%, var(--border-color));
    }

    /* Image */
    .lp-kgi__image {
        position: relative;
        aspect-ratio: 16/10;
        overflow: hidden;
        background: linear-gradient(135deg,
            color-mix(in srgb, var(--accent) 10%, var(--bg-surface-alt)),
            var(--bg-surface-alt));
    }
    .lp-kgi__image img {
        width: 100%; height: 100%; object-fit: cover; display: block;
        transition: transform .6s cubic-bezier(.22,1,.36,1);
    }
    .lp-kgi__card:hover .lp-kgi__image img { transform: scale(1.06); }

    .lp-kgi__placeholder {
        display: flex; align-items: center; justify-content: center;
        width: 100%; height: 100%;
        color: color-mix(in srgb, var(--accent) 55%, transparent);
        background:
            radial-gradient(circle at 30% 20%, color-mix(in srgb, var(--accent) 18%, transparent), transparent 60%),
            linear-gradient(135deg, var(--bg-surface-alt), color-mix(in srgb, var(--accent) 8%, var(--bg-surface-alt)));
    }

    .lp-kgi__image-fade {
        position: absolute; inset: 0;
        background: linear-gradient(to top, rgba(0,0,0,.35) 0%, transparent 45%);
        pointer-events: none;
    }

    /* Badges */
    .lp-kgi__badge, .lp-kgi__badge-full {
        position: absolute; top: 12px;
        display: inline-flex; align-items: center;
        font-size: 11.5px; font-weight: 700;
        letter-spacing: .02em; text-transform: capitalize;
        padding: 5px 11px; border-radius: 999px;
        backdrop-filter: blur(10px);
        -webkit-backdrop-filter: blur(10px);
        color: #fff;
        border: 1px solid rgba(255,255,255,.22);
        box-shadow: 0 4px 14px rgba(0,0,0,.15);
    }
    .lp-kgi__badge { left: 12px; background: rgba(99, 102, 241, .9); }
    .lp-kgi__badge--seminar  { background: rgba(14, 116, 144, .9); }
    .lp-kgi__badge--aksi     { background: rgba(194, 65, 12, .9); }
    .lp-kgi__badge--lainnya  { background: rgba(75, 85, 99, .9); }
    .lp-kgi__badge-full {
        right: 12px;
        background: rgba(185, 28, 28, .92);
        text-transform: uppercase; letter-spacing: .06em;
    }

    /* Body */
    .lp-kgi__body-card {
        padding: 18px 20px 20px;
        display: flex; flex-direction: column; gap: 8px; flex: 1;
    }

    .lp-kgi__tingkat {
        display: inline-flex; align-items: center; gap: 6px;
        align-self: flex-start;
        font-size: 11px; font-weight: 700;
        color: var(--accent);
        text-transform: uppercase; letter-spacing: .06em;
        padding: 3px 9px; border-radius: 6px;
        background: color-mix(in srgb, var(--accent) 10%, transparent);
    }

    .lp-kgi__card-title {
        font-size: 16px; font-weight: 700;
        line-height: 1.4; margin: 2px 0 4px;
        color: var(--text-primary);
        display: -webkit-box;
        -webkit-line-clamp: 2;
        -webkit-box-orient: vertical;
        overflow: hidden;
        transition: color .2s ease;
    }
    .lp-kgi__card:hover .lp-kgi__card-title { color: var(--accent); }

    .lp-kgi__meta-row {
        display: flex; flex-wrap: wrap; gap: 4px 14px;
    }
    .lp-kgi__card-meta {
        display: inline-flex; align-items: center; gap: 6px;
        font-size: 12.5px; color: var(--text-secondary);
        font-weight: 500;
        margin: 0;
    }
    .lp-kgi__card-meta svg { opacity: .75; flex-shrink: 0; }

    /* Quota */
    .lp-kgi__quota {
        margin-top: auto; padding-top: 12px;
        display: flex; flex-direction: column; gap: 6px;
    }
    .lp-kgi__quota-bar {
        width: 100%; height: 5px; border-radius: 999px;
        background: color-mix(in srgb, var(--border-color) 70%, transparent);
        overflow: hidden;
    }
    .lp-kgi__quota-fill {
        height: 100%; border-radius: 999px;
        background: linear-gradient(90deg,
            color-mix(in srgb, var(--accent) 70%, #22c55e),
            var(--accent));
        transition: width .5s cubic-bezier(.22,1,.36,1);
    }
    .lp-kgi__quota-fill.is-full {
        background: linear-gradient(90deg, #ef4444, #b91c1c);
    }
    .lp-kgi__quota-text {
        font-size: 12px; color: var(--text-secondary);
    }
    .lp-kgi__quota-text strong { color: var(--text-primary); }
    .lp-kgi__quota-text.is-full { color: #b91c1c; font-weight: 600; }

    /* CTA */
    .lp-kgi__cta {
        margin-top: 10px;
        display: inline-flex; align-items: center; justify-content: space-between;
        gap: 8px;
        padding: 10px 14px;
        border-radius: 10px;
        background: color-mix(in srgb, var(--accent) 10%, transparent);
        color: var(--accent);
        font-size: 13px; font-weight: 700;
        transition: background .25s ease, color .25s ease;
    }
    .lp-kgi__cta svg { transition: transform .3s ease; }
    .lp-kgi__card:hover .lp-kgi__cta {
        background: var(--accent); color: #fff;
    }
    .lp-kgi__card:hover .lp-kgi__cta svg { transform: translateX(4px); }

    /* ============================================================
       EMPTY STATE
    ============================================================ */
    .lp-kgi__empty {
        text-align: center;
        padding: 72px 24px;
        background: var(--bg-surface);
        border: 1px dashed var(--border-color);
        border-radius: 20px;
    }
    .lp-kgi__empty-icon {
        width: 72px; height: 72px;
        border-radius: 50%;
        display: inline-flex; align-items: center; justify-content: center;
        background: color-mix(in srgb, var(--accent) 10%, transparent);
        color: var(--accent);
        margin-bottom: 18px;
    }
    .lp-kgi__empty h3 {
        font-family: var(--font-display);
        font-size: 19px; font-weight: 700;
        color: var(--text-primary);
        margin: 0 0 8px;
        letter-spacing: -0.01em;
    }
    .lp-kgi__empty p {
        font-size: 14.5px;
        color: var(--text-secondary);
        margin: 0 auto;
        max-width: 420px;
        line-height: 1.6;
    }

    /* ============================================================
       PAGINATION
    ============================================================ */
    .lp-kgi__pagination {
        margin-top: 40px;
        display: flex; justify-content: center;
    }
    .lp-kgi__pagination nav svg { width: 16px; height: 16px; }

    /* ============================================================
       RESPONSIVE
    ============================================================ */
    @media (max-width: 720px) {
        .lp-kgi__hero { padding: 32px 18px 4px; }
        .lp-kgi__body { padding: 24px 18px 60px; }
        .lp-kgi__filters {
            flex-wrap: nowrap;
            overflow-x: auto;
            padding-bottom: 6px;
            margin-left: -18px;
            margin-right: -18px;
            padding-left: 18px;
            padding-right: 18px;
            scrollbar-width: none;
            -ms-overflow-style: none;
        }
        .lp-kgi__filters::-webkit-scrollbar { display: none; }
        .lp-kgi__grid { gap: 16px; grid-template-columns: 1fr; }
        .lp-kgi__empty { padding: 56px 20px; }
    }

    @media (prefers-reduced-motion: reduce) {
        .lp-kgi__card,
        .lp-kgi__card img,
        .lp-kgi__cta svg,
        .lp-kgi__filter { transition: none !important; }
    }
</style>
@endpush

@section('content')
@php
    // Hitung jumlah per jenis untuk counter di filter tab.
    // Karena filter aktif memakai paginator yang sama, kita hitung dari query terpisah
    // agar counter tetap menampilkan total semua jenis (konsisten saat difilter).
    $counts = [
        'all'       => \App\Models\Kegiatan::where('status', 'dibuka')->count(),
        'pendidikan'=> \App\Models\Kegiatan::where('status', 'dibuka')->where('jenis', 'pendidikan')->count(),
        'seminar'   => \App\Models\Kegiatan::where('status', 'dibuka')->where('jenis', 'seminar')->count(),
        'aksi'      => \App\Models\Kegiatan::where('status', 'dibuka')->where('jenis', 'aksi')->count(),
        'lainnya'   => \App\Models\Kegiatan::where('status', 'dibuka')->where('jenis', 'lainnya')->count(),
    ];
    $activeJenis = request('jenis');
@endphp

<div class="lp-kg-index">

    {{-- HERO --}}
    <div class="lp-kgi__hero">
        <span class="lp-kgi__eyebrow">
            <span class="lp-kgi__eyebrow-dot"></span>
            Kegiatan
        </span>
        <h1 class="lp-kgi__title">Kegiatan PUK OMRON</h1>
        <p class="lp-kgi__desc">
            Pendidikan, seminar, dan aksi yang sedang dibuka pendaftarannya. Pilih kegiatan yang ingin Anda ikuti.
        </p>
    </div>

    {{-- BODY --}}
    <div class="lp-kgi__body">

        {{-- FILTER TABS --}}
        <div class="lp-kgi__filters">
            <a href="{{ route('kegiatan.index') }}"
               class="lp-kgi__filter {{ !$activeJenis ? 'is-active' : '' }}">
                Semua
                <span class="lp-kgi__filter-count">{{ $counts['all'] }}</span>
            </a>
            @foreach (['pendidikan' => 'Pendidikan', 'seminar' => 'Seminar', 'aksi' => 'Aksi', 'lainnya' => 'Lainnya'] as $value => $label)
                <a href="{{ route('kegiatan.index', ['jenis' => $value]) }}"
                   class="lp-kgi__filter {{ $activeJenis === $value ? 'is-active' : '' }}">
                    {{ $label }}
                    <span class="lp-kgi__filter-count">{{ $counts[$value] }}</span>
                </a>
            @endforeach
        </div>

        {{-- GRID / EMPTY --}}
        @if ($kegiatan->isEmpty())

            <div class="lp-kgi__empty">
                <div class="lp-kgi__empty-icon">
                    <svg width="32" height="32" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round">
                        <rect x="3" y="4" width="18" height="18" rx="2"/>
                        <path d="M16 2v4M8 2v4M3 10h18"/>
                        <path d="M8 14h.01M12 14h.01M16 14h.01M8 18h.01M12 18h.01"/>
                    </svg>
                </div>
                <h3>Belum Ada Kegiatan</h3>
                <p>
                    @if ($activeJenis)
                        Tidak ada kegiatan dengan jenis ini saat ini. Coba pilih kategori lain atau lihat semua kegiatan.
                    @else
                        Belum ada kegiatan yang dibuka saat ini. Pantau terus halaman ini untuk info terbaru.
                    @endif
                </p>
            </div>

        @else

            <div class="lp-kgi__grid">
                @foreach ($kegiatan as $item)
                    @php
                        $penuh = $item->kuota_penuh;
                        $persen = ($item->kuota && $item->kuota > 0)
                            ? min(100, round(($item->kuota_terisi / $item->kuota) * 100))
                            : null;
                    @endphp

                    <a href="{{ route('kegiatan.show', $item) }}" class="lp-kgi__card">
                        <div class="lp-kgi__image">
                            @if ($item->thumbnail)
                                <img src="{{ asset('storage/' . $item->thumbnail) }}" alt="{{ $item->judul }}" loading="lazy">
                            @else
                                <div class="lp-kgi__placeholder">
                                    <svg width="36" height="36" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="4" width="18" height="18" rx="2"/><path d="M16 2v4M8 2v4M3 10h18"/></svg>
                                </div>
                            @endif
                            <div class="lp-kgi__image-fade"></div>
                            <span class="lp-kgi__badge lp-kgi__badge--{{ $item->jenis }}">{{ ucfirst($item->jenis) }}</span>
                            @if ($penuh)
                                <span class="lp-kgi__badge-full">Penuh</span>
                            @endif
                        </div>

                        <div class="lp-kgi__body-card">
                            @if ($item->jenis === 'pendidikan' && $item->tingkat_kategori)
                                <span class="lp-kgi__tingkat">
                                    {{ ucfirst($item->tingkat_kategori) }}
                                    @if ($item->tingkat_keterangan) · {{ $item->tingkat_keterangan }} @endif
                                </span>
                            @endif

                            <h3 class="lp-kgi__card-title">{{ $item->judul }}</h3>

                            <div class="lp-kgi__meta-row">
                                <p class="lp-kgi__card-meta">
                                    <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="4" width="18" height="18" rx="2"/><path d="M16 2v4M8 2v4M3 10h18"/></svg>
                                    {{ $item->tanggal_mulai->translatedFormat('d M Y') }}
                                </p>
                                <p class="lp-kgi__card-meta">
                                    <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/></svg>
                                    {{ $item->kuota_terisi }} pendaftar
                                </p>
                            </div>

                            @if ($item->kuota)
                                <div class="lp-kgi__quota">
                                    <div class="lp-kgi__quota-bar">
                                        <div class="lp-kgi__quota-fill {{ $penuh ? 'is-full' : '' }}" style="width: {{ $persen }}%"></div>
                                    </div>
                                    <span class="lp-kgi__quota-text {{ $penuh ? 'is-full' : '' }}">
                                        @if ($penuh)
                                            Kuota penuh
                                        @else
                                            <strong>{{ $item->sisa_kuota }}</strong> dari {{ $item->kuota }} kursi tersisa
                                        @endif
                                    </span>
                                </div>
                            @endif

                            <div class="lp-kgi__cta">
                                <span>Lihat Detail</span>
                                <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><path d="M5 12h14M12 5l7 7-7 7"/></svg>
                            </div>
                        </div>
                    </a>
                @endforeach
            </div>

            {{-- PAGINATION --}}
            @if ($kegiatan->hasPages())
                <div class="lp-kgi__pagination">
                    {{ $kegiatan->links() }}
                </div>
            @endif

        @endif

    </div>
</div>
@endsection