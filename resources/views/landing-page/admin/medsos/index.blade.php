@extends('landing-page.admin.layouts.app')

@push('page-styles')
    @vite('resources/css/admin/admin-medsos.css')
    <style>
        [x-cloak] { display: none !important; }
    </style>
@endpush

@section('title', 'Kelola Media Sosial')

@section('content')
<div class="lp-admin-section" x-data="medsosModal" x-init="init()">
    <div class="lp-admin-section__header">
        <div class="lp-admin-section__heading">
            <h2>Kelola Media Sosial</h2>
            <p class="lp-admin-section__subtitle">Atur akun media sosial yang tampil di landing page.</p>
        </div>
        <button type="button" class="lp-btn lp-btn--primary" @click="openModal()">
            <span class="lp-btn__icon" aria-hidden="true">+</span> Tambah Media Sosial
        </button>
    </div>

    @if (session('success'))
        <div class="lp-admin-alert lp-admin-alert--success">
            {{ session('success') }}
        </div>
    @endif

    {{--
        KENAPA IKON SEBELUMNYA CUMA KOTAK POLOS TANPA GAMBAR:
        Class seperti "fab fa-facebook-f" itu cuma nge-set warna lewat CSS
        kita sendiri; glyph ikonnya sendiri datang dari font/CSS Font
        Awesome yang ternyata TIDAK ter-load di halaman ini (makanya tombol
        Edit/Hapus juga polos tanpa ikon). Daripada bergantung ke library
        eksternal yang setup-nya belum tentu ada di semua halaman, semua
        ikon di file ini sekarang SVG inline (logo platform + ikon
        pensil/tempat sampah/share) — dijamin selalu tampil, tanpa perlu
        <link> atau @vite tambahan.

        SATU SUMBER DATA UNTUK SEMUA PLATFORM.
        $platforms dipakai bareng untuk: dropdown Platform, dropdown Ikon,
        kolom Platform di tabel, dan auto-isi ikon di modal (lewat JS).
        Value platform SELALU lowercase, konsisten dengan yang tersimpan
        di database. Key 'icon' menunjuk ke $icons di bawah.
    --}}
    @php
        $icons = [
            'facebook'  => ['viewBox' => '0 0 320 512', 'path' => 'M279.14 288l14.22-92.66h-88.91v-60.13c0-25.35 12.42-50.06 52.24-50.06h40.42V6.26S260.43 0 225.36 0c-73.22 0-121.08 44.38-121.08 124.72v70.62H22.89V288h81.39v224h100.17V288z'],
            'twitter'   => ['viewBox' => '0 0 512 512', 'path' => 'M389.2 48h70.6L305.6 224.2 487 464H345L233.7 318.6 106.5 464H35.8L200.7 275.5 26.8 48H172.4L272.9 180.9 389.2 48zM364.4 421.8h39.1L151.1 88h-42L364.4 421.8z'],
            'instagram' => ['viewBox' => '0 0 448 512', 'path' => 'M224.1 141c-63.6 0-114.9 51.3-114.9 114.9s51.3 114.9 114.9 114.9S339 319.5 339 255.9 287.7 141 224.1 141zm0 189.6c-41.1 0-74.7-33.5-74.7-74.7s33.5-74.7 74.7-74.7 74.7 33.5 74.7 74.7-33.6 74.7-74.7 74.7zm146.4-194.3c0 14.9-12 26.8-26.8 26.8-14.9 0-26.8-12-26.8-26.8s12-26.8 26.8-26.8 26.8 12 26.8 26.8zm76.1 27.2c-1.7-35.9-9.9-67.7-36.2-93.9-26.2-26.2-58-34.4-93.9-36.2-37-2.1-147.9-2.1-184.9 0-35.8 1.7-67.6 9.9-93.9 36.1s-34.4 58-36.2 93.9c-2.1 37-2.1 147.9 0 184.9 1.7 35.9 9.9 67.7 36.2 93.9s58 34.4 93.9 36.2c37 2.1 147.9 2.1 184.9 0 35.9-1.7 67.7-9.9 93.9-36.2 26.2-26.2 34.4-58 36.2-93.9 2.1-37 2.1-147.8 0-184.8zM398.8 388c-7.8 19.6-22.9 34.7-42.6 42.6-29.5 11.7-99.5 9-132.1 9s-102.7 2.6-132.1-9c-19.6-7.8-34.7-22.9-42.6-42.6-11.7-29.5-9-99.5-9-132.1s-2.6-102.7 9-132.1c7.8-19.6 22.9-34.7 42.6-42.6 29.5-11.7 99.5-9 132.1-9s102.7-2.6 132.1 9c19.6 7.8 34.7 22.9 42.6 42.6 11.7 29.5 9 99.5 9 132.1s2.7 102.7-9 132.1z'],
            'youtube'   => ['viewBox' => '0 0 576 512', 'path' => 'M549.7 124.1c-6.3-23.7-24.8-42.3-48.3-48.6C458.8 64 288 64 288 64S117.2 64 74.6 75.5c-23.5 6.3-42 24.9-48.3 48.6-11.4 42.9-11.4 132.3-11.4 132.3s0 89.4 11.4 132.3c6.3 23.7 24.8 41.5 48.3 47.8C117.2 448 288 448 288 448s170.8 0 213.4-11.5c23.5-6.3 42-24.2 48.3-47.8 11.4-42.9 11.4-132.3 11.4-132.3s0-89.4-11.4-132.3zM232 337.6V174.4L361 256l-129 81.6z'],
            'tiktok'    => ['viewBox' => '0 0 448 512', 'path' => 'M448 209.9a210.1 210.1 0 0 1-122.8-39.3V349.4A162.6 162.6 0 1 1 185 188.3v88.4a74.6 74.6 0 1 0 52.2 71.2V0h87a121.2 121.2 0 0 0 1.9 22.2 122.2 122.2 0 0 0 53.9 80.2 121.4 121.4 0 0 0 67.9 20.6z'],
            'telegram'  => ['viewBox' => '0 0 496 512', 'path' => 'M248 8C111 8 0 119 0 256s111 248 248 248 248-111 248-248S385 8 248 8zM362 176.7c-3.7 39.2-19.9 134.4-28.1 178.3-3.5 18.6-10.3 24.8-16.9 25.4-14.4 1.3-25.3-9.5-39.3-18.7-21.8-14.3-34.2-23.2-55.3-37.2-24.5-16.1-8.6-25 5.3-39.5 3.7-3.8 67.1-61.5 68.3-66.7 .2-.7 .3-3.1-1.2-4.4s-3.6-.8-5.1-.5q-3.3 .7-104.6 69.1-14.8 10.2-26.9 9.9c-8.9-.2-25.9-5-38.6-9.1-15.5-5-27.9-7.7-26.8-16.3q.8-6.7 18.5-13.7 108.4-47.2 144.6-62.3c68.9-28.6 83.2-33.6 92.5-33.8 2.1 0 6.6 .5 9.6 2.9a10.5 10.5 0 0 1 3.5 6.7A43.8 43.8 0 0 1 362 176.7z'],
            'whatsapp'  => ['viewBox' => '0 0 448 512', 'path' => 'M380.9 97.1C339 55.1 283.2 32 223.9 32c-122.4 0-222 99.6-222 222 0 39.1 10.2 77.3 29.6 111L0 480l117.7-30.9c32.4 17.7 68.9 27 106.1 27h.1c122.3 0 224.1-99.6 224.1-222 0-59.3-25.2-115-67.1-157zm-157 341.6c-33.2 0-65.7-8.9-94-25.7l-6.7-4-69.8 18.3L72 359.2l-4.4-7c-18.5-29.4-28.2-63.3-28.2-98.2 0-101.7 82.8-184.5 184.6-184.5 49.3 0 95.6 19.2 130.4 54.1 34.8 34.9 56.2 81.2 56.1 130.5 0 101.8-84.9 184.6-186.6 184.6zm101.2-138.2c-5.5-2.8-32.8-16.2-37.9-18-5.1-1.9-8.8-2.8-12.5 2.8-3.7 5.6-14.3 18-17.6 21.8-3.2 3.7-6.5 4.2-12 1.4-32.6-16.3-54-29.1-75.5-66-5.7-9.8 5.7-9.1 16.3-30.3 1.8-3.7 .9-6.9-.5-9.7-1.4-2.8-12.5-30.1-17.1-41.2-4.5-10.8-9.1-9.3-12.5-9.5-3.2-.2-6.9-.2-10.6-.2-3.7 0-9.7 1.4-14.8 6.9-5.1 5.6-19.4 19-19.4 46.3 0 27.3 19.9 53.7 22.6 57.4 2.8 3.7 39.1 59.7 94.8 83.8 35.2 15.2 49 16.5 66.6 13.9 10.7-1.6 32.8-13.4 37.4-26.4 4.6-13 4.6-24.1 3.2-26.4-1.3-2.5-5-3.9-10.5-6.6z'],
        ];

        $platforms = [
            'facebook'  => ['label' => 'Facebook',    'color' => '#1877f2', 'icon' => 'facebook'],
            'twitter'   => ['label' => 'Twitter / X', 'color' => '#0f1419', 'icon' => 'twitter'],
            'instagram' => ['label' => 'Instagram',   'color' => '#e4405f', 'icon' => 'instagram'],
            'youtube'   => ['label' => 'YouTube',     'color' => '#ff0000', 'icon' => 'youtube'],
            'tiktok'    => ['label' => 'TikTok',      'color' => '#010101', 'icon' => 'tiktok'],
            'telegram'  => ['label' => 'Telegram',    'color' => '#26a5e4', 'icon' => 'telegram'],
            'whatsapp'  => ['label' => 'WhatsApp',    'color' => '#25d366', 'icon' => 'whatsapp'],
        ];

        // Helper kecil biar nggak nulis <svg viewBox="..."><path d="..."/></svg>
        // berulang-ulang. Kalau key ikon nggak dikenal (mis. data lama yang
        // masih menyimpan string "fab fa-xxx"), jatuh ke ikon default platform.
        $renderIcon = function ($key, $fallbackKey = null) use ($icons) {
            $def = $icons[$key] ?? $icons[$fallbackKey] ?? null;
            if (! $def) {
                return '';
            }
            return '<svg viewBox="' . $def['viewBox'] . '" fill="currentColor" aria-hidden="true"><path d="' . $def['path'] . '"/></svg>';
        };
    @endphp

    <div class="lp-medsos-table">
        <table>
            <thead>
                <tr>
                    <th>Platform</th>
                    <th>URL</th>
                    <th class="lp-medsos-th-aksi">Aksi</th>
                </tr>
            </thead>
            <tbody>
                @forelse ($medias as $media)
                @php
                    $meta = $platforms[$media->platform] ?? null;
                    $color = $meta['color'] ?? 'var(--accent)';
                    $iconKey = $media->icon ?: ($meta['icon'] ?? null);
                    $label = $meta['label'] ?? ucfirst($media->platform);
                @endphp
                <tr>
                    <td data-label="Platform">
                        <span class="lp-medsos-platform">
                            <span class="lp-medsos-icon" style="background:{{ $color }}">
                                {!! $renderIcon($iconKey, $meta['icon'] ?? null) !!}
                            </span>
                            <span class="lp-medsos-platform__name">{{ $label }}</span>
                        </span>
                    </td>
                    <td data-label="URL">
                        <a href="{{ $media->url }}" target="_blank" rel="noopener noreferrer" class="lp-medsos-url" title="{{ $media->url }}">
                            {{ $media->url }}
                        </a>
                    </td>
                    
                    <td data-label="Aksi" class="lp-medsos-td-aksi">
                        <div class="lp-admin-table__actions">
                            <button type="button" class="lp-btn--ghost" @click="openModal({{ $media->id }})">
                                <span class="lp-icon-ui lp-icon-ui--pen" aria-hidden="true">
                                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M16.862 4.487l1.687-1.688a1.875 1.875 0 112.652 2.652L10.582 16.07a4.5 4.5 0 01-1.897 1.13L6 18l.8-2.685a4.5 4.5 0 011.13-1.897l8.932-8.931z" />
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M19.5 13.5v6a2.25 2.25 0 01-2.25 2.25H4.5A2.25 2.25 0 012.25 19.5V6.75A2.25 2.25 0 014.5 4.5h6" />
                                    </svg>
                                </span>
                                Edit
                            </button>
                            <form action="{{ route('admin.medsos.destroy', $media->id) }}" method="POST" id="delete-form-{{ $media->id }}">
                                @csrf
                                @method('DELETE')
                                <button type="button" class="lp-btn--ghost lp-admin-danger" @click="$dispatch('open-confirm', { message: 'Yakin hapus media sosial ini?', formId: 'delete-form-{{ $media->id }}' })">
                                    <span class="lp-icon-ui lp-icon-ui--trash" aria-hidden="true">
                                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8">
                                            <path stroke-linecap="round" stroke-linejoin="round" d="M14.74 9l-.346 9m-4.788 0L9.26 9m9.968-3.21c.342.052.682.107 1.022.166m-1.022-.165L18.16 19.673a2.25 2.25 0 01-2.244 2.077H8.084a2.25 2.25 0 01-2.244-2.077L4.772 5.79m14.456 0a48.108 48.108 0 00-3.478-.397m-12 .562c.34-.059.68-.114 1.022-.165m0 0a48.11 48.11 0 013.478-.397m7.5 0v-.916c0-1.18-.91-2.164-2.09-2.201a51.964 51.964 0 00-3.32 0c-1.18.037-2.09 1.022-2.09 2.201v.916m7.5 0a48.667 48.667 0 00-7.5 0" />
                                        </svg>
                                    </span>
                                    Hapus
                                </button>
                            </form>
                        </div>
                    </td>
                </tr>
                @empty
                <tr class="lp-medsos-empty-row">
                    <td colspan="5">
                        <div class="lp-admin-empty">
                            <span class="lp-icon-ui lp-icon-ui--empty" aria-hidden="true">
                                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M7.217 10.907a2.25 2.25 0 100 2.186m0-2.186c.18.324.283.696.283 1.093s-.103.77-.283 1.093m0-2.186l9.566-5.314m-9.566 7.5l9.566 5.314m0 0a2.25 2.25 0 103.935 2.186 2.25 2.25 0 00-3.935-2.186zm0-12.814a2.25 2.25 0 103.933-2.185 2.25 2.25 0 00-3.933 2.185z" />
                                </svg>
                            </span>
                            <p>Belum ada data media sosial.</p>
                            <button type="button" class="lp-btn lp-btn--primary" @click="openModal()">+ Tambah Media Sosial</button>
                        </div>
                    </td>
                </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    {{-- ===== MODAL TAMBAH / EDIT =====
        PERBAIKAN TRANSISI:
        Sebelumnya pakai shorthand `x-transition.opacity` di kedua elemen
        (overlay & modal). Shorthand ini bisa memberi durasi/easing default
        yang berbeda antar elemen dan, dikombinasikan dengan
        `backdrop-filter: blur(4px)` yang berubah bersamaan opacity, memicu
        bug rendering di Chromium: area blur terlihat "membesar dari salah
        satu sudut" alih-alih fade rata di tempat.

        Sekarang pakai x-transition eksplisit (enter/leave, opacity-only,
        durasi tetap 200ms) supaya animasi hanya opacity, tidak ada
        scale/translate/posisi yang berubah. Modal tetap statis di tengah
        (fixed + translate(-50%,-50%)) di layar besar, dan jadi bottom-sheet
        penuh lebar di layar kecil — hanya opacity-nya yang difade Alpine.
    --}}
    <div class="lp-modal-overlay"
         x-show="open"
         x-transition:enter="lp-modal-fade"
         x-transition:enter-start="lp-modal-fade--out"
         x-transition:enter-end="lp-modal-fade--in"
         x-transition:leave="lp-modal-fade"
         x-transition:leave-start="lp-modal-fade--in"
         x-transition:leave-end="lp-modal-fade--out"
         x-cloak
         @click="closeModal()"></div>
    <div class="lp-modal"
         x-show="open"
         x-transition:enter="lp-modal-fade"
         x-transition:enter-start="lp-modal-fade--out"
         x-transition:enter-end="lp-modal-fade--in"
         x-transition:leave="lp-modal-fade"
         x-transition:leave-start="lp-modal-fade--in"
         x-transition:leave-end="lp-modal-fade--out"
         x-cloak
         role="dialog" aria-modal="true">
        <div class="lp-modal__content">
            <div class="lp-modal__header">
                <h3 x-text="editId ? 'Edit Media Sosial' : 'Tambah Media Sosial'"></h3>
                <button type="button" class="lp-modal__close" @click="closeModal()" aria-label="Tutup">&times;</button>
            </div>

            <form :action="editId ? MEDSOS_BASE_URL + '/' + editId : MEDSOS_STORE_URL" method="POST" class="lp-modal__form">
                @csrf
                <template x-if="editId">
                    <input type="hidden" name="_method" value="PUT">
                </template>

                <div class="lp-modal__body">
                    <div class="lp-form__row">
                        <div class="lp-form__group">
                            <label for="platform" class="lp-form__label">Platform</label>
                            <select name="platform" id="platform" class="lp-form__input" x-model="form.platform" @change="onPlatformChange()" required>
                                <option value="">Pilih Platform</option>
                                @foreach ($platforms as $value => $meta)
                                    <option value="{{ $value }}">{{ $meta['label'] }}</option>
                                @endforeach
                            </select>
                            @error('platform') <span class="lp-form__error">{{ $message }}</span> @enderror
                        </div>

                        <div class="lp-form__group">
                            <label for="icon" class="lp-form__label">Ikon</label>
                            <div class="lp-icon-select">
                                {{-- Preview ini SVG-nya digambar oleh Alpine (x-html), ambil
                                     path dari window.MEDSOS_ICONS yang sinkron dengan $icons
                                     di PHP. Otomatis ganti begitu ikon dipilih. --}}
                                <span class="lp-icon-preview" :style="{ background: currentColor }" x-html="currentIconSvg"></span>
                                <select name="icon" id="icon" class="lp-form__input" x-model="form.icon" @change="iconTouched = true">
                                    <option value="">-- Pilih Ikon --</option>
                                    @foreach ($platforms as $value => $meta)
                                        <option value="{{ $meta['icon'] }}">{{ $meta['label'] }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <span class="lp-form__info">Terisi otomatis sesuai platform, bisa diganti manual.</span>
                        </div>
                    </div>

                    <div class="lp-form__group">
                        <label for="url" class="lp-form__label">URL</label>
                        <input type="url" name="url" id="url" class="lp-form__input" x-model="form.url" required placeholder="https://...">
                        @error('url') <span class="lp-form__error">{{ $message }}</span> @enderror
                    </div>

                </div>

                <div class="lp-modal__actions">
                    <button type="button" class="lp-btn lp-btn--ghost" @click="closeModal()">Batal</button>
                    <button type="submit" class="lp-btn lp-btn--primary" x-text="editId ? 'Perbarui' : 'Simpan'"></button>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
    window.MEDSOS_BASE_URL = '{{ url('landing/admin/medsos') }}';
    window.MEDSOS_STORE_URL = '{{ route('admin.medsos.store') }}';
    // Data platform (label, warna, key ikon default) — sinkron dengan
    // $platforms di PHP di atas.
    window.MEDSOS_PLATFORMS = @json($platforms);
    // Path SVG tiap ikon (viewBox + path), sinkron dengan $icons di PHP.
    // Dipakai Alpine untuk menggambar preview ikon di modal secara dinamis.
    window.MEDSOS_ICONS = @json($icons);

    document.addEventListener('alpine:init', () => {
        Alpine.data('medsosModal', () => ({
            open: false,
            editId: null,
            iconTouched: false, // <-- tambahan
            platforms: window.MEDSOS_PLATFORMS || {},
            icons: window.MEDSOS_ICONS || {},
            form: {
                platform: '',
                url: '',
                icon: '',
            },
            get currentColor() {
                return this.platforms[this.form.platform]?.color || 'var(--border-color)';
            },
            get currentIconSvg() {
                const def = this.icons[this.form.icon];
                if (!def) {
                    return '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5"><path stroke-linecap="round" stroke-linejoin="round" d="M2.25 15.75l5.159-5.159a2.25 2.25 0 013.182 0l5.159 5.159m-1.5-1.5l1.409-1.409a2.25 2.25 0 013.182 0l2.909 2.909m-18 3.75h16.5a1.5 1.5 0 001.5-1.5V6a1.5 1.5 0 00-1.5-1.5H3.75A1.5 1.5 0 002.25 6v12a1.5 1.5 0 001.5 1.5zm10.5-11.25h.008v.008h-.008V8.25zm.375 0a.375.375 0 11-.75 0 .375.375 0 01.75 0z" /></svg>';
                }
                return `<svg viewBox="${def.viewBox}" fill="currentColor" aria-hidden="true"><path d="${def.path}"/></svg>`;
            },
            onPlatformChange() {
                // Auto-isi ikon HANYA kalau user belum pernah pilih ikon secara
                // manual di sesi modal ini — bukan berdasarkan form.icon kosong
                // atau tidak (karena setelah pilihan pertama, form.icon selalu terisi).
                if (!this.iconTouched) {
                    this.form.icon = this.platforms[this.form.platform]?.icon || '';
                }
            },
            init() {
                this.$watch('open', (val) => {
                    if (!val) {
                        this.resetForm();
                        this.editId = null;
                    }
                });
            },
            openModal(id = null) {
                this.iconTouched = false; // reset setiap kali modal dibuka
                if (id) {
                    const editUrl = window.MEDSOS_BASE_URL + '/' + id + '/edit';
                    fetch(editUrl)
                        .then(res => {
                            if (!res.ok) throw new Error('Gagal mengambil data');
                            return res.json();
                        })
                        .then(data => {
                            this.editId = id;
                            this.form.platform = data.platform;
                            this.form.url = data.url;
                            this.form.icon = data.icon || '';
                            this.open = true;
                        })
                        .catch(err => {
                            console.error('Fetch error:', err);
                            alert('Gagal mengambil data media sosial.');
                        });
                } else {
                    this.editId = null;
                    this.resetForm();
                    this.open = true;
                }
            },
            closeModal() {
                this.open = false;
            },
            resetForm() {
                this.form.platform = '';
                this.form.url = '';
                this.form.icon = '';
                this.iconTouched = false;
            }
        }));
    });
</script>
@endpush