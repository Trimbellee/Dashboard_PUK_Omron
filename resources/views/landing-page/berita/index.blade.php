@extends('landing-page.layouts.app')

@push('styles')
    @vite(['resources/css/landing-page/berita/index.css'])
@endpush

@section('title', 'Berita & Pengumuman - PUK OMRON')
@section('meta_description', 'Berita dan pengumuman terbaru dari PUK OMRON.')

@section('content')
    {{-- ===== BREAKING NEWS TICKER (atas) ===== --}}
    @php
        $breakingNews = \App\Models\News::published()
            ->where('is_breaking', true)
            ->latest('published_at')
            ->take(5)
            ->get();
    @endphp
    @if($breakingNews->isNotEmpty())
    <div class="lp-breaking-ticker">
        <div class="lp-breaking-ticker__label">
            <span class="lp-breaking-ticker__dot"></span>
            BREAKING
        </div>
        <div class="lp-breaking-ticker__track-wrapper">
            <div class="lp-breaking-ticker__track">
                @foreach($breakingNews as $item)
                    <a href="{{ route('berita.show', $item->slug) }}" class="lp-breaking-ticker__item">
                        {{ $item->judul }}
                    </a>
                @endforeach
                {{-- Duplikat untuk loop mulus --}}
                @foreach($breakingNews as $item)
                    <a href="{{ route('berita.show', $item->slug) }}" class="lp-breaking-ticker__item" aria-hidden="true" tabindex="-1">
                        {{ $item->judul }}
                    </a>
                @endforeach
            </div>
        </div>
    </div>
    @endif

    <section class="lp-berita-index">
        <div class="lp-container">

            {{-- ===== HEADER SECTION ===== --}}
            <div class="lp-page-header" x-data="{ activeTab: '{{ request('kategori') ?: 'Semua' }}' }">
                <div class="lp-page-header__top">
                    <div class="lp-page-header__intro">
                        <span class="lp-page-header__eyebrow">
                            <span class="lp-page-header__eyebrow-dot"></span>
                            Portal Berita
                        </span>
                        <h1 class="lp-page-header__title">
                            Kabar Berita
                        </h1>
                        <p class="lp-page-header__subtitle">
                            Informasi terkini seputar perjuangan, program, dan pengumuman resmi
                        </p>
                    </div>

                    <div class="lp-page-header__stats">
                        <div class="lp-stat-chip">
                            <span class="lp-stat-chip__value">{{ $berita->total() }}</span>
                            <span class="lp-stat-chip__label">Total Berita</span>
                        </div>
                        @if($breakingNews->isNotEmpty())
                            <div class="lp-stat-chip lp-stat-chip--live">
                                <span class="lp-stat-chip__pulse"></span>
                                <span class="lp-stat-chip__value">{{ $breakingNews->count() }}</span>
                                <span class="lp-stat-chip__label">Breaking</span>
                            </div>
                        @endif
                    </div>
                </div>

                <div class="lp-page-header__filter">
                    <span class="lp-page-header__filter-label">
                        <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M22 3H2l8 9.46V19l4 2v-8.54L22 3z"/></svg>
                        Filter Kategori
                    </span>

                    <div class="lp-tab-nav" role="tablist">
                        <a href="{{ route('berita.index') }}"
                        class="lp-tab-nav__item {{ request('kategori') ? '' : 'is-active' }}"
                        @click="activeTab = 'Semua'"
                        role="tab">
                            <span>Semua</span>
                        </a>
                        @foreach($categories as $cat)
                            <a href="{{ route('berita.index', ['kategori' => $cat]) }}"
                            class="lp-tab-nav__item {{ request('kategori') == $cat ? 'is-active' : '' }}"
                            @click="activeTab = '{{ $cat }}'"
                            role="tab">
                                <span>{{ $cat }}</span>
                            </a>
                        @endforeach
                    </div>
                </div>
            </div>

            {{-- ===== HERO FEATURED (3 kolom: 1 besar + 2 kecil) ===== --}}
            @if($headline || $heroFeatured->isNotEmpty())
            <div class="lp-hero-featured">
                {{-- Main Feature --}}
                @if($headline)
                    <a href="{{ route('berita.show', $headline->slug) }}" class="lp-hero-featured__main">
                        <div class="lp-hero-featured__image">
                            @if(!empty($headline->gambar))
                                <img src="{{ asset('storage/' . $headline->gambar) }}" alt="{{ $headline->judul }}">
                            @else
                                <span class="lp-hero-featured__placeholder">PUK</span>
                            @endif
                            <div class="lp-hero-featured__overlay">
                                <span class="lp-hero-featured__badge">🔥 Headline</span>
                                <span class="lp-hero-featured__kategori">{{ $headline->kategori }}</span>
                                <h2 class="lp-hero-featured__title">{{ $headline->judul }}</h2>
                                <p class="lp-hero-featured__ringkasan">{{ Str::limit($headline->ringkasan, 250) }}</p>
                                <div class="lp-hero-featured__meta">
                                    <span>{{ $headline->published_at->translatedFormat('d F Y') }}</span>
                                    @if($headline->creator)
                                        <span>✍️ {{ $headline->creator->nama_lengkap }}</span>
                                    @endif
                                </div>
                            </div>
                        </div>
                    </a>
                @endif

                {{-- Side Features --}}
                @if($heroFeatured->isNotEmpty())
                    <div class="lp-hero-featured__side">
                        @foreach($heroFeatured->take(2) as $item)
                            <a href="{{ route('berita.show', $item->slug) }}" class="lp-hero-featured__side-item">
                                <div class="lp-hero-featured__side-thumb">
                                    @if(!empty($item->gambar))
                                        <img src="{{ asset('storage/' . $item->gambar) }}" alt="{{ $item->judul }}" loading="lazy">
                                    @else
                                        <span>PUK</span>
                                    @endif
                                    @if($item->is_breaking)
                                        <span class="lp-hero-featured__side-badge">🚨</span>
                                    @endif
                                </div>
                                <div class="lp-hero-featured__side-body">
                                    <span class="lp-hero-featured__side-kategori">{{ $item->kategori }}</span>
                                    <h4 class="lp-hero-featured__side-title">{{$item->judul}}</h4>
                                    <time>{{ $item->published_at->translatedFormat('d M Y') }}</time>
                                </div>
                            </a>
                        @endforeach
                    </div>
                @endif
            </div>
            @endif

            {{-- ===== CAROUSEL BERITA TERBARU ===== --}}
            <div class="lp-news-carousel" x-data="carousel()" x-init="init()">
                <div class="lp-news-carousel__header">
                    <h3 class="lp-news-carousel__title">
                        <span class="lp-news-carousel__title-icon">⚡</span>
                        Terbaru
                    </h3>
                    <div class="lp-news-carousel__nav">
                        <button type="button" class="lp-news-carousel__prev" @click="prev()" aria-label="Sebelumnya">
                            <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><path d="M15 18l-6-6 6-6"/></svg>
                        </button>
                        <button type="button" class="lp-news-carousel__next" @click="next()" aria-label="Selanjutnya">
                            <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><path d="M9 18l6-6-6-6"/></svg>
                        </button>
                    </div>
                </div>

                <div class="lp-news-carousel__track-wrapper">
                    <div class="lp-news-carousel__track" x-ref="track">
                        @php
                            $latestNews = \App\Models\News::published()
                                ->latest('published_at')
                                ->take(10)
                                ->get();
                        @endphp

                        @forelse($latestNews as $item)
                            <div class="lp-news-carousel__slide">
                                <a href="{{ route('berita.show', $item->slug) }}" class="lp-news-carousel__link">
                                    <div class="lp-news-carousel__thumb">
                                        @if (!empty($item->gambar))
                                            <img src="{{ asset('storage/' . $item->gambar) }}" alt="{{ $item->judul }}" loading="lazy">
                                        @else
                                            <span class="lp-news-carousel__thumb-placeholder">PUK</span>
                                        @endif
                                        @if($item->is_breaking)
                                            <span class="lp-news-carousel__badge-breaking">🚨</span>
                                        @endif
                                    </div>
                                    <div class="lp-news-carousel__body">
                                        <div class="lp-news-carousel__meta-top">
                                            <time class="lp-news-carousel__meta" datetime="{{ $item->published_at->toDateString() }}">
                                                {{ $item->published_at->translatedFormat('d M Y') }}
                                            </time>
                                            @if($item->is_featured)
                                                <span class="lp-news-carousel__badge-featured">⭐</span>
                                            @endif
                                        </div>
                                        <h4 class="lp-news-carousel__judul">{{ $item->judul }}</h4>
                                    </div>
                                </a>
                            </div>
                        @empty
                            <div class="lp-news-carousel__empty">Belum ada berita terbaru</div>
                        @endforelse
                    </div>
                    <div class="lp-news-carousel__progress">
                        <div class="lp-news-carousel__progress-bar" x-ref="progressBar"></div>
                    </div>
                </div>
            </div>

            {{-- ===== MAIN CONTENT + SIDEBAR ===== --}}
            <div class="lp-berita-index__content">

                {{-- ===== MAIN GRID ===== --}}
                <div class="lp-berita-index__main">
                    {{-- Label Section --}}
                    <div class="lp-berita-index__section-label">
                        <span class="lp-berita-index__section-line"></span>
                        <span class="lp-berita-index__section-text">Berita Terkini</span>
                    </div>

                    @if($berita->isNotEmpty())
                        @foreach($berita as $item)
                            <article class="lp-news-card">
                                <div class="lp-news-card__link">
                                    {{-- Thumbnail (tidak lagi jadi link) --}}
                                    <div class="lp-news-card__thumbnail">
                                        @if(!empty($item->gambar))
                                            <img src="{{ asset('storage/' . $item->gambar) }}" alt="{{ $item->judul }}" loading="lazy">
                                        @else
                                            <span class="lp-news-card__thumbnail-placeholder">PUK</span>
                                        @endif
                                    </div>

                                    {{-- Content --}}
                                    <div class="lp-news-card__content">
                                        <div class="lp-news-card__meta">
                                            <span class="lp-news-card__kategori">{{ $item->kategori }}</span>
                                            <span class="lp-news-card__date">{{ $item->published_at->translatedFormat('d F Y') }}</span>
                                        </div>

                                        <h3 class="lp-news-card__judul">
                                            <a href="{{ route('berita.show', $item->slug) }}">{{ $item->judul }}</a>
                                        </h3>

                                        <p class="lp-news-card__ringkasan">{{ Str::limit($item->ringkasan, 140) }}</p>
                                        <a href="{{ route('berita.show', $item->slug) }}" class="lp-news-card__readmore">selengkapnya</a>
                                    </div>
                                </div>
                            </article>
                        @endforeach

                        <div class="lp-berita-index__pagination">
                            {{ $berita->links('vendor.pagination.custom') }}
                        </div>
                    @else
                        <div class="lp-empty-state">
                            <span class="lp-empty-state__icon">📭</span>
                            <p class="lp-empty-state__text">Belum ada berita yang dipublikasikan</p>
                            <p class="lp-empty-state__sub">Pantau terus halaman ini untuk informasi terbaru dari PUK OMRON</p>
                        </div>
                    @endif
                </div>

                {{-- ===== SIDEBAR ===== --}}
                <aside class="lp-berita-index__sidebar">

                    {{-- Trending --}}
                    @if($trendingNews->isNotEmpty())
                    <div class="lp-sidebar-widget lp-sidebar-widget--trending">
                        <h4 class="lp-sidebar-widget__title">
                            <span class="lp-sidebar-widget__icon">🔥</span> Trending
                        </h4>
                        <ul class="lp-sidebar-widget__list">
                            @foreach($trendingNews as $item)
                                <li>
                                    <a href="{{ route('berita.show', $item->slug) }}">
                                        <span class="lp-sidebar-widget__rank">{{ $loop->iteration }}</span>
                                        <span class="lp-sidebar-widget__text">{{ $item->judul }}</span>
                                        <span class="lp-sidebar-widget__views">{{ number_format($item->views) }}x</span>
                                    </a>
                                </li>
                            @endforeach
                        </ul>
                    </div>
                    @endif

                    {{-- Populer --}}
                    <div class="lp-sidebar-widget lp-sidebar-widget--popular">
                        <h4 class="lp-sidebar-widget__title">
                            <span class="lp-sidebar-widget__icon">⭐</span> Populer
                        </h4>
                        <ul class="lp-sidebar-widget__list">
                            @forelse($popularNews as $item)
                                <li>
                                    <a href="{{ route('berita.show', $item->slug) }}">
                                        <span class="lp-sidebar-widget__number">{{ $loop->iteration }}</span>
                                        <span class="lp-sidebar-widget__text">{{ Str::limit($item->judul, 50) }}</span>
                                    </a>
                                </li>
                            @empty
                                <li style="color: var(--text-muted); font-size: 13px; text-align: center; padding: 12px 0;">
                                    Belum ada berita populer
                                </li>
                            @endforelse
                        </ul>
                    </div>

                    

                    {{-- Kategori --}}
                    <div class="lp-sidebar-widget">
                        <h4 class="lp-sidebar-widget__title">
                            <span class="lp-sidebar-widget__icon">🏷️</span> Kategori
                        </h4>
                        <div class="lp-sidebar-widget__tags">
                            @foreach($categories as $cat)
                                <a href="{{ route('berita.index', ['kategori' => $cat]) }}" class="lp-sidebar-widget__tag">
                                    {{ $cat }}
                                </a>
                            @endforeach
                        </div>
                    </div>

                    {{-- Tags Populer --}}
                    @if($popularTags->count() > 0)
                        <div class="lp-sidebar-widget">
                            <h4 class="lp-sidebar-widget__title">
                                <span class="lp-sidebar-widget__icon">#</span> Topik Populer
                            </h4>
                            <div class="lp-sidebar-widget__tags">
                                @foreach($popularTags as $tag)
                                    <a href="{{ route('berita.index', ['tag' => $tag->name]) }}" class="lp-sidebar-widget__tag">
                                        #{{ $tag->name }}
                                    </a>
                                @endforeach
                            </div>
                        </div>
                    @endif

                    {{-- CTA --}}
                    <div class="lp-sidebar-widget lp-sidebar-widget--cta">
                        <div class="lp-sidebar-widget__cta-icon">💬</div>
                        <h4 class="lp-sidebar-widget__title">Ada Aspirasi?</h4>
                        <p style="font-size: 14px; color: var(--text-secondary); margin-bottom: 14px; line-height: 1.6;">
                            Sampaikan aspirasi, kritik, atau saran Anda kepada pengurus PUK OMRON.
                        </p>
                        <a href="{{ route('landing.index') }}#kontak" class="lp-btn lp-btn--primary" style="width: 100%; text-align: center;">
                            Hubungi Pengurus
                        </a>
                    </div>
                </aside>
            </div>
        </div>
    </section>

    {{-- ===== MOBILE BOTTOM NAV ===== --}}
    <nav class="lp-mobile-bottomnav" aria-label="Navigasi cepat">
        <a href="{{ route('landing.index') }}" class="lp-mobile-bottomnav__item">
            <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M3 12l9-9 9 9"/><path d="M9 21V12h6v9"/></svg>
            <span>Beranda</span>
        </a>
        <a href="{{ route('berita.index') }}" class="lp-mobile-bottomnav__item active">
            <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M4 4h16v16H4z"/><path d="M8 9h8M8 13h5"/></svg>
            <span>Berita</span>
        </a>
        <button type="button" class="lp-mobile-bottomnav__item" onclick="window.scrollTo({top:0,behavior:'smooth'})">
            <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M12 19V5M5 12l7-7 7 7"/></svg>
            <span>Ke Atas</span>
        </button>
        <a href="{{ route('landing.index') }}#kontak" class="lp-mobile-bottomnav__item">
            <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M21 15a2 2 0 01-2 2H7l-4 4V5a2 2 0 012-2h14a2 2 0 012 2z"/></svg>
            <span>Kontak</span>
        </a>
    </nav>
