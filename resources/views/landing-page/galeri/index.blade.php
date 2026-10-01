@extends('landing-page.layouts.app')

@php
    // Data pratinjau link (WhatsApp/Facebook membaca meta tag dari server, bukan dari JavaScript).
    // Hanya terisi saat halaman dibuka dengan ?media=ID dan media itu sudah published.
    $ogMedia = $focusMedia ?? null;

    if ($ogMedia) {
        $ogJudul     = $ogMedia->judul ?: ($ogMedia->kegiatan ?: 'Media galeri');
        $ogDeskripsi = ($ogMedia->type === 'video' ? 'Video' : 'Foto')
            . ' dokumentasi PUK OMRON'
            . ($ogMedia->kegiatan ? ' — ' . $ogMedia->kegiatan : '');
        $ogImage     = $ogMedia->thumbnail_url ?: asset('images/placeholder-media.png');
        if (!preg_match('#^https?://#i', $ogImage)) {
            $ogImage = url($ogImage);
        }
        $ogUrl = route('galeri.index', ['media' => $ogMedia->id]);
    }
@endphp

@push('styles')
    @vite(['resources/css/landing-page/galeri/galeri.css'])

    @if ($ogMedia)
        <meta property="og:type" content="website">
        <meta property="og:site_name" content="PUK OMRON">
        <meta property="og:locale" content="id_ID">
        <meta property="og:title" content="{{ $ogJudul }}">
        <meta property="og:description" content="{{ $ogDeskripsi }}">
        <meta property="og:url" content="{{ $ogUrl }}">
        <meta property="og:image" content="{{ $ogImage }}">
        <meta property="og:image:alt" content="{{ $ogJudul }}">
        <meta name="twitter:card" content="summary_large_image">
        <meta name="twitter:title" content="{{ $ogJudul }}">
        <meta name="twitter:description" content="{{ $ogDeskripsi }}">
        <meta name="twitter:image" content="{{ $ogImage }}">
    @endif
@endpush

@section('title', $ogMedia ? $ogJudul . ' - Galeri PUK OMRON' : 'Galeri - PUK OMRON')
@section('meta_description', $ogMedia ? $ogDeskripsi : 'Dokumentasi foto dan video kegiatan PUK OMRON.')

@section('content')
@php
    // Bangun URL dengan mempertahankan filter aktif; override = nilai yang diganti.
    // "#hasil" membuat halaman langsung menggulir ke area toolbar/hasil setelah filter.
    $url = function (array $override = []) use ($filters) {
        $q = array_merge($filters, $override);
        if (($q['sort'] ?? '') === 'terbaru') {
            unset($q['sort']);
        }
        return route('galeri.index', array_filter($q, fn ($v) => $v !== null && $v !== '')) . '#hasil';
    };

    $hasFilter   = $filters['q'] || $filters['type'] || $filters['kategori'] || $filters['kegiatan'];
    $placeholder = asset('images/placeholder-media.png');

    // Kartu pertama dibuat besar (bento) di halaman 1 jika datanya cukup
    $useFeature = $galeriList->currentPage() === 1 && $galeriList->count() >= 5;
@endphp

