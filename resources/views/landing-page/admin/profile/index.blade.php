@extends('landing-page.admin.layouts.app')

@push('page-styles')
    @vite(['resources/css/admin/admin-profile.css'])
@endpush

@section('title', 'Kelola Visi & Misi')

@section('content')
<div class="lp-admin-head">
    <div>
        <h1 class="lp-admin-head__title" style="color: var(--text-secondary);">
            <span style="display: inline-flex; align-items: center; gap: 10px;">
                <svg width="28" height="28" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="color: var(--accent);">
                    <path d="M12 2 4 5v6c0 5 3.5 8.5 8 11 4.5-2.5 8-6 8-11V5l-8-3z"/>
                    <path d="M8 9l2 2 4-4"/>
                </svg>
                Kelola Visi & Misi
            </span>
        </h1>
        <p class="lp-admin-head__sub" style="color: var(--text-secondary); margin-top: 6px; font-size: 14px; max-width: 600px;">
            Sekretaris dapat memperbarui visi, misi, dan informasi organisasi yang tampil di halaman utama publik.
        </p>
    </div>
</div>

@if(session('success'))
    <div class="lp-admin-alert lp-admin-alert--success" style="padding: 14px 18px; background: var(--accent-soft); border-radius: var(--radius-sm); color: var(--accent); margin-bottom: 24px; border-left: 4px solid var(--accent); display: flex; align-items: center; gap: 10px;">
        <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"/><polyline points="22 4 12 14.01 9 11.01"/></svg>
        {{ session('success') }}
    </div>
@endif

