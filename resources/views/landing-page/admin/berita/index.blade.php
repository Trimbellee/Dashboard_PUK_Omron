@extends('landing-page.admin.layouts.app')

@push('page-styles')
    @vite(['resources/css/admin/admin-berita.css'])
    <link href="https://cdn.jsdelivr.net/npm/quill@2.0.3/dist/quill.snow.css" rel="stylesheet">
@endpush

@section('title', 'Kelola Berita')


@section('content')
    @php
        $userId = session('anggota.id');
        $canCreate = \App\Helpers\PermissionHelper::can('create_news');
        $canReview = \App\Helpers\PermissionHelper::can('review_news');
        $canApprove = \App\Helpers\PermissionHelper::can('approve_news');
    @endphp

    <div x-data="beritaIndex()">

    {{-- ===== HEADER ===== --}}
    <div class="lp-admin-header">
        <div class="lp-admin-header__left">
            <h1 class="lp-admin-header__title">📰 Dashboard Berita</h1>
            <p class="lp-admin-header__sub">Kelola semua berita PUK OMRON dari draft hingga publikasi</p>
        </div>
        <div class="lp-admin-header__right">
            @if ($canCreate)
                <a href="{{ route('admin.berita.create') }}" class="lp-btn lp-btn--primary">
                    <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <path d="M12 5v14M5 12h14"/>
                    </svg>
                    Tulis Berita
                </a>
            @endif
        </div>
    </div>

    {{-- ===== STATISTIK RINGKASAN ===== --}}
    <div class="lp-stat-grid">
        <div class="lp-stat-card">
            <div class="lp-stat-card__icon">📊</div>
            <div class="lp-stat-card__info">
                <span class="lp-stat-card__label">Total Berita</span>
                <span class="lp-stat-card__value">{{ $totalBerita }}</span>
            </div>
        </div>
        <div class="lp-stat-card lp-stat-card--views">
            <div class="lp-stat-card__icon">👁️</div>
            <div class="lp-stat-card__info">
                <span class="lp-stat-card__label">Total Views</span>
                <span class="lp-stat-card__value">{{ number_format($totalViews) }}</span>
            </div>
        </div>
        <div class="lp-stat-card lp-stat-card--authors">
            <div class="lp-stat-card__icon">✍️</div>
            <div class="lp-stat-card__info">
                <span class="lp-stat-card__label">Kontributor Aktif</span>
                <span class="lp-stat-card__value">{{ $activeAuthors }}</span>
            </div>
        </div>
    </div>

    {{-- ===== TOP AUTHORS & MOST READ ===== --}}
    <div class="lp-dashboard-grid">
        <div class="lp-top-authors">
            <h3 class="lp-top-authors__title">🏆 Penulis Teraktif</h3>
            <div class="lp-top-authors__list">
                @if($topAuthors->count() > 0)
                    @foreach($topAuthors as $author)
                        <div class="lp-top-author">
                            <img src="{{ $author->creator?->foto ? asset('storage/' . $author->creator->foto) : asset('assets/no-photo.png') }}"
                                alt="{{ $author->creator?->nama_lengkap ?? 'Unknown' }}"
                                class="lp-top-author__avatar">
                            <div class="lp-top-author__info">
                                <strong class="lp-top-author__name">{{ $author->creator?->nama_lengkap ?? 'Unknown' }}</strong>
                                <span class="lp-top-author__count">{{ $author->news_count }} berita</span>
                            </div>
                        </div>
                    @endforeach
                @else
                    <div class="lp-empty-state--small">Belum ada penulis aktif</div>
                @endif
            </div>
        </div>

        <div class="lp-most-read">
            <h3 class="lp-most-read__title">📈 Artikel Terpopuler</h3>
            <div class="lp-most-read__list">
                @if(isset($mostRead) && $mostRead->count() > 0)
                    @foreach($mostRead as $article)
                        <div class="lp-most-read__item">
                            <span class="lp-most-read__rank">{{ $loop->iteration }}</span>
                            <div class="lp-most-read__content">
                                <a href="{{ route('berita.show', $article->slug) }}" 
                                target="_blank" 
                                rel="noopener noreferrer"
                                class="lp-most-read__title-link">
                                    {{ Str::limit($article->judul, 200) }}
                                </a>
                                <span class="lp-most-read__views">{{ number_format($article->views) }} views</span>
                            </div>
                        </div>
                    @endforeach
                @else
                    <div class="lp-empty-state--small">Belum ada artikel yang dipublikasikan</div>
                @endif
            </div>
        </div>
    </div>

    {{-- ===== QUICK ACTIONS ===== --}}
    <div class="lp-quick-actions">
        @if ($canCreate)
        <a href="{{ route('admin.berita.create') }}" class="lp-quick-action lp-quick-action--primary">
            <span>📝</span> Tulis Berita
        </a>
        @endif
        <a href="{{ route('admin.berita.index', ['filter' => 'draft']) }}" class="lp-quick-action">
            <span>📄</span> Draft <span class="lp-quick-action__badge">{{ $stats['draft'] ?? 0 }}</span>
        </a>
        <a href="{{ route('admin.berita.index', ['filter' => 'menunggu']) }}" class="lp-quick-action lp-quick-action--pending">
            <span>⏳</span> Menunggu <span class="lp-quick-action__badge">{{ $stats['menunggu'] ?? 0 }}</span>
        </a>
        <a href="{{ route('admin.berita.index', ['filter' => 'scheduled']) }}" class="lp-quick-action lp-quick-action--scheduled">
            <span>📅</span> Terjadwal <span class="lp-quick-action__badge">{{ $stats['scheduled'] ?? 0 }}</span>
        </a>
        <a href="{{ route('admin.berita.index', ['filter' => 'featured']) }}" class="lp-quick-action lp-quick-action--featured">
            <span>⭐</span> Featured <span class="lp-quick-action__badge">{{ $stats['featured'] ?? 0 }}</span>
        </a>
        <a href="{{ route('admin.berita.index', ['filter' => 'breaking']) }}" class="lp-quick-action lp-quick-action--breaking">
            <span>🚨</span> Breaking <span class="lp-quick-action__badge">{{ $stats['breaking'] ?? 0 }}</span>
        </a>
    </div>

    {{-- ===== FILTER & SEARCH ===== --}}
    <div class="lp-toolbar">
        <div class="lp-filter-tabs">
            <a href="{{ route('admin.berita.index') }}" class="{{ $activeFilter === 'semua' ? 'active' : '' }}">Semua</a>
            <a href="{{ route('admin.berita.index', ['filter' => 'draft']) }}" class="{{ $activeFilter === 'draft' ? 'active' : '' }}">Draft</a>
            <a href="{{ route('admin.berita.index', ['filter' => 'menunggu']) }}" class="{{ $activeFilter === 'menunggu' ? 'active' : '' }}">Menunggu</a>
            <a href="{{ route('admin.berita.index', ['filter' => 'published']) }}" class="{{ $activeFilter === 'published' ? 'active' : '' }}">Published</a>
            <a href="{{ route('admin.berita.index', ['filter' => 'takedown']) }}" class="{{ $activeFilter === 'takedown' ? 'active' : '' }}">🔒 Takedown</a>
            <a href="{{ route('admin.berita.index', ['filter' => 'scheduled']) }}" class="{{ $activeFilter === 'scheduled' ? 'active' : '' }}">📅 Terjadwal</a>
            <a href="{{ route('admin.berita.index', ['filter' => 'featured']) }}" class="{{ $activeFilter === 'featured' ? 'active' : '' }}">⭐ Featured</a>
            <a href="{{ route('admin.berita.index', ['filter' => 'breaking']) }}" class="{{ $activeFilter === 'breaking' ? 'active' : '' }}">🚨 Breaking</a>
        </div>

        <form action="{{ route('admin.berita.index') }}" method="GET" class="lp-search-box">
            <input type="hidden" name="filter" value="{{ $activeFilter !== 'semua' ? $activeFilter : '' }}">
            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                <circle cx="11" cy="11" r="8"/>
                <path d="M21 21l-4.35-4.35"/>
            </svg>
            <input type="text" name="q" value="{{ $searchTerm }}" placeholder="Cari judul berita..." class="lp-search-box__input">
            <button type="submit" class="lp-search-box__btn">Cari</button>
        </form>
    </div>

    {{-- ===== ADVANCED FILTERS ===== --}}
    <div class="lp-advanced-filters" x-data="{ showFilters: false }">
        <button type="button" class="lp-advanced-filters__toggle" @click="showFilters = !showFilters">
            <span>🔍</span> Filter Lanjutan
            <span x-show="!showFilters">▼</span>
            <span x-show="showFilters">▲</span>
        </button>

        <div x-show="showFilters" x-collapse>
            <form action="{{ route('admin.berita.index') }}" method="GET" class="lp-advanced-filters__form">
                <input type="hidden" name="filter" value="{{ $activeFilter !== 'semua' ? $activeFilter : '' }}">
                <input type="hidden" name="q" value="{{ $searchTerm }}">

                <div class="lp-advanced-filters__row">
                    <div class="lp-filter-group">
                        <label class="lp-filter-group__label">Kategori</label>
                        <select name="kategori" class="lp-form-select">
                            <option value="">Semua Kategori</option>
                            @foreach($kategoriList as $kat)
                                <option value="{{ $kat }}" {{ request('kategori') == $kat ? 'selected' : '' }}>{{ $kat }}</option>
                            @endforeach
                        </select>
                    </div>

                    <div class="lp-filter-group">
                        <label class="lp-filter-group__label">Penulis</label>
                        <select name="author" class="lp-form-select">
                            <option value="">Semua Penulis</option>
                            @foreach($authors as $author)
                                <option value="{{ $author->id }}" {{ request('author') == $author->id ? 'selected' : '' }}>{{ $author->nama_lengkap }}</option>
                            @endforeach
                        </select>
                    </div>

                    <div class="lp-filter-group">
                        <label class="lp-filter-group__label">Periode</label>
                        <div class="lp-filter-group__date">
                            <input type="date" name="from" value="{{ request('from') }}" class="lp-form-input">
                            <span>sampai</span>
                            <input type="date" name="to" value="{{ request('to') }}" class="lp-form-input">
                        </div>
                    </div>

                    <div class="lp-filter-group lp-filter-group--actions">
                        <button type="submit" class="lp-btn lp-btn--primary">Terapkan Filter</button>
                        <a href="{{ route('admin.berita.index', ['filter' => $activeFilter !== 'semua' ? $activeFilter : '']) }}" class="lp-btn lp-btn--ghost">Reset</a>
                    </div>
                </div>
            </form>
        </div>
    </div>

    {{-- ===== BULK ACTIONS ===== --}}
    @if($canApprove)
    <div class="lp-bulk-actions">
        <label class="lp-bulk-actions__select-all">
            <input type="checkbox" id="select-all">
            <span>Pilih Semua</span>
        </label>
        <form id="bulk-action-form" action="{{ route('admin.berita.bulk-action') }}" method="POST" class="lp-bulk-actions__form">
            @csrf
            <input type="hidden" name="ids" id="bulk-ids">
            <select name="action" class="lp-form-select lp-form-select--sm" required>
                <option value="">Aksi Massal</option>
                <option value="published">Terbitkan</option>
                <option value="takedown">Takedown</option>
                <option value="delete">Hapus</option>
                <option value="feature">Jadikan Featured</option>
                <option value="unfeature">Hapus Featured</option>
                <option value="breaking">Jadikan Breaking</option>
                <option value="unbreaking">Hapus Breaking</option>
            </select>
            <button type="submit" class="lp-btn lp-btn--primary lp-btn--sm">Terapkan</button>
        </form>
    </div>
    @endif

    {{-- ===== LIST BERITA ===== --}}
    <div class="lp-news-list">
        @forelse ($beritaList as $berita)
            {{-- Tambahkan reviewEditOpen untuk setiap item --}}
            <article class="lp-news-item" x-data="{ rejectOpen: false, reviewEditOpen: false }">
                {{-- Bulk Checkbox --}}
                @if($canApprove)
                <div class="lp-news-item__checkbox">
                    <input type="checkbox" class="news-checkbox" value="{{ $berita->id }}">
                </div>
                @endif

                {{-- THUMBNAIL --}}
                <div class="lp-news-item__thumb">
                    @if ($berita->gambar)
                        <img src="{{ asset('storage/' . $berita->gambar) }}" alt="{{ $berita->judul }}" loading="lazy">
                    @else
                        <div class="lp-news-item__thumb-placeholder">
                            <svg width="40" height="40" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5">
                                <path d="M4 16l4-4 4 4 4-6 4 6M4 4h16v16H4z"/>
                            </svg>
                        </div>
                    @endif

                    {{-- Badges --}}
                    <div class="lp-news-item__badges">
                        <span class="lp-badge lp-badge--{{ $berita->status }}">
                            {{ $berita->getStatusLabel() }}
                        </span>
                        @if($berita->is_featured)
                            <span class="lp-badge lp-badge--featured">⭐ Featured</span>
                        @endif
                        @if($berita->is_breaking)
                            <span class="lp-badge lp-badge--breaking">🚨 Breaking</span>
                        @endif
                        @if($berita->is_headline)
                            <span class="lp-badge lp-badge--headline">📰 Headline</span>
                        @endif
                    </div>
                </div>

                {{-- KONTEN --}}
                <div class="lp-news-item__content">
                    {{-- META --}}
                    <div class="lp-news-item__meta">
                        <span class="lp-news-item__kategori">{{ $berita->kategori ?? 'Umum' }}</span>
                        <span class="lp-news-item__author">
                            <img src="{{ $berita->creator?->foto ? asset('storage/' . $berita->creator->foto) : asset('assets/no-photo.png') }}"
                                 class="lp-avatar lp-avatar--sm"
                                 alt="{{ $berita->creator?->nama_lengkap ?? 'Redaksi' }}">
                            {{ $berita->creator?->nama_lengkap ?? 'Redaksi' }}
                        </span>
                        @if ($berita->edited_by && $berita->editor && $berita->edited_by != $berita->created_by)
                            <span class="lp-news-item__editor">✏️ {{ $berita->editor->nama_lengkap }}</span>
                        @endif
                        <span class="lp-news-item__date">dibuat: {{ $berita->created_at->translatedFormat('d M Y') }}</span>
                        <span class="lp-news-item__views">👁️ {{ number_format($berita->views ?? 0) }}</span>
                        @if(!is_null($berita->scheduled_at) && is_null($berita->published_at))
                            <span class="lp-news-item__scheduled">📅 {{ $berita->scheduled_at->translatedFormat('d M Y H:i') }}</span>
                        @endif
                    </div>

                    {{-- JUDUL --}}
                    <h3 class="lp-news-item__judul">
                        @if($berita->is_featured)
                            <span class="lp-news-item__featured-star">⭐</span>
                        @endif
                        @if($berita->is_breaking)
                            <span class="lp-news-item__breaking-badge">🚨</span>
                        @endif
                        {{ $berita->judul }}
                    </h3>

                    {{-- Tags --}}
                    @if($berita->tags->count() > 0)
                        <div class="lp-news-item__tags">
                            @foreach($berita->tags as $tag)
                                <span class="lp-news-item__tag">#{{ $tag->name }}</span>
                            @endforeach
                        </div>
                    @endif

                    {{-- EXCERPT --}}
                    <div class="lp-news-item__excerpt">
                        {{ Str::limit(strip_tags($berita->ringkasan), 120) }}
                        <button type="button" class="lp-news-item__read-more" @click="openPreview({{ $berita->id }})">
                            selengkapnya
                        </button>
                    </div>

                    {{-- NOTES --}}
                    @if ($berita->status === 'revisi' && $berita->catatan_revisi)
                        <div class="lp-news-item__note lp-news-item__note--warning">⚠️ {{ $berita->catatan_revisi }}</div>
                    @endif
                    @if ($berita->status === 'takedown' && $berita->takedown_reason)
                        <div class="lp-news-item__note lp-news-item__note--takedown">🔒 {{ $berita->takedown_reason }}</div>
                    @endif

                    {{-- ===== ACTIONS ===== --}}
                    <div class="lp-news-item__actions">

                        {{-- Grup kiri: aksi netral/positif --}}
                        <div class="lp-news-item__actions-group">

                            {{-- Creator Actions (Draft & Revisi) --}}
                            @if ($canCreate && $berita->created_by === $userId && in_array($berita->status, ['draft', 'revisi']))
                                <a href="{{ route('admin.berita.edit', $berita) }}" class="lp-action-btn lp-action-btn--edit">✏️ Edit</a>

                                <form id="submit-form-{{ $berita->id }}" action="{{ route('admin.berita.submit', $berita) }}" method="POST" class="lp-inline-form">
                                    @csrf
                                </form>
                                <button type="button" class="lp-action-btn lp-action-btn--submit"
                                        @click="$dispatch('open-confirm', {
                                            message: 'Berita yang sudah dikirim tidak bisa diedit lagi, anda yakin untuk melanjutkan ?',
                                            formId: {{ Js::from('submit-form-' . $berita->id) }},
                                            confirmLabel: 'Ya, Kirim',
                                            variant: 'primary'
                                        })">
                                    📤 Kirim
                                </button>
                            @endif

                            {{-- Reviewer Actions (Submitted Bidang5) --}}
                            @if ($canReview && $berita->status === 'submitted_bidang5')
                                <form id="approve-review-form-{{ $berita->id }}" action="{{ route('admin.berita.review', $berita) }}" method="POST" class="lp-inline-form">
                                    @csrf
                                    <input type="hidden" name="approve" value="1">
                                </form>
                                <button type="button" class="lp-action-btn lp-action-btn--success"
                                        @click="$dispatch('open-confirm', {
                                            message: 'Setujui berita ' + {{ Js::from(Str::limit($berita->judul, 60)) }} + ' dan teruskan ke Sekretaris?',
                                            formId: {{ Js::from('approve-review-form-' . $berita->id) }},
                                            confirmLabel: 'Ya, Setujui',
                                            variant: 'success'
                                        })">
                                    ✅ Setujui
                                </button>

                                {{-- Link ke halaman form dengan mode review --}}
                                <a href="{{ route('admin.berita.edit', ['berita' => $berita, 'mode' => 'review']) }}" class="lp-action-btn lp-action-btn--edit">
                                    ✏️ Edit & Setujui
                                </a>

                                <button type="button" class="lp-action-btn lp-action-btn--danger" @click="rejectOpen = !rejectOpen">❌ Tolak</button>
                            @endif

                            {{-- Approver Actions (Submitted Sekretaris) --}}
                            @if ($canApprove && $berita->status === 'submitted_sekretaris')
                                @php
                                    $isFutureSchedule = $berita->scheduled_at && $berita->scheduled_at->isFuture();
                                    $approveMessage = $isFutureSchedule
                                        ? 'Setujui berita ' . Str::limit($berita->judul, 60) . '? Berita akan otomatis terbit pada ' . $berita->scheduled_at->translatedFormat('d M Y H:i') . '.'
                                        : 'Apakah anda yakin ingin terbitkan berita ini ?';
                                @endphp
                                <form id="approve-form-{{ $berita->id }}" action="{{ route('admin.berita.approve', $berita) }}" method="POST" class="lp-inline-form">
                                    @csrf
                                </form>
                                <button type="button" class="lp-action-btn lp-action-btn--success"
                                        @click="$dispatch('open-confirm', {
                                            message: {{ Js::from($approveMessage) }},
                                            formId: {{ Js::from('approve-form-' . $berita->id) }},
                                            confirmLabel: {{ Js::from($isFutureSchedule ? 'Ya, Setujui' : 'Ya, Terbitkan') }},
                                            variant: 'success'
                                        })">
                                    ✅ Terbitkan
                                </button>

                                <button type="button" class="lp-action-btn lp-action-btn--danger" @click="rejectOpen = !rejectOpen">❌ Tolak</button>
                            @endif

                            {{-- Restore (Takedown) --}}
                            @if ($canApprove && $berita->status === 'takedown')
                                <form id="restore-form-{{ $berita->id }}" action="{{ route('admin.berita.restore', $berita) }}" method="POST" class="lp-inline-form">
                                    @csrf
                                </form>
                                <button type="button" class="lp-action-btn lp-action-btn--success"
                                        @click="$dispatch('open-confirm', {
                                            message: 'Pulihkan dan terbitkan kembali berita ' + {{ Js::from(Str::limit($berita->judul, 60)) }} + '?',
                                            formId: {{ Js::from('restore-form-' . $berita->id) }},
                                            confirmLabel: 'Ya, Restore',
                                            variant: 'success'
                                        })">
                                    ↩️ Restore
                                </button>
                            @endif

                            {{-- Newsletter (Published) --}}
                            @if ($berita->status === 'published')
                                <button type="button" class="lp-action-btn lp-action-btn--newsletter" @click="openNewsletter({{ $berita->id }})">
                                    <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="2" y="4" width="20" height="16" rx="2"/><path d="m22 7-10 5L2 7"/></svg>
                                    Newsletter
                                </button>
                            @endif
                        </div>

                        {{-- Grup kanan: aksi berisiko/destruktif --}}
                        <div class="lp-news-item__actions-group lp-news-item__actions-group--danger">
                            {{-- Takedown (Published) --}}
                            @if ($canApprove && $berita->status === 'published')
                                <button type="button" class="lp-action-btn lp-action-btn--danger" @click="openTakedown({{ $berita->id }})">
                                    🔒 Takedown
                                </button>
                            @endif

                            {{-- Hapus (Creator, Draft/Revisi) --}}
                            @if ($canCreate && $berita->created_by === $userId && in_array($berita->status, ['draft', 'revisi']))
                                <form id="delete-form-{{ $berita->id }}" action="{{ route('admin.berita.destroy', $berita) }}" method="POST" class="lp-inline-form">
                                    @csrf
                                    @method('DELETE')
                                </form>
                                <button type="button" class="lp-action-btn lp-action-btn--danger"
                                        @click="$dispatch('open-confirm', {
                                            message: 'Yakin ingin menghapus berita secara permanen? Tindakan ini tidak bisa dibatalkan.',
                                            formId: {{ Js::from('delete-form-' . $berita->id) }},
                                            confirmLabel: 'Ya, Hapus',
                                            variant: 'danger'
                                        })">
                                    🗑️ Hapus
                                </button>
                            @endif
                        </div>

                        {{-- Panel form Tolak (untuk reviewer & approver) --}}
                        @if (($canReview && $berita->status === 'submitted_bidang5') || ($canApprove && $berita->status === 'submitted_sekretaris'))
                            <form id="reject-form-{{ $berita->id }}"
                                  x-show="rejectOpen"
                                  x-cloak
                                  x-transition
                                  action="{{ ($canReview && $berita->status === 'submitted_bidang5') ? route('admin.berita.review', $berita) : route('admin.berita.reject', $berita) }}"
                                  method="POST"
                                  class="lp-admin-reject-form"
                                  @submit.prevent="$dispatch('open-confirm', {
                                      message: 'Tolak berita ' + {{ Js::from(Str::limit($berita->judul, 60)) }} + ' dan kembalikan untuk direvisi?',
                                      formId: {{ Js::from('reject-form-' . $berita->id) }},
                                      confirmLabel: 'Ya, Tolak',
                                      variant: 'danger'
                                  })">
                                @csrf
                                @if ($canReview && $berita->status === 'submitted_bidang5')
                                    <input type="hidden" name="approve" value="0">
                                @endif
                                <textarea name="catatan_revisi" placeholder="Catatan revisi..." required></textarea>
                                <button type="submit" class="lp-action-btn lp-action-btn--danger">Kirim</button>
                            </form>
                        @endif
                    </div>
                </div>
            </article>
        @empty
            <div class="lp-empty-state">
                <div class="lp-empty-state__icon">📭</div>
                <h3 class="lp-empty-state__title">Belum Ada Berita</h3>
                <p class="lp-empty-state__desc">Mulai tulis berita pertama Anda untuk PUK OMRON</p>
                @if ($canCreate)
                    <a href="{{ route('admin.berita.create') }}" class="lp-btn lp-btn--primary">+ Tulis Berita</a>
                @endif
            </div>
        @endforelse
    </div>

    {{-- ===== PAGINATION ===== --}}
    <div class="lp-pagination-wrapper">
        {{ $beritaList->links('vendor.pagination.custom') }}
    </div>

    {{-- ===== RECENT ACTIVITY ===== --}}
    @if($recentLogs->count() > 0)
    <div class="lp-activity-section">
        <h3 class="lp-activity-section__title">📋 Aktivitas Terbaru</h3>
        <div class="lp-activity-timeline">
            @foreach($recentLogs as $log)
                <div class="lp-activity-item">
                    <span class="lp-activity-item__icon">{{ $log->icon ?? '📌' }}</span>
                    <div class="lp-activity-item__content">
                        <strong>{{ $log->user?->nama_lengkap ?? 'System' }}</strong>
                        <span>{{ $log->description ?? $log->action }}</span>
                        <small>{{ $log->created_at->diffForHumans() }}</small>
                    </div>
                </div>
            @endforeach
        </div>
    </div>
    @endif

    {{-- ===== MODAL PREVIEW ===== --}}
    <div x-show="previewOpen"
         x-cloak
         x-transition
         @click.self="previewOpen = false"
         @keydown.escape.window="previewOpen = false"
         class="lp-modal">
        <div class="lp-modal__inner lp-modal__inner--preview">
            <div class="lp-modal__header">
                <span class="lp-modal__title">📖 Pratinjau — Tampilan Pembaca</span>
                <button type="button" class="lp-modal__close" @click="previewOpen = false" aria-label="Tutup">✕</button>
            </div>
            <div class="lp-preview-article">
                <template x-if="previewLoading">
                    <div class="lp-preview-loading">
                        <div class="lp-spinner"></div>
                        <p>Memuat pratinjau...</p>
                    </div>
                </template>
                <template x-if="previewError && !previewLoading">
                    <div class="lp-preview-loading">
                        <p style="color:#dc2626;">❌ Gagal memuat pratinjau. Silahkan coba lagi.</p>
                    </div>
                </template>
                <template x-if="previewData && !previewLoading && !previewError">
                    <div>
                        <span class="lp-preview-article__kategori" x-text="previewData.kategori || 'Tanpa Kategori'"></span>
                        <h1 class="lp-preview-article__title" x-text="previewData.judul"></h1>
                        <div class="lp-preview-article__byline">
                            <span>✍️ <strong>Penulis:</strong> <span x-text="previewData.penulis || 'Redaksi'"></span></span>
                            <span>✍️ <strong>Editor:</strong> <span x-text="previewData.editor || 'Redaksi'"></span></span>
                            <span class="lp-preview-article__date" x-text="previewData.tanggal"></span>
                        </div>
                        <template x-if="previewData.gambar">
                            <div class="lp-preview-article__image">
                                <img :src="previewData.gambar" :alt="previewData.judul">
                            </div>
                        </template>
                        <div class="lp-preview-article__body ql-editor" x-html="previewData.konten"></div>
                        <div class="lp-preview-article__footer">
                            <span>⏱️ <span x-text="previewData.waktu_baca || '0'"></span> menit baca</span>
                            <span>📝 <span x-text="previewData.jumlah_kata || '0'"></span> kata</span>
                            <span>🏷️ <span x-text="previewData.kategori || 'Tanpa kategori'"></span></span>
                            <span x-show="previewData.is_featured">⭐ Featured</span>
                            <span x-show="previewData.is_breaking">🚨 Breaking News</span>
                        </div>
                    </div>
                </template>
            </div>
        </div>
    </div>

    {{-- ===== MODAL TAKEDOWN ===== --}}
    <div x-show="takedownOpen"
         x-cloak
         x-transition
         @click.self="takedownOpen = false"
         @keydown.escape.window="takedownOpen = false"
         class="lp-modal">
        <div class="lp-modal__inner lp-modal__inner--sm lp-modal__inner--danger">
            <div class="lp-modal__header">
                <h3 class="lp-modal__title">🔒 Takedown Berita</h3>
                <button type="button" class="lp-modal__close" @click="takedownOpen = false" aria-label="Tutup">✕</button>
            </div>
            <form :action="takedownActionUrl()" method="POST" class="lp-modal__body">
                @csrf
                <p>Berikan alasan mengapa berita ini perlu di-takedown. Alasan ini akan tercatat pada log aktivitas.</p>
                <textarea name="takedown_reason" x-model="takedownReason" rows="3" class="lp-form-textarea"
                          placeholder="Contoh: Berita mengandung informasi yang tidak akurat..." required minlength="5"></textarea>
                <div class="lp-modal__actions">
                    <button type="button" class="lp-btn lp-btn--ghost" @click="takedownOpen = false">Batal</button>
                    <button type="submit" class="lp-btn lp-btn--danger">🔒 Takedown</button>
                </div>
            </form>
        </div>
    </div>

    {{-- ===== MODAL NEWSLETTER ===== --}}
    <div x-show="newsletterOpen"
         x-cloak
         x-transition
         @click.self="newsletterOpen = false"
         @keydown.escape.window="newsletterOpen = false"
         class="lp-modal">
        <div class="lp-modal__inner lp-modal__inner--sm lp-modal__inner--violet">
            <div class="lp-modal__header">
                <h3 class="lp-modal__title">📧 Kirim ke Newsletter</h3>
                <button type="button" class="lp-modal__close" @click="newsletterOpen = false" aria-label="Tutup">✕</button>
            </div>
            <form :action="newsletterActionUrl()" method="POST" class="lp-modal__body">
                @csrf
                <p>Kirim berita ini ke subscriber newsletter?</p>
                <div class="lp-form-group">
                    <label class="lp-form-label">Pilih Segmentasi</label>
                    <select name="segment" x-model="newsletterSegment" class="lp-form-select">
                        <option value="all">Semua Subscriber</option>
                        <option value="active">Subscriber Aktif</option>
                        <option value="new">Subscriber Baru</option>
                    </select>
                </div>
                <div class="lp-modal__actions">
                    <button type="button" class="lp-btn lp-btn--ghost" @click="newsletterOpen = false">Batal</button>
                    <button type="submit" class="lp-btn lp-btn--primary">📧 Kirim</button>
                </div>
            </form>
        </div>
    </div>

    </div>{{-- /x-data="beritaIndex()" --}}
