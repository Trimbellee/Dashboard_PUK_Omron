@extends('landing-page.admin.layouts.app')

@push('page-styles')
    @vite(['resources/css/admin/admin-galeri.css'])
@endpush

@section('title', 'Edit Media Galeri - Admin PUK OMRON')

@section('content')
@php
    $sumberLabel = ['youtube' => 'YouTube', 'tiktok' => 'TikTok', 'googledrive' => 'Google Drive'][$foto->source] ?? $foto->source;
    $asalUrl = match ($foto->source) {
        'youtube' => "https://www.youtube.com/watch?v={$foto->external_id}",
        'tiktok'  => "https://www.tiktok.com/@_/video/{$foto->external_id}",
        default   => "https://drive.google.com/file/d/{$foto->external_id}/view",
    };
    $tipeBisaDiubah = $foto->source === 'googledrive';
    $bisaResetThumb = in_array($foto->source, ['youtube', 'googledrive'], true);
    $tipeAktif = old('type', $foto->type);
@endphp

<div class="lp-editor__topbar lp-editor__topbar--simple">
    <a href="{{ route('admin.galeri.index') }}" class="lp-editor__back">← Kembali</a>
    <h1 class="lp-editor__title-label">Edit Media Galeri</h1>
</div>

<form action="{{ route('admin.galeri.update', $foto) }}" method="POST" class="lp-editor__grid lp-editor__grid--narrow">
    @csrf
    @method('PUT')

    <div class="lp-editor__main">
        <div class="lp-form__group">
            <label for="judul">Judul (opsional)</label>
            <input type="text" id="judul" name="judul" value="{{ old('judul', $foto->judul) }}" placeholder="Judul media" maxlength="55">
            @error('judul') <span class="lp-form__error">{{ $message }}</span> @enderror
        </div>

        <div class="lp-form__group">
            <label for="kategori">Kategori</label>
            <select name="kategori" id="kategori" class="lp-form__input">
                <option value="">-- Pilih Kategori --</option>
                @foreach (\App\Models\Gallery::KATEGORI_LIST as $value => $label)
                    <option value="{{ $value }}" @selected(old('kategori', $foto->kategori) === $value)>{{ $label }}</option>
                @endforeach
            </select>
            @error('kategori') <span class="lp-form__error">{{ $message }}</span> @enderror
        </div>

        <div class="lp-form__group">
            <label for="kegiatan">Deskripsi / Kegiatan (opsional)</label>
            <input type="text" id="kegiatan" name="kegiatan" value="{{ old('kegiatan', $foto->kegiatan) }}"
                   placeholder="Contoh: Rapat Anggota Tahunan 2026" list="kegiatan-list" autocomplete="off">
            <datalist id="kegiatan-list">
                @foreach (($kegiatanList ?? []) as $namaKegiatan)
                    <option value="{{ $namaKegiatan }}"></option>
                @endforeach
            </datalist>
            <p class="lp-editor-hint">Nama yang sama persis mengelompokkan media dalam satu album di galeri publik.</p>
            @error('kegiatan') <span class="lp-form__error">{{ $message }}</span> @enderror
        </div>

        @if ($tipeBisaDiubah)
            <div class="lp-form__group">
                <label>Tipe Media</label>
                <div style="display:flex; gap:16px; margin-top:4px;">
                    <label style="display:flex; align-items:center; gap:6px;">
                        <input type="radio" name="type" value="image" @checked($tipeAktif === 'image')> Gambar
                    </label>
                    <label style="display:flex; align-items:center; gap:6px;">
                        <input type="radio" name="type" value="video" @checked($tipeAktif === 'video')> Video
                    </label>
                </div>
                @error('type') <span class="lp-form__error">{{ $message }}</span> @enderror
            </div>
        @endif

        <div class="lp-form__group">
            <label for="thumbnail">Thumbnail URL (opsional)</label>
            <input type="url" id="thumbnail" name="thumbnail" value="{{ old('thumbnail') }}" placeholder="https://... (kosong = thumbnail tidak diubah)">
            <p class="lp-editor-hint">Isi hanya jika ingin mengganti thumbnail dengan gambar sendiri.</p>
            @error('thumbnail') <span class="lp-form__error">{{ $message }}</span> @enderror

            @if ($bisaResetThumb)
                <label style="display:flex; align-items:center; gap:8px; margin-top:8px; font-weight:500;">
                    <input type="checkbox" name="reset_thumbnail" value="1" @checked(old('reset_thumbnail'))>
                    Kembalikan ke thumbnail otomatis {{ $sumberLabel }}
                </label>
            @endif
        </div>
    </div>

    <aside class="lp-editor__sidebar">
        <div class="lp-editor__panel">
            <h3 class="lp-editor__panel-title">Thumbnail saat ini</h3>
            <div style="background: var(--bg-surface-alt); border-radius: var(--radius-sm); overflow:hidden; aspect-ratio:16/9; display:flex; align-items:center; justify-content:center;">
                @if ($foto->thumbnail_url)
                    <img src="{{ $foto->thumbnail_url }}" alt="Thumbnail"
                         style="max-width:100%; max-height:100%; object-fit:contain;"
                         onerror="this.onerror=null;this.src='{{ asset('images/placeholder-media.png') }}'">
                @else
                    <span style="color:var(--text-muted); font-size:14px;">Belum ada thumbnail</span>
                @endif
            </div>
        </div>

        <div class="lp-editor__panel">
            <h3 class="lp-editor__panel-title">Info media</h3>
            <p class="lp-editor-hint" style="margin:0 0 6px;">Sumber: <strong>{{ $sumberLabel }}</strong></p>
            <p class="lp-editor-hint" style="margin:0 0 6px;">
                Status:
                <span class="lp-admin-badge lp-admin-badge--{{ $foto->status }}">{{ str_replace('_', ' ', $foto->status) }}</span>
            </p>
            <p class="lp-editor-hint" style="margin:0 0 10px;">
                <a href="{{ $asalUrl }}" target="_blank" rel="noopener">Buka sumber asli ↗</a>
            </p>
            <p class="lp-editor-hint" style="margin:0;">Link media tidak bisa diubah. Jika link salah, hapus media lalu tambahkan ulang.</p>
        </div>

        <div class="lp-editor__panel">
            <button type="submit" class="lp-btn lp-btn--primary lp-form__submit">Simpan Perubahan</button>
        </div>
    </aside>
</form>
@endsection