@endsection

@push('scripts')
<script>
function carousel() {
    return {
        currentIndex: 0,
        slidesPerView: 0,
        totalSlides: 0,
        autoplayInterval: null,
        isTransitioning: false,

        init() {
            this.updateSlidesPerView();
            this.totalSlides = this.$refs.track.children.length;
            this.updateCarousel(false);
            this.startAutoplay();
            window.addEventListener('resize', () => {
                this.updateSlidesPerView();
                this.currentIndex = 0;
                this.updateCarousel(false);
            });
            const track = this.$refs.track.parentElement;
            track.addEventListener('mouseenter', () => this.stopAutoplay());
            track.addEventListener('mouseleave', () => this.startAutoplay());
        },

        updateSlidesPerView() {
            const w = window.innerWidth;
            if (w >= 1400) this.slidesPerView = 6;
            else if (w >= 1024) this.slidesPerView = 5;
            else if (w >= 768) this.slidesPerView = 4;
            else if (w >= 480) this.slidesPerView = 3;
            else this.slidesPerView = 2;
        },

        getSlideWidth() {
            const trackWidth = this.$refs.track.parentElement.clientWidth;
            const gap = 12;
            return (trackWidth - (gap * (this.slidesPerView - 1))) / this.slidesPerView;
        },

        updateCarousel(animate = true) {
            const slideWidth = this.getSlideWidth();
            const gap = 12;
            const offset = this.currentIndex * (slideWidth + gap);

            const slides = this.$refs.track.children;
            for (let i = 0; i < slides.length; i++) {
                slides[i].style.flex = '0 0 ' + slideWidth + 'px';
            }

            this.$refs.track.style.transition = animate ? 'transform 0.5s cubic-bezier(0.25, 0.46, 0.45, 0.94)' : 'none';
            this.$refs.track.style.transform = 'translateX(-' + offset + 'px)';

            const maxIndex = Math.max(0, this.totalSlides - this.slidesPerView);
            const progress = maxIndex > 0 ? (this.currentIndex / maxIndex) * 100 : 0;
            this.$refs.progressBar.style.width = progress + '%';
        },

        next() {
            if (this.isTransitioning) return;
            const maxIndex = Math.max(0, this.totalSlides - this.slidesPerView);
            if (this.currentIndex < maxIndex) {
                this.currentIndex++;
                this.isTransitioning = true;
                this.updateCarousel(true);
                setTimeout(() => { this.isTransitioning = false; }, 500);
            } else {
                this.currentIndex = 0;
                this.updateCarousel(true);
            }
        },

        prev() {
            if (this.isTransitioning) return;
            if (this.currentIndex > 0) {
                this.currentIndex--;
                this.isTransitioning = true;
                this.updateCarousel(true);
                setTimeout(() => { this.isTransitioning = false; }, 500);
            }
        },

        startAutoplay() {
            if (this.autoplayInterval) clearInterval(this.autoplayInterval);
            if (this.totalSlides <= this.slidesPerView) return;
            this.autoplayInterval = setInterval(() => {
                this.next();
            }, 5000);
        },

        stopAutoplay() {
            if (this.autoplayInterval) {
                clearInterval(this.autoplayInterval);
                this.autoplayInterval = null;
            }
        }
    };
}

</script>
@endpush