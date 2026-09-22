@extends('landing-page.admin.layouts.app')

@push('page-styles')
    @vite(['resources/css/admin/admin-galeri.css'])
@endpush

@section('title', 'Tambah Media Galeri - Admin PUK OMRON')

@section('content')
    <div class="lp-editor__topbar lp-editor__topbar--simple">
        <a href="{{ route('admin.galeri.index') }}" class="lp-editor__back">← Kembali</a>
        <h1 class="lp-editor__title-label">Tambah Media Galeri</h1>
    </div>

    <form action="{{ route('admin.galeri.store') }}" method="POST" class="lp-editor__grid lp-editor__grid--narrow" x-data="mediaForm()" x-init="init()">
        @csrf

        <div class="lp-editor__main">
            <div class="lp-form__group">
                <label for="judul">Judul (opsional)</label>
                <input type="text" id="judul" name="judul" value="{{ old('judul') }}" placeholder="Judul media">
                @error('judul') <span class="lp-form__error">{{ $message }}</span> @enderror
            </div>

            <div class="lp-form__group">
                <label for="kategori">Kategori</label>
                <select name="kategori" id="kategori" class="lp-form__input">
                    <option value="">-- Pilih Kategori --</option>
                    @foreach (\App\Models\Gallery::KATEGORI_LIST as $value => $label)
                        <option value="{{ $value }}" @selected(old('kategori') === $value)>{{ $label }}</option>
                    @endforeach
                </select>
            </div>

            <div class="lp-form__group">
                <label for="kegiatan">Deskripsi / Kegiatan (opsional)</label>
                <input type="text" id="kegiatan" name="kegiatan" value="{{ old('kegiatan') }}" placeholder="Contoh: Rapat Anggota Tahunan 2026">
                @error('kegiatan') <span class="lp-form__error">{{ $message }}</span> @enderror
            </div>

            <div class="lp-form__group">
                <label for="url">Link YouTube atau Google Drive</label>
                <input type="url" id="url" name="url" x-model="url" @input="processUrl()" placeholder="https://www.youtube.com/watch?v=... atau https://drive.google.com/file/d/..." required>
                @error('url') <span class="lp-form__error">{{ $message }}</span> @enderror
                <p class="lp-editor-hint">Masukkan link video YouTube atau link share Google Drive (file/folder).</p>
            </div>

            <div class="lp-form__group" x-show="url && !error">
                <label>Tipe Media</label>
                <div style="display:flex; gap:16px; margin-top:4px;">
                    <label style="display:flex; align-items:center; gap:6px;">
                        <input type="radio" name="type" value="image" x-model="mediaType" :checked="mediaType === 'image'"> Gambar
                    </label>
                    <label style="display:flex; align-items:center; gap:6px;">
                        <input type="radio" name="type" value="video" x-model="mediaType" :checked="mediaType === 'video'"> Video
                    </label>
                </div>
            </div>

            <div class="lp-form__group" x-show="url && source === 'googledrive'">
                <label for="thumbnail">Thumbnail URL (opsional, untuk Google Drive)</label>
                <input type="url" id="thumbnail" name="thumbnail" value="{{ old('thumbnail') }}" placeholder="https://... (jika kosong, gunakan placeholder)">
                <p class="lp-editor-hint">Google Drive tidak menyediakan thumbnail publik, silakan upload gambar kecil dan tempel link-nya, atau biarkan placeholder.</p>
            </div>
        </div>

        <aside class="lp-editor__sidebar">
            <div class="lp-editor__panel">
                <h3 class="lp-editor__panel-title">Preview</h3>
                <div style="background: var(--bg-surface-alt); border-radius: var(--radius-sm); overflow:hidden; aspect-ratio:16/9; display:flex; align-items:center; justify-content:center;">
                    <template x-if="thumbnail">
                        <img :src="thumbnail" alt="Thumbnail" style="max-width:100%; max-height:100%; object-fit:contain;">
                    </template>
                    <template x-if="!thumbnail && !error">
                        <span style="color:var(--text-muted); font-size:14px;">Masukkan link untuk melihat preview</span>
                    </template>
                    <template x-if="error">
                        <span style="color:#d14545; font-size:14px;" x-text="error"></span>
                    </template>
                </div>
            </div>
            <div class="lp-editor__panel">
                <button type="submit" class="lp-btn lp-btn--primary lp-form__submit">Simpan sebagai Draft</button>
            </div>
        </aside>
    </form>

    <script>
    function mediaForm() {
        return {
            url: '',
            source: null,
            externalId: null,
            thumbnail: null,
            mediaType: 'image',
            error: null,
            init() {
                // Inisialisasi dari old value jika ada (gunakan Blade escape)
                const oldUrl = @json(old('url', ''));
                if (oldUrl) {
                    this.url = oldUrl;
                    this.processUrl();
                }
            },
            processUrl() {
                if (!this.url) {
                    this.thumbnail = null;
                    this.source = null;
                    this.error = null;
                    return;
                }
                // Regex untuk YouTube
                const ytRegex = /(?:youtube\.com\/watch\?v=|youtu\.be\/|youtube\.com\/embed\/|youtube\.com\/v\/|youtube\.com\/shorts\/)([^&?]+)/;
                const gdRegex = /\/file\/d\/([^\/]+)/;
                let match = this.url.match(ytRegex);
                if (match) {
                    const id = match[1];
                    this.source = 'youtube';
                    this.externalId = id;
                    this.thumbnail = `https://img.youtube.com/vi/${id}/hqdefault.jpg`;
                    this.mediaType = 'video';
                    this.error = null;
                    return;
                }
                match = this.url.match(gdRegex);
                if (match) {
                    const id = match[1];
                    this.source = 'googledrive';
                    this.externalId = id;
                    // Thumbnail tidak bisa otomatis, kosongkan
                    this.thumbnail = '';
                    // Tipe default image, admin bisa ubah
                    this.mediaType = 'image';
                    this.error = null;
                    return;
                }
                this.thumbnail = null;
                this.source = null;
                this.error = 'URL tidak valid. Hanya YouTube dan Google Drive yang didukung.';
            }
        }
    }
    </script>
@endsection