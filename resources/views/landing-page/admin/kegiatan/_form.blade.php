@php
    // Opsi jenis spesialisasi — konstanta, belum jadi tabel referensi (lihat catatan desain sebelumnya)
    $jenisSpesialisasiOptions = [
        'pkb' => 'PKB',
        'public_speaking' => 'Public speaking',
        'negosiasi' => 'Negosiasi',
        'advokasi' => 'Advokasi',
        'k3' => 'K3',
        'lainnya' => 'Lainnya',
    ];

    // Data awal untuk Alpine — dari old() kalau validasi gagal, atau dari $kegiatan kalau mode edit
    $initCustomFields = old('custom_fields', isset($kegiatan)
        ? $kegiatan->customFields->map(fn ($f) => [
            'label' => $f->label,
            'tipe' => $f->tipe,
            'opsi' => $f->opsi ? implode(', ', $f->opsi) : '',
            'wajib' => (bool) $f->wajib,
        ])->toArray()
        : []
    );
@endphp

@push('page-styles')
<link href="https://cdn.jsdelivr.net/npm/quill@2.0.3/dist/quill.snow.css" rel="stylesheet">
<link href="https://cdn.jsdelivr.net/npm/quill-table-better@1/dist/quill-table-better.css" rel="stylesheet">
<link href="https://cdn.jsdelivr.net/npm/@enzedonline/quill-blot-formatter2/dist/css/quill-blot-formatter2.css" rel="stylesheet">
<link href="https://cdnjs.cloudflare.com/ajax/libs/highlight.js/11.9.0/styles/github.min.css" rel="stylesheet">
@vite('resources/css/admin/kegiatan/form.css')
@endpush

