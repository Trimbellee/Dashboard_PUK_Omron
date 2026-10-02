@extends('landing-page.admin.layouts.app')

@push('page-styles')
    @vite(['resources/css/admin/admin-galeri.css'])
    <style>
        .lp-bulk-textarea {
            width: 100%;
            min-height: 220px;
            padding: 12px 14px;
            border: 1px solid var(--border-color);
            border-radius: var(--radius-sm);
            background: var(--bg-base);
            font-family: ui-monospace, SFMono-Regular, Menlo, Consolas, monospace;
            font-size: 13px;
            line-height: 1.6;
            color: var(--text-primary);
            resize: vertical;
        }
        .lp-bulk-textarea:focus {
            outline: none;
            border-color: var(--accent);
            box-shadow: 0 0 0 3px var(--accent-soft);
        }
        .lp-bulk-report { margin-bottom: 20px; }
        .lp-bulk-report h4 { margin: 14px 0 6px; font-size: 14px; color: var(--text-primary); }
        .lp-bulk-report ul {
            margin: 0;
            padding-left: 18px;
            font-size: 13px;
            word-break: break-all;
            color: var(--text-secondary);
        }
        .lp-bulk-ok { color: #059669; font-weight: 700; }
        .lp-bulk-counter { font-size: 12.5px; color: var(--text-muted); }
    </style>
@endpush

@section('title', 'Tambah Banyak Media Galeri - Admin PUK OMRON')

@section('content')
@php $laporan = session('bulk_report'); @endphp

<div class="lp-editor__topbar lp-editor__topbar--simple">
    <a href="{{ route('admin.galeri.index') }}" class="lp-editor__back">← Kembali</a>
    <h1 class="lp-editor__title-label">Tambah Banyak Media</h1>
</div>

@if ($laporan)
    <div class="lp-editor__panel lp-bulk-report">
        <h3 class="lp-editor__panel-title">Hasil proses terakhir</h3>
        <p style="margin:0; font-size:14px;">
            <span class="lp-bulk-ok">{{ $laporan['berhasil'] }}</span> media ditambahkan sebagai draft.
            @if ($laporan['tanpa_thumbnail'] > 0)
                {{ $laporan['tanpa_thumbnail'] }} di antaranya tanpa thumbnail (TikTok gagal diambil); lengkapi lewat tombol Edit.
            @endif
        </p>

        @if (!empty($laporan['duplikat']))
            <h4>Dilewati karena sudah ada ({{ count($laporan['duplikat']) }})</h4>
            <ul>@foreach ($laporan['duplikat'] as $l) <li>{{ $l }}</li> @endforeach</ul>
        @endif

        @if (!empty($laporan['gagal']))
            <h4>Gagal diproses ({{ count($laporan['gagal']) }})</h4>
            <ul>@foreach ($laporan['gagal'] as $l) <li>{{ $l }}</li> @endforeach</ul>
            <p class="lp-editor-hint">Pastikan link berupa YouTube, Google Drive (file), atau TikTok.</p>
        @endif
    </div>
@endif

<form action="{{ route('admin.galeri.bulk-store') }}" method="POST" class="lp-editor__grid lp-editor__grid--narrow"
      x-data="{ jumlah: 0, hitung(el) { this.jumlah = el.value.split(/\r?\n/).filter(l => l.trim() !== '').length } }">
    @csrf

    <div class="lp-editor__main">
        <div class="lp-form__group">
            <label for="links">Link media (satu link per baris)</label>
            <textarea id="links" name="links" class="lp-bulk-textarea" required
                      placeholder="https://www.youtube.com/watch?v=...&#10;https://www.tiktok.com/@akun/video/...&#10;https://drive.google.com/file/d/..."
                      @input="hitung($el)" x-init="hitung($el)">{{ old('links') }}</textarea>
            <span class="lp-bulk-counter">
                <span x-text="jumlah"></span> link terdeteksi · maksimal {{ $maxBulk }} per proses
            </span>
            @error('links') <span class="lp-form__error">{{ $message }}</span> @enderror
        </div>

        <div class="lp-form__group">
            <label for="kegiatan">Kegiatan / Album (opsional, berlaku untuk semua link)</label>
            <input type="text" id="kegiatan" name="kegiatan" value="{{ old('kegiatan') }}"
                   placeholder="Contoh: Rapat Anggota Tahunan 2026" list="kegiatan-list" autocomplete="off">
            <datalist id="kegiatan-list">
                @foreach (($kegiatanList ?? []) as $namaKegiatan)
                    <option value="{{ $namaKegiatan }}"></option>
                @endforeach
            </datalist>
            @error('kegiatan') <span class="lp-form__error">{{ $message }}</span> @enderror
        </div>

        <div class="lp-form__group">
            <label for="kategori">Kategori (opsional)</label>
            <select name="kategori" id="kategori" class="lp-form__input">
                <option value="">-- Pilih Kategori --</option>
                @foreach (\App\Models\Gallery::KATEGORI_LIST as $value => $label)
                    <option value="{{ $value }}" @selected(old('kategori') === $value)>{{ $label }}</option>
                @endforeach
            </select>
            @error('kategori') <span class="lp-form__error">{{ $message }}</span> @enderror
        </div>

        <div class="lp-form__group">
            <label>Tipe untuk link Google Drive</label>
            <div style="display:flex; gap:16px; margin-top:4px;">
                <label style="display:flex; align-items:center; gap:6px; font-weight:500;">
                    <input type="radio" name="type_drive" value="image" @checked(old('type_drive', 'image') === 'image')> Gambar
                </label>
                <label style="display:flex; align-items:center; gap:6px; font-weight:500;">
                    <input type="radio" name="type_drive" value="video" @checked(old('type_drive') === 'video')> Video
                </label>
            </div>
            <p class="lp-editor-hint">YouTube dan TikTok otomatis disimpan sebagai video.</p>
        </div>
    </div>

    <aside class="lp-editor__sidebar">
        <div class="lp-editor__panel">
            <h3 class="lp-editor__panel-title">Cara kerja</h3>
            <p class="lp-editor-hint" style="margin:0 0 8px;">Semua media masuk sebagai <strong>draft</strong>; kirim untuk review dari daftar galeri seperti biasa.</p>
            <p class="lp-editor-hint" style="margin:0 0 8px;">Link yang sudah ada otomatis dilewati.</p>
            <p class="lp-editor-hint" style="margin:0;">Judul bisa diisi belakangan lewat tombol Edit.</p>
        </div>
        <div class="lp-editor__panel">
            <button type="submit" class="lp-btn lp-btn--primary lp-form__submit">Simpan Semua sebagai Draft</button>
        </div>
    </aside>
</form>
@endsection
