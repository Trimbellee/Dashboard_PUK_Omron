@extends('landing-page.admin.layouts.app')

@push('page-styles')
    @vite('resources/css/admin/admin-absensi.css')
@endpush

@section('title', 'Buat Sesi Absensi')

@section('content')


<div class="abs-wrap abs-wrap--narrow">

    <a href="{{ route('admin.sesi-absensi.index') }}" class="abs-back">← Kembali ke Daftar Sesi</a>

    <div class="abs-header">
        <div>
            <h1>Buat Sesi Absensi</h1>
            <p>Pilih kolom data anggota yang mau ditampilkan di daftar hadir kegiatan ini.</p>
        </div>
    </div>

    @include('landing-page.admin.absensi._form')

</div>

@endsection