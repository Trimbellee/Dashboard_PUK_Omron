@extends('landing-page.layouts.app')

@push('styles')
    @vite(['resources/css/landing-page/galeri/galeri.css'])
@endpush

@section('title', 'Galeri - PUK OMRON')

@section('content')
@php
    // Bangun URL dengan mempertahankan filter aktif; override = nilai yang diganti
    $url = function (array $override = []) use ($filters) {
        $q = array_merge($filters, $override);
        if (($q['sort'] ?? '') === 'terbaru') {
            unset($q['sort']);
        }
        return route('galeri.index', array_filter($q, fn ($v) => $v !== null && $v !== ''));
    };

    $hasFilter = $filters['q'] || $filters['type'] || $filters['kategori'] || $filters['kegiatan'];
    $placeholder = asset('images/placeholder-media.png');
@endphp

<section class="lp-section lp-galeri-page">
    <div class="lp-container">

        {{-- ===== HEADER ===== --}}
        <div class="lp-galeri-header">
            <div class="lp-galeri-header__text">
                <span class="lp-hero__badge">Galeri</span>
                <h1 class="lp-section__title">Semua Galeri Kegiatan</h1>
                <p class="lp-section__desc">Dokumentasi foto dan video kegiatan PUK OMRON.</p>
            </div>

            <div class="lp-galeri-header__stats">
                <div class="lp-galeri-stat">
                    <span class="lp-galeri-stat__value">{{ $galeriList->total() }}</span>
                    <span class="lp-galeri-stat__label">Total Media</span>
                </div>
            </div>
        </div>

        {{-- ===== TOOLBAR: PENCARIAN, KATEGORI, URUTAN ===== --}}
        <form action="{{ route('galeri.index') }}" method="GET" class="lp-galeri-toolbar" x-data>
            @if ($filters['type'])
                <input type="hidden" name="type" value="{{ $filters['type'] }}">
            @endif
            @if ($filters['kegiatan'])
                <input type="hidden" name="kegiatan" value="{{ $filters['kegiatan'] }}">
            @endif

            <div class="lp-galeri-search">
                <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="11" cy="11" r="7"/><path d="m21 21-4.3-4.3"/></svg>
                <input type="text" name="q" value="{{ $filters['q'] }}" placeholder="Cari judul atau nama kegiatan..."
                       autocomplete="off"
                       @input.debounce.600ms="sessionStorage.setItem('lpGaleriFocus', '1'); $el.form.requestSubmit()"
                       x-init="if (sessionStorage.getItem('lpGaleriFocus')) {
                                   sessionStorage.removeItem('lpGaleriFocus');
                                   $el.focus();
                                   $el.setSelectionRange($el.value.length, $el.value.length);
                               }">
                @if ($filters['q'])
                    <a href="{{ $url(['q' => '']) }}" class="lp-galeri-search__clear" aria-label="Hapus pencarian">
                        <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4"><path d="M6 6l12 12M18 6 6 18"/></svg>
                    </a>
                @endif
            </div>

            <div class="lp-galeri-toolbar__right">
                <select name="kategori" class="lp-galeri-select" onchange="this.form.submit()" aria-label="Kategori">
                    <option value="">Semua Kategori</option>
                    @foreach (\App\Models\Gallery::KATEGORI_LIST as $value => $label)
                        <option value="{{ $value }}" @selected($filters['kategori'] === $value)>{{ $label }}</option>
                    @endforeach
                </select>

                <select name="sort" class="lp-galeri-select" onchange="this.form.submit()" aria-label="Urutkan">
                    <option value="terbaru" @selected($filters['sort'] === 'terbaru')>Terbaru</option>
                    <option value="terlama" @selected($filters['sort'] === 'terlama')>Terlama</option>
                </select>

                <button type="submit" class="lp-btn lp-btn--primary">Cari</button>
                @if ($hasFilter)
                    <a href="{{ route('galeri.index') }}" class="lp-btn lp-btn--ghost">Reset</a>
                @endif
            </div>
        </form>

        {{-- ===== ALBUM (KEGIATAN) ===== --}}
        @if ($albums->isNotEmpty())
            <div class="lp-galeri-albums">
                <span class="lp-galeri-albums__label">Album</span>
                <div class="lp-galeri-albums__list">
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

        {{-- ===== FILTER TABS: TIPE MEDIA ===== --}}
        <div class="lp-galeri-filter-tabs">
            <a href="{{ $url(['type' => '']) }}" class="{{ $filters['type'] === '' ? 'active' : '' }}">Semua</a>
            <a href="{{ $url(['type' => 'image']) }}" class="{{ $filters['type'] === 'image' ? 'active' : '' }}">
                <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="3" width="18" height="18" rx="2"/><circle cx="8.5" cy="9.5" r="1.5"/><path d="m21 15-5-5L5 21"/></svg>
                Foto
            </a>
            <a href="{{ $url(['type' => 'video']) }}" class="{{ $filters['type'] === 'video' ? 'active' : '' }}">
                <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="m10 8 6 4-6 4V8z"/><rect x="3" y="4" width="18" height="16" rx="2"/></svg>
                Video
            </a>
        </div>

        {{-- ===== CHIP FILTER AKTIF ===== --}}
        @if ($hasFilter)
            <div class="lp-galeri-active-filters">
                <span class="lp-galeri-active-filters__label">Filter aktif:</span>

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

        <p class="lp-galeri-result-count">
            Menampilkan <strong>{{ $galeriList->total() }}</strong> media
        </p>

        {{-- ===== GRID GALERI ===== --}}
        <div class="lp-galeri-full-grid">
            @forelse ($galeriList as $item)
                @php
                    $kategoriLabel = \App\Models\Gallery::KATEGORI_LIST[$item->kategori] ?? null;
                    $ariaJenis = $item->type === 'video' ? 'video' : 'foto';
                @endphp
                <div class="lp-galeri-item">
                    <div class="lp-galeri-item__badges">
                        <span class="lp-galeri-item__type-badge lp-galeri-item__type-badge--{{ $item->type }}">
                            {{ $item->type === 'video' ? 'Video' : 'Foto' }}
                        </span>
                        @if ($kategoriLabel)
                            <span class="lp-galeri-item__kategori-badge lp-galeri-item__kategori-badge--{{ $item->kategori }}">
                                {{ $kategoriLabel }}
                            </span>
                        @endif
                    </div>

                    <div class="lp-thumb-static"
                         role="button"
                         tabindex="0"
                         data-galeri-open="{{ $item->id }}"
                         aria-label="Buka {{ $ariaJenis }}: {{ $item->judul ?? 'Tanpa judul' }}">
                        @if ($item->thumbnail_url)
                            <img src="{{ $item->thumbnail_url }}"
                                 alt="{{ $item->judul ?? 'Media' }}"
                                 loading="lazy"
                                 onerror="this.onerror=null;this.src='{{ $placeholder }}'">
                        @else
                            <div class="lp-thumb-placeholder">
                                {{ $item->type === 'video' ? '🎬' : '🖼️' }}
                            </div>
                        @endif
                        @if ($item->type === 'video')
                            <span class="lp-video-play-icon" aria-hidden="true">▶</span>
                        @endif

                        <div class="lp-galeri-item__overlay">
                            <p class="lp-galeri-item__overlay-judul">{{ $item->judul ?? 'Tanpa judul' }}</p>
                            @if ($item->kegiatan)
                                <p class="lp-galeri-item__overlay-kegiatan">{{ $item->kegiatan }}</p>
                            @endif
                        </div>
                    </div>
                </div>
            @empty
                <div class="lp-empty-state lp-galeri-empty">
                    <span class="lp-empty-state__icon">🖼️</span>
                    @if ($hasFilter)
                        <p class="lp-empty-state__text">Tidak ada media yang cocok dengan pencarian/filter ini.</p>
                        <p class="lp-empty-state__sub">
                            <a href="{{ route('galeri.index') }}">Reset filter</a> untuk melihat semua galeri.
                        </p>
                    @else
                        <p class="lp-empty-state__text">Belum ada media galeri.</p>
                    @endif
                </div>
            @endforelse
        </div>

        <div class="lp-pagination">{{ $galeriList->links('vendor.pagination.lp') }}</div>
    </div>
</section>

@include('landing-page.partials.galeri-lightbox', [
    'mediaItems' => $galeriList,
    'focusMedia' => $focusMedia,
    'syncUrl'    => true,
])
@endsection