<section class="lp-gx-page">
    <div class="lp-container">

        {{-- ===== HERO ===== --}}
        <header class="lp-gx-hero">
            <div class="lp-gx-hero__bg" aria-hidden="true">
                <span class="lp-gx-blob lp-gx-blob--1"></span>
                <span class="lp-gx-blob lp-gx-blob--2"></span>
            </div>

            <div class="lp-gx-hero__inner">
                <div class="lp-gx-hero__text">
                    <span class="lp-gx-eyebrow"><span class="lp-gx-eyebrow__dot"></span> Galeri</span>
                    <h1 class="lp-gx-title">Dokumentasi <span class="lp-gx-title__accent">Kegiatan</span> PUK OMRON</h1>
                    <p class="lp-gx-lead">
                        Foto dan video perjuangan, pendidikan, serta kebersamaan anggota. Cari, saring, dan putar langsung tanpa meninggalkan halaman.
                    </p>
                </div>

                <div class="lp-gx-stats">
                    <div class="lp-gx-stat">
                        <span class="lp-gx-stat__value" data-count="{{ $stats['total'] }}">{{ $stats['total'] }}</span>
                        <span class="lp-gx-stat__label">Total media</span>
                    </div>
                    <div class="lp-gx-stat">
                        <span class="lp-gx-stat__value" data-count="{{ $stats['album'] }}">{{ $stats['album'] }}</span>
                        <span class="lp-gx-stat__label">Album kegiatan</span>
                    </div>
                    <div class="lp-gx-stat">
                        <span class="lp-gx-stat__value" data-count="{{ $stats['image'] }}">{{ $stats['image'] }}</span>
                        <span class="lp-gx-stat__label">Foto</span>
                    </div>
                    <div class="lp-gx-stat">
                        <span class="lp-gx-stat__value" data-count="{{ $stats['video'] }}">{{ $stats['video'] }}</span>
                        <span class="lp-gx-stat__label">Video</span>
                    </div>
                </div>
            </div>
        </header>

        {{-- ===== TOOLBAR: PENCARIAN, KATEGORI, URUTAN ===== --}}
        <form id="hasil" action="{{ route('galeri.index') }}#hasil" method="GET" class="lp-gx-toolbar" x-data>
            @if ($filters['type'])
                <input type="hidden" name="type" value="{{ $filters['type'] }}">
            @endif
            @if ($filters['kegiatan'])
                <input type="hidden" name="kegiatan" value="{{ $filters['kegiatan'] }}">
            @endif

            <label class="lp-gx-search">
                <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"><circle cx="11" cy="11" r="7"/><path d="m21 21-4.3-4.3"/></svg>
                <span class="lp-gx-sr">Cari media</span>
                <input type="search" name="q" value="{{ $filters['q'] }}"
                       placeholder="Cari judul atau nama kegiatan..."
                       autocomplete="off"
                       @input.debounce.600ms="sessionStorage.setItem('lpGaleriFocus', '1'); $el.form.requestSubmit()"
                       x-init="if (sessionStorage.getItem('lpGaleriFocus')) {
                                   sessionStorage.removeItem('lpGaleriFocus');
                                   $el.focus();
                                   $el.setSelectionRange($el.value.length, $el.value.length);
                               }">
                @if ($filters['q'])
                    <a href="{{ $url(['q' => '']) }}" class="lp-gx-search__clear" aria-label="Hapus pencarian">
                        <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3" stroke-linecap="round"><path d="M6 6l12 12M18 6 6 18"/></svg>
                    </a>
                @else
                    <kbd class="lp-gx-kbd" title="Tekan / untuk mencari">/</kbd>
                @endif
            </label>

            <div class="lp-gx-selects">
                <label class="lp-gx-select">
                    <span class="lp-gx-sr">Kategori</span>
                    <select name="kategori" onchange="this.form.submit()">
                        <option value="">Semua kategori</option>
                        @foreach (\App\Models\Gallery::KATEGORI_LIST as $value => $label)
                            <option value="{{ $value }}" @selected($filters['kategori'] === $value)>{{ $label }}</option>
                        @endforeach
                    </select>
                </label>
                <label class="lp-gx-select">
                    <span class="lp-gx-sr">Urutkan</span>
                    <select name="sort" onchange="this.form.submit()">
                        <option value="terbaru" @selected($filters['sort'] === 'terbaru')>Terbaru</option>
                        <option value="terlama" @selected($filters['sort'] === 'terlama')>Terlama</option>
                    </select>
                </label>
            </div>

            <div class="lp-gx-actions">
                <button type="submit" class="lp-gx-btn lp-gx-btn--primary">Cari</button>
                @if ($hasFilter)
                    <a href="{{ route('galeri.index') }}#hasil" class="lp-gx-btn lp-gx-btn--ghost">Reset</a>
                @endif
            </div>
        </form>

        {{-- ===== ALBUM (KEGIATAN) ===== --}}
        @if ($albums->isNotEmpty())
            <div class="lp-gx-albums">
                <span class="lp-gx-albums__label">Album</span>
                <div class="lp-gx-albums__list">
                    <a href="{{ $url(['kegiatan' => '']) }}"
                       class="lp-album-chip {{ $filters['kegiatan'] === '' ? 'active' : '' }}">Semua</a>
                    @foreach ($albums as $album)
                        <a href="{{ $url(['kegiatan' => $album->kegiatan]) }}"
                           class="lp-album-chip {{ $filters['kegiatan'] === $album->kegiatan ? 'active' : '' }}">
                            {{ $album->kegiatan }} <em>{{ $album->total }}</em>
                        </a>
                    @endforeach
                </div>
            </div>
        @endif

        {{-- ===== TAB TIPE + JUMLAH HASIL ===== --}}
        <div class="lp-gx-bar">
            <nav class="lp-gx-seg" aria-label="Tipe media">
                <a href="{{ $url(['type' => '']) }}" class="{{ $filters['type'] === '' ? 'active' : '' }}">
                    Semua <small>{{ $stats['total'] }}</small>
                </a>
                <a href="{{ $url(['type' => 'image']) }}" class="{{ $filters['type'] === 'image' ? 'active' : '' }}">
                    <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2"><rect x="3" y="3" width="18" height="18" rx="2"/><circle cx="8.5" cy="9.5" r="1.5"/><path d="m21 15-5-5L5 21"/></svg>
                    Foto <small>{{ $stats['image'] }}</small>
                </a>
                <a href="{{ $url(['type' => 'video']) }}" class="{{ $filters['type'] === 'video' ? 'active' : '' }}">
                    <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2"><path d="m10 8 6 4-6 4V8z"/><rect x="3" y="4" width="18" height="16" rx="2"/></svg>
                    Video <small>{{ $stats['video'] }}</small>
                </a>
            </nav>

            <p class="lp-gx-count">
                @if ($galeriList->total() > 0)
                    Menampilkan <strong>{{ $galeriList->firstItem() }}–{{ $galeriList->lastItem() }}</strong>
                    dari <strong>{{ $galeriList->total() }}</strong> media
                @else
                    Tidak ada media
                @endif
            </p>
        </div>

        {{-- ===== CHIP FILTER AKTIF ===== --}}
        @if ($hasFilter)
            <div class="lp-gx-chips">
                <span class="lp-gx-chips__label">Filter aktif:</span>

                @if ($filters['q'])
                    <a href="{{ $url(['q' => '']) }}" class="lp-filter-chip">
                        "{{ $filters['q'] }}" <span aria-hidden="true">&times;</span>
                    </a>
                @endif
                @if ($filters['type'])
                    <a href="{{ $url(['type' => '']) }}" class="lp-filter-chip">
                        {{ $filters['type'] === 'video' ? 'Video' : 'Foto' }} <span aria-hidden="true">&times;</span>
                    </a>
                @endif
                @if ($filters['kategori'])
                    <a href="{{ $url(['kategori' => '']) }}" class="lp-filter-chip">
                        {{ \App\Models\Gallery::KATEGORI_LIST[$filters['kategori']] ?? $filters['kategori'] }} <span aria-hidden="true">&times;</span>
                    </a>
                @endif
                @if ($filters['kegiatan'])
                    <a href="{{ $url(['kegiatan' => '']) }}" class="lp-filter-chip">
                        Album: {{ $filters['kegiatan'] }} <span aria-hidden="true">&times;</span>
                    </a>
                @endif
            </div>
        @endif

        {{-- Penanda JS aktif sebelum grid dirender: animasi "muncul" hanya berjalan jika JS ada --}}
        <script>document.documentElement.classList.add('lp-gx-js');</script>

        {{-- ===== GRID GALERI ===== --}}
        <div class="lp-gx-grid">
            @forelse ($galeriList as $item)
                @php
                    $kategoriLabel = \App\Models\Gallery::KATEGORI_LIST[$item->kategori] ?? null;
                    $isVideo       = $item->type === 'video';
                    $isFeature     = $useFeature && $loop->first;
                    $tanggal       = $item->published_at
                        ? \Illuminate\Support\Carbon::parse($item->published_at)->translatedFormat('d M Y')
                        : null;
                    $meta = collect([$item->kegiatan, $tanggal])->filter()->implode(' · ');
                @endphp
                <article class="lp-gx-card {{ $isFeature ? 'lp-gx-card--feature' : '' }}" style="--i: {{ $loop->index }}">
                    <div class="lp-gx-card__media"
                         role="button"
                         tabindex="0"
                         data-galeri-open="{{ $item->id }}"
                         aria-label="Buka {{ $isVideo ? 'video' : 'foto' }}: {{ $item->judul ?? 'Tanpa judul' }}">

                        @if ($item->thumbnail_url)
                            <img class="lp-gx-card__img"
                                 src="{{ $item->thumbnail_url }}"
                                 alt="{{ $item->judul ?? 'Media' }}"
                                 loading="{{ $loop->index < 4 ? 'eager' : 'lazy' }}"
                                 decoding="async"
                                 onerror="this.onerror=null;this.src='{{ $placeholder }}'">
                        @else
                            <div class="lp-gx-card__placeholder">{{ $isVideo ? '🎬' : '🖼️' }}</div>
                        @endif

                        <span class="lp-gx-card__shade"></span>

                        <div class="lp-gx-card__top">
                            <span class="lp-gx-badge lp-gx-badge--{{ $item->type }}"><i></i>{{ $isVideo ? 'Video' : 'Foto' }}</span>
                            @if ($kategoriLabel)
                                <span class="lp-gx-badge lp-gx-badge--{{ $item->kategori }}">{{ $kategoriLabel }}</span>
                            @endif
                        </div>

                        @if ($isVideo)
                            <span class="lp-gx-play" aria-hidden="true">
                                <svg width="22" height="22" viewBox="0 0 24 24" fill="currentColor"><path d="M8 5v14l11-7z"/></svg>
                            </span>
                        @endif

                        <div class="lp-gx-card__info">
                            <h3 class="lp-gx-card__title">{{ $item->judul ?? 'Tanpa judul' }}</h3>
                            @if ($meta !== '')
                                <p class="lp-gx-card__meta">{{ $meta }}</p>
                            @endif
                        </div>
                    </div>
                </article>
            @empty
                <div class="lp-gx-empty">
                    <span class="lp-gx-empty__icon">🖼️</span>
                    @if ($hasFilter)
                        <p class="lp-gx-empty__title">Tidak ada media yang cocok</p>
                        <p>Coba kata kunci lain atau <a href="{{ route('galeri.index') }}#hasil">reset filter</a> untuk melihat semua galeri.</p>
                    @else
                        <p class="lp-gx-empty__title">Belum ada media galeri</p>
                        <p>Dokumentasi kegiatan akan tampil di sini setelah dipublikasikan.</p>
                    @endif
                </div>
            @endforelse
        </div>

        {{-- ===== PAGINATION ===== --}}
        @if ($galeriList->hasPages())
            <div class="lp-pagination">
                {{ $galeriList->links('vendor.pagination.lp') }}
                <p class="lp-gx-pageinfo">Halaman {{ $galeriList->currentPage() }} dari {{ $galeriList->lastPage() }}</p>
            </div>
        @endif
    </div>
