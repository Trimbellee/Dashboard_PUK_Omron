{{--
    Lightbox galeri (dipakai halaman galeri publik dan beranda).

    Variabel:
      $mediaItems  : Collection / Paginator berisi model Gallery yang tampil di halaman
      $focusMedia  : (opsional) model Gallery untuk deep-link ?media=ID
      $syncUrl     : (opsional, default false) true = sinkronkan ?media=ID di URL

    Cara membuka dari elemen mana pun:
      <div data-galeri-open="{{ $item->id }}" role="button" tabindex="0">...</div>
--}}
@php
    $lbSource = $mediaItems instanceof \Illuminate\Pagination\AbstractPaginator
        ? $mediaItems->getCollection()
        : collect($mediaItems ?? []);

    if (!empty($focusMedia) && !$lbSource->contains('id', $focusMedia->id)) {
        $lbSource = $lbSource->concat([$focusMedia]);
    }

    $lbData = $lbSource->map(fn ($m) => [
        'id'         => $m->id,
        'type'       => $m->type,
        'source'     => $m->source,
        'externalId' => $m->external_id,
        'embed'      => $m->embed_url,
        'thumb'      => $m->thumbnail_url,
        'judul'      => $m->judul,
        'kegiatan'   => $m->kegiatan,
        'kategori'   => \App\Models\Gallery::KATEGORI_LIST[$m->kategori] ?? null,
        'tanggal'    => $m->published_at
            ? \Illuminate\Support\Carbon::parse($m->published_at)->translatedFormat('d F Y')
            : null,
    ])->values()->all();

    $lbOpts = [
        'syncUrl'   => (bool) ($syncUrl ?? false),
        'shareBase' => route('galeri.index'),
    ];
@endphp

