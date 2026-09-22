{{--
    resources/views/landing-page/partials/kegiatan-section.blade.php

    Carousel kegiatan "multi-card peek" — 3 kartu tampak di desktop, 2 di
    tablet, ~1.15 (dengan peek) di mobile. Geser 1 kartu per step, auto-slide,
    pause on hover. Self-contained (query langsung lewat blok PHP di bawah).
--}}
@if (session('anggota'))
@php
    $kegiatanCarousel = \App\Models\Kegiatan::where('status', 'dibuka')
        ->orderBy('tanggal_mulai')
        ->limit(10)
        ->get();
@endphp

@if ($kegiatanCarousel->isNotEmpty())
<section id="kegiatan" class="lp-section lp-section--alt lp-kg-section">
    <div class="lp-container">
        <div class="lp-section__head-row animate-on-scroll">
            <div class="lp-section__head lp-section__head--left">
                <span class="lp-section__eyebrow lp-kg-eyebrow">
                    <span class="lp-kg-eyebrow__dot"></span>
                    Kegiatan
                </span>
                <h2 class="lp-section__title lp-kg-title">Ikuti Kegiatan PUK OMRON</h2>
                <p class="lp-section__desc lp-kg-desc">
                    Pendidikan, seminar, dan aksi yang sedang dibuka pendaftarannya. Login sebagai anggota untuk mendaftar.
                </p>
            </div>
            <a href="{{ route('kegiatan.index') }}" class="lp-btn lp-btn--ghost lp-kg-viewall">
                Lihat Semua Kegiatan
                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><path d="M5 12h14M12 5l7 7-7 7"/></svg>
            </a>
        </div>

        <div class="lp-kg-carousel"
             x-data="lpKegiatanCarousel({{ $kegiatanCarousel->count() }})"
             x-init="init()"
             @mouseenter="pause()" @mouseleave="resume()">

            <div class="lp-kg-carousel__viewport">
                <div class="lp-kg-carousel__track" x-ref="track" :style="{ transform: 'translateX(-' + (index * stepPx) + 'px)' }">
                    @foreach ($kegiatanCarousel as $item)
                        @php
                            $sisa = $item->sisa_kuota;
                            $penuh = $item->kuota_penuh;
                            $persen = ($item->kuota && $item->kuota > 0)
                                ? min(100, round(($item->kuota_terisi / $item->kuota) * 100))
                                : null;
                        @endphp
                        <div class="lp-kg-carousel__slide">
                            <a href="{{ route('kegiatan.show', $item) }}" class="lp-kg-card">
                                <div class="lp-kg-card__image">
                                    @if ($item->thumbnail)
                                        <img src="{{ asset('storage/' . $item->thumbnail) }}" alt="{{ $item->judul }}" loading="lazy">
                                    @else
                                        <div class="lp-kg-card__placeholder">
                                            <svg width="36" height="36" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="4" width="18" height="18" rx="2"/><path d="M16 2v4M8 2v4M3 10h18"/></svg>
                                        </div>
                                    @endif
                                    <div class="lp-kg-card__image-fade"></div>
                                    <span class="lp-kg-card__badge lp-kg-card__badge--{{ $item->jenis }}">
                                        {{ ucfirst($item->jenis) }}
                                    </span>
                                    @if ($penuh)
                                        <span class="lp-kg-card__badge-full">Penuh</span>
                                    @endif
                                </div>

                                <div class="lp-kg-card__body">
                                    @if ($item->jenis === 'pendidikan' && $item->tingkat_kategori)
                                        <span class="lp-kg-card__tingkat">
                                            {{ ucfirst($item->tingkat_kategori) }}@if ($item->tingkat_keterangan) <span class="lp-kg-card__tingkat-sep">·</span> {{ $item->tingkat_keterangan }} @endif
                                        </span>
                                    @endif

                                    <h3 class="lp-kg-card__title">{{ $item->judul }}</h3>

                                    <div class="lp-kg-card__meta-row">
                                        <span class="lp-kg-card__meta">
                                            <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="4" width="18" height="18" rx="2"/><path d="M16 2v4M8 2v4M3 10h18"/></svg>
                                            {{ $item->tanggal_mulai->translatedFormat('d M Y') }}
                                        </span>
                                        <span class="lp-kg-card__meta">
                                            <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/><path d="M23 21v-2a4 4 0 0 0-3-3.87"/><path d="M16 3.13a4 4 0 0 1 0 7.75"/></svg>
                                            {{ $item->kuota_terisi }} pendaftar
                                        </span>
                                    </div>

                                    @if ($item->kuota)
                                        <div class="lp-kg-card__quota">
                                            <div class="lp-kg-card__quota-bar">
                                                <div class="lp-kg-card__quota-fill {{ $penuh ? 'is-full' : '' }}" style="width: {{ $persen }}%"></div>
                                            </div>
                                            <span class="lp-kg-card__quota-text {{ $penuh ? 'is-full' : '' }}">
                                                @if ($penuh)
                                                    Kuota penuh
                                                @else
                                                    <strong>{{ $sisa }}</strong> dari {{ $item->kuota }} kuota tersisa
                                                @endif
                                            </span>
                                        </div>
                                    @endif

                                    <div class="lp-kg-card__cta">
                                        <span>Lihat Detail</span>
                                        <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><path d="M5 12h14M12 5l7 7-7 7"/></svg>
                                    </div>
                                </div>
                            </a>
                        </div>
                    @endforeach
                </div>
            </div>

            <template x-if="total > visible">
                <div>
                    <button type="button" class="lp-kg-carousel__nav lp-kg-carousel__nav--prev" @click="prev()" aria-label="Kegiatan sebelumnya">
                        <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><path d="M15 18l-6-6 6-6"/></svg>
                    </button>
                    <button type="button" class="lp-kg-carousel__nav lp-kg-carousel__nav--next" @click="next()" aria-label="Kegiatan berikutnya">
                        <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><path d="M9 18l6-6-6-6"/></svg>
                    </button>

                    <div class="lp-kg-carousel__dots">
                        <template x-for="i in (maxIndex + 1)" :key="i">
                            <button type="button"
                                    class="lp-kg-carousel__dot"
                                    :class="{ 'is-active': index === i - 1 }"
                                    @click="goTo(i - 1)"
                                    :aria-label="'Ke posisi ' + i"></button>
                        </template>
                    </div>
                </div>
            </template>
        </div>
    </div>