@endsection

@push('scripts')
<script>
function beritaIndex() {
    return {
        previewOpen: false,
        previewLoading: false,
        previewError: false,
        previewData: null,

        takedownOpen: false,
        takedownId: null,
        takedownReason: '',

        newsletterOpen: false,
        newsletterId: null,
        newsletterSegment: 'all',

        openPreview(id) {
            this.previewOpen = true;
            this.previewLoading = true;
            this.previewError = false;
            this.previewData = null;

            fetch('{{ url("landing/admin/berita") }}/' + id + '/preview')
                .then(res => {
                    if (!res.ok) throw new Error('HTTP ' + res.status);
                    return res.json();
                })
                .then(data => {
                    this.previewData = data;
                    this.previewLoading = false;
                })
                .catch(err => {
                    console.error('Error loading preview:', err);
                    this.previewError = true;
                    this.previewLoading = false;
                });
        },

        openTakedown(id) {
            this.takedownId = id;
            this.takedownReason = '';
            this.takedownOpen = true;
        },

        takedownActionUrl() {
            return '{{ url("landing/admin/berita") }}/' + (this.takedownId ?? '') + '/takedown';
        },

        openNewsletter(id) {
            this.newsletterId = id;
            this.newsletterSegment = 'all';
            this.newsletterOpen = true;
        },

        newsletterActionUrl() {
            return '{{ url("landing/admin/berita/newsletter") }}/' + (this.newsletterId ?? '');
        },
    };
}

