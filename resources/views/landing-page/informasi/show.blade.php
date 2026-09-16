@extends('landing-page.layouts.app')

@section('title', $informasi->judul . ' - PUK OMRON')

@push('styles')
    @vite(['resources/css/landing-page/informasi/informasi.css'])
@endpush

@section('content')
<section class="lp-section lp-info-page">
    <div class="lp-container lp-info-page__inner">
        <a href="{{ route('landing.index') }}#" class="lp-info-page__back">&larr; Kembali ke Beranda</a>

        <div class="lp-article-grid">

            {{-- ===== KOLOM UTAMA (KONTEN INFORMASI) ===== --}}
            <main class="lp-article-main">
                
                <h1 class="lp-section__title">{{ $informasi->judul }}</h1>

                <div class="lp-info-page__content">
                    {!! $informasi->konten !!}
                </div>
            </main>

            {{-- ===== SIDEBAR (reuse widget Berita) ===== --}}
            <aside class="lp-article-sidebar">
                <div class="lp-article-sidebar__inner">

                    {{-- Widget: Berita Terbaru --}}
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

                    {{-- Widget: Populer --}}
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
</section>
@endsection