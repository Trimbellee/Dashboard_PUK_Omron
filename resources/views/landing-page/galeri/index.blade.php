@extends('landing-page.layouts.app')

@push('styles')
    @vite(['resources/css/landing-page/galeri/galeri.css'])
@endpush

@section('title', 'Galeri - PUK OMRON')

@section('content')
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

        {{-- ===== TOOLBAR: PENCARIAN & FILTER ===== --}}
        <form action="{{ route('galeri.index') }}" method="GET" class="lp-galeri-toolbar">
            <div class="lp-galeri-search">
                <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="11" cy="11" r="7"/><path d="m21 21-4.3-4.3"/></svg>
                <input type="text" name="q" value="{{ $filters['q'] }}" placeholder="Cari judul atau nama kegiatan...">
                @if ($filters['q'])
                    <a href="{{ route('galeri.index', array_filter(['type' => $filters['type'], 'kategori' => $filters['kategori']])) }}"
                       class="lp-galeri-search__clear" aria-label="Hapus pencarian">
                        <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4"><path d="M6 6l12 12M18 6 6 18"/></svg>
                    </a>
                @endif
            </div>

            <div class="lp-galeri-toolbar__right">
                <select name="kategori" class="lp-galeri-select" onchange="this.form.submit()">
                    <option value="">Semua Kategori</option>
                    @foreach (\App\Models\Gallery::KATEGORI_LIST as $value => $label)
                        <option value="{{ $value }}" @selected($filters['kategori'] === $value)>{{ $label }}</option>
                    @endforeach
                </select>

                <button type="submit" class="lp-btn lp-btn--primary">Cari</button>
                @if ($filters['q'] || $filters['type'] || $filters['kategori'])
                    <a href="{{ route('galeri.index') }}" class="lp-btn lp-btn--ghost">Reset</a>
                @endif
            </div>
        </form>

        {{-- ===== FILTER TABS: TIPE MEDIA ===== --}}
        <div class="lp-galeri-filter-tabs">
            @php
                $tabQuery = fn ($type) => array_filter(['q' => $filters['q'], 'kategori' => $filters['kategori'], 'type' => $type]);
            @endphp
            <a href="{{ route('galeri.index', $tabQuery(null)) }}" class="{{ $filters['type'] === '' ? 'active' : '' }}">Semua</a>
            <a href="{{ route('galeri.index', $tabQuery('image')) }}" class="{{ $filters['type'] === 'image' ? 'active' : '' }}">
                <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="3" width="18" height="18" rx="2"/><circle cx="8.5" cy="9.5" r="1.5"/><path d="m21 15-5-5L5 21"/></svg>
                Foto
            </a>
            <a href="{{ route('galeri.index', $tabQuery('video')) }}" class="{{ $filters['type'] === 'video' ? 'active' : '' }}">
                <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="m10 8 6 4-6 4V8z"/><rect x="3" y="4" width="18" height="16" rx="2"/></svg>
                Video
            </a>
        </div>

        {{-- ===== CHIP FILTER AKTIF ===== --}}
        @if ($filters['q'] || $filters['type'] || $filters['kategori'])
            <div class="lp-galeri-active-filters">
                <span class="lp-galeri-active-filters__label">Filter aktif:</span>

                @if ($filters['q'])
                    <a href="{{ route('galeri.index', array_filter(['type' => $filters['type'], 'kategori' => $filters['kategori']])) }}" class="lp-filter-chip">
                        "{{ $filters['q'] }}" <span aria-hidden="true">&times;</span>
                    </a>
                @endif

                @if ($filters['type'])
                    <a href="{{ route('galeri.index', array_filter(['q' => $filters['q'], 'kategori' => $filters['kategori']])) }}" class="lp-filter-chip">
                        {{ $filters['type'] === 'video' ? 'Video' : 'Foto' }} <span aria-hidden="true">&times;</span>
                    </a>
                @endif

                @if ($filters['kategori'])
                    <a href="{{ route('galeri.index', array_filter(['q' => $filters['q'], 'type' => $filters['type']])) }}" class="lp-filter-chip">
                        {{ \App\Models\Gallery::KATEGORI_LIST[$filters['kategori']] ?? $filters['kategori'] }} <span aria-hidden="true">&times;</span>
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
                    $isYoutube = $item->source === 'youtube';
                    $playSrc = $isYoutube ? null : $item->embed_url;
                    $kategoriLabel = \App\Models\Gallery::KATEGORI_LIST[$item->kategori] ?? null;
                @endphp
                <div class="lp-galeri-item"
                     @if ($isYoutube)
                        x-data="lpGaleriYtPlayer('yt-full-player-{{ $item->id }}', 'yt-full-wrap-{{ $item->id }}', '{{ $item->external_id }}')"
                     @else
                        x-data="{ playing: false }"
                     @endif>

                    <div class="lp-galeri-item__badges" x-show="!playing">
                        <span class="lp-galeri-item__type-badge lp-galeri-item__type-badge--{{ $item->type }}">
                            {{ $item->type === 'video' ? 'Video' : 'Foto' }}
                        </span>
                        @if ($kategoriLabel)
                            <span class="lp-galeri-item__kategori-badge lp-galeri-item__kategori-badge--{{ $item->kategori }}">
                                {{ $kategoriLabel }}
                            </span>
                        @endif
                    </div>

                    {{-- State diam --}}
                    <template x-if="!playing">
                        <div class="lp-thumb-static"
                             @click="{{ $isYoutube ? 'start()' : 'playing = true' }}"
                             role="button" tabindex="0"
                             @keydown.enter="{{ $isYoutube ? 'start()' : 'playing = true' }}">
                            @if ($item->thumbnail_url)
                                <img src="{{ $item->thumbnail_url }}" alt="{{ $item->judul ?? 'Media' }}" loading="lazy">
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
                    </template>

                    {{-- State aktif --}}
                    <template x-if="playing">
                        <div class="lp-thumb-playing" id="yt-full-wrap-{{ $item->id }}">
                            @if ($isYoutube)
                                @if ($item->external_id)
                                    <div id="yt-full-player-{{ $item->id }}"></div>
                                @else
                                    <div class="lp-thumb-playing__empty">Video tidak ditemukan.</div>
                                @endif
                            @elseif ($playSrc)
                                <iframe src="{{ $playSrc }}" frameborder="0"
                                        allow="autoplay; encrypted-media; fullscreen"
                                        allowfullscreen loading="lazy"></iframe>
                            @else
                                <div class="lp-thumb-playing__empty">Sumber media tidak tersedia.</div>
                            @endif

                            <button type="button" class="lp-thumb-close"
                                    @click.stop="{{ $isYoutube ? 'close()' : 'playing = false' }}"
                                    aria-label="Tutup preview">
                                <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.6"><path d="M6 6l12 12M18 6 6 18"/></svg>
                            </button>
                        </div>
                    </template>
                </div>
            @empty
                <div class="lp-empty-state lp-galeri-empty">
                    <span class="lp-empty-state__icon">🖼️</span>
                    @if ($filters['q'] || $filters['type'] || $filters['kategori'])
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

        <div class="lp-pagination">{{ $galeriList->links() }}</div>
    </div>
