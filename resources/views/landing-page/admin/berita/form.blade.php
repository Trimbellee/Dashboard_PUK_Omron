@extends('landing-page.admin.layouts.app')

@push('page-styles')
    @vite(['resources/css/admin/admin-berita.css'])
    <link href="https://cdn.jsdelivr.net/npm/quill@2.0.3/dist/quill.snow.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/quill-table-better@1/dist/quill-table-better.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/@enzedonline/quill-blot-formatter2/dist/css/quill-blot-formatter2.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/highlight.js/11.9.0/styles/github.min.css" rel="stylesheet">
@endpush

@section('title', ($berita->exists ? 'Edit' : 'Tulis') . ' Berita')

@php
    use App\Helpers\PermissionHelper;

    // Deteksi apakah ini mode review edit
    $isReviewEdit = request()->query('mode') === 'review' 
        && PermissionHelper::can('review_news') 
        && $berita->exists 
        && $berita->status === 'submitted_bidang5';

    // Tentukan action form
    if ($isReviewEdit) {
        $formAction = route('admin.berita.review-with-edit', $berita);
    } elseif ($berita->exists) {
        $formAction = route('admin.berita.update', $berita);
    } else {
        $formAction = route('admin.berita.store');
    }

    $submitLabel = $isReviewEdit ? '✅ Setujui & Kirim ke Appover' : ($berita->exists ? 'Simpan Perubahan' : 'Simpan Draft');
@endphp

