@extends('layouts.app')

@section('title', 'Tambah Anggota')

@section('content')

<div class="max-w-5xl mx-auto">

    <div class="space-y-6">

        <div class="mb-8">

            <h1 class="text-2xl font-bold text-gray-800 dark:text-white">
                Tambah Anggota
            </h1>

            <p class="text-sm text-gray-500 dark:text-gray-400 mt-1">
                Tambahkan data anggota baru
            </p>

        </div>

        <form action="{{ route('anggota.store') }}"
              method="POST"
              enctype="multipart/form-data">

            @include('anggota.partials.form')

        </form>

    </div>

</div>


@endsection