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
                <input type="text" id="judul" name="judul" value="{{ old('judul') }}" placeholder="Judul media" maxlength="55">
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
                @error('kategori') <span class="lp-form__error">{{ $message }}</span> @enderror
            </div>

            <div class="lp-form__group">
                <label for="kegiatan">Deskripsi / Kegiatan (opsional)</label>
                <input type="text" id="kegiatan" name="kegiatan" value="{{ old('kegiatan') }}" placeholder="Contoh: Rapat Anggota Tahunan 2026" list="kegiatan-list" autocomplete="off">
                <datalist id="kegiatan-list">
                    @foreach (($kegiatanList ?? []) as $namaKegiatan)
                        <option value="{{ $namaKegiatan }}"></option>
                    @endforeach
                </datalist>
                <p class="lp-editor-hint">Gunakan nama yang sama persis untuk mengelompokkan media dalam satu album di galeri publik.</p>
                @error('kegiatan') <span class="lp-form__error">{{ $message }}</span> @enderror
            </div>

            <div class="lp-form__group">
                <label for="url">Link YouTube, Google Drive, atau TikTok</label>
                <input type="url" id="url" name="url" x-model="url" @input="processUrl()"
                       placeholder="https://www.youtube.com/watch?v=... / https://drive.google.com/file/d/... / https://www.tiktok.com/@akun/video/..." required>
                @error('url') <span class="lp-form__error">{{ $message }}</span> @enderror
                <p class="lp-editor-hint">Masukkan link video YouTube, link share Google Drive (file), atau link video TikTok (link pendek vm.tiktok.com juga bisa).</p>
            </div>

            <div class="lp-form__group" x-show="url && !error">
                <label>Tipe Media</label>
                <div style="display:flex; gap:16px; margin-top:4px;">
                    <label style="display:flex; align-items:center; gap:6px;">
                        <input type="radio" name="type" value="image" x-model="mediaType" :disabled="source === 'tiktok'"> Gambar
                    </label>
                    <label style="display:flex; align-items:center; gap:6px;">
                        <input type="radio" name="type" value="video" x-model="mediaType"> Video
                    </label>
                </div>
                <p class="lp-editor-hint" x-show="source === 'tiktok'">Link TikTok selalu disimpan sebagai video.</p>
                @error('type') <span class="lp-form__error">{{ $message }}</span> @enderror
            </div>

            <div class="lp-form__group" x-show="url && (source === 'googledrive' || source === 'tiktok')">
                <label for="thumbnail">Thumbnail URL (opsional)</label>
                <input type="url" id="thumbnail" name="thumbnail" value="{{ old('thumbnail') }}" placeholder="https://... (jika kosong, otomatis / placeholder)">
                <p class="lp-editor-hint" x-show="source === 'googledrive'">Thumbnail Google Drive diambil otomatis jika file dibagikan publik. Jika tampil kosong, tempel link gambar sendiri di kolom ini.</p>
                <p class="lp-editor-hint" x-show="source === 'tiktok'">Thumbnail TikTok diambil otomatis saat disimpan. Isi kolom ini hanya jika ingin memakai gambar sendiri.</p>
                @error('thumbnail') <span class="lp-form__error">{{ $message }}</span> @enderror
            </div>
        </div>

        <aside class="lp-editor__sidebar">
            <div class="lp-editor__panel">
                <h3 class="lp-editor__panel-title">Preview</h3>
                <div style="background: var(--bg-surface-alt); border-radius: var(--radius-sm); overflow:hidden; aspect-ratio:16/9; display:flex; align-items:center; justify-content:center; text-align:center; padding:8px;">
                    <template x-if="thumbnail">
                        <img :src="thumbnail" alt="Thumbnail" style="max-width:100%; max-height:100%; object-fit:contain;">
                    </template>
                    <template x-if="!thumbnail && !error">
                        <span style="color:var(--text-muted); font-size:14px;"
                              x-text="source === 'tiktok'
                                  ? 'Thumbnail TikTok akan diambil otomatis saat disimpan'
                                  : 'Masukkan link untuk melihat preview'"></span>
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
            mediaType: @json(old('type', 'image')),
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
                this.url = (this.url || '').trim();

                if (!this.url) {
                    this.thumbnail = null;
                    this.source = null;
                    this.externalId = null;
                    this.error = null;
                    return;
                }

                // Regex untuk YouTube
                const ytRegex = /(?:youtube\.com\/watch\?v=|youtu\.be\/|youtube\.com\/embed\/|youtube\.com\/v\/|youtube\.com\/shorts\/)([^&?]+)/;
                // Regex untuk TikTok (link panjang dan link pendek vm/vt/t)
                const ttRegex = /tiktok\.com\/(?:[^\/]+\/video|embed(?:\/v2)?|player\/v1)\/(\d+)/;
                const ttShort = /^https?:\/\/(?:vm|vt)\.tiktok\.com\/|tiktok\.com\/t\//;
                // Regex untuk Google Drive
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

                match = this.url.match(ttRegex);
                if (match || ttShort.test(this.url)) {
                    this.source = 'tiktok';
                    this.externalId = match ? match[1] : null;
                    // Thumbnail diambil server via oEmbed saat disimpan (browser terblokir CORS)
                    this.thumbnail = '';
                    this.mediaType = 'video';
                    this.error = null;
                    return;
                }

                match = this.url.match(gdRegex);
                if (match) {
                    const id = match[1];
                    this.source = 'googledrive';
                    this.externalId = id;
                    // Thumbnail Drive (endpoint tidak resmi, hanya untuk file publik)
                    this.thumbnail = `https://drive.google.com/thumbnail?id=${id}&sz=w640`;
                    // Tipe default image, admin bisa ubah
                    if (this.mediaType !== 'video') this.mediaType = 'image';
                    this.error = null;
                    return;
                }

                this.thumbnail = null;
                this.source = null;
                this.externalId = null;
                this.error = 'URL tidak valid. Hanya YouTube, Google Drive, dan TikTok yang didukung.';
            }
        }
    }
    </script>
@endsection
