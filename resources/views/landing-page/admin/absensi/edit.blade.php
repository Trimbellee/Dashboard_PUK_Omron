@extends('landing-page.admin.layouts.app')

@push('page-styles')
    @vite('resources/css/admin/admin-absensi.css')
@endpush


@section('title', 'Edit Sesi Absensi')

@section('content')


<div class="abs-wrap abs-wrap--narrow">

    <a href="{{ route('admin.sesi-absensi.show', $sesi) }}" class="abs-back">← Kembali ke Sesi</a>

    <div class="abs-header">
        <div>
            <h1>Edit Sesi Absensi</h1>
            <p>{{ $sesi->judul }}</p>
        </div>
    </div>

    @include('landing-page.admin.absensi._form')

</div>

@endsection