// ===== BULK ACTIONS =====
document.addEventListener('DOMContentLoaded', function() {
    const selectAll = document.getElementById('select-all');
    const checkboxes = document.querySelectorAll('.news-checkbox');
    const bulkIds = document.getElementById('bulk-ids');

    if (selectAll) {
        selectAll.addEventListener('change', function() {
            checkboxes.forEach(cb => cb.checked = this.checked);
            updateBulkIds();
        });
    }

    checkboxes.forEach(cb => {
        cb.addEventListener('change', updateBulkIds);
    });

    function updateBulkIds() {
        const checked = document.querySelectorAll('.news-checkbox:checked');
        const ids = Array.from(checked).map(cb => cb.value);
        if (bulkIds) {
            bulkIds.value = ids.join(',');
        }
        if (selectAll) {
            selectAll.checked = checkboxes.length > 0 && ids.length === checkboxes.length;
            selectAll.indeterminate = ids.length > 0 && ids.length < checkboxes.length;
        }
    }

    const bulkForm = document.getElementById('bulk-action-form');
    if (bulkForm) {
        bulkForm.addEventListener('submit', function(e) {
            const selected = document.querySelectorAll('.news-checkbox:checked');
            if (selected.length === 0) {
                e.preventDefault();
                if (window.showToast) {
                    showToast('error', 'Pilih minimal satu berita!');
                } else {
                    alert('Pilih minimal satu berita!');
                }
            }
        });
    }

    document.addEventListener('keydown', function(e) {
        if (e.ctrlKey && e.key === '/') {
            e.preventDefault();
            const toggleBtn = document.querySelector('.lp-advanced-filters__toggle');
            if (toggleBtn) toggleBtn.click();
        }
    });
});
</script>
@endpush