<style>
    .lp-lb {
        position: fixed;
        inset: 0;
        z-index: 1000;
        display: flex;
        align-items: center;
        justify-content: center;
        padding: 56px 72px 96px;
        background: rgba(0, 0, 0, 0.9);
    }
    .lp-lb__stage {
        position: relative;
        width: min(1100px, 100%, calc((100vh - 152px) * 16 / 9));
        aspect-ratio: 16 / 9;
        background: #000;
        border-radius: var(--radius-md, 12px);
        overflow: hidden;
    }
    .lp-lb__stage--vertical {
        width: auto;
        height: 100%;
        max-width: 100%;
        aspect-ratio: 9 / 16;
    }
    .lp-lb__stage iframe {
        width: 100%;
        height: 100%;
        border: 0;
        display: block;
    }
    .lp-lb__btn {
        position: absolute;
        z-index: 2;
        display: flex;
        align-items: center;
        justify-content: center;
        width: 42px;
        height: 42px;
        border: none;
        border-radius: 50%;
        background: rgba(255, 255, 255, 0.14);
        color: #fff;
        cursor: pointer;
        transition: background 0.2s ease;
    }
    .lp-lb__btn:hover,
    .lp-lb__btn:focus-visible {
        background: rgba(255, 255, 255, 0.3);
        outline: none;
    }
    .lp-lb__btn--close { top: 12px; right: 16px; }
    .lp-lb__btn--prev  { left: 16px;  top: 50%; transform: translateY(-50%); }
    .lp-lb__btn--next  { right: 16px; top: 50%; transform: translateY(-50%); }
    .lp-lb__counter {
        position: absolute;
        top: 22px;
        left: 20px;
        z-index: 2;
        color: rgba(255, 255, 255, 0.85);
        font-size: 13px;
        font-weight: 600;
        font-variant-numeric: tabular-nums;
    }
    .lp-lb__caption {
        position: absolute;
        left: 0;
        right: 0;
        bottom: 0;
        z-index: 2;
        display: flex;
        flex-wrap: wrap;
        align-items: center;
        justify-content: space-between;
        gap: 10px 20px;
        padding: 14px 72px 18px;
        color: #fff;
    }
    .lp-lb__title {
        margin: 0;
        font-size: 15px;
        font-weight: 700;
    }
    .lp-lb__meta {
        margin: 2px 0 0;
        font-size: 12.5px;
        color: rgba(255, 255, 255, 0.72);
    }
    .lp-lb__actions {
        display: flex;
        gap: 8px;
        flex-wrap: wrap;
    }
    .lp-lb__link {
        display: inline-flex;
        align-items: center;
        gap: 6px;
        padding: 6px 14px;
        border: none;
        border-radius: 30px;
        background: rgba(255, 255, 255, 0.14);
        color: #fff;
        font-size: 12.5px;
        font-weight: 600;
        text-decoration: none;
        cursor: pointer;
        transition: background 0.2s ease;
    }
    .lp-lb__link:hover { background: rgba(255, 255, 255, 0.28); }
    .lp-lb__link--wa { background: #25d366; color: #06280f; }
    .lp-lb__link--wa:hover { background: #3be07b; }
    .lp-lb__link svg { flex-shrink: 0; }

    @media (max-width: 640px) {
        .lp-lb { padding: 56px 8px 120px; }
        .lp-lb__btn { width: 36px; height: 36px; }
        .lp-lb__btn--prev { left: 6px; }
        .lp-lb__btn--next { right: 6px; }
        .lp-lb__caption { padding: 12px 14px 16px; }
    }
</style>

<div x-data="galeriLightbox(@js($lbData), @js($lbOpts))"
     x-cloak
     @galeri-open.window="open($event.detail.id)"
     @keydown.escape.window="close()"
     @keydown.arrow-left.window="prev()"
     @keydown.arrow-right.window="next()">

    <div class="lp-lb"
         x-show="current"
         x-transition.opacity
         role="dialog"
         aria-modal="true"
         :aria-label="current ? (current.judul || 'Media galeri') : 'Media galeri'"
         @click.self="close()">

        <span class="lp-lb__counter" x-text="(index + 1) + ' / ' + items.length"></span>

        <button type="button" class="lp-lb__btn lp-lb__btn--close" x-ref="closeBtn" @click="close()" aria-label="Tutup">
            <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4"><path d="M6 6l12 12M18 6 6 18"/></svg>
        </button>

        <button type="button" class="lp-lb__btn lp-lb__btn--prev" x-show="hasPrev" @click="prev()" aria-label="Sebelumnya">
            <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4"><path d="m15 18-6-6 6-6"/></svg>
        </button>
        <button type="button" class="lp-lb__btn lp-lb__btn--next" x-show="hasNext" @click="next()" aria-label="Berikutnya">
            <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4"><path d="m9 18 6-6-6-6"/></svg>
        </button>

        <div class="lp-lb__stage" :class="{ 'lp-lb__stage--vertical': current && current.source === 'tiktok' }">
            {{-- iframe hanya ada saat lightbox terbuka; menutup = video berhenti --}}
            <template x-if="current">
                <iframe :src="src"
                        allow="autoplay; encrypted-media; fullscreen; picture-in-picture"
                        referrerpolicy="strict-origin-when-cross-origin"
                        allowfullscreen></iframe>
            </template>
        </div>

        <div class="lp-lb__caption" x-show="current">
            <div>
                <p class="lp-lb__title" x-text="current ? (current.judul || 'Tanpa judul') : ''"></p>
                <p class="lp-lb__meta"
                   x-text="current ? [current.kegiatan, current.kategori, current.tanggal].filter(Boolean).join(' · ') : ''"></p>
            </div>
            <div class="lp-lb__actions">
                {{-- HP/browser yang mendukung Web Share API: buka menu bagikan bawaan (WhatsApp, Telegram, dll.) --}}
                <button type="button" class="lp-lb__link lp-lb__link--wa" x-show="canShare" @click="nativeShare()">
                    <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4" stroke-linecap="round" stroke-linejoin="round"><circle cx="18" cy="5" r="3"/><circle cx="6" cy="12" r="3"/><circle cx="18" cy="19" r="3"/><path d="m8.6 13.5 6.8 4M15.4 6.5l-6.8 4"/></svg>
                    Bagikan
                </button>
                {{-- Desktop: langsung ke WhatsApp --}}
                <a class="lp-lb__link lp-lb__link--wa" x-show="!canShare" :href="whatsappUrl" target="_blank" rel="noopener">
                    <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4" stroke-linecap="round" stroke-linejoin="round"><path d="M21 11.5a8.4 8.4 0 0 1-12.4 7.4L3 20.5l1.7-5.4A8.4 8.4 0 1 1 21 11.5z"/></svg>
                    WhatsApp
                </a>
                <button type="button" class="lp-lb__link" @click="copyLink()">
                    <span x-text="copied ? 'Tersalin ✓' : 'Salin link'"></span>
                </button>
                <a class="lp-lb__link" :href="originalUrl" target="_blank" rel="noopener">Buka sumber asli</a>
            </div>
        </div>
    </div>
</div>

<script>
    // Satu listener untuk semua elemen [data-galeri-open] (klik, Enter, Spasi)
    (function () {
        if (window.__lpGaleriOpenBound) return;
        window.__lpGaleriOpenBound = true;

        const fire = (el) => window.dispatchEvent(new CustomEvent('galeri-open', {
            detail: { id: parseInt(el.dataset.galeriOpen, 10) },
        }));

        document.addEventListener('click', (e) => {
            const t = e.target.closest('[data-galeri-open]');
            if (t) fire(t);
        });

        document.addEventListener('keydown', (e) => {
            if (e.key !== 'Enter' && e.key !== ' ') return;
            const t = e.target.closest && e.target.closest('[data-galeri-open]');
            if (t && t === e.target) {
                e.preventDefault();
                fire(t);
            }
        });
    })();

    document.addEventListener('alpine:init', () => {
        Alpine.data('galeriLightbox', (items, opts) => ({
            items,
            openId: null,
            syncUrl: !!opts.syncUrl,
            shareBase: opts.shareBase,
            copied: false,
            canShare: false,

            init() {
                this.canShare = typeof navigator !== 'undefined' && typeof navigator.share === 'function';

                this.$watch('openId', (v) => {
                    document.body.style.overflow = v ? 'hidden' : '';

                    if (this.syncUrl) {
                        const u = new URL(location.href);
                        if (v) u.searchParams.set('media', v); else u.searchParams.delete('media');
                        history.replaceState(null, '', u);
                    }

                    if (v) this.$nextTick(() => this.$refs.closeBtn && this.$refs.closeBtn.focus());
                });

                // Deep-link ?media=ID
                if (this.syncUrl) {
                    const id = parseInt(new URLSearchParams(location.search).get('media'), 10);
                    if (id && this.items.some((i) => i.id === id)) this.open(id);
                }
            },

            get index() { return this.items.findIndex((i) => i.id === this.openId); },
            get current() { return this.index >= 0 ? this.items[this.index] : null; },
            get hasPrev() { return this.index > 0; },
            get hasNext() { return this.index >= 0 && this.index < this.items.length - 1; },

            get src() {
                const c = this.current;
                if (!c) return '';
                if (c.source === 'youtube') {
                    return `https://www.youtube.com/embed/${c.externalId}?autoplay=1&rel=0&modestbranding=1&playsinline=1`;
                }
                if (c.source === 'tiktok') {
                    return c.embed + (c.embed.includes('?') ? '&' : '?') + 'autoplay=1';
                }
                return c.embed;
            },

            get originalUrl() {
                const c = this.current;
                if (!c) return '#';
                if (c.source === 'youtube') return `https://www.youtube.com/watch?v=${c.externalId}`;
                if (c.source === 'tiktok') return `https://www.tiktok.com/@_/video/${c.externalId}`;
                return `https://drive.google.com/file/d/${c.externalId}/view`;
            },

            get shareUrl() {
                return this.current ? `${this.shareBase}?media=${this.current.id}` : '';
            },

            open(id) {
                if (this.items.some((i) => i.id === id)) this.openId = id;
            },
            close() { this.openId = null; },
            prev() { if (this.hasPrev) this.openId = this.items[this.index - 1].id; },
            next() { if (this.hasNext) this.openId = this.items[this.index + 1].id; },

            get shareText() {
                const c = this.current;
                if (!c) return '';
                const nama = c.judul || c.kegiatan || 'Media galeri';
                return `${nama} — Galeri PUK OMRON`;
            },

            get whatsappUrl() {
                return `https://wa.me/?text=${encodeURIComponent(this.shareText + '\n' + this.shareUrl)}`;
            },

            async nativeShare() {
                if (!this.current) return;
                try {
                    await navigator.share({
                        title: this.shareText,
                        text: this.shareText,
                        url: this.shareUrl,
                    });
                } catch (e) {
                    // AbortError = pengguna menutup menu bagikan; selain itu pakai salin link
                    if (!e || e.name !== 'AbortError') this.copyLink();
                }
            },

            async copyLink() {
                const url = this.shareUrl;
                try {
                    await navigator.clipboard.writeText(url);
                } catch (e) {
                    const ta = document.createElement('textarea');
                    ta.value = url;
                    document.body.appendChild(ta);
                    ta.select();
                    document.execCommand('copy');
                    ta.remove();
                }
                this.copied = true;
                setTimeout(() => (this.copied = false), 1800);
            },
        }));
    });
</script>