<form action="{{ route('admin.profile.update') }}" method="POST" class="profile-form" style="display: grid; grid-template-columns: 1fr 320px; gap: 28px; align-items: start;">
    @csrf
    @method('PUT')

    {{-- ===== KOLOM UTAMA ===== --}}
    <div class="profile-main" style="display: flex; flex-direction: column; gap: 24px;">

        {{-- Kartu Visi --}}
        <div class="profile-card" style="background: var(--bg-surface); border: 1px solid var(--border-color); border-radius: var(--radius-md); padding: 24px; transition: border-color 0.2s ease, box-shadow 0.2s ease;">
            <div style="display: flex; align-items: center; gap: 10px; margin-bottom: 14px;">
                <span style="font-size: 20px;">🎯</span>
                <label for="visi" style="font-weight: 700; font-size: 16px; color: var(--text-primary); cursor: pointer;">Visi</label>
                <span style="margin-left: auto; font-size: 12px; color: var(--text-secondary); opacity: 0.7;">Maks. 500 karakter</span>
            </div>
            <textarea
                id="visi"
                name="visi"
                rows="4"
                class="lp-form__textarea"
                placeholder="Masukkan visi PUK OMRON..."
                required
                style="min-height: 100px; resize: vertical; font-size: 14.5px; line-height: 1.6;"
            >{{ old('visi', $profile->visi ?? '') }}</textarea>
            @error('visi')
                <span class="lp-form__error">{{ $message }}</span>
            @enderror
        </div>

        {{-- Kartu Misi --}}
        <div class="profile-card" style="background: var(--bg-surface); border: 1px solid var(--border-color); border-radius: var(--radius-md); padding: 24px; transition: border-color 0.2s ease, box-shadow 0.2s ease;">
            <div style="display: flex; align-items: center; gap: 10px; margin-bottom: 14px;">
                <span style="font-size: 20px;">📋</span>
                <label style="font-weight: 700; font-size: 16px; color: var(--text-primary);">Misi</label>
                <span style="margin-left: auto; font-size: 12px; color: var(--text-secondary); opacity: 0.7;">Min. 1, maks. 255 karakter per item</span>
            </div>

            <div id="misi-container" x-data="misiEditor({{ Js::from(old('misi', $profile->misi ?? [''])) }})">
                <template x-for="(item, index) in misiList" :key="index">
                    <div class="misi-item" style="display: flex; gap: 10px; align-items: stretch; margin-bottom: 10px;">
                        <div style="display: flex; align-items: center; justify-content: center; min-width: 28px; font-weight: 600; color: var(--accent); background: var(--accent-soft); border-radius: 6px; font-size: 13px;" x-text="index + 1"></div>
                        <input
                            type="text"
                            :name="'misi[' + index + ']'"
                            x-model="misiList[index]"
                            class="lp-form__input"
                            placeholder="Tulis misi ke-" x-text="index + 1"
                            required
                            style="flex: 1; padding: 10px 14px; border-radius: var(--radius-sm); border: 1px solid var(--border-color); background: var(--bg-base); color: var(--text-primary); font-size: 14px; transition: border-color 0.15s ease;"
                            @focus="$el.style.borderColor = 'var(--accent)'"
                            @blur="$el.style.borderColor = ''"
                        >
                        <button
                            type="button"
                            class="lp-pill-btn lp-pill-btn--danger"
                            @click="removeMisi(index)"
                            x-show="misiList.length > 1"
                            style="padding: 0 14px; font-size: 14px; border-radius: var(--radius-sm); white-space: nowrap;"
                            title="Hapus misi ini"
                        >
                            ✕
                        </button>
                    </div>
                </template>

                <button
                    type="button"
                    class="lp-btn lp-btn--ghost"
                    @click="addMisi"
                    style="display: inline-flex; align-items: center; gap: 6px; margin-top: 6px; padding: 8px 18px; font-size: 13px; border: 1px dashed var(--border-color); border-radius: var(--radius-sm); background: transparent; color: var(--accent); cursor: pointer; transition: all 0.15s ease;"
                    @mouseenter="$el.style.borderColor = 'var(--accent)'; $el.style.background = 'var(--accent-soft)'"
                    @mouseleave="$el.style.borderColor = ''; $el.style.background = ''"
                >
                    <span style="font-size: 18px; line-height: 1;">+</span> Tambah Misi
                </button>
            </div>

            @error('misi')
                <span class="lp-form__error">{{ $message }}</span>
            @enderror
            @error('misi.*')
                <span class="lp-form__error">{{ $message }}</span>
            @enderror
        </div>

        {{-- Tombol Simpan (mobile) --}}
        <button type="submit" class="lp-btn lp-btn--primary" style="display: none; width: 100%; padding: 14px; font-size: 16px; border-radius: var(--radius-sm);" id="save-mobile">
            💾 Simpan Perubahan
        </button>
    </div>

    {{-- ===== SIDEBAR ===== --}}
    <aside class="profile-sidebar" style="display: flex; flex-direction: column; gap: 20px;">

        {{-- Kartu Informasi Tambahan --}}
        <div class="profile-card" style="background: var(--bg-surface); border: 1px solid var(--border-color); border-radius: var(--radius-md); padding: 24px;">
            <h3 style="font-size: 13px; font-weight: 700; text-transform: uppercase; letter-spacing: 0.04em; color: var(--text-secondary); margin: 0 0 16px 0; display: flex; align-items: center; gap: 8px;">
                <span>📌</span> Informasi Tambahan
            </h3>

            <div class="lp-form__group" style="margin-bottom: 16px;">
                <label for="sejarah_singkat" style="font-size: 13px; font-weight: 600; color: var(--text-primary); display: block; margin-bottom: 4px;">Sejarah Singkat</label>
                <textarea
                    id="sejarah_singkat"
                    name="sejarah_singkat"
                    rows="3"
                    class="lp-form__textarea"
                    placeholder="Cerita singkat tentang PUK OMRON..."
                    style="min-height: 80px; resize: vertical; font-size: 13.5px; line-height: 1.5;"
                >{{ old('sejarah_singkat', $profile->sejarah_singkat ?? '') }}</textarea>
                @error('sejarah_singkat')
                    <span class="lp-form__error">{{ $message }}</span>
                @enderror
            </div>

            <div class="lp-form__group" style="margin-bottom: 16px;">
                <label for="periode_kepengurusan" style="font-size: 13px; font-weight: 600; color: var(--text-primary); display: block; margin-bottom: 4px;">Periode Kepengurusan</label>
                <input
                    type="text"
                    id="periode_kepengurusan"
                    name="periode_kepengurusan"
                    value="{{ old('periode_kepengurusan', $profile->periode_kepengurusan ?? '') }}"
                    class="lp-form__input"
                    placeholder="Contoh: 2024–2027"
                    style="padding: 10px 14px; font-size: 13.5px;"
                >
                @error('periode_kepengurusan')
                    <span class="lp-form__error">{{ $message }}</span>
                @enderror
            </div>

            <div class="lp-form__group">
                <label for="alamat_sekretariat" style="font-size: 13px; font-weight: 600; color: var(--text-primary); display: block; margin-bottom: 4px;">Alamat Sekretariat</label>
                <input
                    type="text"
                    id="alamat_sekretariat"
                    name="alamat_sekretariat"
                    value="{{ old('alamat_sekretariat', $profile->alamat_sekretariat ?? '') }}"
                    class="lp-form__input"
                    placeholder="Alamat lengkap sekretariat"
                    style="padding: 10px 14px; font-size: 13.5px;"
                >
                @error('alamat_sekretariat')
                    <span class="lp-form__error">{{ $message }}</span>
                @enderror
            </div>
        </div>

        {{-- Kartu Aksi --}}
        <div class="profile-card" style="background: var(--bg-surface); border: 1px solid var(--border-color); border-radius: var(--radius-md); padding: 24px; position: sticky; top: 20px;">
            <h3 style="font-size: 13px; font-weight: 700; text-transform: uppercase; letter-spacing: 0.04em; color: var(--text-secondary); margin: 0 0 14px 0;">💾 Aksi</h3>
            <button type="submit" class="lp-btn lp-btn--primary" style="width: 100%; padding: 14px; font-size: 15px; border-radius: var(--radius-sm); display: flex; align-items: center; justify-content: center; gap: 8px;">
                <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M19 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h11l5 5v11a2 2 0 0 1-2 2z"/><polyline points="17 21 17 13 7 13 7 21"/><polyline points="7 3 7 8 15 8"/></svg>
                Simpan Perubahan
            </button>
            <a href="{{ route('landing.index') }}#visi-misi" target="_blank" style="display: block; text-align: center; margin-top: 12px; font-size: 13px; color: var(--text-secondary); text-decoration: none; transition: color 0.15s ease;" onmouseover="this.style.color='var(--accent)'" onmouseout="this.style.color=''">
                👁️ Lihat di halaman utama
            </a>
        </div>
    </aside>
