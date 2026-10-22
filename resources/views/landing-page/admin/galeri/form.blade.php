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

    {{-- Satu form, dua mode. Action berganti sesuai mode; input mode yang tidak aktif di-disable
         sehingga tidak ikut terkirim dan tidak memicu validasi "required" browser. --}}
    <form :action="mode === 'folder' ? folderAction : singleAction"
          action="{{ route('admin.galeri.store') }}"
          method="POST"
          enctype="multipart/form-data"
          class="lp-editor__grid lp-editor__grid--narrow"
          x-data="mediaForm()"
          x-init="init()">
        @csrf

        <div class="lp-editor__main">

            {{-- Pemilih mode --}}
            <div class="lp-filter-tabs" style="margin-bottom: 20px;">
                <a href="#" :class="mode === 'single' ? 'active' : ''" @click.prevent="mode = 'single'">Satu Media</a>
                <a href="#" :class="mode === 'folder' ? 'active' : ''" @click.prevent="mode = 'folder'">Impor Folder Google Drive</a>
            </div>

            {{-- ===== Khusus mode "Satu Media": judul ===== --}}
            <fieldset :disabled="mode !== 'single'" x-show="mode === 'single'" class="lp-form__fieldset">
                <div class="lp-form__group">
                    <label for="judul">Judul (opsional)</label>
                    <input type="text" id="judul" name="judul" value="{{ old('judul') }}" placeholder="Judul media" maxlength="55">
                    @error('judul') <span class="lp-form__error">{{ $message }}</span> @enderror
                </div>
            </fieldset>

            {{-- ===== Dipakai bersama kedua mode: kategori & kegiatan ===== --}}
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

            {{-- ===== MODE: SATU MEDIA ===== --}}
            <fieldset :disabled="mode !== 'single'" x-show="mode === 'single'" class="lp-form__fieldset">
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
                    <label for="thumbnail_file">Unggah Gambar Thumbnail (opsional)</label>
                    <input type="file" id="thumbnail_file" name="thumbnail_file"
                           accept="image/jpeg,image/png,image/webp"
                           @change="onFile($event)">
                    <p class="lp-editor-hint" x-show="source === 'tiktok'">
                        Thumbnail TikTok dicoba diambil otomatis saat disimpan, tetapi TikTok tidak selalu menyediakannya.
                        Jika tidak berhasil, unggah gambar sampul di sini (JPG/PNG/WebP, maks. 5 MB; otomatis dikecilkan agar hemat penyimpanan).
                    </p>
                    <p class="lp-editor-hint" x-show="source === 'googledrive'">
                        Thumbnail Google Drive diambil otomatis jika file dibagikan publik. Jika tampil kosong, unggah gambar sendiri di sini.
                    </p>
                    @error('thumbnail_file') <span class="lp-form__error">{{ $message }}</span> @enderror
                </div>

                <div class="lp-form__group" x-show="url && (source === 'googledrive' || source === 'tiktok')">
                    <label for="thumbnail">Atau Thumbnail URL (opsional)</label>
                    <input type="url" id="thumbnail" name="thumbnail" value="{{ old('thumbnail') }}" placeholder="https://... (jika kosong, otomatis / placeholder)">
                    <p class="lp-editor-hint">Jika Anda mengunggah gambar di atas, kolom ini diabaikan.</p>
                    @error('thumbnail') <span class="lp-form__error">{{ $message }}</span> @enderror
                </div>
            </fieldset>

            {{-- ===== MODE: IMPOR FOLDER GOOGLE DRIVE ===== --}}
            <fieldset :disabled="mode !== 'folder'" x-show="mode === 'folder'" x-cloak class="lp-form__fieldset">
                <div class="lp-form__group">
                    <label for="folder_url">Link Folder Google Drive</label>
                    <input type="url" id="folder_url" name="folder_url" value="{{ old('folder_url') }}"
                           placeholder="https://drive.google.com/drive/folders/..." required>
                    @error('folder_url') <span class="lp-form__error">{{ $message }}</span> @enderror
                    <p class="lp-editor-hint">
                        Folder harus dibagikan dengan akses "Siapa saja yang memiliki link". Semua gambar di dalam folder
                        akan ditambahkan sebagai draft dengan kategori &amp; kegiatan di atas. File yang sudah pernah ditambahkan akan dilewati.
                    </p>
                </div>

                <div class="lp-form__group">
                    <label for="judul_folder">Judul untuk Semua Foto (opsional)</label>
                    <input type="text" id="judul_folder" name="judul" value="{{ old('judul') }}" placeholder="Contoh: Dokumentasi RAKERNIK" maxlength="45">
                    @error('judul') <span class="lp-form__error">{{ $message }}</span> @enderror
                    <label style="display:flex; align-items:center; gap:6px; font-weight:500; margin-top:6px;">
                        <input type="checkbox" name="nomor_urut" value="1" {{ old('folder_url') ? (old('nomor_urut') ? 'checked' : '') : 'checked' }}>
                        Beri nomor urut (Dokumentasi RAKERNIK 01, 02, 03, ...)
                    </label>
                    <p class="lp-editor-hint">Jika dikosongkan, judul memakai nama kegiatan. Jika kegiatan juga kosong, foto tanpa judul. Urutan nomor mengikuti nama file di Drive.</p>
                </div>
            </fieldset>
        </div>

        <aside class="lp-editor__sidebar">
            {{-- Preview hanya untuk mode satu media --}}
            <div class="lp-editor__panel" x-show="mode === 'single'">
                <h3 class="lp-editor__panel-title">Preview</h3>
                <div style="background: var(--bg-surface-alt); border-radius: var(--radius-sm); overflow:hidden; aspect-ratio:16/9; display:flex; align-items:center; justify-content:center; text-align:center; padding:8px;">
                    <template x-if="thumbnail">
                        <img :src="thumbnail" alt="Thumbnail" style="max-width:100%; max-height:100%; object-fit:contain;">
                    </template>
                    <template x-if="!thumbnail && !error">
                        <span style="color:var(--text-muted); font-size:14px;"
                              x-text="source === 'tiktok'
                                  ? 'Thumbnail TikTok dicoba diambil otomatis saat disimpan (atau unggah gambar sendiri)'
                                  : 'Masukkan link untuk melihat preview'"></span>
                    </template>
                    <template x-if="error">
                        <span style="color:#d14545; font-size:14px;" x-text="error"></span>
                    </template>
                </div>
            </div>

            <div class="lp-editor__panel" x-show="mode === 'folder'" x-cloak>
                <h3 class="lp-editor__panel-title">Impor Folder</h3>
                <p class="lp-editor-hint" style="margin:0;">
                    Semua foto di folder akan masuk sebagai draft. Judul diisi otomatis dari isian di sebelah kiri; Anda tetap bisa mengeditnya satu per satu setelah impor.
                </p>
            </div>

            <div class="lp-editor__panel">
                <button type="submit" class="lp-btn lp-btn--primary lp-form__submit"
                        x-text="mode === 'folder' ? 'Impor Semua sebagai Draft' : 'Simpan sebagai Draft'">Simpan sebagai Draft</button>
            </div>
        </aside>
    </form>

    <script>
    function mediaForm() {
        return {
            // Mode: 'single' (satu media) atau 'folder' (impor folder Google Drive)
            mode: @json($errors->has('folder_url') || old('folder_url') ? 'folder' : 'single'),
            singleAction: @json(route('admin.galeri.store')),
            folderAction: @json(route('admin.galeri.store-folder')),

            url: '',
            source: null,
            externalId: null,
            thumbnail: null,
            mediaType: @json(old('type', 'image')),
            error: null,
            _blobUrl: null,
            init() {
                // Inisialisasi dari old value jika ada (gunakan Blade escape)
                const oldUrl = @json(old('url', ''));
                if (oldUrl) {
                    this.url = oldUrl;
                    this.processUrl();
                }
            },
            // Preview lokal gambar yang dipilih; jika pilihan dibatalkan, kembali ke preview otomatis
            onFile(event) {
                if (this._blobUrl) {
                    URL.revokeObjectURL(this._blobUrl);
                    this._blobUrl = null;
                }
                const file = event.target.files && event.target.files[0];
                if (file) {
                    this._blobUrl = URL.createObjectURL(file);
                    this.thumbnail = this._blobUrl;
                } else {
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
                    // Thumbnail diambil server saat disimpan (browser terblokir CORS), atau unggah manual
                    this.thumbnail = this._blobUrl || '';
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
                    this.thumbnail = this._blobUrl || `https://drive.google.com/thumbnail?id=${id}&sz=w640`;
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