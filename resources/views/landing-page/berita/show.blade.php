@extends('landing-page.layouts.app')

@push('styles')
    @vite(['resources/css/landing-page/berita/show.css'])
    <link href="https://cdn.jsdelivr.net/npm/quill@2.0.3/dist/quill.snow.css" rel="stylesheet">

    @include('landing-page.partials.og-meta', [
        'ogType'        => 'article',
        'ogTitle'       => $berita->judul,
        'ogDescription' => \Illuminate\Support\Str::limit(strip_tags($berita->ringkasan), 200),
        'ogUrl'         => route('berita.show', $berita->slug),
        'ogImage'       => !empty($berita->gambar) ? asset('storage/' . $berita->gambar) : asset('assets/media.png'),
        'ogPublished'   => optional($berita->published_at)->toIso8601String(),
        'ogSection'     => $berita->kategori,
        'ogTags'        => $berita->tags->pluck('name')->all(),
    ])
@endpush

@section('title', $berita->judul . ' - PUK OMRON')
@section('meta_description', $berita->ringkasan)

@section('content')
    {{-- ===== READING PROGRESS BAR ===== --}}
    <div class="lp-reading-progress">
        <div class="lp-reading-progress__bar" id="lpReadingProgressBar"></div>
    </div>

    {{-- ===== BREADCRUMB ===== --}}
    <nav class="lp-breadcrumb" aria-label="Breadcrumb">
        <div class="lp-container">
            <ol class="lp-breadcrumb__list">
                <li><a href="{{ route('berita.index') }}">📰 Berita</a></li>
                @if(request('kategori'))
                    <li><a href="{{ route('berita.index', ['kategori' => request('kategori')]) }}">{{ request('kategori') }}</a></li>
                @endif
                <li aria-current="page">{{ Str::limit($berita->judul, 50) }}</li>
            </ol>
        </div>
    </nav>

    {{-- ===== KONTEN UTAMA + SIDEBAR ===== --}}
    <div class="lp-article-wrapper">
        <div class="lp-container">
            <div class="lp-article-grid">

                {{-- ===== KOLOM ARTIKEL ===== --}}
                <main class="lp-article-main">
                    <article class="lp-article" itemscope itemtype="https://schema.org/NewsArticle">
                        <meta itemprop="datePublished" content="{{ $berita->published_at->toIso8601String() }}">
                        <meta itemprop="dateModified" content="{{ ($berita->last_edited_at ?? $berita->published_at)->toIso8601String() }}">

                        {{-- HEADER --}}
                        <header class="lp-article__header">
                            <div class="lp-article__header-top">
                                <a href="{{ route('berita.index', ['kategori' => $berita->kategori]) }}" class="lp-article__kategori">{{ $berita->kategori }}</a>
                                @if($berita->is_breaking)
                                    <span class="lp-badge lp-badge--breaking">🚨 Breaking</span>
                                @endif
                                @if($berita->is_featured)
                                    <span class="lp-badge lp-badge--featured">⭐ Featured</span>
                                @endif
                                @if($berita->is_headline)
                                    <span class="lp-badge lp-badge--headline">📰 Headline</span>
                                @endif
                                @if($berita->published_at->diffInDays(now()) <= 7)
                                    <span class="lp-badge lp-badge--new">Baru</span>
                                @endif
                            </div>

                            <h1 class="lp-article__title" itemprop="headline">{{ $berita->judul }}</h1>

                            {{-- META — seperti di gambar: avatar, penulis, tanggal, views --}}
                            <div class="lp-article__meta">
                                <div class="lp-article__meta-left">
                                    {{-- Avatar Penulis --}}
                                    <div class="lp-article__avatar">
                                        <img src="{{ asset('assets/media.png') }}" 
                                            class="lp-article__avatar-img">
                                    </div>

                                    <div class="lp-article__meta-info">
                                        @if($berita->creator)
                                            <span class="lp-article__author">
                                                <a >Media PUK OMI</a>
                                            </span>
                                        @endif
                                        <span class="lp-article__meta-separator">•</span>
                                        <time class="lp-article__date" datetime="{{ $berita->published_at->toDateString() }}">
                                            {{ $berita->published_at->translatedFormat('d F Y') }}
                                        </time>
                                        <span class="lp-article__meta-separator">•</span>
                                        <span class="lp-article__views">
                                            {{ number_format($berita->views) }} views
                                        </span>
                                    </div>
                                </div>

                                {{-- Ikon Medsos (desktop only) — share berita ini --}}
                                <div class="lp-article__meta-right">
                                    <div class="lp-article__meta-social">
                                        
                                        <a href="https://twitter.com/intent/tweet?url={{ urlencode(url()->current()) }}&text={{ urlencode($berita->judul) }}" target="_blank" rel="noopener" class="lp-article__meta-social-link lp-article__meta-social-link--tw" aria-label="Bagikan ke X">
                                            <svg width="16" height="16" viewBox="0 0 24 24" fill="currentColor"><path d="M18.244 2.25h3.308l-7.227 8.26 8.502 11.24H16.17l-5.214-6.817L4.99 21.75H1.68l7.73-8.835L1.254 2.25H8.08l4.713 6.231zm-1.161 17.52h1.833L7.084 4.126H5.117z"/></svg>
                                        </a>
                                        <a href="https://facebook.com/sharer/sharer.php?u={{ urlencode(url()->current()) }}" target="_blank" rel="noopener" class="lp-article__meta-social-link lp-article__meta-social-link--fb" aria-label="Bagikan ke Facebook">
                                            <svg width="16" height="16" viewBox="0 0 24 24" fill="currentColor"><path d="M24 12.073c0-6.627-5.373-12-12-12s-12 5.373-12 12c0 5.99 4.388 10.954 10.125 11.854v-8.385H7.078v-3.47h3.047V9.43c0-3.007 1.792-4.669 4.533-4.669 1.312 0 2.686.235 2.686.235v2.953H15.83c-1.491 0-1.956.925-1.956 1.874v2.25h3.328l-.532 3.47h-2.796v8.385C19.612 23.027 24 18.062 24 12.073z"/></svg>
                                        </a>
                                        <a href="https://wa.me/?text={{ urlencode($berita->judul . ' - ' . url()->current()) }}" target="_blank" rel="noopener" class="lp-article__meta-social-link lp-article__meta-social-link--wa" aria-label="Bagikan ke WhatsApp">
                                            <svg width="16" height="16" viewBox="0 0 24 24" fill="currentColor"><path d="M17.472 14.382c-.297-.149-1.758-.867-2.03-.967-.273-.099-.471-.148-.67.15-.197.297-.767.966-.94 1.164-.173.199-.347.223-.644.075-.297-.15-1.255-.463-2.39-1.475-.883-.788-1.48-1.761-1.653-2.059-.173-.297-.018-.458.13-.606.134-.133.298-.347.446-.52.149-.174.198-.298.298-.497.099-.198.05-.371-.025-.52-.075-.149-.669-1.612-.916-2.207-.242-.579-.487-.5-.669-.51-.173-.008-.371-.01-.57-.01-.198 0-.52.074-.792.372-.272.297-1.04 1.016-1.04 2.479 0 1.462 1.065 2.875 1.213 3.074.149.198 2.096 3.2 5.077 4.487.709.306 1.262.489 1.694.625.712.227 1.36.195 1.871.118.571-.085 1.758-.719 2.006-1.413.248-.694.248-1.289.173-1.413-.074-.124-.272-.198-.57-.347m-5.421 7.403h-.004a9.87 9.87 0 01-5.031-1.378l-.361-.214-3.741.982.998-3.648-.235-.374a9.86 9.86 0 01-1.51-5.26c.001-5.45 4.436-9.884 9.888-9.884 2.64 0 5.122 1.03 6.988 2.898a9.825 9.825 0 012.893 6.994c-.003 5.45-4.437 9.884-9.885 9.884m8.413-18.297A11.815 11.815 0 0012.05 0C5.495 0 .16 5.335.157 11.892c0 2.096.547 4.142 1.588 5.945L.057 24l6.305-1.654a11.882 11.882 0 005.683 1.448h.005c6.554 0 11.89-5.335 11.893-11.893a11.821 11.821 0 00-3.48-8.413z"/></svg>
                                        </a>
                                    </div>
                                </div>
                            </div>
                        </header>

                        {{-- FEATURED IMAGE --}}
                        @if(!empty($berita->gambar))
                            <figure class="lp-article__featured" itemprop="image" itemscope itemtype="https://schema.org/ImageObject">
                                <img src="{{ asset('storage/' . $berita->gambar) }}" alt="{{ $berita->judul }}" loading="lazy" itemprop="url">
                                @if($berita->gambar_caption)
                                    <figcaption itemprop="caption">{{ $berita->gambar_caption }}</figcaption>
                                @endif
                            </figure>
                        @endif

                        {{-- ===== BODY ARTIKEL ===== --}}
                        <div class="lp-article__body ql-editor" itemprop="articleBody">
                            @php
                                $konten = $berita->konten;
                                $marker = '<!-- bacaan_lainnya -->';
                                $posisi = strpos($konten, $marker);
                                $before = '';
                                $after = '';

                                if ($posisi !== false) {
                                    // Jika ada marker, gunakan posisi marker
                                    $before = substr($konten, 0, $posisi);
                                    $after = substr($konten, $posisi + strlen($marker));
                                } else {
                                    // ===== OTOMATIS: sisipkan setelah 2 paragraf pertama =====
                                    $jumlahParagrafAwal = 2;

                                    $parts = explode('</p>', $konten);
                                    if (end($parts) === '') {
                                        array_pop($parts);
                                    }

                                    if (count($parts) > $jumlahParagrafAwal) {
                                        $before = implode('</p>', array_slice($parts, 0, $jumlahParagrafAwal)) . '</p>';
                                        $after = implode('</p>', array_slice($parts, $jumlahParagrafAwal));
                                    } else {
                                        $before = $konten;
                                        $after = '';
                                    }
                                }
                            @endphp

                            {{-- Tampilkan konten sebelum "Bacaan Lainnya" --}}
                            {!! $before !!}

                            {{-- ===== BACAAAN LAINNYA (di tengah artikel, seperti KPonline) ===== --}}
                            @if($beritaLain->isNotEmpty())
                                <div class="gmr-related-post gmr-gallery-related-insidepost">
                                    <div class="gmr-related-post__title"><strong>Bacaan Lainnya</strong></div>
                                    <div class="gmr-related-post__grid">
                                        @foreach($beritaLain->take(3) as $item)
                                            <div class="gmr-related-post__item">
                                                <a href="{{ route('berita.show', $item->slug) }}" title="{{ $item->judul }}">
                                                    {{ $item->judul }}
                                                </a>
                                            </div>
                                        @endforeach
                                    </div>
                                </div>
                            @endif

                            {{-- Tampilkan sisa konten setelah "Bacaan Lainnya" --}}
                            {!! $after !!}
                        </div>

                        {{-- TAGS --}}
                        @if($berita->tags->count() > 0)
                            <div class="lp-article__tags">
                                <span class="lp-article__tags-label">🏷️ Tags:</span>
                                @foreach($berita->tags as $tag)
                                    <a href="{{ route('berita.index', ['tag' => $tag->name]) }}" class="lp-article__tag">
                                        #{{ $tag->name }}
                                    </a>
                                @endforeach
                            </div>
                        @endif

                        {{-- PENULIS & EDITOR (simple byline) --}}
                        @if($berita->creator || ($berita->editor && $berita->edited_by != $berita->created_by))
                            <div class="lp-byline">
                                @if($berita->creator)
                                    <p class="lp-byline__line">
                                        Penulis: {{ $berita->creator->nama_lengkap }}
                                    </p>
                                @endif
                                @if($berita->editor && $berita->edited_by != $berita->created_by)
                                    <p class="lp-byline__line">
                                        Editor: {{ $berita->editor->nama_lengkap }}
                                    </p>
                                @endif
                            </div>
                        @endif

                        {{-- SHARE & BACK --}}
                        <footer class="lp-article__footer">
                            <div class="lp-article__share">
                                <span class="lp-article__share-label">Bagikan:</span>
                                <a href="https://facebook.com/sharer/sharer.php?u={{ urlencode(url()->current()) }}"
                                   target="_blank" rel="noopener" class="lp-article__share-link lp-article__share-link--fb">
                                    <svg width="20" height="20" viewBox="0 0 24 24" fill="currentColor"><path d="M24 12.073c0-6.627-5.373-12-12-12s-12 5.373-12 12c0 5.99 4.388 10.954 10.125 11.854v-8.385H7.078v-3.47h3.047V9.43c0-3.007 1.792-4.669 4.533-4.669 1.312 0 2.686.235 2.686.235v2.953H15.83c-1.491 0-1.956.925-1.956 1.874v2.25h3.328l-.532 3.47h-2.796v8.385C19.612 23.027 24 18.062 24 12.073z"/></svg>
                                </a>
                                <a href="https://twitter.com/intent/tweet?url={{ urlencode(url()->current()) }}&text={{ urlencode($berita->judul) }}"
                                   target="_blank" rel="noopener" class="lp-article__share-link lp-article__share-link--tw">
                                    <svg width="20" height="20" viewBox="0 0 24 24" fill="currentColor"><path d="M18.244 2.25h3.308l-7.227 8.26 8.502 11.24H16.17l-5.214-6.817L4.99 21.75H1.68l7.73-8.835L1.254 2.25H8.08l4.713 6.231zm-1.161 17.52h1.833L7.084 4.126H5.117z"/></svg>
                                </a>
                                <a href="https://wa.me/?text={{ urlencode($berita->judul . ' - ' . url()->current()) }}"
                                   target="_blank" rel="noopener" class="lp-article__share-link lp-article__share-link--wa">
                                    <svg width="20" height="20" viewBox="0 0 24 24" fill="currentColor"><path d="M17.472 14.382c-.297-.149-1.758-.867-2.03-.967-.273-.099-.471-.148-.67.15-.197.297-.767.966-.94 1.164-.173.199-.347.223-.644.075-.297-.15-1.255-.463-2.39-1.475-.883-.788-1.48-1.761-1.653-2.059-.173-.297-.018-.458.13-.606.134-.133.298-.347.446-.52.149-.174.198-.298.298-.497.099-.198.05-.371-.025-.52-.075-.149-.669-1.612-.916-2.207-.242-.579-.487-.5-.669-.51-.173-.008-.371-.01-.57-.01-.198 0-.52.074-.792.372-.272.297-1.04 1.016-1.04 2.479 0 1.462 1.065 2.875 1.213 3.074.149.198 2.096 3.2 5.077 4.487.709.306 1.262.489 1.694.625.712.227 1.36.195 1.871.118.571-.085 1.758-.719 2.006-1.413.248-.694.248-1.289.173-1.413-.074-.124-.272-.198-.57-.347m-5.421 7.403h-.004a9.87 9.87 0 01-5.031-1.378l-.361-.214-3.741.982.998-3.648-.235-.374a9.86 9.86 0 01-1.51-5.26c.001-5.45 4.436-9.884 9.888-9.884 2.64 0 5.122 1.03 6.988 2.898a9.825 9.825 0 012.893 6.994c-.003 5.45-4.437 9.884-9.885 9.884m8.413-18.297A11.815 11.815 0 0012.05 0C5.495 0 .16 5.335.157 11.892c0 2.096.547 4.142 1.588 5.945L.057 24l6.305-1.654a11.882 11.882 0 005.683 1.448h.005c6.554 0 11.89-5.335 11.893-11.893a11.821 11.821 0 00-3.48-8.413z"/></svg>
                                </a>
                            </div>

                            <a href="{{ route('berita.index') }}" class="lp-btn lp-btn--ghost lp-article__back">
                                ← Kembali ke Berita
                            </a>
                        </footer>
                    </article>

                    {{-- BERITA LAINNYA (di bawah artikel) --}}
                    @if($beritaLain->isNotEmpty())
                        <section class="lp-article-related">
                            <div class="lp-section__head">
                                <span class="lp-section__eyebrow">📖 Baca Juga</span>
                                <h2 class="lp-section__title">Berita Lainnya</h2>
                                <p class="lp-section__desc">Artikel menarik lainnya</p>
                            </div>
                            <div class="lp-article-related__grid lp-article-related__grid--4col">
                                @foreach($beritaLain->take(4) as $item)
                                    <article class="lp-news-card--related lp-news-card--related--clean">
                                        <a href="{{ route('berita.show', $item->slug) }}" class="lp-news-card__link">
                                            <div class="lp-news-card__thumb">
                                                @if(!empty($item->gambar))
                                                    <img src="{{ asset('storage/' . $item->gambar) }}" alt="{{ $item->judul }}" loading="lazy">
                                                @else
                                                    <span class="lp-news-card__thumb-placeholder">PUK</span>
                                                @endif
                                                @if($item->is_breaking)
                                                    <span class="lp-news-card__badge-breaking">🚨</span>
                                                @endif
                                            </div>
                                            <div class="lp-news-card__body">
                                                <h3 class="lp-news-card__judul">{{ $item->judul }}</h3>
                                            </div>
                                        </a>
                                    </article>
                                @endforeach
                            </div>
                        </section>
                    @endif
                </main>

                {{-- ===== SIDEBAR ===== --}}
                <aside class="lp-article-sidebar">
                    <div class="lp-article-sidebar__inner">

                        {{-- Widget: Berita Terbaru --}}
                        @php
                            $recentNews = \App\Models\News::published()
                                ->where('id', '!=', $berita->id)
                                ->latest('published_at')
                                ->take(5)
                                ->get();
                        @endphp
                        @if($recentNews->isNotEmpty())
                            <div class="lp-sidebar-widget lp-sidebar-widget--recent">
                                <h4 class="lp-sidebar-widget__title">
                                    <span class="lp-sidebar-widget__icon">⚡</span> Berita Terbaru
                                </h4>
                                <ul class="lp-sidebar-widget__list lp-sidebar-widget__list--recent">
                                    @foreach($recentNews as $item)
                                        <li class="lp-sidebar-widget__item">
                                            <a href="{{ route('berita.show', $item->slug) }}" class="lp-sidebar-widget__link lp-sidebar-widget__link--recent">
                                                <div class="lp-sidebar-widget__thumb">
                                                    @if(!empty($item->gambar))
                                                        <img src="{{ asset('storage/' . $item->gambar) }}" alt="{{ $item->judul }}" loading="lazy">
                                                    @else
                                                        <span class="lp-sidebar-widget__thumb-placeholder">PUK</span>
                                                    @endif
                                                </div>
                                                <div class="lp-sidebar-widget__content">
                                                    <div class="lp-sidebar-widget__top">
                                                        <span class="lp-sidebar-widget__kategori">{{ $item->kategori }}</span>
                                                        <time class="lp-sidebar-widget__time">{{ $item->published_at->translatedFormat('d M Y') }}</time>
                                                    </div>
                                                    <span class="lp-sidebar-widget__judul">{{ $item->judul }}</span>
                                                </div>
                                            </a>
                                        </li>
                                    @endforeach
                                </ul>
                            </div>
                        @endif

                        {{-- Widget: Populer (dengan thumbnail) --}}
                        @php
                            $popularNews = \App\Models\News::published()
                                ->where('id', '!=', $berita->id)
                                ->orderBy('views', 'desc')
                                ->take(5)
                                ->get();
                        @endphp
                        @if($popularNews->isNotEmpty())
                            <div class="lp-sidebar-widget lp-sidebar-widget--popular">
                                <h4 class="lp-sidebar-widget__title">
                                    <span class="lp-sidebar-widget__icon">⭐</span> Berita Populer Minggu Ini
                                </h4>
                                <ul class="lp-sidebar-widget__list lp-sidebar-widget__list--popular">
                                    @foreach($popularNews as $item)
                                        <li class="lp-sidebar-widget__item">
                                            <a href="{{ route('berita.show', $item->slug) }}" class="lp-sidebar-widget__link--popular">
                                                <span class="lp-sidebar-widget__number">{{ $loop->iteration }}</span>
                                                <div class="lp-sidebar-widget__thumb">
                                                    @if(!empty($item->gambar))
                                                        <img src="{{ asset('storage/' . $item->gambar) }}" alt="{{ $item->judul }}" loading="lazy">
                                                    @else
                                                        <span class="lp-sidebar-widget__thumb-placeholder">PUK</span>
                                                    @endif
                                                </div>
                                                <div class="lp-sidebar-widget__content--popular">
                                                    <span class="lp-sidebar-widget__text">{{ $item->judul }}</span>
                                                    <span class="lp-sidebar-widget__views">{{ number_format($item->views) }} views</span>
                                                </div>
                                            </a>
                                        </li>
                                    @endforeach
                                </ul>
                            </div>
                        @endif

                        {{-- Widget: Kategori --}}
                        @php
                            $categories = \App\Models\News::published()
                                ->select('kategori')
                                ->distinct()
                                ->pluck('kategori');
                        @endphp
                        @if($categories->isNotEmpty())
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
                        @endif

                        {{-- Widget: Tags Populer --}}
                        @php
                            $popularTags = \App\Models\Tag::withCount('news')
                                ->having('news_count', '>', 0)
                                ->orderBy('news_count', 'desc')
                                ->take(10)
                                ->get();
                        @endphp
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

                        

                    </div>
                </aside>

            </div>
        </div>
    </div>

    {{-- ===== MOBILE BOTTOM NAV ===== --}}
    <nav class="lp-mobile-bottomnav" aria-label="Navigasi cepat">
        <a href="{{ route('landing.index') }}" class="lp-mobile-bottomnav__item">
            <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M3 12l9-9 9 9"/><path d="M9 21V12h6v9"/></svg>
            <span>Beranda</span>
        </a>
        <a href="{{ route('berita.index') }}" class="lp-mobile-bottomnav__item">
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
{{-- ===== JSON-LD SCHEMA ===== --}}
@php
    $jsonLd = [
        '@context' => 'https://schema.org',
        '@type' => 'NewsArticle',
        'headline' => $berita->judul,
        'image' => !empty($berita->gambar) ? asset('storage/' . $berita->gambar) : asset('favicon.png'),
        'datePublished' => $berita->published_at->toIso8601String(),
        'dateModified' => \Illuminate\Support\Carbon::parse($berita->last_edited_at ?? $berita->published_at)->toIso8601String(),
        'author' => [
            '@type' => 'Person',
            'name' => $berita->creator->nama_lengkap ?? 'Redaksi PUK OMRON',
        ],
        'publisher' => [
            '@type' => 'Organization',
            'name' => 'PUK OMRON',
            'logo' => [
                '@type' => 'ImageObject',
                'url' => asset('favicon.png'),
            ],
        ],
        'description' => $berita->ringkasan,
        'mainEntityOfPage' => [
            '@type' => 'WebPage',
            '@id' => route('berita.show', $berita->slug),
        ],
    ];
@endphp
<script type="application/ld+json">{!! json_encode($jsonLd, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) !!}</script>

<script>
document.addEventListener('DOMContentLoaded', function () {
    // ===== READING PROGRESS BAR =====
    const bar = document.getElementById('lpReadingProgressBar');
    const article = document.querySelector('.lp-article__body');
    if (bar && article) {
        function updateProgress() {
            const rect = article.getBoundingClientRect();
            const articleTop = rect.top + window.scrollY;
            const articleHeight = article.offsetHeight;
            const scrolled = window.scrollY - articleTop + window.innerHeight * 0.3;
            const percent = Math.min(100, Math.max(0, (scrolled / articleHeight) * 100));
            bar.style.width = percent + '%';
        }
        window.addEventListener('scroll', updateProgress, { passive: true });
        window.addEventListener('resize', updateProgress);
        updateProgress();
    }
});

</script>
@endpush