</section>

<script>
    document.addEventListener('alpine:init', () => {
        Alpine.data('lpGaleriYtPlayer', (playerId, wrapId, videoId) => ({
            playing: false,
            player: null,
            start() {
                this.playing = true;
                this.$nextTick(() => this._initPlayer(playerId, videoId));
            },
            _initPlayer(playerId, videoId) {
                const create = () => {
                    this.player = new YT.Player(playerId, {
                        videoId: videoId,
                        playerVars: { autoplay: 1, rel: 0, modestbranding: 1, playsinline: 1 },
                        events: { onReady: (e) => e.target.playVideo() },
                    });
                };
                if (window.YT && window.YT.Player) {
                    create();
                } else {
                    window.__lpYtQueue = window.__lpYtQueue || [];
                    window.__lpYtQueue.push(create);
                    if (!window.__lpYtApiLoading) {
                        window.__lpYtApiLoading = true;
                        window.onYouTubeIframeAPIReady = function () {
                            (window.__lpYtQueue || []).forEach((fn) => fn());
                            window.__lpYtQueue = [];
                        };
                        const tag = document.createElement('script');
                        tag.src = 'https://www.youtube.com/iframe_api';
                        document.head.appendChild(tag);
                    }
                }
            },
            close() {
                if (this.player && this.player.destroy) this.player.destroy();
                this.player = null;
                this.playing = false;
            },
        }));
    });
</script>
@endsection