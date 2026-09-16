@extends('landing-page.admin.layouts.app')

@push('page-styles')
    @vite(['resources/css/admin/admin-halaman.css'])
@endpush

@section('title', 'Manajemen Halaman')

@section('content')
<div class="hlm-wrapper">
    <div class="hlm-header">
        <div>
            <h1 class="hlm-header__title">Manajemen Halaman</h1>
            <p class="hlm-header__sub">
                Tambahkan entri menu sidebar di sini. Route, controller, dan tampilan halaman
                tetap harus dikoding manual sebelum menu ini bisa dibuka pengguna.
            </p>
        </div>
        <a href="{{ route('admin.halaman.create') }}" class="hlm-btn hlm-btn--primary">
            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><path d="M12 5v14M5 12h14"/></svg>
            Tambah Halaman
        </a>
    </div>

    <div class="hlm-stats">
        <div class="hlm-stat-card">
            <span class="hlm-stat-card__value">{{ $pages->count() }}</span>
            <span class="hlm-stat-card__label">Total Halaman</span>
        </div>
        <div class="hlm-stat-card hlm-stat-card--active">
            <span class="hlm-stat-card__value">{{ $pages->where('is_active', true)->count() }}</span>
            <span class="hlm-stat-card__label">Aktif di Menu</span>
        </div>
        <div class="hlm-stat-card hlm-stat-card--warning">
            <span class="hlm-stat-card__value">{{ $pages->filter(fn($p) => $p->route_name && !\Illuminate\Support\Facades\Route::has($p->route_name))->count() }}</span>
            <span class="hlm-stat-card__label">Route Belum Siap</span>
        </div>
    </div>

    @forelse($pages as $page)
        @if ($loop->first)
            <div class="hlm-list">
        @endif

        <div class="hlm-row">
            <div class="hlm-row__order">
                <form action="{{ route('admin.halaman.move-up', $page) }}" method="POST">
                    @csrf
                    <button type="submit" class="hlm-order-btn" title="Naikkan urutan" {{ $loop->first ? 'disabled' : '' }}>
                        <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3"><path d="M18 15l-6-6-6 6"/></svg>
                    </button>
                </form>
                <span class="hlm-row__order-num">{{ $page->order }}</span>
                <form action="{{ route('admin.halaman.move-down', $page) }}" method="POST">
                    @csrf
                    <button type="submit" class="hlm-order-btn" title="Turunkan urutan" {{ $loop->last ? 'disabled' : '' }}>
                        <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3"><path d="M6 9l6 6 6-6"/></svg>
                    </button>
                </form>
            </div>

            <div class="hlm-row__icon">
                @if($page->icon_svg)
                    {!! $page->icon_svg !!}
                @else
                    <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="4" y="4" width="16" height="16" rx="3"/></svg>
                @endif
            </div>

            <div class="hlm-row__main">
                <div class="hlm-row__title">
                    {{ $page->label }}
                    @if($page->is_locked)
                        <span class="hlm-badge hlm-badge--locked">
                            <svg width="10" height="10" viewBox="0 0 24 24" fill="currentColor"><path d="M12 2a5 5 0 00-5 5v3H6a2 2 0 00-2 2v8a2 2 0 002 2h12a2 2 0 002-2v-8a2 2 0 00-2-2h-1V7a5 5 0 00-5-5zm0 2a3 3 0 013 3v3H9V7a3 3 0 013-3z"/></svg>
                            Terkunci
                        </span>
                    @endif
                </div>
                <div class="hlm-row__meta">
                    <code class="hlm-code">{{ $page->slug }}</code>
                    @if($page->route_name)
                        <span class="hlm-row__meta-sep">•</span>
                        <code class="hlm-code">{{ $page->route_name }}</code>
                        @unless(Route::has($page->route_name))
                            <span class="hlm-badge hlm-badge--warning">Belum ada route</span>
                        @endunless
                    @else
                        <span class="hlm-row__meta-sep">•</span>
                        <span class="hlm-empty-hint">placeholder otomatis</span>
                    @endif
                </div>
            </div>

            <div class="hlm-row__status">
                <span class="hlm-badge {{ $page->is_active ? 'hlm-badge--active' : 'hlm-badge--inactive' }}">
                    <span class="hlm-dot"></span>
                    {{ $page->is_active ? 'Aktif' : 'Nonaktif' }}
                </span>
            </div>

            <div class="hlm-row__actions">
                <a href="{{ route('admin.halaman.edit', $page) }}" class="hlm-icon-btn" title="Edit">
                    <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M11 4H4a2 2 0 00-2 2v14a2 2 0 002 2h14a2 2 0 002-2v-7"/><path d="M18.5 2.5a2.121 2.121 0 013 3L12 15l-4 1 1-4 9.5-9.5z"/></svg>
                </a>
                @unless($page->is_locked)
                    <form id="delete-halaman-{{ $page->id }}" action="{{ route('admin.halaman.destroy', $page) }}" method="POST" style="display:none;">
                        @csrf
                        @method('DELETE')
                    </form>
                    <button type="button" class="hlm-icon-btn hlm-icon-btn--danger" title="Hapus"
                        @click="$dispatch('open-confirm', {
                            message: 'Hapus halaman &quot;{{ $page->label }}&quot; dari menu?',
                            formId: 'delete-halaman-{{ $page->id }}'
                        })">
                        <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M3 6h18M8 6V4a2 2 0 012-2h4a2 2 0 012 2v2m3 0v14a2 2 0 01-2 2H7a2 2 0 01-2-2V6h14z"/></svg>
                    </button>
                @endunless
            </div>
        </div>

        @if ($loop->last)
            </div>
        @endif
    @empty
        <div class="hlm-empty-block">
            <div class="hlm-empty-block__icon">
                <svg width="32" height="32" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5"><rect x="4" y="4" width="16" height="16" rx="3"/><path d="M9 9h6M9 13h6M9 17h3"/></svg>
            </div>
            <p>Belum ada halaman terdaftar.</p>
            <a href="{{ route('admin.halaman.create') }}" class="hlm-btn hlm-btn--primary hlm-btn--sm">Tambah halaman pertama</a>
        </div>
    @endforelse
</div>
@endsection