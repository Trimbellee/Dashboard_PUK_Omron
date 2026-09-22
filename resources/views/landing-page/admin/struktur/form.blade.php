{{-- resources/views/landing-page/admin/struktur/form.blade.php --}}
@extends('landing-page.admin.layouts.app')

@push('page-styles')
    @vite(['resources/css/admin/admin-struktur.css'])
@endpush

@section('title', isset($struktur) ? 'Edit Jabatan - Admin PUK OMRON' : 'Tambah Jabatan Baru - Admin PUK OMRON')

@section('content')
<div class="lp-form-container">
    <div class="lp-form-header">
        <div class="lp-form-header__left">
            <div class="lp-form-header__back">
                <a href="{{ route('admin.struktur.index') }}" class="lp-back-btn">
                    <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <path d="M19 12H5M12 19l-7-7 7-7"/>
                    </svg>
                    Kembali ke Daftar Struktur
                </a>
            </div>
            <h1 class="lp-form-header__title">
                {{ isset($struktur) ? '✏️ Edit Jabatan' : '➕ Tambah Jabatan Baru' }}
            </h1>
            <p class="lp-form-header__desc">
                {{ isset($struktur) ? 'Perbarui informasi jabatan dan tugaskan anggota' : 'Buat jabatan baru dalam struktur organisasi PUK OMRON' }}
            </p>
        </div>
        <div class="lp-form-header__status">
            @if(isset($struktur))
                <span class="lp-status-badge lp-status-badge--edit">
                    <span class="lp-status-badge__dot"></span>
                    Mode Edit
                </span>
            @else
                <span class="lp-status-badge lp-status-badge--create">
                    <span class="lp-status-badge__dot"></span>
                    Mode Baru
                </span>
            @endif
        </div>
    </div>

    @php
        $isEdit = isset($struktur) && $struktur && $struktur->exists;
        $actionUrl = $isEdit ? route('admin.struktur.update', $struktur->id) : route('admin.struktur.store');
        $oldAnggotaId = old('anggota_id', $struktur->anggota_id ?? null);
        
        // Get initial parent ID untuk Alpine
        $initialParentId = old('parent_id', $struktur->parent_id ?? request()->query('parent_id') ?? '');
        
        // Prepare parent options untuk Alpine
        $parentOptions = [];
        foreach($jabatanOptions as $option) {
            if (isset($struktur) && $struktur->id == $option->id) {
                continue; // Skip self
            }
            $parentOptions[] = [
                'id' => $option->id,
                'jabatan' => $option->jabatan,
                'level' => $option->level ?? 0,
                'indent' => str_repeat('— ', $option->level ?? 0)
            ];
        }
    @endphp

    <form action="{{ $actionUrl }}" 
          method="POST" 
          x-data="strukturForm({{ Js::from($oldAnggotaId) }}, {{ Js::from($initialParentId) }}, {{ Js::from($parentOptions) }})"
          @submit.prevent="submitForm"
          class="lp-form-main">
        @csrf
        @if($isEdit)
            @method('PUT')
        @endif

        <div class="lp-form-layout">
            {{-- Kolom Utama --}}
            <div class="lp-form-layout__main">
                {{-- Card: Informasi Dasar --}}
                <div class="lp-form-card">
                    <div class="lp-form-card__header">
                        <div class="lp-form-card__icon">📋</div>
                        <div>
                            <h3 class="lp-form-card__title">Informasi Dasar</h3>
                            <p class="lp-form-card__desc">Isi data utama jabatan dalam struktur organisasi</p>
                        </div>
                    </div>
                    
                    <div class="lp-form-card__body">
                        {{-- Nama Jabatan --}}
                        <div class="lp-form-group">
                            <label for="jabatan" class="lp-form-label">
                                Nama Jabatan
                                <span class="lp-form-required">*</span>
                            </label>
                            <div class="lp-form-input-wrapper">
                                <span class="lp-form-input-icon">👤</span>
                                <input type="text" 
                                       id="jabatan" 
                                       name="jabatan" 
                                       value="{{ old('jabatan', $struktur->jabatan ?? '') }}" 
                                       class="lp-form-input @error('jabatan') is-invalid @enderror" 
                                       placeholder="Contoh: Ketua, Wakil Ketua Bidang 1"
                                       required>
                            </div>
                            @error('jabatan') 
                                <span class="lp-form-error">{{ $message }}</span> 
                            @enderror
                        </div>

                        {{-- Parent Jabatan --}}
                        <div class="lp-form-group">
                            <label for="parent_id" class="lp-form-label">
                                Jabatan Atasan (Parent)
                            </label>
                            <div class="lp-form-input-wrapper">
                                <span class="lp-form-input-icon">🏛️</span>
                                <select id="parent_id" 
                                        name="parent_id" 
                                        x-model="selectedParentId"
                                        @change="updateParentInfo()"
                                        class="lp-form-input lp-form-select @error('parent_id') is-invalid @enderror">
                                    <option value="">— Tidak ada (Root Level) —</option>
                                    @foreach($jabatanOptions as $option)
                                        @if(isset($struktur) && $struktur->id == $option->id)
                                            @continue
                                        @endif
                                        <option value="{{ $option->id }}" 
                                            {{ old('parent_id', $struktur->parent_id ?? request()->query('parent_id')) == $option->id ? 'selected' : '' }}>
                                            {{ str_repeat('— ', $option->level ?? 0) }}{{ $option->jabatan }}
                                        </option>
                                    @endforeach
                                </select>
                                <span class="lp-form-input-suffix">▼</span>
                            </div>
                            @error('parent_id') 
                                <span class="lp-form-error">{{ $message }}</span> 
                            @enderror
                        </div>

                    </div>
                </div>

                {{-- Card: Penugasan Anggota --}}
                <div class="lp-form-card">
                    <div class="lp-form-card__header">
                        <div class="lp-form-card__icon">👥</div>
                        <div>
                            <h3 class="lp-form-card__title">Penugasan Anggota</h3>
                            <p class="lp-form-card__desc">Cari dan tugaskan anggota untuk jabatan ini</p>
                        </div>
                    </div>
                    
                    <div class="lp-form-card__body">
                        {{-- Search Anggota - Hanya tampil jika tidak ada selectedAnggota --}}
                        <template x-if="!selectedAnggota">
                            <div class="lp-form-group">
                                <label for="nik_search" class="lp-form-label">
                                    Cari Anggota
                                    <span class="lp-form-hint">(NIK / Nama)</span>
                                </label>
                                <div class="lp-search-container">
                                    <div class="lp-form-input-wrapper lp-search-input">
                                        <span class="lp-form-input-icon">🔍</span>
                                        <input type="text" 
                                               id="nik_search" 
                                               x-model="nikSearch" 
                                               @input.debounce.300ms="searchAnggota" 
                                               placeholder="Ketik NIK atau nama karyawan..." 
                                               class="lp-form-input"
                                               :class="{ 'is-loading': loading }">
                                        <span x-show="loading" class="lp-search-spinner">
                                            <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                                <path d="M12 2v4M12 18v4M4.93 4.93l2.83 2.83M16.24 16.24l2.83 2.83M2 12h4M18 12h4M4.93 19.07l2.83-2.83M16.24 7.76l2.83-2.83"/>
                                            </svg>
                                        </span>
                                    </div>
                                </div>
                                
                                {{-- Hasil Pencarian --}}
                                <div x-show="searchResults.length > 0" class="lp-search-results">
                                    <div class="lp-search-results__header">
                                        <span>Hasil Pencarian</span>
                                        <span class="lp-search-results__count" x-text="searchResults.length + ' ditemukan'"></span>
                                    </div>
                                    <template x-for="anggota in searchResults" :key="anggota.id">
                                        <div class="lp-search-result" @click="selectAnggota(anggota)">
                                            <img :src="anggota.foto_url || '{{ asset('assets/no-photo.png') }}'" 
                                                 class="lp-search-result__avatar"
                                                 :alt="anggota.nama_lengkap">
                                            <div class="lp-search-result__info">
                                                <div class="lp-search-result__name">
                                                    <strong x-text="anggota.nama_lengkap"></strong>
                                                    <span class="lp-search-result__nik" x-text="'(' + anggota.nik_karyawan + ')'"></span>
                                                </div>
                                                <div class="lp-search-result__detail">
                                                    <span x-text="anggota.departemen"></span>
                                                    <span class="lp-search-result__separator">•</span>
                                                    <span x-text="anggota.jabatan"></span>
                                                </div>
                                            </div>
                                            <span class="lp-search-result__action">Pilih →</span>
                                        </div>
                                    </template>
                                </div>
                                
                                <div x-show="searchResults.length === 0 && nikSearch.length >= 2 && !loading" 
                                     class="lp-search-empty">
                                    <span>😕</span>
                                    <span>Tidak ada anggota yang ditemukan</span>
                                </div>
                            </div>
                        </template>

                        {{-- Preview Anggota Terpilih --}}
                        <template x-if="selectedAnggota">
                            <div class="lp-selected-member">
                                <div class="lp-selected-member__header">
                                    <span class="lp-selected-member__badge">✅ Terpilih</span>
                                    <button type="button" @click="clearSelected" class="lp-selected-member__remove">
                                        ✕
                                    </button>
                                </div>
                                <div class="lp-selected-member__body">
                                    <img :src="selectedAnggota.foto_url || '{{ asset('assets/no-photo.png') }}'" 
                                         class="lp-selected-member__avatar"
                                         :alt="selectedAnggota.nama_lengkap">
                                    <div class="lp-selected-member__info">
                                        <div class="lp-selected-member__name" x-text="selectedAnggota.nama_lengkap"></div>
                                        <div class="lp-selected-member__details">
                                            <span class="lp-selected-member__detail">
                                                <span class="lp-selected-member__detail-label">NIK</span>
                                                <span x-text="selectedAnggota.nik_karyawan"></span>
                                            </span>
                                            <span class="lp-selected-member__detail">
                                                <span class="lp-selected-member__detail-label">Departemen</span>
                                                <span x-text="selectedAnggota.departemen"></span>
                                            </span>
                                            <span class="lp-selected-member__detail">
                                                <span class="lp-selected-member__detail-label">Jabatan</span>
                                                <span x-text="selectedAnggota.jabatan"></span>
                                            </span>
                                            <span class="lp-selected-member__detail" x-show="selectedAnggota.nomor_whatsapp">
                                                <span class="lp-selected-member__detail-label">📱 WA</span>
                                                <span x-text="selectedAnggota.nomor_whatsapp"></span>
                                            </span>
                                            <span class="lp-selected-member__detail" x-show="selectedAnggota.email">
                                                <span class="lp-selected-member__detail-label">📧 Email</span>
                                                <span x-text="selectedAnggota.email"></span>
                                            </span>
                                        </div>
                                    </div>
                                </div>
                                {{-- Hidden input untuk anggota_id --}}
                                <input type="hidden" name="anggota_id" :value="selectedAnggota ? selectedAnggota.id : ''">
                            </div>
                        </template>

                        @error('anggota_id') 
                            <span class="lp-form-error">{{ $message }}</span> 
                        @enderror
                    </div>
                </div>
            </div>

            {{-- Sidebar --}}
            <aside class="lp-form-layout__sidebar">
                <div class="lp-form-sidebar">
                    {{-- Action Panel --}}
                    <div class="lp-form-panel lp-form-panel--actions">
                        <h4 class="lp-form-panel__title">💾 Simpan Perubahan</h4>
                        <button type="submit" class="lp-btn lp-btn--primary lp-btn--block">
                            <span class="lp-btn__icon">{{ isset($struktur) ? '✅' : '💾' }}</span>
                            <span class="lp-btn__text">{{ isset($struktur) ? 'Perbarui Jabatan' : 'Simpan Jabatan' }}</span>
                        </button>
                        <a href="{{ route('admin.struktur.index') }}" class="lp-btn lp-btn--ghost lp-btn--block">
                            <span class="lp-btn__icon">↩️</span>
                            <span class="lp-btn__text">Batal & Kembali</span>
                        </a>
                    </div>

                    {{-- Informasi Tambahan --}}
                    <div class="lp-form-panel">
                        <h4 class="lp-form-panel__title">ℹ️ Informasi</h4>
                        <ul class="lp-form-info-list">
                            {{-- Parent Jabatan Info - Dynamic --}}
                            <li class="lp-form-info-item">
                                <span class="lp-form-info-item__icon">📌</span>
                                <div>
                                    <span class="lp-form-info-item__label">Jabatan Atasan</span>
                                    <span class="lp-form-info-item__value" x-html="parentInfoDisplay"></span>
                                </div>
                            </li>
                            
                            {{-- Anggota Info --}}
                            <li class="lp-form-info-item">
                                <span class="lp-form-info-item__icon">👤</span>
                                <div>
                                    <span class="lp-form-info-item__label">Anggota</span>
                                    <span class="lp-form-info-item__value" x-text="selectedAnggota ? 'Terpilih: ' + selectedAnggota.nama_lengkap : 'Belum ada'"></span>
                                </div>
                            </li>
                            
                            @if($isEdit)
                                <li class="lp-form-info-item">
                                    <span class="lp-form-info-item__icon">🕐</span>
                                    <div>
                                        <span class="lp-form-info-item__label">Dibuat</span>
                                        <span class="lp-form-info-item__value">{{ $struktur->created_at->format('d M Y, H:i') }}</span>
                                    </div>
                                </li>
                                <li class="lp-form-info-item">
                                    <span class="lp-form-info-item__icon">🔄</span>
                                    <div>
                                        <span class="lp-form-info-item__label">Terakhir Diupdate</span>
                                        <span class="lp-form-info-item__value">{{ $struktur->updated_at->format('d M Y, H:i') }}</span>
                                    </div>
                                </li>
                            @endif
                        </ul>
                    </div>

                    {{-- Shortcuts --}}
                    <div class="lp-form-panel lp-form-panel--shortcuts">
                        <h4 class="lp-form-panel__title">⚡ Shortcuts</h4>
                        <div class="lp-shortcut-grid">
                            <kbd class="lp-shortcut-key">Ctrl + S</kbd>
                            <span class="lp-shortcut-desc">Simpan</span>
                            <kbd class="lp-shortcut-key">Esc</kbd>
                            <span class="lp-shortcut-desc">Batal</span>
                        </div>
                    </div>
                </div>
            </aside>
        </div>
    </form>