</section>

@push('styles')
<style>
    /* ---------- Section head ---------- */
    .lp-kg-section { position: relative; }
    .lp-kg-eyebrow {
        display: inline-flex; align-items: center; gap: 8px;
        padding: 6px 14px; border-radius: 999px;
        background: color-mix(in srgb, var(--accent) 10%, transparent);
        color: var(--accent); font-size: 12.5px; font-weight: 700;
        letter-spacing: .04em; text-transform: uppercase;
        border: 1px solid color-mix(in srgb, var(--accent) 22%, transparent);
    }
    .lp-kg-eyebrow__dot {
        width: 7px; height: 7px; border-radius: 50%;
        background: var(--accent);
        box-shadow: 0 0 0 3px color-mix(in srgb, var(--accent) 22%, transparent);
    }
    .lp-kg-title {
        font-size: clamp(24px, 3vw, 32px) !important;
        letter-spacing: -0.02em;
        margin-top: 10px !important;
    }
    .lp-kg-desc {
        max-width: 620px; margin-top: 8px !important;
        font-size: 15.5px !important; line-height: 1.6;
    }
    .lp-kg-viewall { white-space: nowrap; }

    /* ---------- Carousel shell ---------- */
    .lp-kg-carousel { position: relative; margin-top: 32px; }
    .lp-kg-carousel__viewport { overflow: hidden; padding: 6px 0 4px; margin: -6px 0 -4px; }
    .lp-kg-carousel__track {
        display: flex; gap: 22px;
        transition: transform .55s cubic-bezier(.22,1,.36,1);
        will-change: transform;
    }

    .lp-kg-carousel__slide {
        flex: 0 0 calc((100% - 2 * 22px) / 3);
        display: flex;
    }

    /* ---------- Card ---------- */
    .lp-kg-card {
        display: flex; flex-direction: column; width: 100%;
        background: var(--bg-surface);
        border: 1px solid var(--border-color);
        border-radius: 18px;
        overflow: hidden;
        text-decoration: none; color: inherit;
        transition: transform .35s cubic-bezier(.22,1,.36,1),
                    box-shadow .35s ease,
                    border-color .25s ease;
        position: relative;
    }
    .lp-kg-card::after {
        content: '';
        position: absolute; inset: 0;
        border-radius: 18px;
        pointer-events: none;
        box-shadow: inset 0 0 0 1px transparent;
        transition: box-shadow .3s ease;
    }
    .lp-kg-card:hover {
        transform: translateY(-6px);
        box-shadow: 0 20px 40px -18px rgba(15, 23, 42, .28),
                    0 8px 20px -12px rgba(15, 23, 42, .18);
        border-color: color-mix(in srgb, var(--accent) 35%, var(--border-color));
    }

    /* Image area */
    .lp-kg-card__image {
        position: relative;
        aspect-ratio: 16/10;
        background: linear-gradient(135deg,
            color-mix(in srgb, var(--accent) 10%, var(--bg-surface-alt)),
            var(--bg-surface-alt));
        overflow: hidden;
    }
    .lp-kg-card__image img {
        width: 100%; height: 100%; object-fit: cover; display: block;
        transition: transform .6s cubic-bezier(.22,1,.36,1);
    }
    .lp-kg-card:hover .lp-kg-card__image img { transform: scale(1.06); }

    .lp-kg-card__placeholder {
        display: flex; align-items: center; justify-content: center;
        width: 100%; height: 100%;
        color: color-mix(in srgb, var(--accent) 55%, transparent);
        background:
            radial-gradient(circle at 30% 20%, color-mix(in srgb, var(--accent) 18%, transparent), transparent 60%),
            linear-gradient(135deg, var(--bg-surface-alt), color-mix(in srgb, var(--accent) 8%, var(--bg-surface-alt)));
    }

    .lp-kg-card__image-fade {
        position: absolute; inset: 0;
        background: linear-gradient(to top, rgba(0,0,0,.35) 0%, transparent 45%);
        pointer-events: none;
    }

    /* Badges */
    .lp-kg-card__badge, .lp-kg-card__badge-full {
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
    .lp-kg-card__badge { left: 12px; background: rgba(99, 102, 241, .9); }
    .lp-kg-card__badge--seminar  { background: rgba(14, 116, 144, .9); }
    .lp-kg-card__badge--aksi     { background: rgba(194, 65, 12, .9); }
    .lp-kg-card__badge--lainnya  { background: rgba(75, 85, 99, .9); }
    .lp-kg-card__badge-full {
        right: 12px;
        background: rgba(185, 28, 28, .92);
        text-transform: uppercase; letter-spacing: .06em;
    }

    /* Body */
    .lp-kg-card__body {
        padding: 18px 20px 20px;
        display: flex; flex-direction: column; gap: 8px;
        flex: 1;
    }

    .lp-kg-card__tingkat {
        display: inline-flex; align-items: center; gap: 6px;
        align-self: flex-start;
        font-size: 11px; font-weight: 700;
        color: var(--accent);
        text-transform: uppercase; letter-spacing: .06em;
        padding: 3px 9px; border-radius: 6px;
        background: color-mix(in srgb, var(--accent) 10%, transparent);
    }
    .lp-kg-card__tingkat-sep { opacity: .5; }

    .lp-kg-card__title {
        font-size: 16px; font-weight: 700;
        line-height: 1.4; margin: 2px 0 4px;
        color: var(--text-primary);
        display: -webkit-box;
        -webkit-line-clamp: 2;
        -webkit-box-orient: vertical;
        overflow: hidden;
        transition: color .2s ease;
    }
    .lp-kg-card:hover .lp-kg-card__title { color: var(--accent); }

    .lp-kg-card__meta-row {
        display: flex; flex-wrap: wrap; gap: 4px 14px;
        margin-bottom: 2px;
    }
    .lp-kg-card__meta {
        display: inline-flex; align-items: center; gap: 6px;
        font-size: 12.5px; color: var(--text-secondary);
        font-weight: 500;
    }
    .lp-kg-card__meta svg { opacity: .75; flex-shrink: 0; }

    /* Quota bar */
    .lp-kg-card__quota {
        margin-top: auto; padding-top: 12px;
        display: flex; flex-direction: column; gap: 6px;
    }
    .lp-kg-card__quota-bar {
        width: 100%; height: 5px; border-radius: 999px;
        background: color-mix(in srgb, var(--border-color) 70%, transparent);
        overflow: hidden;
    }
    .lp-kg-card__quota-fill {
        height: 100%; border-radius: 999px;
        background: linear-gradient(90deg,
            color-mix(in srgb, var(--accent) 70%, #22c55e),
            var(--accent));
        transition: width .5s cubic-bezier(.22,1,.36,1);
    }
    .lp-kg-card__quota-fill.is-full {
        background: linear-gradient(90deg, #ef4444, #b91c1c);
    }
    .lp-kg-card__quota-text {
        font-size: 12px; color: var(--text-secondary);
    }
    .lp-kg-card__quota-text strong { color: var(--text-primary); }
    .lp-kg-card__quota-text.is-full { color: #b91c1c; font-weight: 600; }

    /* CTA */
    .lp-kg-card__cta {
        margin-top: 10px;
        display: inline-flex; align-items: center; justify-content: space-between;
        gap: 8px;
        padding: 10px 14px;
        border-radius: 10px;
        background: color-mix(in srgb, var(--accent) 10%, transparent);
        color: var(--accent);
        font-size: 13px; font-weight: 700;
        letter-spacing: .01em;
        transition: background .25s ease, color .25s ease, transform .25s ease;
    }
    .lp-kg-card__cta svg { transition: transform .3s ease; }
    .lp-kg-card:hover .lp-kg-card__cta {
        background: var(--accent); color: #fff;
    }
    .lp-kg-card:hover .lp-kg-card__cta svg { transform: translateX(4px); }

    /* ---------- Nav buttons ---------- */
    .lp-kg-carousel__nav {
        position: absolute; top: 42%;
        transform: translateY(-50%) scale(.92);
        width: 44px; height: 44px; border-radius: 50%;
        border: 1px solid var(--border-color);
        background: color-mix(in srgb, var(--bg-surface) 92%, transparent);
        backdrop-filter: blur(12px);
        -webkit-backdrop-filter: blur(12px);
        color: var(--text-primary);
        box-shadow: 0 8px 24px -10px rgba(15, 23, 42, .25);
        display: flex; align-items: center; justify-content: center;
        cursor: pointer; z-index: 2;
        opacity: 0;
        transition: opacity .3s ease, transform .3s ease,
                    background .25s ease, color .25s ease, border-color .25s ease;
    }
    .lp-kg-carousel:hover .lp-kg-carousel__nav { opacity: 1; transform: translateY(-50%) scale(1); }
    .lp-kg-carousel__nav:hover {
        background: var(--accent); color: #fff;
        border-color: var(--accent);
        transform: translateY(-50%) scale(1.06) !important;
    }
    .lp-kg-carousel__nav--prev { left: -18px; }
    .lp-kg-carousel__nav--next { right: -18px; }

    /* ---------- Dots ---------- */
    .lp-kg-carousel__dots {
        display: flex; justify-content: center;
        gap: 6px; margin-top: 26px;
    }
    .lp-kg-carousel__dot {
        width: 7px; height: 7px; border-radius: 999px;
        border: none; padding: 0; cursor: pointer;
        background: color-mix(in srgb, var(--text-secondary) 30%, transparent);
        transition: all .3s cubic-bezier(.22,1,.36,1);
    }
    .lp-kg-carousel__dot:hover { background: color-mix(in srgb, var(--accent) 60%, transparent); }
    .lp-kg-carousel__dot.is-active {
        width: 26px;
        background: var(--accent);
    }

    /* ---------- Responsive ---------- */
    @media (max-width: 1024px) {
        .lp-kg-carousel__slide { flex-basis: calc((100% - 22px) / 2); }
        .lp-kg-carousel__nav { opacity: 1; transform: translateY(-50%) scale(1); }
        .lp-kg-carousel__nav--prev { left: -8px; }
        .lp-kg-carousel__nav--next { right: -8px; }
    }
    @media (max-width: 640px) {
        .lp-kg-carousel__track { gap: 16px; }
        .lp-kg-carousel__slide { flex-basis: 82%; }
        .lp-kg-carousel__nav { display: none; }
        .lp-kg-card__body { padding: 16px 16px 18px; }
        .lp-kg-card__title { font-size: 15px; }
        .lp-kg-carousel__dots { margin-top: 20px; }
    }
</style>
@endpush

@push('scripts')
<script>
    document.addEventListener('alpine:init', () => {
        Alpine.data('lpKegiatanCarousel', (total) => ({
            index: 0,
            total,
            visible: 3,
            stepPx: 0,
            _timer: null,
            _resizeHandler: null,

            init() {
                this.$nextTick(() => this.measure());
                this._resizeHandler = () => this.measure();
                window.addEventListener('resize', this._resizeHandler);
                this.resume();
            },
            destroy() {
                window.removeEventListener('resize', this._resizeHandler);
                this.pause();
            },

            measure() {
                const track = this.$refs.track;
                const firstCard = track?.children[0];
                if (!firstCard) return;

                const trackStyles = getComputedStyle(track);
                const gap = parseFloat(trackStyles.columnGap || trackStyles.gap || 0);
                this.stepPx = firstCard.getBoundingClientRect().width + gap;

                const viewportWidth = track.parentElement.getBoundingClientRect().width;
                this.visible = Math.max(1, Math.round((viewportWidth + gap) / this.stepPx));

                if (this.index > this.maxIndex) this.index = this.maxIndex;
            },

            get maxIndex() { return Math.max(0, this.total - this.visible); },

            next() { this.index = this.index >= this.maxIndex ? 0 : this.index + 1; },
            prev() { this.index = this.index <= 0 ? this.maxIndex : this.index - 1; },
            goTo(i) { this.index = Math.min(Math.max(i, 0), this.maxIndex); this.resume(); },

            pause() { if (this._timer) { clearInterval(this._timer); this._timer = null; } },
            resume() {
                this.pause();
                if (this.total <= this.visible || window.matchMedia('(prefers-reduced-motion: reduce)').matches) return;
                this._timer = setInterval(() => this.next(), 2500);
            },
        }));
    });
</script>
@endpush
@endif
@endif