</section>

<button type="button" class="lp-gx-top" id="lpGxTop" aria-label="Kembali ke atas">
    <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.6" stroke-linecap="round" stroke-linejoin="round"><path d="m18 15-6-6-6 6"/></svg>
</button>

@include('landing-page.partials.galeri-lightbox', [
    'mediaItems' => $galeriList,
    'focusMedia' => $focusMedia,
    'syncUrl'    => true,
])

<script>
(function () {
    const reduced = window.matchMedia('(prefers-reduced-motion: reduce)').matches;

    // 1) Kartu muncul bertahap saat masuk layar
    const cards = document.querySelectorAll('.lp-gx-card');
    if ('IntersectionObserver' in window && !reduced) {
        const io = new IntersectionObserver((entries) => {
            entries.forEach((en) => {
                if (en.isIntersecting) {
                    en.target.classList.add('is-visible');
                    io.unobserve(en.target);
                }
            });
        }, { threshold: 0.08, rootMargin: '0px 0px -4% 0px' });
        cards.forEach((c) => io.observe(c));
    } else {
        cards.forEach((c) => c.classList.add('is-visible'));
    }

    // 2) Hitung naik pada angka statistik
    if (!reduced) {
        document.querySelectorAll('[data-count]').forEach((el) => {
            const target = parseInt(el.dataset.count, 10) || 0;
            if (target < 2) return;
            const start = performance.now();
            const dur = 900;
            const tick = (now) => {
                const p = Math.min((now - start) / dur, 1);
                el.textContent = Math.round(target * (1 - Math.pow(1 - p, 3))).toLocaleString('id-ID');
                if (p < 1) requestAnimationFrame(tick);
            };
            el.textContent = '0';
            requestAnimationFrame(tick);
        });
    }

    // 3) Tekan "/" untuk fokus ke kolom pencarian
    document.addEventListener('keydown', (e) => {
        if (e.key !== '/' || e.ctrlKey || e.metaKey || e.altKey) return;
        const t = e.target;
        if (t && (t.tagName === 'INPUT' || t.tagName === 'TEXTAREA' || t.tagName === 'SELECT' || t.isContentEditable)) return;
        const input = document.querySelector('.lp-gx-search input');
        if (input) {
            e.preventDefault();
            input.focus();
        }
    });

    // 4) Album aktif digeser ke tengah daftar
    const activeAlbum = document.querySelector('.lp-album-chip.active');
    const albumList = document.querySelector('.lp-gx-albums__list');
    if (activeAlbum && albumList) {
        albumList.scrollLeft = activeAlbum.offsetLeft - (albumList.clientWidth - activeAlbum.clientWidth) / 2;
    }

    // 5) Tombol kembali ke atas
    const topBtn = document.getElementById('lpGxTop');
    if (topBtn) {
        const toggle = () => topBtn.classList.toggle('is-shown', window.scrollY > 700);
        window.addEventListener('scroll', toggle, { passive: true });
        toggle();
        topBtn.addEventListener('click', () => window.scrollTo({ top: 0, behavior: reduced ? 'auto' : 'smooth' }));
    }
})();
</script>
@endsection
