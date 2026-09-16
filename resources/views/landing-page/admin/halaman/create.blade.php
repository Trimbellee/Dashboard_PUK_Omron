@extends('landing-page.admin.layouts.app')

@push('page-styles')
    @vite(['resources/css/admin/admin-halaman.css'])
@endpush

@section('title', 'Tambah Halaman')

@section('content')
<div class="hlm-wrapper" x-data="{
        label: @js(old('label', '')),
        slug: @js(old('slug', '')),
        slugTouched: {{ old('slug') ? 'true' : 'false' }},
        iconSvg: @js(old('icon_svg', '')),
        slugify(text) {
            return text.toString().toLowerCase().trim()
                .replace(/[^a-z0-9\s-]/g, '')
                .replace(/\s+/g, '-')
                .replace(/-+/g, '-');
        }
    }">
    <div class="hlm-header">
        <h1 class="hlm-header__title">Tambah Halaman</h1>
    </div>

    <form action="{{ route('admin.halaman.store') }}" method="POST" class="hlm-form">
        @include('landing-page.admin.halaman._form')
    </form>
</div>
@endsection