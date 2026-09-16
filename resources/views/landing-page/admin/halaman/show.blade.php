@extends('landing-page.admin.layouts.app')

@push('page-styles')
    @vite(['resources/css/admin/admin-halaman.css'])
@endpush

@section('title', $page->label)

@section('content')
<div class="hlm-empty-state">
    <div class="hlm-empty-state__icon">
        <svg width="30" height="30" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><path d="M12 9v4M12 17h.01"/><path d="M10.29 3.86L1.82 18a2 2 0 001.71 3h16.94a2 2 0 001.71-3L13.71 3.86a2 2 0 00-3.42 0z"/></svg>
    </div>
    <h2>Halaman masih Kosong</h2>
    <p>
        Halaman <strong>"{{ $page->label }}"</strong> (slug: <code class="hlm-code">{{ $page->slug }}</code>) sudah aktif di menu,
        tapi belum ada controller &amp; view khusus yang dibuat untuk halaman ini.
    </p>
    <div class="hlm-empty-state__hint">
        <p>Untuk mengisinya:</p>
        <ol>
            <li>Buat controller &amp; route sendiri di <code>routes/web.php</code></li>
            <li>Isi kolom <strong>Route Kustom</strong> di pengaturan halaman ini dengan nama route yang sudah dibuat</li>
            <li>Menu akan otomatis mengarah ke halaman baru itu</li>
        </ol>
    </div>
    <a href="{{ route('admin.halaman.edit', $page) }}" class="hlm-btn hlm-btn--primary">
        <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M11 4H4a2 2 0 00-2 2v14a2 2 0 002 2h14a2 2 0 002-2v-7"/><path d="M18.5 2.5a2.121 2.121 0 013 3L12 15l-4 1 1-4 9.5-9.5z"/></svg>
        Atur Route Halaman
    </a>
</div>
@endsection