<form method="POST"
      enctype="multipart/form-data"
      class="kf-form-wrap"
      action="{{ isset($kegiatan) ? route('admin.kegiatan.update', $kegiatan->slug) : route('admin.kegiatan.store') }}"
      x-data="kegiatanForm({
          jenis: '{{ old('jenis', $kegiatan->jenis ?? '') }}',
          tingkat: '{{ old('tingkat_kategori', $kegiatan->tingkat_kategori ?? '') }}',
          spesialis: '{{ old('spesialis', in_array($kegiatan->tingkat_keterangan ?? null, array_keys($jenisSpesialisasiOptions)) ? $kegiatan->tingkat_keterangan : '') }}',
          spesialisManual: '{{ old('spesialis_manual', ($kegiatan->tingkat_kategori ?? null) === 'spesialis' && !in_array($kegiatan->tingkat_keterangan ?? null, array_keys($jenisSpesialisasiOptions)) ? $kegiatan->tingkat_keterangan : '') }}',
          tingkatManual: '{{ old('tingkat_manual', ($kegiatan->tingkat_kategori ?? null) === 'lainnya' ? $kegiatan->tingkat_keterangan : '') }}',
          customFields: {{ Illuminate\Support\Js::from($initCustomFields) }}
      })"
      @submit="$refs.deskripsiTextarea.value = (quillDeskripsi ? quillDeskripsi.root.innerHTML : '')">
    @csrf
    @if (isset($kegiatan))
        @method('PUT')
    @endif

    {{-- ===== INFORMASI UMUM ===== --}}
    <div class="lp-form-card">
        <h2 style="color:var(--text-primary); font-size:14px; font-weight:700; margin-bottom:16px; padding-bottom:10px; border-bottom:1px solid var(--border-color);">Informasi Umum</h2>

        <div style="margin-bottom:16px;">
            <label class="lp-form__info" style="display:block; font-weight:600; color:var(--text-primary); margin-bottom:6px;">Nama kegiatan</label>
            {{-- FIX: kolomnya `judul`, bukan `nama` --}}
            <input type="text" name="nama" class="lp-form__input" value="{{ old('nama', $kegiatan->judul ?? '') }}" placeholder="mis. Pelatihan Dasar Organisasi Angkatan 12">
            @error('nama') <span class="lp-form__error">{{ $message }}</span> @enderror
        </div>

        <div class="kf-info-grid">
            <div>
                <label class="lp-form__info" style="display:block; font-weight:600; color:var(--text-primary); margin-bottom:6px;">Tanggal</label>
                {{-- FIX: kolomnya `tanggal_mulai`, bukan `tanggal` --}}
                <input type="date" name="tanggal" class="lp-form__input" value="{{ old('tanggal', isset($kegiatan) ? $kegiatan->tanggal_mulai?->format('Y-m-d') : '') }}">
                @error('tanggal') <span class="lp-form__error">{{ $message }}</span> @enderror
            </div>
            <div>
                <label class="lp-form__info" style="display:block; font-weight:600; color:var(--text-primary); margin-bottom:6px;">Tanggal selesai</label>
                <input type="date" name="tanggal_selesai" class="lp-form__input"
                    value="{{ old('tanggal_selesai', isset($kegiatan) ? $kegiatan->tanggal_selesai?->format('Y-m-d') : '') }}">
                <p class="lp-editor-hint">Kosongkan jika kegiatan berlangsung satu hari saja.</p>
                @error('tanggal_selesai') <span class="lp-form__error">{{ $message }}</span> @enderror
            </div>
            <div>
                <label class="lp-form__info" style="display:block; font-weight:600; color:var(--text-primary); margin-bottom:6px;">Batas pendaftaran</label>
                <input type="date" name="batas_pendaftaran" class="lp-form__input"
                    value="{{ old('batas_pendaftaran', isset($kegiatan) ? $kegiatan->batas_pendaftaran?->format('Y-m-d') : '') }}">
                <p class="lp-editor-hint">Setelah tanggal ini, pendaftaran otomatis ditutup. Kosongkan kalau pendaftaran tetap dibuka sampai kegiatannya sendiri lewat.</p>
                @error('batas_pendaftaran') <span class="lp-form__error">{{ $message }}</span> @enderror
            </div>
            <div>
                <label class="lp-form__info" style="display:block; font-weight:600; color:var(--text-primary); margin-bottom:6px;">Lokasi</label>
                <input type="text" name="lokasi" class="lp-form__input" value="{{ old('lokasi', $kegiatan->lokasi ?? '') }}" placeholder="mis. Aula Serbaguna PUK OMRON">
            </div>
            <div>
                <label class="lp-form__info" style="display:block; font-weight:600; color:var(--text-primary); margin-bottom:6px;">Kuota peserta</label>
                <input type="number" min="0" name="kuota" class="lp-form__input" value="{{ old('kuota', $kegiatan->kuota ?? '') }}" placeholder="mis. 40">
                <p class="lp-editor-hint">Kosongkan jika kegiatan ini tidak dibatasi kuota.</p>
            </div>
        </div>

        <div>
            <label class="lp-form__info" style="display:block; font-weight:600; color:var(--text-primary); margin-bottom:6px;">Deskripsi</label>

            <div class="kf-quill-wrap"
                :class="{ 'kf-quill-fullscreen': isFullscreen }"
                @keydown.escape.window="if (isFullscreen) toggleFullscreen()"
                x-init="quillDeskripsi = initDeskripsiQuill($refs.quillDeskripsiRoot, @js(old('deskripsi', $kegiatan->deskripsi ?? '')))">
                <div x-ref="quillDeskripsiRoot"></div>
            </div>    

            {{-- Textarea asli disembunyikan, isinya disinkronkan dari Quill tepat sebelum submit --}}
            <textarea name="deskripsi" x-ref="deskripsiTextarea" class="lp-hidden">{{ old('deskripsi', $kegiatan->deskripsi ?? '') }}</textarea>

            <p class="lp-editor-hint">Detail kegiatan ini akan tampil di halaman publik.</p>
            @error('deskripsi') <span class="lp-form__error">{{ $message }}</span> @enderror
        </div>
        <div style="margin-top:16px;">
            <label class="lp-form__info" style="display:block; font-weight:600; color:var(--text-primary); margin-bottom:6px;">Thumbnail</label>
            <label class="lp-dropzone" @click="$refs.thumbnailInput.click()">
                <input type="file" name="thumbnail" accept="image/*" x-ref="thumbnailInput" class="lp-hidden"
                    @change="thumbnailPreview = $event.target.files[0] ? URL.createObjectURL($event.target.files[0]) : null">

                <template x-if="thumbnailPreview">
                    <img :src="thumbnailPreview" class="lp-dropzone__preview">
                </template>

                <template x-if="!thumbnailPreview">
                    <div class="lp-dropzone__empty">
                        @if (isset($kegiatan) && $kegiatan->thumbnail)
                            <img src="{{ asset('storage/' . $kegiatan->thumbnail) }}" class="lp-dropzone__existing">
                            <span>Klik untuk ganti thumbnail</span>
                        @else
                            <span>Klik untuk unggah thumbnail</span>
                        @endif
                    </div>
                </template>
            </label>
            <p class="lp-editor-hint">Format JPG/PNG, maks. 2MB.</p>
            @error('thumbnail') <span class="lp-form__error">{{ $message }}</span> @enderror
        </div>
    </div>

    {{-- ===== JENIS & TINGKAT ===== --}}
    <div class="lp-form-card">
        <h2 style="color: var(--text-primary); font-size:14px; font-weight:700; margin-bottom:16px; padding-bottom:10px; border-bottom:1px solid var(--border-color);">Jenis &amp; Tingkat</h2>

        <div style="margin-bottom:16px;">
            <label class="lp-form__info" style="display:block; font-weight:600; color:var(--text-primary); margin-bottom:6px;">Jenis kegiatan</label>
            <select name="jenis" class="lp-form__input" x-model="jenis" @change="onJenisChange()">
                <option value="">— Pilih jenis —</option>
                <option value="pendidikan">Pendidikan</option>
                <option value="seminar">Seminar</option>
                <option value="aksi">Aksi</option>
                <option value="lainnya">Lainnya</option>
            </select>
            @error('jenis') <span class="lp-form__error">{{ $message }}</span> @enderror
        </div>

        <div x-show="jenis === 'pendidikan'" x-cloak style="padding:14px 14px 2px 16px; margin-bottom:4px; background:var(--bg-surface-alt); border-left:3px solid var(--accent); border-radius:0 var(--radius-sm) var(--radius-sm) 0;">
            <div style="margin-bottom:16px;">
                <label class="lp-form__info" style="display:block; font-weight:600; color:var(--text-primary); margin-bottom:6px;">Tingkat pendidikan</label>
                <select name="tingkat_kategori" class="lp-form__input" x-model="tingkat" @change="onTingkatChange()">
                    <option value="">— Pilih tingkat —</option>
                    <option value="dasar">Dasar</option>
                    <option value="lanjutan">Lanjutan</option>
                    <option value="spesialis">Spesialis</option>
                    <option value="lainnya">Lainnya</option>
                </select>
                @error('tingkat_kategori') <span class="lp-form__error">{{ $message }}</span> @enderror
            </div>

            <div x-show="tingkat === 'spesialis'" x-cloak style="margin-bottom:16px;">
                <label class="lp-form__info" style="display:block; font-weight:600; color:var(--text-primary); margin-bottom:6px;">Jenis spesialisasi</label>
                <select name="spesialis" class="lp-form__input" x-model="spesialis">
                    <option value="">— Pilih jenis spesialisasi —</option>
                    @foreach ($jenisSpesialisasiOptions as $val => $label)
                        <option value="{{ $val }}">{{ $label }}</option>
                    @endforeach
                </select>

                <div x-show="spesialis === 'lainnya'" x-cloak style="margin-top:12px;">
                    <label class="lp-form__info" style="display:block; font-weight:600; color:var(--text-primary); margin-bottom:6px;">Nama spesialisasi (manual)</label>
                    <input type="text" name="spesialis_manual" class="lp-form__input" x-model="spesialisManual" placeholder="mis. Spesialis Mediasi">
                </div>
            </div>

            <div x-show="tingkat === 'lainnya'" x-cloak style="margin-bottom:16px;">
                <label class="lp-form__info" style="display:block; font-weight:600; color:var(--text-primary); margin-bottom:6px;">Nama tingkat (manual)</label>
                <input type="text" name="tingkat_manual" class="lp-form__input" x-model="tingkatManual" placeholder="mis. Tingkat Penyegaran">
            </div>
        </div>
    </div>

    {{-- ===== FIELD PENDAFTARAN CUSTOM ===== --}}
    <div class="lp-form-card">
        <h2 style="color: var(--text-primary); font-size:14px; font-weight:700; margin-bottom:4px; padding-bottom:10px; border-bottom:1px solid var(--border-color);">Field Pendaftaran Custom</h2>
        <p class="lp-editor-hint" style="margin-bottom:16px;">Field tambahan ini dirender di form pendaftaran publik, di luar field standar (nama/NIK/departemen/email).</p>

        <template x-if="customFields.length === 0">
            <p class="lp-form__info" style="font-style:italic; margin-bottom:14px;">Belum ada field custom. Klik "+ Tambah field" untuk menambahkan.</p>
        </template>

        <template x-for="(field, index) in customFields" :key="index">
            <div class="kf-field-card">
                <div class="kf-field-card__head">
                    <span style="font-size:12px; font-weight:700; color:var(--accent);" x-text="'FIELD ' + (index + 1)"></span>
                    <button type="button" class="lp-admin-danger" style="background:none; border:none; font-size:12.5px; font-weight:600; cursor:pointer;" @click="customFields.splice(index, 1)">Hapus</button>
                </div>

                <div style="margin-bottom:12px;">
                    <label class="lp-form__info" style="display:block; font-weight:600; color:var(--text-primary); margin-bottom:6px;">Label field</label>
                    <input type="text" :name="'custom_fields[' + index + '][label]'" class="lp-form__input" x-model="field.label" placeholder="mis. Ukuran kaos">
                </div>

                <div style="margin-bottom:12px;">
                    <label class="lp-form__info" style="display:block; font-weight:600; color:var(--text-primary); margin-bottom:6px;">Tipe field</label>
                    <select :name="'custom_fields[' + index + '][tipe]'" class="lp-form__input" x-model="field.tipe">
                        <option value="text">Text</option>
                        <option value="select">Select</option>
                        <option value="number">Number</option>
                        <option value="checkbox">Checkbox</option>
                    </select>
                </div>

                <div x-show="field.tipe === 'select'" x-cloak style="margin-bottom:12px;">
                    <label class="lp-form__info" style="display:block; font-weight:600; color:var(--text-primary); margin-bottom:6px;">Pilihan (pisahkan dengan koma)</label>
                    <input type="text" :name="'custom_fields[' + index + '][opsi]'" class="lp-form__input" x-model="field.opsi" placeholder="mis. S, M, L, XL">
                </div>

                {{-- Toggle switch (ganti checkbox polos) --}}
                <label class="kf-toggle">
                    <input type="checkbox" :name="'custom_fields[' + index + '][wajib]'" value="1" x-model="field.wajib">
                    <span class="kf-toggle-track"><span class="kf-toggle-thumb"></span></span>
                    <span class="kf-toggle-label">Wajib diisi</span>
                </label>
            </div>
        </template>

        <button type="button" class="lp-btn lp-btn--ghost" @click="customFields.push({ label: '', tipe: 'text', opsi: '', wajib: false })">+ Tambah field</button>
    </div>

    {{-- ===== AKSI ===== --}}
    <div class="kf-form-actions">
        <button type="submit" class="lp-btn lp-btn--primary">{{ isset($kegiatan) ? 'Simpan perubahan' : 'Simpan & Buka Pendaftaran' }}</button>
        <a href="{{ route('admin.kegiatan.index') }}" class="lp-btn lp-btn--ghost">Batal</a>
    </div>