@section('content')
    <form action="{{ $formAction }}"
          method="POST"
          enctype="multipart/form-data"
          x-data="beritaEditor({{ Js::from(old('konten', $berita->konten)) }}, {{ Js::from(old('kategori', $berita->kategori ?? '')) }})"
          @submit="$refs.kontenTextarea.value = (quill ? quill.root.innerHTML : konten)"
          class="lp-editor-form"
          x-init="initAutoSave()">
        @csrf
        @if ($berita->exists && !$isReviewEdit) 
            @method('PUT')
        @endif
        <input type="hidden" name="news_id" value="{{ $berita->id ?? 0 }}">

        {{-- ===== TOPBAR ===== --}}
        <div class="lp-editor-topbar">
            <div class="lp-editor-topbar__left">
                <a href="{{ route('admin.berita.index') }}" class="lp-editor-topbar__back">
                    <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <path d="M19 12H5M12 19l-7-7 7-7"/>
                    </svg>
                    <span>Kembali</span>
                </a>
                <h1 class="lp-editor-topbar__title">
                    {{ $isReviewEdit ? '✏️ Edit & Setujui Berita' : ($berita->exists ? '✏️ Edit Berita' : '📝 Tulis Berita Baru') }}
                </h1>
            </div>
            <div class="lp-editor-topbar__right">
                @if ($berita->exists)
                    <span class="lp-editor-topbar__status">
                        <span class="lp-badge lp-badge--{{ $berita->status }}">
                            {{ $berita->getStatusLabel() }}
                        </span>
                    </span>
                @endif
                @if ($isReviewEdit)
                    <span class="lp-editor-topbar__badge lp-badge lp-badge--review">
                        🔍 Mode Reviewer
                    </span>
                @endif
                <div class="lp-editor-topbar__shortcuts">
                    <span class="lp-shortcut-hint">Ctrl+S</span>
                </div>
                <button type="submit" class="lp-btn lp-btn--primary" id="btn-save">
                    <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <path d="M19 21H5a2 2 0 01-2-2V5a2 2 0 012-2h11l5 5v11a2 2 0 01-2 2z"/>
                        <path d="M17 21v-8H7v8"/>
                        <path d="M7 3v5h8"/>
                    </svg>
                    {{ $submitLabel }}
                </button>
            </div>
        </div>

        {{-- ===== NOTIFIKASI MODE REVIEWER ===== --}}
        @if ($isReviewEdit)
            <div class="lp-review-notice">
                <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <circle cx="12" cy="12" r="10"/>
                    <path d="M12 16v-4M12 8h.01"/>
                </svg>
                <div>
                    <strong>Anda sedang dalam mode edit reviewer.</strong>
                    <span>Setelah menyimpan, berita akan langsung diteruskan untuk approval final.</span>
                </div>
            </div>
        @endif

        {{-- ===== AUTO-SAVE STATUS ===== --}}
        <div class="lp-auto-save-status" x-show="autoSaveStatus" x-text="autoSaveStatus"></div>

        {{-- ===== FORM BODY ===== --}}
        <div class="lp-editor-body">

            {{-- Card 1: Informasi Dasar --}}
            <div class="lp-editor-card">
                <div class="lp-editor-card__header">
                    <h2 class="lp-editor-card__title">📰 Informasi Dasar</h2>
                </div>
                <div class="lp-editor-card__body">
                    {{-- Kategori --}}
                    <div class="lp-form-group">
                        <label class="lp-form-label">Kategori</label>
                        <div class="lp-category-pills">
                            <button type="button"
                                    class="lp-category-pill"
                                    :class="{ 'lp-category-pill--active': kategori === 'Pengumuman' }"
                                    @click="kategori = 'Pengumuman'">Pengumuman</button>
                            <button type="button"
                                    class="lp-category-pill"
                                    :class="{ 'lp-category-pill--active': kategori === 'Kegiatan' }"
                                    @click="kategori = 'Kegiatan'">Kegiatan</button>
                            <button type="button"
                                    class="lp-category-pill"
                                    :class="{ 'lp-category-pill--active': kategori === 'Rapat' }"
                                    @click="kategori = 'Rapat'">Rapat</button>
                            <input type="text"
                                   x-model="kategori"
                                   placeholder="Kategori lain..."
                                   class="lp-category-input">
                        </div>
                        <input type="hidden" name="kategori" :value="kategori">
                        @error('kategori') <span class="lp-form-error">⚠ {{ $message }}</span> @enderror
                    </div>

                    {{-- Judul --}}
                    <div class="lp-form-group">
                        <label class="lp-form-label" for="judul">Judul Berita</label>
                        <input type="text"
                               id="judul"
                               name="judul"
                               x-model="judul"
                               placeholder="Tulis judul yang menarik perhatian pembaca..."
                               class="lp-title-input"
                               maxlength="255"
                               required>
                        @error('judul') <span class="lp-form-error">⚠ {{ $message }}</span> @enderror
                    </div>

                    {{-- Meta --}}
                    <div class="lp-editor-meta">
                        <span>{{ session('anggota.nama_lengkap', session('anggota.nama', 'Redaksi PUK OMRON')) }}</span>
                        <span>{{ \Carbon\Carbon::now()->translatedFormat('d F Y') }}</span>
                        @if($berita->exists)
                            <span>👁️ {{ number_format($berita->views ?? 0) }} views</span>
                            <span>📝 {{ $berita->getReadingTime() }} menit baca</span>
                        @endif
                    </div>
                </div>
            </div>

            {{-- Card 2: Status Khusus --}}
            <div class="lp-editor-card">
                <div class="lp-editor-card__header">
                    <h2 class="lp-editor-card__title">⭐ Status Khusus</h2>
                </div>
                <div class="lp-editor-card__body">
                    <div class="lp-toggle-group">
                        <label class="lp-toggle">
                            <input type="checkbox" name="is_featured" value="1" {{ old('is_featured', $berita->is_featured ?? false) ? 'checked' : '' }}>
                            <span class="lp-toggle__slider"></span>
                            <span>⭐ Featured</span>
                        </label>
                        <label class="lp-toggle">
                            <input type="checkbox" name="is_breaking" value="1" {{ old('is_breaking', $berita->is_breaking ?? false) ? 'checked' : '' }}>
                            <span class="lp-toggle__slider"></span>
                            <span>🚨 Breaking News</span>
                        </label>
                        <label class="lp-toggle">
                            <input type="checkbox" name="is_headline" value="1" {{ old('is_headline', $berita->is_headline ?? false) ? 'checked' : '' }}>
                            <span class="lp-toggle__slider"></span>
                            <span>📰 Headline</span>
                        </label>
                    </div>
                </div>
            </div>

            {{-- Card 3: Tags --}}
            <div class="lp-editor-card">
                <div class="lp-editor-card__header">
                    <h2 class="lp-editor-card__title">🏷️ Tags</h2>
                    <span class="lp-editor-card__hint">Pisahkan dengan koma</span>
                </div>
                <div class="lp-editor-card__body">
                    <div class="lp-form-group">
                        <input type="text"
                               name="tags"
                               class="lp-form-input"
                               value="{{ old('tags', $berita->tags->pluck('name')->implode(', ')) }}"
                               placeholder="Contoh: politik, ekonomi, sosial, budaya">
                        @error('tags') <span class="lp-form-error">⚠ {{ $message }}</span> @enderror
                    </div>
                </div>
            </div>

            {{-- Card 4: Ringkasan --}}
            <div class="lp-editor-card">
                <div class="lp-editor-card__header">
                    <h2 class="lp-editor-card__title">📄 Ringkasan</h2>
                    <span class="lp-editor-card__counter" x-text="ringkasan.length + ' / 500'"></span>
                </div>
                <div class="lp-editor-card__body">
                    <div class="lp-form-group">
                        <textarea id="ringkasan"
                                  name="ringkasan"
                                  rows="2"
                                  x-model="ringkasan"
                                  maxlength="500"
                                  placeholder="Tulis ringkasan singkat yang akan tampil di kartu berita dan daftar..."
                                  class="lp-form-textarea"
                                  required></textarea>
                        @error('ringkasan') <span class="lp-form-error">⚠ {{ $message }}</span> @enderror
                    </div>
                </div>
            </div>

            {{-- Card 5: Konten (Quill) --}}
            <div class="lp-editor-card lp-editor-card--full">
                <div class="lp-editor-card__header">
                    <h2 class="lp-editor-card__title">✍️ Konten Lengkap</h2>
                    <span class="lp-editor-card__counter" x-text="wordCount + ' kata · ⏱️ ' + Math.ceil(wordCount / 200) + ' menit baca'"></span>
                </div>
                <div class="lp-editor-card__body">
                    <div class="lp-quill-wrap"
                         :class="{ 'lp-quill-fullscreen': isFullscreen }"
                         @keydown.escape.window="if (isFullscreen) toggleFullscreen()"
                         x-init="quill = initQuillEditor($refs.quillRoot, konten)">
                        <div x-ref="quillRoot"></div>
                    </div>
                    <div class="lp-quill-uploading" x-show="mediaUploading" x-cloak>
                        ⏳ Mengunggah gambar...
                    </div>

                    {{-- Tidak ada atribut maxlength di sini — konten Quill tidak
                         dibatasi jumlah karakternya sama sekali. --}}
                    <textarea name="konten" x-ref="kontenTextarea" class="lp-hidden">{{ old('konten', $berita->konten) }}</textarea>
                    @error('konten') <span class="lp-form-error">⚠ {{ $message }}</span> @enderror
                </div>
            </div>

            {{-- Card 6: Gambar --}}
            <div class="lp-editor-card">
                <div class="lp-editor-card__header">
                    <h2 class="lp-editor-card__title">🖼️ Gambar Utama</h2>
                    <span class="lp-editor-card__hint">JPG/PNG · maks 2MB</span>
                </div>
                <div class="lp-editor-card__body">
                    <label for="gambar" class="lp-dropzone">
                        <input type="file"
                               id="gambar"
                               name="gambar"
                               accept="image/*"
                               x-ref="gambarInput"
                               class="lp-hidden"
                               @change="imagePreview = $event.target.files[0] ? URL.createObjectURL($event.target.files[0]) : null">

                        <template x-if="!imagePreview">
                            <div class="lp-dropzone__content">
                                @if ($berita->exists && $berita->gambar)
                                    <img src="{{ asset('storage/' . $berita->gambar) }}" alt="Gambar saat ini" class="lp-dropzone__existing">
                                    <span>Klik atau seret untuk ganti gambar</span>
                                @else
                                    <svg width="32" height="32" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5">
                                        <path d="M4 16l4-4 4 4 4-6 4 6M4 4h16v16H4z"/>
                                    </svg>
                                    <span>Seret gambar ke sini atau klik untuk unggah</span>
                                @endif
                            </div>
                        </template>
                        <template x-if="imagePreview">
                            <img :src="imagePreview" class="lp-dropzone__preview" alt="Preview gambar">
                        </template>
                    </label>
                    @error('gambar') <span class="lp-form-error">⚠ {{ $message }}</span> @enderror
                </div>
            </div>

            {{-- Card 7: Jadwalkan --}}
            <div class="lp-editor-card">
                <div class="lp-editor-card__header">
                    <h2 class="lp-editor-card__title">📅 Jadwalkan Publikasi</h2>
                </div>
                <div class="lp-editor-card__body">
                    <div class="lp-form-group">
                        <input type="datetime-local"
                               name="scheduled_at"
                               class="lp-form-input"
                               value="{{ old('scheduled_at', $berita->scheduled_at ? $berita->scheduled_at->format('Y-m-d\TH:i') : '') }}">
                        <p class="lp-form-hint">Kosongkan jika ingin langsung publish atau simpan draft</p>
                        @error('scheduled_at') <span class="lp-form-error">⚠ {{ $message }}</span> @enderror
                    </div>
                </div>
            </div>

            {{-- Card 8: SEO Settings --}}
            <div class="lp-editor-card">
                <div class="lp-editor-card__header">
                    <h2 class="lp-editor-card__title">🔍 SEO Settings</h2>
                    <span class="lp-editor-card__hint">Optimasi untuk mesin pencari</span>
                </div>
                <div class="lp-editor-card__body">
                    <div class="lp-form-group">
                        <label class="lp-form-label" for="meta_title">Meta Title</label>
                        <input type="text"
                               id="meta_title"
                               name="meta_title"
                               class="lp-form-input"
                               placeholder="{{ $berita->judul ?? 'Judul berita' }}"
                               maxlength="70"
                               x-model="metaTitle"
                               @input="updateSeoScore()">
                        <span class="lp-form-counter" id="meta-title-counter">{{ strlen(old('meta_title', $berita->meta_title ?? '')) }}/70</span>
                    </div>
                    <div class="lp-form-group">
                        <label class="lp-form-label" for="meta_description">Meta Description</label>
                        <textarea id="meta_description"
                                  name="meta_description"
                                  rows="2"
                                  class="lp-form-textarea"
                                  placeholder="Deskripsi singkat untuk SEO..."
                                  maxlength="250"
                                  x-model="metaDescription"
                                  @input="updateSeoScore()"></textarea>
                        <span class="lp-form-counter" id="meta-desc-counter">{{ strlen(old('meta_description', $berita->meta_description ?? '')) }}/250</span>
                    </div>

                    <div class="lp-seo-score" x-show="showSeoScore">
                        <div class="lp-seo-score__bar">
                            <div class="lp-seo-score__fill" :style="{ width: seoScore + '%', background: seoColor }"></div>
                        </div>
                        <div class="lp-seo-score__info">
                            <span class="lp-seo-score__label">SEO Score</span>
                            <span class="lp-seo-score__value" x-text="seoScore + '%'"></span>
                        </div>
                    </div>
                </div>
            </div>

            {{-- Card 9: Actions --}}
            <div class="lp-editor-card lp-editor-card--actions">
                <div class="lp-editor-card__body">
                    <div class="lp-editor-actions">
                        <div class="lp-editor-actions__left">
                            {{--
                                FIX: sinkronkan `konten` langsung dari Quill SEBELUM modal dibuka,
                                jangan andalkan event text-change saja (bisa telat/tidak ke-fire
                                kalau perubahan terakhir berasal dari toggle fullscreen, table-better,
                                atau interaksi lain yang tidak lewat jalur normal user-typing).
                            --}}
                            <button type="button" class="lp-btn lp-btn--ghost" @click="konten = (quill ? quill.root.innerHTML : konten); previewOpen = true">
                                👁️ Lihat Pratinjau
                            </button>
                            <button type="button" class="lp-btn lp-btn--ghost" @click="openMediaManager()">
                                🖼️ Media Library
                            </button>
                        </div>
                        <div class="lp-editor-actions__right">
                            <a href="{{ route('admin.berita.index') }}" class="lp-btn lp-btn--ghost">Batal</a>
                            <button type="submit" class="lp-btn lp-btn--primary" id="btn-save-bottom">
                                {{ $submitLabel }}
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        {{-- ===== PREVIEW MODAL ===== --}}
        <div x-show="previewOpen"
            x-cloak
            x-transition
            @click.self="previewOpen = false"
            @keydown.escape.window="previewOpen = false"
            class="lp-modal">
            <div class="lp-modal__inner lp-modal__inner--preview">
                <div class="lp-modal__header">
                    <span class="lp-modal__title">📖 Pratinjau — Tampilan Pembaca</span>
                    <button type="button" class="lp-modal__close" @click="previewOpen = false" aria-label="Tutup">✕</button>
                </div>
                <article class="lp-preview-article">
                    <span class="lp-preview-article__kategori" x-text="kategori || 'Tanpa Kategori'"></span>
                    <h1 class="lp-preview-article__title" x-text="judul || 'Judul berita akan tampil di sini'"></h1>
                    <div class="lp-preview-article__byline">
                        <span>✍️ <strong>Penulis:</strong> {{ session('anggota.nama_lengkap', session('anggota.nama', 'Redaksi PUK OMRON')) }}</span>
                        <span class="lp-preview-article__date">
                            <span x-text="new Date().toLocaleDateString('id-ID', { day: 'numeric', month: 'long', year: 'numeric' })"></span>
                        </span>
                    </div>
                    <template x-if="imagePreview">
                        <div class="lp-preview-article__image">
                            <img :src="imagePreview" alt="Gambar utama">
                        </div>
                    </template>
                    <!-- FIX: Pastikan class ql-editor ada di sini -->
                    <template x-if="konten">
                        <div x-html="konten" class="lp-preview-article__body ql-editor"></div>
                    </template>
                    <template x-if="!konten">
                        <p class="lp-preview-article__empty" style="color: var(--text-secondary); font-style: italic;">
                            Belum ada konten untuk ditampilkan. Tulis sesuatu di editor terlebih dahulu.
                        </p>
                    </template>
                    <div class="lp-preview-article__footer">
                        <span>⏱️ <span x-text="Math.ceil(wordCount / 200)"></span> menit baca</span>
                        <span>📝 <span x-text="wordCount"></span> kata</span>
                        <span>🏷️ <span x-text="kategori || 'Tanpa kategori'"></span></span>
                    </div>
                </article>
            </div>
        </div>

        {{-- ===== MEDIA MANAGER MODAL ===== --}}
        <div x-show="mediaManagerOpen"
             x-cloak
             x-transition
             @click.self="mediaManagerOpen = false"
             @keydown.escape.window="mediaManagerOpen = false"
             class="lp-modal">
            <div class="lp-modal__inner lp-modal__inner--media">
                <div class="lp-modal__header">
                    <span class="lp-modal__title">🖼️ Media Library</span>
                    <button type="button" class="lp-modal__close" @click="mediaManagerOpen = false" aria-label="Tutup">✕</button>
                </div>
                <div class="lp-modal__body">
                    <div class="lp-media-grid">
                        <template x-if="!mediaLoading && mediaFiles.length === 0">
                            <div class="lp-media-empty">Belum ada media. Unggah gambar pertama Anda di bawah ini.</div>
                        </template>
                        <template x-if="mediaLoading">
                            <div class="lp-media-empty">Memuat media...</div>
                        </template>
                        <template x-for="media in mediaFiles" :key="media.id">
                            <div class="lp-media-item" @click="insertMedia(media.url)">
                                <img :src="media.thumbnail" :alt="media.name">
                                <span x-text="media.name"></span>
                            </div>
                        </template>
                    </div>
                    <div class="lp-media-upload">
                        <input type="file"
                            x-ref="mediaInput"
                            multiple
                            accept="image/*"
                            @change="uploadMedia($event)"
                            class="lp-media-input">
                        <button type="button" class="lp-btn lp-btn--primary" @click="$refs.mediaInput.click()">📤 Upload Gambar</button>
                    </div>
                </div>
            </div>
        </div>

        {{-- ===== KEYBOARD SHORTCUTS HELPER ===== --}}
        <div class="lp-keyboard-shortcuts" x-show="showShortcuts" x-cloak @click.away="showShortcuts = false">
            <h4>⌨️ Keyboard Shortcuts</h4>
            <ul>
                <li><kbd>Ctrl+S</kbd> Simpan draft</li>
                <li><kbd>Ctrl+Shift+P</kbd> Preview</li>
                <li><kbd>Ctrl+Shift+F</kbd> Fullscreen editor</li>
                <li><kbd>Ctrl+B</kbd> Bold</li>
                <li><kbd>Ctrl+I</kbd> Italic</li>
                <li><kbd>Ctrl+U</kbd> Underline</li>
                <li><kbd>Ctrl+K</kbd> Insert link</li>
                <li><kbd>Ctrl+Z</kbd> Undo</li>
                <li><kbd>Ctrl+Y</kbd> Redo</li>
                <li><kbd>Ctrl+/</kbd> Show shortcuts</li>
            </ul>
        </div>
    </form>

    @push('scripts')
    {{-- highlight.js HARUS dimuat SEBELUM quill.js. Quill's Syntax module membaca
         window.hljs untuk nilai default-nya pada saat file quill.js itu sendiri
         dieksekusi oleh browser (bukan saat new Quill() dipanggil belakangan) —
         kalau urutannya kebalik, window.hljs masih undefined di momen itu dan
         nilainya ke-bake secara permanen jadi undefined, menyebabkan Quill selalu
         throw "Syntax module requires highlight.js" begitu new Quill() dipanggil,
         yang ujung-ujungnya bikin instance Quill gagal terbentuk sama sekali. --}}
    <script src="https://cdnjs.cloudflare.com/ajax/libs/highlight.js/11.9.0/highlight.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/quill@2.0.3/dist/quill.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/quill-table-better@1/dist/quill-table-better.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/@enzedonline/quill-blot-formatter2@3.0/dist/index.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/quill-magic-url@4.1.3/dist/index.min.js"></script>
    <script>
        // ===== Registrasi module Quill, dilakukan sekali secara global =====

        // Tabel
        if (window.Quill && window.QuillTableBetter) {
            Quill.register({ 'modules/table-better': QuillTableBetter }, true);
        }

        // Resize & reposisi gambar / video (iframe) langsung di editor
        if (window.Quill && window.QuillBlotFormatter2) {
            const BlotFormatter2 = window.QuillBlotFormatter2.default || window.QuillBlotFormatter2;
            Quill.register('modules/blotFormatter2', BlotFormatter2, true);
        }

        // Auto-convert URL jadi link saat mengetik / paste
        if (window.Quill && window.MagicUrl) {
            Quill.register('modules/magicUrl', window.MagicUrl, true);
        }

        // Pilihan font tambahan (dibatasi ke font aman yang tidak perlu di-load dari luar)
        if (window.Quill) {
            const Font = Quill.import('formats/font');
            Font.whitelist = ['serif', 'monospace'];
            Quill.register(Font, true);
        }

        // Blot sederhana untuk garis pembatas <hr> agar formatnya tersimpan
        if (window.Quill && !Quill.imports['formats/divider']) {
            const BlockEmbed = Quill.import('blots/block/embed');
            class DividerBlot extends BlockEmbed {}
            DividerBlot.blotName = 'divider';
            DividerBlot.tagName = 'hr';
            Quill.register(DividerBlot, true);
        }

        function beritaEditor(initialKonten, initialKategori) {
            return {
                // Data
                quill: null,
                konten: initialKonten || '',
                ringkasan: {{ Js::from(old('ringkasan', $berita->ringkasan ?? '')) }},
                judul: {{ Js::from(old('judul', $berita->judul ?? '')) }},
                kategori: initialKategori || '',
                metaTitle: {{ Js::from(old('meta_title', $berita->meta_title ?? '')) }},
                metaDescription: {{ Js::from(old('meta_description', $berita->meta_description ?? '')) }},
                imagePreview: null,
                previewOpen: false,
                mediaManagerOpen: false,
                mediaLoading: false,
                mediaUploading: false,
                showShortcuts: false,
                isFullscreen: false,
                autoSaveStatus: '',
                autoSaveTimer: null,
                seoScore: 0,
                showSeoScore: false,
                mediaFiles: [],
                _pendingImageRange: null,

                // Computed
                get wordCount() {
                    if (!this.quill) return 0;
                    const text = this.quill.getText().trim();
                    return text ? text.split(/\s+/).filter(Boolean).length : 0;
                },
                get seoColor() {
                    if (this.seoScore >= 80) return '#059669';
                    if (this.seoScore >= 50) return '#d97706';
                    return '#dc2626';
                },

                // ===== Quill setup =====
                initQuillEditor(el, initialHtml) {
                    // Ikon custom untuk tombol HR & Fullscreen
                    const icons = Quill.import('ui/icons');
                    icons['hr'] = '<svg viewBox="0 0 18 18"><line class="ql-stroke" x1="2" y1="9" x2="16" y2="9"></line></svg>';
                    icons['fullscreen'] = '<svg viewBox="0 0 18 18"><path class="ql-stroke" fill="none" d="M3,7 L3,3 L7,3 M11,3 L15,3 L15,7 M15,11 L15,15 L11,15 M7,15 L3,15 L3,11"/></svg>';

                    const toolbarOptions = [
                        [{ font: [] }],
                        [{ size: ['small', false, 'large', 'huge'] }],
                        [{ header: [1, 2, 3, 4, 5, 6, false] }],
                        ['bold', 'italic', 'underline', 'strike'],
                        [{ color: [] }, { background: [] }],
                        [{ script: 'sub' }, { script: 'super' }],
                        ['blockquote', 'code-block'],
                        [{ list: 'ordered' }, { list: 'bullet' }, { list: 'check' }],
                        [{ indent: '-1' }, { indent: '+1' }],
                        [{ direction: 'rtl' }],
                        [{ align: [] }],
                        ['link', 'image', 'video'],
                        ['table-better'],
                        ['hr', 'fullscreen'],
                        ['clean'],
                    ];

                    const modules = {
                        toolbar: {
                            container: toolbarOptions,
                            handlers: {
                                image: () => this.openMediaManager(),
                                hr: () => {
                                    const range = quill.getSelection(true) || { index: quill.getLength() };
                                    quill.insertEmbed(range.index, 'divider', true, 'user');
                                    quill.setSelection(range.index + 1, 0, 'silent');
                                },
                                fullscreen: () => this.toggleFullscreen(),
                            },
                        },
                        keyboard: {
                            bindings: {
                                fullscreenToggle: {
                                    key: 'F',
                                    shortKey: true,
                                    shiftKey: true,
                                    handler: () => { this.toggleFullscreen(); return false; },
                                },
                            },
                        },
                        // userOnly: true -> hanya perubahan yang benar-benar diketik/dipilih
                        // user yang masuk ke stack undo/redo. Tanpa ini, setiap gerakan kecil
                        // dari resize gambar (blotFormatter2) atau operasi internal tabel
                        // (table-better) ikut tercatat sebagai langkah undo terpisah, sehingga
                        // Ctrl+Z/Ctrl+Y terasa "salah sasaran" (cuma membatalkan sepotong kecil
                        // dari resize/tabel, bukan perubahan teks terakhir).
                        history: {
                            delay: 1000,
                            maxStack: 500,
                            userOnly: true,
                        },
                    };

                    // Only wire up the table module if it loaded successfully from the CDN.
                    if (window.QuillTableBetter) {
                        modules.table = false;
                        modules['table-better'] = {
                            language: 'en_US',
                            menus: ['column', 'row', 'merge', 'table', 'cell', 'wrap', 'copy', 'delete'],
                            toolbarTable: true,
                        };
                        Object.assign(modules.keyboard.bindings, window.QuillTableBetter.keyboardBindings);
                    }

                    // Resize / reposisi gambar & video (iframe) — hanya jika berhasil dimuat dari CDN.
                    if (window.QuillBlotFormatter2) {
                        modules.blotFormatter2 = {
                            image: { allowAltTitleEdit: true },
                        };
                    }

                    // Auto-link URL saat mengetik atau paste.
                    if (window.MagicUrl) {
                        modules.magicUrl = true;
                    }

                    // Syntax highlighting untuk code-block — hanya jika highlight.js berhasil dimuat.
                    // Catatan: `hljs` di sini WAJIB di-pass eksplisit (bukan cuma `highlight`),
                    // karena Quill's Syntax module membaca opsi `hljs` untuk validasi internal —
                    // kalau tidak di-set, ia jatuh ke default module-load-time yang bisa saja
                    // sudah ke-bake `undefined` (lihat urutan <script> di atas) dan tetap throw
                    // meski window.hljs sebenarnya sudah tersedia saat baris ini dieksekusi.
                    if (window.hljs) {
                        modules.syntax = {
                            hljs: window.hljs,
                            highlight: (text) => window.hljs.highlightAuto(text).value,
                        };
                    } else {
                        // Jangan biarkan Quill mencoba init syntax module kalau hljs tidak ada.
                        modules.syntax = false;
                    }

                    const quill = new Quill(el, {
                        theme: 'snow',
                        placeholder: 'Mulai menulis artikel Anda di sini...',
                        modules,
                    });

                    if (initialHtml) {
                        quill.root.innerHTML = initialHtml;
                    }

                    // FIX: set konten dari state Quill sekali di sini juga, supaya nilai
                    // reaktif `this.konten` pasti sudah sinkron dengan isi editor
                    // sesaat setelah inisialisasi (tidak menunggu text-change pertama).
                    this.konten = quill.root.innerHTML;

                    quill.on('text-change', () => {
                        this.konten = quill.root.innerHTML;
                    });

                    // Auto-upload pasted or dropped images instead of leaving them as base64 blobs.
                    quill.root.addEventListener('paste', (e) => this.handlePasteOrDrop(e, quill));
                    quill.root.addEventListener('drop', (e) => this.handlePasteOrDrop(e, quill));

                    return quill;
                },

                toggleFullscreen() {
                    this.isFullscreen = !this.isFullscreen;
                    if (this.quill) {
                        setTimeout(() => this.quill.focus(), 50);
                    }
                },

                handlePasteOrDrop(e, quill) {
                    const items = e.clipboardData ? e.clipboardData.files : (e.dataTransfer ? e.dataTransfer.files : null);
                    if (!items || items.length === 0) return;

                    const imageFile = Array.from(items).find(f => f.type.startsWith('image/'));
                    if (!imageFile) return;

                    e.preventDefault();
                    const range = quill.getSelection(true) || { index: quill.getLength() };
                    this.uploadAndInsertImage(imageFile, quill, range.index);
                },

                uploadAndInsertImage(file, quill, index) {
                    this.mediaUploading = true;
                    const formData = new FormData();
                    formData.append('media[]', file);

                    fetch('{{ route("admin.media.upload") }}', {
                        method: 'POST',
                        headers: { 'X-CSRF-TOKEN': '{{ csrf_token() }}' },
                        body: formData,
                    })
                        .then(res => { if (!res.ok) throw new Error('HTTP ' + res.status); return res.json(); })
                        .then(data => {
                            if (data.success && data.media && data.media[0]) {
                                // FIX: argumen ke-2 insertEmbed harus NAMA FORMAT ('image'),
                                // bukan URL-nya. Urutan yang benar:
                                // insertEmbed(index, format, value, source)
                                quill.insertEmbed(index, 'image', data.media[0].url, 'user');
                                quill.setSelection(index + 1);
                                this.konten = quill.root.innerHTML;
                            } else if (window.showToast) {
                                showToast('error', 'Gagal upload gambar.');
                            }
                        })
                        .catch(() => {
                            if (window.showToast) showToast('error', 'Gagal upload gambar.');
                        })
                        .finally(() => { this.mediaUploading = false; });
                },

                updateSeoScore() {
                    let score = 0;
                    if (this.judul && this.judul.length >= 10) score += 25;
                    if (this.metaTitle && this.metaTitle.length >= 30 && this.metaTitle.length <= 70) score += 25;
                    if (this.metaDescription && this.metaDescription.length >= 50 && this.metaDescription.length <= 250) score += 25;
                    if (this.wordCount >= 300) score += 25;

                    this.seoScore = score;
                    this.showSeoScore = true;
                },

                // ===== Media library (inserts into Quill) =====
                openMediaManager() {
                    if (this.quill) {
                        const range = this.quill.getSelection(true);
                        this._pendingImageRange = range ? range.index : this.quill.getLength();
                    }
                    this.mediaManagerOpen = true;
                    this.mediaLoading = true;
                    fetch('{{ route("admin.media.list") }}')
                        .then(res => {
                            if (!res.ok) throw new Error('HTTP ' + res.status);
                            return res.json();
                        })
                        .then(data => {
                            this.mediaFiles = Array.isArray(data) ? data : [];
                            this.mediaLoading = false;
                        })
                        .catch(err => {
                            console.error('Error loading media:', err);
                            this.mediaFiles = [];
                            this.mediaLoading = false;
                            if (window.showToast) showToast('error', 'Gagal memuat media library.');
                        });
                },

                insertMedia(url) {
                    if (!this.quill) return;
                    const index = this._pendingImageRange ?? this.quill.getLength();
                    // FIX: sama seperti di atas — format dulu, baru value.
                    this.quill.insertEmbed(index, 'image', url, 'user');
                    this.quill.setSelection(index + 1);
                    this.konten = this.quill.root.innerHTML;
                    this.mediaManagerOpen = false;
                },

                uploadMedia(event) {
                    const files = event.target.files;
                    if (!files || files.length === 0) return;

                    const formData = new FormData();
                    for (let file of files) {
                        formData.append('media[]', file);
                    }

                    fetch('{{ route("admin.media.upload") }}', {
                        method: 'POST',
                        headers: {
                            'X-CSRF-TOKEN': '{{ csrf_token() }}'
                        },
                        body: formData
                    })
                    .then(res => {
                        if (!res.ok) throw new Error('HTTP ' + res.status);
                        return res.json();
                    })
                    .then(data => {
                        if (data.success) {
                            this.openMediaManager();
                            if (window.showToast) showToast('success', 'Media berhasil diunggah');
                        } else if (window.showToast) {
                            showToast('error', 'Gagal upload media. Silahkan coba lagi.');
                        }
                    })
                    .catch(err => {
                        console.error('Error uploading media:', err);
                        if (window.showToast) {
                            showToast('error', 'Gagal upload media. Silahkan coba lagi.');
                        } else {
                            alert('Gagal upload media. Silahkan coba lagi.');
                        }
                    })
                    .finally(() => {
                        event.target.value = '';
                    });
                },

                initAutoSave() {
                    this.autoSaveTimer = setInterval(() => {
                        if (!this.judul && !this.konten) return;

                        this.$refs.kontenTextarea.value = this.quill ? this.quill.root.innerHTML : this.konten;

                        const form = document.querySelector('form');
                        const formData = new FormData(form);
                        formData.delete('gambar');

                        fetch('{{ route("admin.berita.auto-save") }}', {
                            method: 'POST',
                            headers: {
                                'X-CSRF-TOKEN': '{{ csrf_token() }}',
                                'X-Requested-With': 'XMLHttpRequest'
                            },
                            body: formData
                        })
                        .then(res => {
                            if (!res.ok) throw new Error('HTTP ' + res.status);
                            return res.json();
                        })
                        .then(data => {
                            if (data.success) {
                                this.autoSaveStatus = '💾 Auto-saved at ' + new Date().toLocaleTimeString();
                                setTimeout(() => { this.autoSaveStatus = ''; }, 3000);
                            }
                        })
                        .catch(err => {
                            console.error('Auto-save error:', err);
                        });
                    }, 30000);

                    // Keyboard shortcuts
                    document.addEventListener('keydown', (e) => {
                        if (e.ctrlKey && e.key === 's') {
                            e.preventDefault();
                            document.querySelector('#btn-save')?.click();
                        }
                        if (e.ctrlKey && e.shiftKey && e.key === 'P') {
                            e.preventDefault();
                            this.konten = this.quill ? this.quill.root.innerHTML : this.konten;
                            this.previewOpen = true;
                        }
                        if (e.ctrlKey && e.key === '/') {
                            e.preventDefault();
                            this.showShortcuts = !this.showShortcuts;
                        }
                    });
                }
            };
        }

        // Meta counter
        document.addEventListener('DOMContentLoaded', function() {
            const metaTitle = document.getElementById('meta_title');
            const metaDesc = document.getElementById('meta_description');
            const titleCounter = document.getElementById('meta-title-counter');
            const descCounter = document.getElementById('meta-desc-counter');

            if (metaTitle && titleCounter) {
                metaTitle.addEventListener('input', function() {
                    titleCounter.textContent = this.value.length + '/70';
                });
            }
            if (metaDesc && descCounter) {
                metaDesc.addEventListener('input', function() {
                    descCounter.textContent = this.value.length + '/250';
                });
            }
        });
    </script>
    @endpush
@endsection