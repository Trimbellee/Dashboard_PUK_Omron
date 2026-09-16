@extends('layouts.app')

@section('title', 'Edit Anggota')

@section('content')

<div class="max-w-5xl mx-auto">

    <div class="space-y-6">

        <div class="mb-8">

            <h1 class="text-2xl font-bold text-gray-800 dark:text-white">
                Edit Anggota
            </h1>

            <p class="text-sm text-gray-500 dark:text-gray-400 mt-1">
                Update data anggota
            </p>

        </div>

        <form action="{{ route('anggota.update', $anggota) }}"
              method="POST"
              enctype="multipart/form-data">

            @csrf
            @method('PUT')

            @include('anggota.partials.form')

        </form>

    </div>

</div>

@endsection