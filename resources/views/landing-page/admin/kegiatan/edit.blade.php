{{-- resources/views/landing-page/admin/kegiatan/edit.blade.php --}}

@extends('landing-page.admin.layouts.app')

@section('title', 'Edit Kegiatan')

@section('content')
<div class="lp-page-header" style="margin-bottom: 1rem;">
    <h1 style="font-size: 1.1rem; font-weight: 600; margin: 0;">Edit Kegiatan</h1>
    <p style="font-size: .8125rem; color: var(--text-secondary, #6b7280); margin: .2rem 0 0;">
        {{ $kegiatan->judul }}
    </p>
</div>

{{-- $kegiatan wajib sudah load('customFields') dari controller --}}
@include('landing-page.admin.kegiatan._form', ['kegiatan' => $kegiatan])
@endsection