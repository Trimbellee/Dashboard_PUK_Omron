@extends('landing-page.admin.layouts.app')

@push('page-styles')
    @vite(['resources/css/admin/admin-halaman.css'])
@endpush

@section('title', 'Edit Halaman')

@section('content')
<div class="hlm-wrapper" x-data="{
        label: @js(old('label', $page->label)),
        slug: @js(old('slug', $page->slug)),
        slugTouched: true,
        iconSvg: @js(old('icon_svg', $page->icon_svg)),
        slugify(text) {
            return text.toString().toLowerCase().trim()
                .replace(/[^a-z0-9\s-]/g, '')
                .replace(/\s+/g, '-')
                .replace(/-+/g, '-');
        }
    }">
    <div class="hlm-header">
        <h1 class="hlm-header__title">Edit Halaman: {{ $page->label }}</h1>
    </div>

    <form action="{{ route('admin.halaman.update', $page) }}" method="POST" class="hlm-form">
        @include('landing-page.admin.halaman._form')
    </form>
</div>
@endsection