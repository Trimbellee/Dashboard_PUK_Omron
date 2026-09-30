{{-- ===== GALERI (beranda) — pemutaran lewat lightbox bersama ===== --}}
@php $placeholder = asset('images/placeholder-media.png'); @endphp

<section class="lp-section" id="galeri">
    <div class="lp-container">
        <div class="lp-section__head-row">
            <div>
                <span class="lp-hero__badge">Galeri</span>
                <h2 class="lp-section__title">Dokumentasi Kegiatan</h2>
            </div>
            <a href="{{ route('galeri.index') }}" class="lp-btn lp-btn--ghost" style="gap: 8px;">
                Lihat Semua Galeri
                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><path d="M5 12h14M12 5l7 7-7 7"/></svg>
            </a>
        </div>

        <div class="lp-galeri-grid">
            @forelse ($galeriTerbaru as $foto)
                <div class="lp-galeri-item">
                    <div class="lp-thumb-static"
                         role="button"
                         tabindex="0"
                         data-galeri-open="{{ $foto->id }}"
                         aria-label="Buka {{ $foto->type === 'video' ? 'video' : 'foto' }}: {{ $foto->judul ?? 'Media' }}">
                        @if ($foto->thumbnail_url)
                            <img src="{{ $foto->thumbnail_url }}"
                                 alt="{{ $foto->judul ?? 'Media' }}"
                                 loading="lazy"
                                 onerror="this.onerror=null;this.src='{{ $placeholder }}'">
                        @else
                            <div class="lp-thumb-placeholder">
                                {{ $foto->type === 'video' ? '🎬' : '🖼️' }}
                            </div>
                        @endif
                        @if ($foto->type === 'video')
                            <span class="lp-video-play-icon" aria-hidden="true">▶</span>
                        @endif
                        <div class="lp-galeri-item__caption">
                            {{ $foto->judul ?? 'Media' }}
                        </div>
                    </div>
                </div>
            @empty
                <div class="lp-empty-state">
                    <span class="lp-empty-state__icon">🖼️</span>
                    <p class="lp-empty-state__text">Belum ada media galeri.</p>
                </div>
            @endforelse
        </div>
    </div>
</section>

@include('landing-page.partials.galeri-lightbox', ['mediaItems' => $galeriTerbaru])