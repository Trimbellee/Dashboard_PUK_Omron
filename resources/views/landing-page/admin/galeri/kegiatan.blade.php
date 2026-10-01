@extends('landing-page.admin.layouts.app')

@push('page-styles')
    @vite(['resources/css/admin/admin-galeri.css'])
    <style>
        .lp-kg-table { width: 100%; border-collapse: collapse; }
        .lp-kg-table th {
            padding: 10px 12px;
            border-bottom: 1px solid var(--border-color);
            font-size: 12px;
            font-weight: 700;
            letter-spacing: 0.04em;
            text-align: left;
            text-transform: uppercase;
            color: var(--text-secondary);
        }
        .lp-kg-table td {
            padding: 12px;
            border-bottom: 1px solid var(--border-color);
            font-size: 14px;
            color: var(--text-primary);
            vertical-align: middle;
        }
        .lp-kg-table tr:last-child td { border-bottom: none; }
        .lp-kg-num { text-align: center; font-variant-numeric: tabular-nums; }
        .lp-kg-form { display: flex; gap: 8px; }
        .lp-kg-form input {
            flex: 1;
            min-width: 160px;
            padding: 8px 12px;
            border: 1px solid var(--border-color);
            border-radius: var(--radius-sm);
            background: var(--bg-base);
            font-size: 14px;
            color: var(--text-primary);
        }
        .lp-kg-form input:focus { outline: none; border-color: var(--accent); }
        .lp-kg-scroll { overflow-x: auto; }
        .lp-kg-alert {
            margin-bottom: 16px;
            padding: 10px 14px;
            border-left: 3px solid #dc2626;
            border-radius: 4px;
            background: #fee2e2;
            font-size: 13.5px;
            color: #991b1b;
        }
        @media (max-width: 640px) {
            .lp-kg-form { flex-direction: column; }
        }
    </style>
@endpush

@section('title', 'Kelola Kegiatan Galeri - Admin PUK OMRON')

@section('content')
    <div class="lp-editor__topbar lp-editor__topbar--simple">
        <a href="{{ route('admin.galeri.index') }}" class="lp-editor__back">← Kembali</a>
        <h1 class="lp-editor__title-label">Kelola Kegiatan (Album)</h1>
    </div>

    <p class="lp-editor-hint" style="margin: 0 0 16px; max-width: 760px;">
        Ganti nama kegiatan untuk <strong>semua media</strong> yang memakainya. Jika nama baru sama dengan album lain,
        keduanya otomatis tergabung. Cocok untuk merapikan salah ketik yang memecah album.
    </p>

    @if ($errors->any())
        <div class="lp-kg-alert">
            @foreach ($errors->all() as $pesan)
                <div>{{ $pesan }}</div>
            @endforeach
        </div>
    @endif

    <div class="lp-editor__panel">
        @if ($daftar->isEmpty())
            <p class="lp-editor-hint" style="margin:0;">Belum ada media yang memiliki nama kegiatan.</p>
        @else
            <div class="lp-kg-scroll">
                <table class="lp-kg-table">
                    <thead>
                        <tr>
                            <th>Kegiatan</th>
                            <th class="lp-kg-num">Media</th>
                            <th class="lp-kg-num">Published</th>
                            <th>Ganti nama / gabungkan ke</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($daftar as $row)
                            <tr>
                                <td>{{ $row->kegiatan }}</td>
                                <td class="lp-kg-num">{{ $row->total }}</td>
                                <td class="lp-kg-num">{{ (int) $row->published }}</td>
                                <td>
                                    <form action="{{ route('admin.galeri.rename-kegiatan') }}" method="POST" class="lp-kg-form"
                                          onsubmit="return confirm('Ganti nama album untuk {{ (int) $row->total }} media?')">
                                        @csrf
                                        <input type="hidden" name="dari" value="{{ $row->kegiatan }}">
                                        <input type="text" name="ke" value="{{ $row->kegiatan }}" maxlength="255"
                                               list="kegiatan-options" required aria-label="Nama baru untuk {{ $row->kegiatan }}">
                                        <button type="submit" class="lp-pill-btn lp-pill-btn--primary">Simpan</button>
                                    </form>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>

            {{-- Saran nama album yang sudah ada, untuk memudahkan penggabungan --}}
            <datalist id="kegiatan-options">
                @foreach ($daftar as $row)
                    <option value="{{ $row->kegiatan }}"></option>
                @endforeach
            </datalist>
        @endif
    </div>
@endsection