</form>
@endsection

@push('scripts')
<script>
    function misiEditor(initialMisi) {
        return {
            misiList: Array.isArray(initialMisi) && initialMisi.length > 0 ? initialMisi : [''],
            addMisi() {
                this.misiList.push('');
                // Scroll ke bawah setelah menambah
                this.$nextTick(() => {
                    const container = document.getElementById('misi-container');
                    if (container) {
                        container.scrollIntoView({ behavior: 'smooth', block: 'nearest' });
                    }
                });
            },
            removeMisi(index) {
                if (this.misiList.length > 1) {
                    this.misiList.splice(index, 1);
                }
            }
        }
    }
</script>
@endpush

@push('styles')
<style>
    /* ===== Profile Form ===== */
    .profile-card {
        transition: border-color 0.2s ease, box-shadow 0.2s ease;
    }
    .profile-card:hover {
        border-color: var(--accent);
        box-shadow: 0 4px 20px rgba(37, 99, 235, 0.06);
    }

    .lp-form__textarea:focus,
    .lp-form__input:focus {
        outline: none;
        border-color: var(--accent);
        box-shadow: 0 0 0 3px var(--accent-soft);
    }

    .misi-item input:focus {
        border-color: var(--accent) !important;
        box-shadow: 0 0 0 3px var(--accent-soft);
    }

    /* ===== Responsive ===== */
    @media (max-width: 960px) {
        .profile-form {
            grid-template-columns: 1fr !important;
        }
        .profile-sidebar {
            order: 2;
        }
        .profile-main {
            order: 1;
        }
        #save-mobile {
            display: flex !important;
        }
        /* Sembunyikan tombol simpan di sidebar pada mobile */
        .profile-sidebar .lp-btn--primary {
            display: none !important;
        }
        .profile-card {
            padding: 18px !important;
        }
    }

    @media (max-width: 480px) {
        .profile-card {
            padding: 14px !important;
        }
        .misi-item {
            flex-wrap: wrap;
        }
        .misi-item .lp-pill-btn {
            flex: 0 0 auto;
            padding: 0 12px;
        }
        .lp-admin-head__title {
            font-size: 20px;
        }
    }

    /* Dark mode adjustment */
    [data-theme="dark"] .profile-card:hover {
        border-color: var(--accent);
        box-shadow: 0 4px 20px rgba(91, 140, 255, 0.08);
    }
    [data-theme="dark"] .lp-btn--ghost:hover {
        background: var(--accent-soft);
    }
</style>
@endpush