@extends('landing-page.admin.layouts.app')

@push('page-styles')
    @vite(['resources/css/admin/admin-galeri.css'])
    
@endpush

@section('title', 'Kelola Galeri')

@section('content')
    @php
        use App\Helpers\PermissionHelper;
        $jabatan = session('anggota.jabatan_puk');
        $userId = session('anggota.id');
    @endphp

    <div class="lp-admin-head">
        <h1 class="lp-admin-head__title">Kelola Galeri</h1>
        <div style="display:flex; gap:8px; flex-wrap:wrap;">
            @if (PermissionHelper::can('approve_gallery'))
                <a href="{{ route('admin.galeri.kegiatan') }}" class="lp-btn lp-btn--ghost">Kelola Kegiatan</a>
            @endif
            @if (PermissionHelper::can('create_gallery'))
                <a href="{{ route('admin.galeri.create') }}" class="lp-btn lp-btn--primary">+ Tambah Media</a>
            @endif
        </div>
    </div>

    <div class="lp-stat-row">
        <div class="lp-stat-card">
            <span class="lp-stat-card__label">Draft / Revisi</span>
            <span class="lp-stat-card__value">{{ $stats['draft'] }}</span>
        </div>
        <div class="lp-stat-card lp-stat-card--pending">
            <span class="lp-stat-card__label">Menunggu Approval</span>
            <span class="lp-stat-card__value">{{ $stats['menunggu'] }}</span>
        </div>
        <div class="lp-stat-card lp-stat-card--success">
            <span class="lp-stat-card__label">Published</span>
            <span class="lp-stat-card__value">{{ $stats['published'] }}</span>
        </div>
    </div>

    <div class="lp-filter-tabs" style="margin-bottom: 20px;">
        <a href="{{ route('admin.galeri.index') }}" class="{{ $activeFilter === 'semua' ? 'active' : '' }}">Semua</a>
        <a href="{{ route('admin.galeri.index', ['filter' => 'draft']) }}" class="{{ $activeFilter === 'draft' ? 'active' : '' }}">Draft</a>
        <a href="{{ route('admin.galeri.index', ['filter' => 'menunggu']) }}" class="{{ $activeFilter === 'menunggu' ? 'active' : '' }}">Menunggu</a>
        <a href="{{ route('admin.galeri.index', ['filter' => 'published']) }}" class="{{ $activeFilter === 'published' ? 'active' : '' }}">Published</a>
    </div>

    <div class="lp-admin-galeri-grid">
        @forelse ($galeriList as $foto)
            @php
                $isYoutube = $foto->source === 'youtube';

                // Google Drive: iframe apa adanya, kontrol bawaan Drive yang dipakai.
                // YouTube TIDAK pakai iframe src manual lagi -- videoId dikirim langsung
                // ke YT.Player() lewat JS supaya API-nya tahu video mana yang diputar.
                $playSrc = $isYoutube ? null : $foto->embed_url;

                $tickerText = trim(($foto->judul ?? '') . ($foto->kegiatan ? ' — ' . $foto->kegiatan : ''));
            @endphp
            <article class="lp-admin-galeri-card">
                {{-- Scope playing state ke masing-masing card, biar tiap thumbnail independen --}}
                <div class="lp-admin-galeri-card__thumb"
                     @if ($isYoutube)
                        x-data="lpYtPlayer('yt-player-{{ $foto->id }}', 'yt-wrap-{{ $foto->id }}', '{{ $foto->external_id }}')"
                     @else
                        x-data="{ playing: false }"
                     @endif>

                    {{-- State diam: thumbnail statis + ikon hover --}}
                    <template x-if="!playing">
                        <div class="lp-thumb-static"
                             @click="{{ $isYoutube ? 'start()' : 'playing = true' }}"
                             role="button"
                             tabindex="0"
                             @keydown.enter="{{ $isYoutube ? 'start()' : 'playing = true' }}">
                            @if ($foto->thumbnail_url)
                                <img src="{{ $foto->thumbnail_url }}" alt="{{ $foto->judul ?? 'Media' }}" loading="lazy">
                            @else
                                <div class="lp-thumb-placeholder">
                                    {{ $foto->type === 'video' ? '🎬' : '🖼️' }}
                                </div>
                            @endif
                            <div class="lp-thumb-hover-icon">
                                @if ($foto->type === 'video')
                                    <svg viewBox="0 0 24 24" fill="currentColor"><path d="M8 5v14l11-7z"/></svg>
                                @else
                                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="11" cy="11" r="7"/><path d="m21 21-4.3-4.3"/><path d="M11 8v6M8 11h6"/></svg>
                                @endif
                            </div>
                        </div>
                    </template>

                    {{-- State aktif --}}
                    <template x-if="playing">
                        <div class="lp-thumb-playing" id="yt-wrap-{{ $foto->id }}">
                            @if ($isYoutube)
                                @if ($foto->external_id)
                                    <div id="yt-player-{{ $foto->id }}"></div>

                                    {{-- Custom control bar, hanya untuk YouTube (nyambung via IFrame API) --}}
                                    <div class="lp-yt-controls">
                                        <input type="range"
                                               class="lp-yt-progress"
                                               min="0"
                                               :max="duration || 0"
                                               :value="currentTime"
                                               @input="seekTo($event.target.value)"
                                               @click.stop>

                                        <div class="lp-yt-controls__row">
                                            <button type="button" class="lp-yt-btn" @click.stop="togglePlay()" aria-label="Play/Pause">
                                                <svg x-show="!isPlaying" width="16" height="16" viewBox="0 0 24 24" fill="currentColor"><path d="M8 5v14l11-7z"/></svg>
                                                <svg x-show="isPlaying" width="16" height="16" viewBox="0 0 24 24" fill="currentColor"><path d="M6 5h4v14H6zM14 5h4v14h-4z"/></svg>
                                            </button>

                                            <button type="button" class="lp-yt-btn" @click.stop="seek(-10)" aria-label="Mundur 10 detik">
                                                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M3 12a9 9 0 1 0 3-6.7"/><path d="M3 4v5h5"/></svg>
                                            </button>

                                            <button type="button" class="lp-yt-btn" @click.stop="seek(10)" aria-label="Maju 10 detik">
                                                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M21 12a9 9 0 1 1-3-6.7"/><path d="M21 4v5h-5"/></svg>
                                            </button>

                                            <button type="button" class="lp-yt-btn" @click.stop="toggleMute()" aria-label="Mute/Unmute">
                                                <svg x-show="!muted" width="16" height="16" viewBox="0 0 24 24" fill="currentColor"><path d="M3 10v4h4l5 5V5L7 10H3z"/><path d="M16 8a4 4 0 0 1 0 8" fill="none" stroke="currentColor" stroke-width="2"/></svg>
                                                <svg x-show="muted" width="16" height="16" viewBox="0 0 24 24" fill="currentColor"><path d="M3 10v4h4l5 5V5L7 10H3z"/><path d="m17 9 4 6M21 9l-4 6" fill="none" stroke="currentColor" stroke-width="2"/></svg>
                                            </button>

                                            <span class="lp-yt-time" x-text="formatTime(currentTime) + ' / ' + formatTime(duration)"></span>

                                            @if ($tickerText)
                                                <div class="lp-yt-ticker">
                                                    <span class="lp-yt-ticker__text">{{ $tickerText }}</span>
                                                </div>
                                            @else
                                                <div class="lp-yt-ticker"></div>
                                            @endif

                                            <span class="lp-yt-hd">HD</span>

                                            <button type="button" class="lp-yt-btn" @click.stop="toggleFullscreen()" aria-label="Fullscreen">
                                                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M8 3H5a2 2 0 0 0-2 2v3M16 3h3a2 2 0 0 1 2 2v3M21 16v3a2 2 0 0 1-2 2h-3M8 21H5a2 2 0 0 1-2-2v-3"/></svg>
                                            </button>
                                        </div>
                                    </div>
                                @else
                                    <div class="lp-thumb-playing__empty">ID video YouTube tidak ditemukan untuk media ini.</div>
                                @endif
                            @elseif ($playSrc)
                                <iframe src="{{ $playSrc }}"
                                        frameborder="0"
                                        allow="autoplay; encrypted-media; fullscreen"
                                        allowfullscreen
                                        loading="lazy"></iframe>
                            @else
                                <div class="lp-thumb-playing__empty">Sumber media tidak tersedia.</div>
                            @endif

                            <button type="button" class="lp-thumb-close" @click.stop="{{ $isYoutube ? 'close()' : 'playing = false' }}" aria-label="Tutup preview">
                                <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.6"><path d="M6 6l12 12M18 6 6 18"/></svg>
                            </button>
                        </div>
                    </template>

                    <span class="lp-admin-badge lp-admin-badge--{{ $foto->status }} lp-admin-galeri-card__badge" x-show="!playing">
                        {{ str_replace('_', ' ', $foto->status) }}
                    </span>
                    @if ($foto->type === 'video')
                        <span class="lp-video-badge" x-show="!playing">▶</span>
                    @endif
                </div>
                <div class="lp-admin-galeri-card__body">
                    <p class="lp-admin-galeri-card__judul">{{ $foto->judul ?? '(Tanpa judul)' }}</p>
                    @if ($foto->kegiatan)
                        <p class="lp-admin-galeri-card__kegiatan">{{ $foto->kegiatan }}</p>
                    @endif

                    @if ($foto->status === 'revisi' && $foto->catatan_revisi)
                        <div class="lp-admin-note">{{ $foto->catatan_revisi }}</div>
                    @endif

                    <div class="lp-admin-galeri-card__actions">
                        {{-- Creator: submit/hapus draft & revisi miliknya sendiri --}}

                        @if (PermissionHelper::can('approve_gallery') || ($foto->created_by === $userId && in_array($foto->status, ['draft', 'revisi'], true)))
                            <a href="{{ route('admin.galeri.edit', $foto) }}" class="lp-pill-btn">Edit</a>
                        @endif  
                        @if (PermissionHelper::can('create_gallery') && $foto->created_by === $userId && in_array($foto->status, ['draft', 'revisi']))
                            <form action="{{ route('admin.galeri.submit', $foto) }}" method="POST">
                                @csrf
                                <button type="submit" class="lp-pill-btn lp-pill-btn--primary">
                                    Kirim ke {{ PermissionHelper::can('review_gallery') ? 'Reviewer' : 'Sekretaris' }}
                                </button>
                            </form>
                            <form id="delete-galeri-{{ $foto->id }}" action="{{ route('admin.galeri.destroy', $foto) }}" method="POST">
                                @csrf @method('DELETE')
                            </form>
                            <button type="button"
                                    class="lp-pill-btn lp-pill-btn--danger"
                                    @click="$dispatch('open-confirm', { message: 'Hapus media ini secara permanen?', formId: 'delete-galeri-{{ $foto->id }}' })">
                                Hapus
                            </button>
                        @endif

                        {{-- Reviewer: review yang menunggu --}}
                        @if (PermissionHelper::can('review_gallery') && $foto->status === 'submitted_bidang5')
                            <form action="{{ route('admin.galeri.review-bidang5', $foto) }}" method="POST">
                                @csrf
                                <input type="hidden" name="approve" value="1">
                                <button type="submit" class="lp-pill-btn lp-pill-btn--success">Teruskan</button>
                            </form>
                            <button type="button" class="lp-pill-btn" onclick="document.getElementById('tolak-bidang5-{{ $foto->id }}').classList.toggle('lp-hidden')">Tolak</button>
                            <form id="tolak-bidang5-{{ $foto->id }}" action="{{ route('admin.galeri.review-bidang5', $foto) }}" method="POST" class="lp-hidden lp-admin-reject-form">
                                @csrf
                                <input type="hidden" name="approve" value="0">
                                <textarea name="catatan_revisi" placeholder="Catatan revisi..." required></textarea>
                                <button type="submit" class="lp-pill-btn lp-pill-btn--danger">Kirim</button>
                            </form>
                        @endif

                        {{-- Approver: approval final --}}
                        @if (PermissionHelper::can('approve_gallery') && $foto->status === 'submitted_sekretaris')
                            <form action="{{ route('admin.galeri.approve-sekretaris', $foto) }}" method="POST">
                                @csrf
                                <input type="hidden" name="approve" value="1">
                                <button type="submit" class="lp-pill-btn lp-pill-btn--success">Setujui</button>
                            </form>
                            <button type="button" class="lp-pill-btn" onclick="document.getElementById('tolak-sekretaris-{{ $foto->id }}').classList.toggle('lp-hidden')">Tolak</button>
                            <form id="tolak-sekretaris-{{ $foto->id }}" action="{{ route('admin.galeri.approve-sekretaris', $foto) }}" method="POST" class="lp-hidden lp-admin-reject-form">
                                @csrf
                                <input type="hidden" name="approve" value="0">
                                <textarea name="catatan_revisi" placeholder="Catatan revisi..." required></textarea>
                                <button type="submit" class="lp-pill-btn lp-pill-btn--danger">Kirim</button>
                            </form>
                        @endif
                        
                        {{-- Approver: hapus media yang sudah published --}}
                        @if (PermissionHelper::can('approve_gallery') && $foto->status === 'published')
                            <form id="delete-published-{{ $foto->id }}" action="{{ route('admin.galeri.destroy', $foto) }}" method="POST">
                                @csrf @method('DELETE')
                            </form>
                            <button type="button"
                                    class="lp-pill-btn lp-pill-btn--danger"
                                    @click="$dispatch('open-confirm', { message: 'Media ini sudah published dan akan dihapus secara permanen. Lanjutkan?', formId: 'delete-published-{{ $foto->id }}' })">
                                Hapus
                            </button>
                        @endif
                    </div>
                </div>
            </article>
        @empty
            <div class="lp-admin-empty">
                <svg width="40" height="40" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5"><rect x="3" y="3" width="18" height="18" rx="2"/><circle cx="8.5" cy="9.5" r="1.5"/><path d="m21 15-5-5L5 21"/></svg>
                <p>Tidak ada media yang cocok.</p>
            </div>
        @endforelse
    </div>

    <div class="lp-pagination">{{ $galeriList->links() }}</div>

    {{-- Loader untuk YouTube IFrame API (sekali per page load) + factory Alpine component --}}
    <script>
        document.addEventListener('alpine:init', () => {
            Alpine.data('lpYtPlayer', (playerId, wrapId, videoId) => ({
                playing: false,
                player: null,
                isPlaying: false,
                muted: false,
                currentTime: 0,
                duration: 0,
                _pollInterval: null,

                start() {
                    this.playing = true;
                    this.$nextTick(() => this._initPlayer());
                },

                _initPlayer() {
                    const create = () => {
                        this.player = new YT.Player(playerId, {
                            videoId: videoId,
                            playerVars: {
                                autoplay: 1,
                                controls: 0,
                                rel: 0,
                                modestbranding: 1,
                                playsinline: 1,
                            },
                            events: {
                                onReady: (e) => {
                                    e.target.playVideo();
                                    this.duration = e.target.getDuration() || 0;
                                    this.muted = e.target.isMuted();
                                    this._startPolling();
                                },
                                onStateChange: (e) => {
                                    this.isPlaying = e.data === YT.PlayerState.PLAYING;
                                    if (e.data === YT.PlayerState.ENDED) {
                                        this.isPlaying = false;
                                    }
                                },
                                onError: (e) => {
                                    // 2=id salah, 5=html5 error, 100=video tidak ada/private, 101/150=embedding dimatikan pemilik
                                    console.error('YouTube player error, code:', e.data, 'videoId:', videoId);
                                },
                            },
                        });
                    };

                    if (window.YT && window.YT.Player) {
                        create();
                    } else {
                        window.__lpYtQueue = window.__lpYtQueue || [];
                        window.__lpYtQueue.push(create);
                        _lpLoadYouTubeApi();
                    }
                },

                _startPolling() {
                    this._pollInterval = setInterval(() => {
                        if (this.player && this.player.getCurrentTime) {
                            this.currentTime = this.player.getCurrentTime();
                            const d = this.player.getDuration();
                            if (d) this.duration = d;
                        }
                    }, 500);
                },

                togglePlay() {
                    if (!this.player) return;
                    this.isPlaying ? this.player.pauseVideo() : this.player.playVideo();
                },

                seek(delta) {
                    if (!this.player) return;
                    const t = Math.max(0, Math.min(this.duration, this.player.getCurrentTime() + delta));
                    this.player.seekTo(t, true);
                    this.currentTime = t;
                },

                seekTo(value) {
                    if (!this.player) return;
                    const t = parseFloat(value);
                    this.player.seekTo(t, true);
                    this.currentTime = t;
                },

                toggleMute() {
                    if (!this.player) return;
                    if (this.muted) {
                        this.player.unMute();
                        this.muted = false;
                    } else {
                        this.player.mute();
                        this.muted = true;
                    }
                },

                toggleFullscreen() {
                    const el = document.getElementById(wrapId);
                    if (el && el.requestFullscreen) el.requestFullscreen();
                },

                close() {
                    if (this._pollInterval) clearInterval(this._pollInterval);
                    if (this.player && this.player.destroy) this.player.destroy();
                    this.player = null;
                    this.playing = false;
                    this.isPlaying = false;
                    this.currentTime = 0;
                    this.duration = 0;
                },

                formatTime(s) {
                    if (!s || isNaN(s)) return '0:00';
                    const m = Math.floor(s / 60);
                    const sec = Math.floor(s % 60).toString().padStart(2, '0');
                    return `${m}:${sec}`;
                },
                
            }));
        });

        function _lpLoadYouTubeApi() {
            if (window.__lpYtApiLoading) return;
            window.__lpYtApiLoading = true;

            window.onYouTubeIframeAPIReady = function () {
                (window.__lpYtQueue || []).forEach((fn) => fn());
                window.__lpYtQueue = [];
            };

            const tag = document.createElement('script');
            tag.src = 'https://www.youtube.com/iframe_api';
            document.head.appendChild(tag);
        }
    </script>
@endsection