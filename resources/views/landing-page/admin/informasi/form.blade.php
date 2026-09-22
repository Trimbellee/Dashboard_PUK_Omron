@extends('landing-page.admin.layouts.app')

@push('page-styles')
    @vite(['resources/css/admin/admin-informasi.css'])
    <style>[x-cloak] { display: none !important; }</style>
@endpush

@section('title', $informasi ? 'Edit Informasi' : 'Tambah Informasi')

@section('content')
<div class="lp-admin-section" x-data="informasiForm()" x-init="init()">

    <div class="lp-editor__topbar">
        <a href="{{ route('admin.informasi.index') }}" class="lp-editor__back">
            <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="m15 18-6-6 6-6"/></svg>
            Kembali ke Kelola Informasi
        </a>
    </div>

    {{-- ===== HEADER ===== --}}
    <div class="lp-form-header">
        <div class="lp-form-header__icon" aria-hidden="true">
            <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><circle cx="12" cy="12" r="10"/><path d="M12 16v-4M12 8h.01"/></svg>
        </div>
        <div>
            <h2>{{ $informasi ? 'Edit Informasi' : 'Tambah Informasi' }}</h2>
            <p class="lp-admin-section__subtitle">
                {{ $informasi ? 'Perbarui konten informasi yang tampil di footer landing page.' : 'Buat konten baru seperti info iklan, pedoman siber, legal, dsb.' }}
            </p>
        </div>
        @if ($informasi)
            <span class="lp-admin-badge lp-admin-badge--{{ $informasi->status }}" style="margin-left:auto;">
                {{ \App\Models\Information::STATUS_LIST[$informasi->status] ?? $informasi->status }}
            </span>
        @endif
    </div>

    <form action="{{ $informasi ? route('admin.informasi.update', $informasi->id) : route('admin.informasi.store') }}"
          method="POST" class="lp-info-form" @submit="onSubmit($event)">
        @csrf
        @if ($informasi)
            @method('PUT')
        @endif

        <div class="lp-editor__grid lp-editor__grid--narrow">
            <div class="lp-editor__main">

                {{-- ===== JUDUL ===== --}}
                <div class="lp-editor__panel">
                    <div class="lp-form__group">
                        <label for="judul" class="lp-form__label">Judul</label>
                        <input type="text" name="judul" id="judul" class="lp-form__input lp-form__input--title"
                               value="{{ old('judul', $informasi->judul ?? '') }}"
                               placeholder="Tulis judul informasi..." required>
                        @error('judul') <span class="lp-form__error">{{ $message }}</span> @enderror
                    </div>
                </div>

                {{-- ===== KONTEN — RICH TEXT EDITOR ===== --}}
                <div class="lp-editor__panel lp-editor__panel--flush">
                    <div class="lp-form__group" style="padding: 20px 20px 0;">
                        <label class="lp-form__label">Konten</label>
                    </div>

                    <div class="lp-rte">
                        {{-- Toolbar --}}
                        <div class="lp-rte__toolbar" role="toolbar" aria-label="Alat pemformatan teks">

                            <div class="lp-rte__group">
                                <button type="button" class="lp-rte__btn" title="Urungkan" @click="exec('undo')">
                                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M9 14 4 9l5-5"/><path d="M4 9h10.5a5.5 5.5 0 0 1 0 11H11"/></svg>
                                </button>
                                <button type="button" class="lp-rte__btn" title="Ulangi" @click="exec('redo')">
                                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="m15 14 5-5-5-5"/><path d="M20 9H9.5a5.5 5.5 0 0 0 0 11H13"/></svg>
                                </button>
                            </div>

                            <span class="lp-rte__divider"></span>

                            <div class="lp-rte__group">
                                <select class="lp-rte__select" @change="formatBlock($event.target.value); $event.target.selectedIndex = 0" title="Gaya paragraf">
                                    <option value="" disabled selected>Gaya</option>
                                    <option value="P">Paragraf</option>
                                    <option value="H2">Judul Besar</option>
                                    <option value="H3">Judul Kecil</option>
                                    <option value="BLOCKQUOTE">Kutipan</option>
                                </select>
                            </div>

                            <span class="lp-rte__divider"></span>

                            <div class="lp-rte__group">
                                <button type="button" class="lp-rte__btn" :class="{ 'lp-rte__btn--active': formatState.bold }" title="Tebal (Ctrl+B)" @click="exec('bold')">
                                    <span class="lp-rte__glyph" style="font-weight:800;">B</span>
                                </button>
                                <button type="button" class="lp-rte__btn" :class="{ 'lp-rte__btn--active': formatState.italic }" title="Miring (Ctrl+I)" @click="exec('italic')">
                                    <span class="lp-rte__glyph" style="font-style:italic;">I</span>
                                </button>
                                <button type="button" class="lp-rte__btn" :class="{ 'lp-rte__btn--active': formatState.underline }" title="Garis bawah (Ctrl+U)" @click="exec('underline')">
                                    <span class="lp-rte__glyph" style="text-decoration:underline;">U</span>
                                </button>
                                <button type="button" class="lp-rte__btn" :class="{ 'lp-rte__btn--active': formatState.strikeThrough }" title="Coret" @click="exec('strikeThrough')">
                                    <span class="lp-rte__glyph" style="text-decoration:line-through;">S</span>
                                </button>
                            </div>

                            <span class="lp-rte__divider"></span>

                            {{-- Warna teks --}}
                            <div class="lp-rte__group" style="position:relative;">
                                <button type="button" class="lp-rte__btn" title="Warna teks" @click="showTextColorPopover = !showTextColorPopover; showHighlightPopover = false" @click.outside="showTextColorPopover = false">
                                    <span class="lp-rte__glyph" style="font-weight:700;">A</span>
                                    <span class="lp-rte__color-bar" style="background:#dc2626;"></span>
                                </button>
                                <div class="lp-rte__popover" x-show="showTextColorPopover" x-cloak x-transition.opacity.duration.120ms>
                                    <div class="lp-rte__swatches">
                                        <button type="button" class="lp-rte__swatch" style="background:#0b1a2b" @click="applyColor('#0b1a2b')" title="Hitam"></button>
                                        <button type="button" class="lp-rte__swatch" style="background:#1a5fb4" @click="applyColor('#1a5fb4')" title="Biru"></button>
                                        <button type="button" class="lp-rte__swatch" style="background:#dc2626" @click="applyColor('#dc2626')" title="Merah"></button>
                                        <button type="button" class="lp-rte__swatch" style="background:#059669" @click="applyColor('#059669')" title="Hijau"></button>
                                        <button type="button" class="lp-rte__swatch" style="background:#d97706" @click="applyColor('#d97706')" title="Oranye"></button>
                                        <button type="button" class="lp-rte__swatch" style="background:#6d28d9" @click="applyColor('#6d28d9')" title="Ungu"></button>
                                        <button type="button" class="lp-rte__swatch" style="background:#6b7f94" @click="applyColor('#6b7f94')" title="Abu-abu"></button>
                                    </div>
                                </div>
                            </div>

                            {{-- Highlight --}}
                            <div class="lp-rte__group" style="position:relative;">
                                <button type="button" class="lp-rte__btn" title="Sorot teks" @click="showHighlightPopover = !showHighlightPopover; showTextColorPopover = false" @click.outside="showHighlightPopover = false">
                                    <span class="lp-rte__glyph" style="background:#fef08a; padding:0 3px; border-radius:2px;">H</span>
                                </button>
                                <div class="lp-rte__popover" x-show="showHighlightPopover" x-cloak x-transition.opacity.duration.120ms>
                                    <div class="lp-rte__swatches">
                                        <button type="button" class="lp-rte__swatch" style="background:#fef08a" @click="applyHighlight('#fef08a')" title="Kuning"></button>
                                        <button type="button" class="lp-rte__swatch" style="background:#bbf7d0" @click="applyHighlight('#bbf7d0')" title="Hijau muda"></button>
                                        <button type="button" class="lp-rte__swatch" style="background:#bfdbfe" @click="applyHighlight('#bfdbfe')" title="Biru muda"></button>
                                        <button type="button" class="lp-rte__swatch" style="background:#fecaca" @click="applyHighlight('#fecaca')" title="Merah muda"></button>
                                        <button type="button" class="lp-rte__swatch" style="background:transparent; border:1px dashed var(--border-color);" @click="applyHighlight('transparent')" title="Hapus sorot"></button>
                                    </div>
                                </div>
                            </div>

                            <span class="lp-rte__divider"></span>

                            <div class="lp-rte__group">
                                <button type="button" class="lp-rte__btn" :class="{ 'lp-rte__btn--active': formatState.justifyLeft }" title="Rata kiri" @click="exec('justifyLeft')">
                                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M4 6h16M4 12h10M4 18h13"/></svg>
                                </button>
                                <button type="button" class="lp-rte__btn" :class="{ 'lp-rte__btn--active': formatState.justifyCenter }" title="Rata tengah" @click="exec('justifyCenter')">
                                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M4 6h16M8 12h8M6 18h12"/></svg>
                                </button>
                                <button type="button" class="lp-rte__btn" :class="{ 'lp-rte__btn--active': formatState.justifyRight }" title="Rata kanan" @click="exec('justifyRight')">
                                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M4 6h16M10 12h10M7 18h13"/></svg>
                                </button>
                            </div>

                            <span class="lp-rte__divider"></span>

                            <div class="lp-rte__group">
                                <button type="button" class="lp-rte__btn" :class="{ 'lp-rte__btn--active': formatState.insertUnorderedList }" title="Daftar bertitik" @click="exec('insertUnorderedList')">
                                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="4.5" cy="6" r="1"/><circle cx="4.5" cy="12" r="1"/><circle cx="4.5" cy="18" r="1"/><path d="M9 6h11M9 12h11M9 18h11"/></svg>
                                </button>
                                <button type="button" class="lp-rte__btn" :class="{ 'lp-rte__btn--active': formatState.insertOrderedList }" title="Daftar bernomor" @click="exec('insertOrderedList')">
                                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M9 6h11M9 12h11M9 18h11"/><path d="M4 6h1v3M4 12.5h2M4 12.5c0-.5.5-1 1-1s1 .5 1 1-1.5 1.5-2 2.5h2"/></svg>
                                </button>
                            </div>

                            <span class="lp-rte__divider"></span>

                            <div class="lp-rte__group" style="position:relative;">
                                <button type="button" class="lp-rte__btn" title="Sisipkan tautan" @click="showLinkPopover = !showLinkPopover" @click.outside="showLinkPopover = false">
                                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M10 13a5 5 0 0 0 7.07 0l2-2a5 5 0 0 0-7.07-7.07L10.5 5.5"/><path d="M14 11a5 5 0 0 0-7.07 0l-2 2a5 5 0 0 0 7.07 7.07l1.5-1.5"/></svg>
                                </button>
                                <div class="lp-rte__popover lp-rte__popover--link" x-show="showLinkPopover" x-cloak x-transition.opacity.duration.120ms @keydown.escape.window="showLinkPopover = false">
                                    <label class="lp-rte__popover-label">Alamat tautan (URL)</label>
                                    <div class="lp-rte__link-row">
                                        <input type="text" class="lp-form__input" x-model="linkUrl" placeholder="https://..." @keydown.enter.prevent="applyLink()" x-ref="linkInput">
                                        <button type="button" class="lp-btn lp-btn--primary" style="padding:8px 14px;" @click="applyLink()">Terapkan</button>
                                    </div>
                                </div>
                            </div>

                            <span class="lp-rte__divider"></span>

                            <div class="lp-rte__group">
                                <button type="button" class="lp-rte__btn" title="Bersihkan format" @click="clearFormat()">
                                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M20 20H8l-6-6 10-10 8 8-8 8"/><path d="m5 12 6 6"/></svg>
                                </button>
                            </div>
                        </div>

                        {{-- Area edit --}}
                        <div class="lp-rte__editor"
                             contenteditable="true"
                             x-ref="editor"
                             data-placeholder="Tulis isi informasi di sini..."
                             @input="onInput()"
                             @keydown.ctrl.b.prevent="exec('bold')"
                             @keydown.ctrl.i.prevent="exec('italic')"
                             @keydown.ctrl.u.prevent="exec('underline')"
                             @paste="onPaste($event)"></div>

                        <div class="lp-rte__footer">
                            <span x-text="wordCount + ' kata'"></span>
                            <span aria-hidden="true">·</span>
                            <span x-text="charCount + ' karakter'"></span>
                        </div>
                    </div>

                    {{-- Field asli yang benar-benar dikirim ke server --}}
                    <textarea name="konten" id="konten" x-ref="hiddenKonten" class="lp-hidden" required>{{ old('konten', $informasi->konten ?? '') }}</textarea>
                    <div style="padding: 0 20px 20px;">
                        <span class="lp-form__info">Gunakan toolbar di atas seperti Word: judul, tebal/miring, daftar, warna, dan tautan.</span>
                        @error('konten') <span class="lp-form__error">{{ $message }}</span> @enderror
                    </div>
                </div>
            </div>

            <div class="lp-editor__sidebar">
                <div class="lp-editor__panel">
                    <h4 class="lp-editor__panel-title">Pengaturan</h4>

                    <div class="lp-form__group">
                        <label for="kategori" class="lp-form__label">Kategori</label>
                        <select name="kategori" id="kategori" class="lp-form__input">
                            <option value="">-- Pilih Kategori --</option>
                            @foreach (\App\Models\Information::KATEGORI_LIST as $value => $label)
                                <option value="{{ $value }}" @selected(old('kategori', $informasi->kategori ?? '') === $value)>{{ $label }}</option>
                            @endforeach
                        </select>
                        @error('kategori') <span class="lp-form__error">{{ $message }}</span> @enderror
                    </div>

                    <div class="lp-form__group" style="margin-top: 18px;">
                        <label class="lp-form__label">Status</label>
                        <div class="lp-segmented">
                            @foreach (\App\Models\Information::STATUS_LIST as $value => $label)
                                <label class="lp-segmented__option">
                                    <input type="radio" name="status" value="{{ $value }}"
                                           @checked(old('status', $informasi->status ?? 'draft') === $value)>
                                    <span>{{ $label }}</span>
                                </label>
                            @endforeach
                        </div>
                        @error('status') <span class="lp-form__error">{{ $message }}</span> @enderror
                    </div>

                    <div class="lp-form__group" style="margin-top: 18px;">
                        <label for="urutan" class="lp-form__label">Urutan Tampil</label>
                        <div class="lp-stepper">
                            <button type="button" class="lp-stepper__btn" @click="$refs.urutan.stepDown(); $refs.urutan.dispatchEvent(new Event('input'))" aria-label="Kurangi urutan">−</button>
                            <input type="number" name="urutan" id="urutan" x-ref="urutan" class="lp-form__input lp-stepper__input" min="0"
                                   value="{{ old('urutan', $informasi->urutan ?? 0) }}">
                            <button type="button" class="lp-stepper__btn" @click="$refs.urutan.stepUp(); $refs.urutan.dispatchEvent(new Event('input'))" aria-label="Tambah urutan">+</button>
                        </div>
                        <span class="lp-form__info">Angka lebih kecil tampil lebih dulu di footer.</span>
                        @error('urutan') <span class="lp-form__error">{{ $message }}</span> @enderror
                    </div>

                    @if ($informasi)
                        <div class="lp-form__group" style="margin-top: 18px;">
                            <label class="lp-form__label">URL Publik</label>
                            <div class="lp-url-box">
                                <span class="lp-url-box__text">/informasi/{{ $informasi->slug }}</span>
                                <button type="button" class="lp-url-box__copy" title="Salin URL"
                                        @click="copyUrl('{{ url('/informasi/'.$informasi->slug) }}')">
                                    <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="9" y="9" width="13" height="13" rx="2"/><path d="M5 15H4a2 2 0 0 1-2-2V4a2 2 0 0 1 2-2h9a2 2 0 0 1 2 2v1"/></svg>
                                </button>
                            </div>
                        </div>
                    @endif
                </div>

                <button type="submit" class="lp-btn lp-btn--primary lp-form__submit">
                    {{ $informasi ? 'Perbarui Informasi' : 'Simpan Informasi' }}
                </button>
            </div>
        </div>
    </form>
