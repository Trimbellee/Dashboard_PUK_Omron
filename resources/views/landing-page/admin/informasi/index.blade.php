@extends('landing-page.admin.layouts.app')

@push('page-styles')
    @vite(['resources/css/admin/admin-informasi.css'])
    <style>[x-cloak] { display: none !important; }</style>
@endpush

@section('title', 'Kelola Informasi')

@section('content')
<div class="lp-admin-section"
     x-data="{
        statusFilter: '',
        previewOpen: false,
        previewJudul: '',
        previewKategori: '',
        previewKonten: '',
        matchesFilter(status) {
            return this.statusFilter === '' || status === this.statusFilter;
        },
        openPreview(judul, kategori, konten) {
            this.previewJudul = judul;
            this.previewKategori = kategori;
            this.previewKonten = konten;
            this.previewOpen = true;
        }
     }">

    {{-- ===== HEADER ===== --}}
    <div class="lp-admin-section__header">
        <div class="lp-admin-section__heading">
            <h2>Kelola Informasi</h2>
            <p class="lp-admin-section__subtitle">Konten seperti info iklan, pedoman siber, legal, dsb. yang tampil di footer landing page.</p>
        </div>
        <a href="{{ route('admin.informasi.create') }}" class="lp-btn lp-btn--primary">
            <span class="lp-btn__icon" aria-hidden="true">+</span> Tambah Informasi
        </a>
    </div>

    @if (session('success'))
        <div class="lp-admin-alert lp-admin-alert--success">{{ session('success') }}</div>
    @endif

    {{-- ===== STAT RINGKAS ===== --}}
    <div class="lp-stat-row">
        <div class="lp-stat-card">
            <span class="lp-stat-card__label">Total Informasi</span>
            <span class="lp-stat-card__value">{{ $informasiList->count() }}</span>
        </div>
        <div class="lp-stat-card lp-stat-card--success">
            <span class="lp-stat-card__label">Published</span>
            <span class="lp-stat-card__value">{{ $informasiList->where('status', 'published')->count() }}</span>
        </div>
        <div class="lp-stat-card lp-stat-card--pending">
            <span class="lp-stat-card__label">Draft</span>
            <span class="lp-stat-card__value">{{ $informasiList->where('status', 'draft')->count() }}</span>
        </div>
    </div>

    {{-- ===== FILTER STATUS ===== --}}
    <div class="lp-filter-tabs" style="margin-bottom: 20px;">
        <a href="#" @click.prevent="statusFilter = ''" :class="{ active: statusFilter === '' }">Semua</a>
        <a href="#" @click.prevent="statusFilter = 'published'" :class="{ active: statusFilter === 'published' }">Published</a>
        <a href="#" @click.prevent="statusFilter = 'draft'" :class="{ active: statusFilter === 'draft' }">Draft</a>
    </div>

    {{-- ===== LIST INFORMASI (CARD) ===== --}}
    <div class="lp-info-list">
        @forelse ($informasiList as $info)
            @php
                $kategoriLabel = \App\Models\Information::KATEGORI_LIST[$info->kategori] ?? null;
            @endphp
            <article class="lp-info-card" x-show="matchesFilter('{{ $info->status }}')" x-cloak>
                <div class="lp-info-card__main">
                    <div class="lp-info-card__order" title="Urutan tampil di footer">{{ $info->urutan }}</div>

                    <div class="lp-info-card__text">
                        <div class="lp-info-card__title-row">
                            <h3 class="lp-info-card__judul">{{ $info->judul }}</h3>
                            <span class="lp-admin-badge lp-admin-badge--{{ $info->status }}">{{ \App\Models\Information::STATUS_LIST[$info->status] ?? $info->status }}</span>
                        </div>
                        <p class="lp-info-card__slug">/informasi/{{ $info->slug }}</p>

                        @if ($kategoriLabel)
                            <span class="lp-info-kategori-badge lp-info-kategori-badge--{{ $info->kategori }}">
                                {{ $kategoriLabel }}
                            </span>
                        @endif
                    </div>
                </div>

                <div class="lp-info-card__actions">
                    <button type="button" class="lp-info-action-btn"
                            @click="openPreview({{ Js::from($info->judul) }}, {{ Js::from($kategoriLabel) }}, {{ Js::from($info->konten) }})">
                        <span class="lp-icon-ui" aria-hidden="true">
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M2.036 12.322a1.012 1.012 0 010-.639C3.423 7.51 7.36 4.5 12 4.5c4.638 0 8.573 3.007 9.963 7.178.07.207.07.431 0 .639C20.577 16.49 16.64 19.5 12 19.5c-4.638 0-8.573-3.007-9.963-7.178z" />
                                <path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                            </svg>
                        </span>
                        Preview
                    </button>

                    <a href="{{ route('admin.informasi.edit', $info->id) }}" class="lp-info-action-btn">
                        <span class="lp-icon-ui" aria-hidden="true">
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M16.862 4.487l1.687-1.688a1.875 1.875 0 112.652 2.652L10.582 16.07a4.5 4.5 0 01-1.897 1.13L6 18l.8-2.685a4.5 4.5 0 011.13-1.897l8.932-8.931z" />
                                <path stroke-linecap="round" stroke-linejoin="round" d="M19.5 13.5v6a2.25 2.25 0 01-2.25 2.25H4.5A2.25 2.25 0 012.25 19.5V6.75A2.25 2.25 0 014.5 4.5h6" />
                            </svg>
                        </span>
                        Edit
                    </a>

                    <form action="{{ route('admin.informasi.destroy', $info->id) }}" method="POST" id="delete-info-{{ $info->id }}">
                        @csrf @method('DELETE')
                    </form>
                    <button type="button" class="lp-info-action-btn lp-info-action-btn--danger"
                            @click="$dispatch('open-confirm', { message: 'Hapus informasi \'{{ addslashes($info->judul) }}\'?', formId: 'delete-info-{{ $info->id }}' })">
                        <span class="lp-icon-ui" aria-hidden="true">
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M14.74 9l-.346 9m-4.788 0L9.26 9m9.968-3.21c.342.052.682.107 1.022.166m-1.022-.165L18.16 19.673a2.25 2.25 0 01-2.244 2.077H8.084a2.25 2.25 0 01-2.244-2.077L4.772 5.79m14.456 0a48.108 48.108 0 00-3.478-.397m-12 .562c.34-.059.68-.114 1.022-.165m0 0a48.11 48.11 0 013.478-.397m7.5 0v-.916c0-1.18-.91-2.164-2.09-2.201a51.964 51.964 0 00-3.32 0c-1.18.037-2.09 1.022-2.09 2.201v.916m7.5 0a48.667 48.667 0 00-7.5 0" />
                            </svg>
                        </span>
                        Hapus
                    </button>
                </div>
            </article>
        @empty
            <div class="lp-admin-empty">
                <svg width="40" height="40" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5"><circle cx="12" cy="12" r="10"/><path d="M12 16v-4M12 8h.01"/></svg>
                <p>Belum ada informasi.</p>
                <a href="{{ route('admin.informasi.create') }}" class="lp-btn lp-btn--primary">+ Tambah Informasi</a>
            </div>
        @endforelse
    </div>

    {{-- ===== MODAL PREVIEW ===== --}}
    <div class="lp-modal-overlay"
         x-show="previewOpen"
         x-transition:enter="lp-modal-fade" x-transition:enter-start="lp-modal-fade--out" x-transition:enter-end="lp-modal-fade--in"
         x-transition:leave="lp-modal-fade" x-transition:leave-start="lp-modal-fade--in" x-transition:leave-end="lp-modal-fade--out"
         x-cloak @click="previewOpen = false"></div>

    <div class="lp-modal lp-modal--wide lp-preview-modal"
         x-show="previewOpen"
         x-transition:enter="lp-modal-fade" x-transition:enter-start="lp-modal-fade--out" x-transition:enter-end="lp-modal-fade--in"
         x-transition:leave="lp-modal-fade" x-transition:leave-start="lp-modal-fade--in" x-transition:leave-end="lp-modal-fade--out"
         x-cloak role="dialog" aria-modal="true">
        <div class="lp-modal__content">
            <div class="lp-modal__header">
                <h3 x-text="previewJudul"></h3>
                <button type="button" class="lp-modal__close" @click="previewOpen = false" aria-label="Tutup">&times;</button>
            </div>

            <div class="lp-modal__body lp-preview-modal__body">
                <span class="lp-preview-modal__kategori" x-show="previewKategori" x-text="previewKategori"></span>
                <div class="lp-preview-modal__konten" x-html="previewKonten"></div>
            </div>

            <div class="lp-modal__actions">
                <button type="button" class="lp-btn lp-btn--ghost" @click="previewOpen = false">Tutup</button>
            </div>
        </div>
    </div>
</div>
@endsection