</form>

@push('scripts')
{{-- highlight.js HARUS dimuat sebelum quill.js, alasan sama seperti di berita/form --}}
<script src="https://cdnjs.cloudflare.com/ajax/libs/highlight.js/11.9.0/highlight.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/quill@2.0.3/dist/quill.js"></script>
<script src="https://cdn.jsdelivr.net/npm/quill-table-better@1/dist/quill-table-better.js"></script>
<script src="https://cdn.jsdelivr.net/npm/@enzedonline/quill-blot-formatter2@3.0/dist/index.js"></script>
<script src="https://cdn.jsdelivr.net/npm/quill-magic-url@4.1.3/dist/index.min.js"></script>
<script>
    // ===== Registrasi module Quill secara global (sama seperti berita/form) =====
    if (window.Quill && window.QuillTableBetter) {
        Quill.register({ 'modules/table-better': QuillTableBetter }, true);
    }
    if (window.Quill && window.QuillBlotFormatter2) {
        const BlotFormatter2 = window.QuillBlotFormatter2.default || window.QuillBlotFormatter2;
        Quill.register('modules/blotFormatter2', BlotFormatter2, true);
    }
    if (window.Quill && window.MagicUrl) {
        Quill.register('modules/magicUrl', window.MagicUrl, true);
    }
    if (window.Quill) {
        const Font = Quill.import('formats/font');
        Font.whitelist = ['serif', 'monospace'];
        Quill.register(Font, true);
    }
    if (window.Quill && !Quill.imports['formats/divider']) {
        const BlockEmbed = Quill.import('blots/block/embed');
        class DividerBlot extends BlockEmbed {}
        DividerBlot.blotName = 'divider';
        DividerBlot.tagName = 'hr';
        Quill.register(DividerBlot, true);
    }

    function kegiatanForm(init) {
        return {
            jenis: init.jenis || '',
            tingkat: init.tingkat || '',
            spesialis: init.spesialis || '',
            spesialisManual: init.spesialisManual || '',
            tingkatManual: init.tingkatManual || '',
            customFields: init.customFields && init.customFields.length ? init.customFields : [],
            quillDeskripsi: null,
            isFullscreen: false,
            thumbnailPreview: null,

            onJenisChange() {
                if (this.jenis !== 'pendidikan') {
                    this.tingkat = '';
                    this.spesialis = '';
                    this.spesialisManual = '';
                    this.tingkatManual = '';
                }
            },
            onTingkatChange() {
                if (this.tingkat !== 'spesialis') {
                    this.spesialis = '';
                    this.spesialisManual = '';
                }
                if (this.tingkat !== 'lainnya') {
                    this.tingkatManual = '';
                }
            },

            toggleFullscreen() {
                this.isFullscreen = !this.isFullscreen;
                if (this.quillDeskripsi) {
                    setTimeout(() => this.quillDeskripsi.focus(), 50);
                }
            },

            // Toolbar lengkap setara berita/form — TANPA tombol/handler 'image'
            initDeskripsiQuill(el, initialHtml) {
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
                    ['link', 'video'], // 'image' sengaja tidak disertakan
                    ['table-better'],
                    ['hr', 'fullscreen'],
                    ['clean'],
                ];

                const modules = {
                    toolbar: {
                        container: toolbarOptions,
                        handlers: {
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
                    history: { delay: 1000, maxStack: 500, userOnly: true },
                };

                if (window.QuillTableBetter) {
                    modules.table = false;
                    modules['table-better'] = {
                        language: 'en_US',
                        menus: ['column', 'row', 'merge', 'table', 'cell', 'wrap', 'copy', 'delete'],
                        toolbarTable: true,
                    };
                    Object.assign(modules.keyboard.bindings, window.QuillTableBetter.keyboardBindings);
                }

                // Cuma dipakai buat resize/reposisi video (iframe) — tidak ada image config
                if (window.QuillBlotFormatter2) {
                    modules.blotFormatter2 = {};
                }

                if (window.MagicUrl) {
                    modules.magicUrl = true;
                }

                if (window.hljs) {
                    modules.syntax = {
                        hljs: window.hljs,
                        highlight: (text) => window.hljs.highlightAuto(text).value,
                    };
                } else {
                    modules.syntax = false;
                }

                const quill = new Quill(el, {
                    theme: 'snow',
                    placeholder: 'Detail kegiatan yang akan tampil di halaman publik...',
                    modules,
                });

                if (initialHtml) {
                    quill.root.innerHTML = initialHtml;
                }

                return quill;
            }
        }
    }
</script>
@endpush