</div>
@endsection

@push('scripts')
<script>
    function informasiForm() {
        return {
            showLinkPopover: false,
            showTextColorPopover: false,
            showHighlightPopover: false,
            linkUrl: '',
            wordCount: 0,
            charCount: 0,
            formatState: {},

            // PENTING: konten HTML awal di-inject sekali lewat x-init, BUKAN lewat
            // x-html/x-model pada elemen contenteditable — kalau dipasang via
            // binding reaktif Alpine, setiap kali Alpine re-render, innerHTML akan
            // ditimpa ulang dan posisi kursor pengguna ikut lompat ke awal
            // (masalah yang sama seperti di editor berita).
            init() {
                this.$refs.editor.innerHTML = @js(old('konten', $informasi->konten ?? ''));
                this.syncCounts();
                this.$nextTick(() => this.updateFormatState());
                document.addEventListener('selectionchange', () => {
                    if (document.activeElement === this.$refs.editor) this.updateFormatState();
                });
            },

            exec(cmd, value = null) {
                this.$refs.editor.focus();
                document.execCommand(cmd, false, value);
                this.onInput();
            },

            formatBlock(tag) {
                if (!tag) return;
                this.exec('formatBlock', tag);
            },

            onInput() {
                this.$refs.hiddenKonten.value = this.$refs.editor.innerHTML;
                this.syncCounts();
                this.updateFormatState();
            },

            onPaste(e) {
                // Tempel sebagai teks polos supaya tidak membawa style asing dari Word/situs lain
                e.preventDefault();
                const text = (e.clipboardData || window.clipboardData).getData('text/plain');
                document.execCommand('insertText', false, text);
            },

            syncCounts() {
                const text = this.$refs.editor.innerText.trim();
                this.charCount = text.length;
                this.wordCount = text === '' ? 0 : text.split(/\s+/).length;
            },

            updateFormatState() {
                try {
                    this.formatState = {
                        bold: document.queryCommandState('bold'),
                        italic: document.queryCommandState('italic'),
                        underline: document.queryCommandState('underline'),
                        strikeThrough: document.queryCommandState('strikeThrough'),
                        insertUnorderedList: document.queryCommandState('insertUnorderedList'),
                        insertOrderedList: document.queryCommandState('insertOrderedList'),
                        justifyLeft: document.queryCommandState('justifyLeft'),
                        justifyCenter: document.queryCommandState('justifyCenter'),
                        justifyRight: document.queryCommandState('justifyRight'),
                    };
                } catch (e) { /* no-op di browser lama */ }
            },

            applyLink() {
                if (!this.linkUrl) { this.showLinkPopover = false; return; }
                let url = this.linkUrl.trim();
                if (!/^https?:\/\//i.test(url) && !url.startsWith('/') && !url.startsWith('#')) {
                    url = 'https://' + url;
                }
                this.exec('createLink', url);
                this.$refs.editor.querySelectorAll('a:not([target])').forEach(a => {
                    a.setAttribute('target', '_blank');
                    a.setAttribute('rel', 'noopener noreferrer');
                });
                this.linkUrl = '';
                this.showLinkPopover = false;
                this.onInput();
            },

            applyColor(color) {
                this.exec('foreColor', color);
                this.showTextColorPopover = false;
            },

            applyHighlight(color) {
                const cmd = document.queryCommandSupported('hiliteColor') ? 'hiliteColor' : 'backColor';
                this.exec(cmd, color);
                this.showHighlightPopover = false;
            },

            clearFormat() {
                this.exec('removeFormat');
                document.execCommand('unlink');
                this.onInput();
            },

            copyUrl(url) {
                navigator.clipboard.writeText(url).then(() => {
                    if (window.showToast) window.showToast('success', 'URL disalin!');
                });
            },

            onSubmit(e) {
                this.$refs.hiddenKonten.value = this.$refs.editor.innerHTML;
                const plain = this.$refs.editor.innerText.trim();
                if (plain === '') {
                    e.preventDefault();
                    if (window.showToast) window.showToast('error', 'Konten tidak boleh kosong.');
                    this.$refs.editor.focus();
                }
            },
        };
    }
</script>
@endpush