</div>

@push('scripts')
<script>
    function strukturForm(initialAnggotaId, initialParentId, parentOptions) {
        return {
            nikSearch: '',
            searchResults: [],
            selectedAnggota: null,
            loading: false,
            selectedParentId: initialParentId,
            parentOptions: parentOptions,
            parentInfoDisplay: '',

            init() {
                // Load initial anggota if exists
                if (initialAnggotaId) {
                    fetch('{{ route("admin.struktur.anggota-by-id") }}?id=' + initialAnggotaId)
                        .then(res => res.json())
                        .then(data => {
                            if (data && data.id) {
                                this.selectedAnggota = data;
                            }
                        })
                        .catch(err => console.error('Error fetching anggota:', err));
                }

                // Update parent info on init
                this.updateParentInfo();

                // Keyboard shortcuts
                document.addEventListener('keydown', (e) => {
                    // Ctrl + S
                    if ((e.ctrlKey || e.metaKey) && e.key === 's') {
                        e.preventDefault();
                        this.$el.submit();
                    }
                    // Escape
                    if (e.key === 'Escape') {
                        window.location.href = '{{ route("admin.struktur.index") }}';
                    }
                });
            },

            updateParentInfo() {
                const parentId = this.selectedParentId;
                if (!parentId) {
                    this.parentInfoDisplay = '<span style="color: var(--text-muted); font-style: italic;">— Tidak ada (Root Level) —</span>';
                    return;
                }

                const parent = this.parentOptions.find(p => p.id == parentId);
                if (parent) {
                    const levelBadge = parent.level !== undefined ? 
                        `<span class="lp-badge lp-badge--level-${parent.level}" style="font-size: 10px; padding: 1px 8px; margin-left: 6px;">Lv.${parent.level}</span>` : '';
                    this.parentInfoDisplay = `
                        <span style="display: flex; align-items: center; gap: 6px;">
                            <span>🏛️</span>
                            ${parent.jabatan}
                            
                        </span>
                    `;
                } else {
                    this.parentInfoDisplay = '<span style="color: var(--text-muted); font-style: italic;">— Tidak ada (Root Level) —</span>';
                }
            },

            searchAnggota() {
                if (this.nikSearch.length < 2) {
                    this.searchResults = [];
                    return;
                }
                
                this.loading = true;
                fetch('{{ route("admin.struktur.anggota-by-nik") }}?nik=' + encodeURIComponent(this.nikSearch))
                    .then(res => res.json())
                    .then(data => {
                        this.searchResults = data || [];
                        this.loading = false;
                    })
                    .catch(() => {
                        this.loading = false;
                        this.searchResults = [];
                    });
            },

            selectAnggota(anggota) {
                this.selectedAnggota = anggota;
                this.searchResults = [];
                this.nikSearch = '';
            },

            clearSelected() {
                this.selectedAnggota = null;
            },

            submitForm() {
                this.$el.submit();
            }
        };
    }
</script>
@endpush

@endsection