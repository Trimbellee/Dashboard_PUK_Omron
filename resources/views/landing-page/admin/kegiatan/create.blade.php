{{-- resources/views/landing-page/admin/kegiatan/create.blade.php --}}

@extends('landing-page.admin.layouts.app')

@section('title', 'Tambah Kegiatan')

@section('content')
<div class="lp-page-header" style="margin-bottom: 1rem;">
    <h1 style="font-size: 1.1rem; font-weight: 600; margin: 0; color: var(--text-primary">Tambah Kegiatan</h1>
</div>

@include('landing-page.admin.kegiatan._form', ['kegiatan' => null])
@endsection