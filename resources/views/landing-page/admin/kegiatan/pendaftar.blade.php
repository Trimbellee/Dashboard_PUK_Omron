{{--
    resources/views/landing-page/admin/kegiatan/pendaftar.blade.php

    List pendaftar satu kegiatan. Kolom custom field dirender dinamis
    sesuai $kegiatan->customFields (bukan hardcode), dibaca dari
    $pendaftaran->custom_values[$field->id].
--}}

@extends('landing-page.admin.layouts.app')

@section('title', 'Pendaftar — ' . $kegiatan->judul)

@push('page-styles')
<style>
    .lp-pendaftar .lp-toolbar {
        display: flex;
        flex-wrap: wrap;
        gap: .6rem;
        align-items: center;
        justify-content: space-between;
        margin-bottom: 1rem;
    }

    .lp-pendaftar .lp-filters {
        display: flex;
        flex-wrap: wrap;
        gap: .5rem;
    }

    .lp-pendaftar .lp-filters input,
    .lp-pendaftar .lp-filters select {
        border: 1px solid var(--border, #d1d5db);
        border-radius: 8px;
        padding: .45rem .65rem;
        font-size: .8125rem;
    }

    .lp-pendaftar .lp-btn-ghost {
        border: 1px solid var(--border, #d1d5db);
        background: var(--surface-1, #fff);
        border-radius: 8px;
        padding: .5rem .9rem;
        font-size: .8125rem;
        text-decoration: none;
        color: inherit;
    }

    .lp-pendaftar .lp-table-wrap {
        background: var(--surface-2, #fff);
        border: 1px solid var(--border, #e5e7eb);
        border-radius: 12px;
        overflow-x: auto;
    }

    .lp-pendaftar table {
        width: 100%;
        min-width: 760px;
        border-collapse: collapse;
        font-size: .8125rem;
    }

    .lp-pendaftar thead th {
        text-align: left;
        font-size: .72rem;
        color: var(--text-secondary, #6b7280);
        padding: .7rem .85rem;
        border-bottom: 1px solid var(--border, #e5e7eb);
        white-space: nowrap;
    }

    .lp-pendaftar tbody td {
        padding: .7rem .85rem;
        border-bottom: 1px solid var(--border, #f1f2f4);
        vertical-align: middle;
    }

    .lp-pendaftar tbody tr:last-child td {
        border-bottom: none;
    }

    .lp-pendaftar .lp-chip {
        display: inline-block;
        font-size: .7rem;
        padding: .2rem .55rem;
        border-radius: 999px;
    }

    .lp-pendaftar .lp-chip-diterima     { background: #dcfce7; color: #15803d; }
    .lp-pendaftar .lp-chip-ditolak_kuota { background: #fee2e2; color: #b91c1c; }
    .lp-pendaftar .lp-chip-hadir        { background: #e0e7ff; color: #3730a3; }

    .lp-pendaftar .lp-aksi {
        display: flex;
        gap: .4rem;
        white-space: nowrap;
    }

    .lp-pendaftar .lp-icon-btn {
        border: 1px solid var(--border, #d1d5db);
        background: var(--surface-1, #fff);
        border-radius: 6px;
        width: 30px;
        height: 30px;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        cursor: pointer;
        color: inherit;
    }

    .lp-pendaftar .lp-icon-btn.danger {
        color: #b91c1c;
        border-color: #fecaca;
    }

    .lp-pendaftar .lp-empty {
        text-align: center;
        padding: 3rem 1rem;
        color: var(--text-secondary, #9ca3af);
    }

    .lp-tambah-overlay {
    position: fixed; inset: 0; background: rgba(0,0,0,.5);
    display: flex; align-items: center; justify-content: center;
    z-index: 1000; padding: 20px;
    }
    .lp-tambah-modal {
        background: var(--bg-surface, #fff); border-radius: var(--radius-md, 12px);
        width: 100%; max-width: 440px; max-height: 90vh; overflow-y: auto;
    }   
    .lp-tambah-modal__head {
        display: flex; justify-content: space-between; align-items: center;
        padding: 18px 20px; border-bottom: 1px solid var(--border-color, #e5e7eb);
    }
    .lp-tambah-modal__head h2 { font-size: 16px; font-weight: 700; margin: 0; }
    .lp-tambah-modal__body { padding: 20px; display: flex; flex-direction: column; gap: 14px; }

    .lp-tambah-field { position: relative; }
    .lp-tambah-field label {
        display: block; font-weight: 600; font-size: 13.5px;
        color: var(--text-primary, #111827); margin-bottom: 6px;
    }

    .lp-tambah-dropdown {
        position: absolute; top: 100%; left: 0; right: 0; margin-top: 4px;
        background: var(--bg-surface, #fff); border: 1px solid var(--border-color, #e5e7eb);
        border-radius: var(--radius-sm, 8px); box-shadow: var(--shadow-md, 0 4px 12px rgba(0,0,0,.1));
        max-height: 220px; overflow-y: auto; z-index: 10;
    }
    .lp-tambah-dropdown__item {
        display: flex; flex-direction: column; align-items: flex-start; gap: 2px;
        width: 100%; text-align: left; padding: 10px 12px; background: none; border: none;
        cursor: pointer; border-bottom: 1px solid var(--border-color, #f1f2f4);
    }
    .lp-tambah-dropdown__item:last-child { border-bottom: none; }
    .lp-tambah-dropdown__item:hover { background: var(--bg-surface-alt, #f9fafb); }
    .lp-tambah-dropdown__item span { font-size: 14px; font-weight: 500; color: var(--text-primary); }
    .lp-tambah-dropdown__item small { font-size: 12px; color: var(--text-secondary); }
</style>
@endpush

@section('content')

@php
    $jabatanUser = session('anggota.jabatan_puk', '');
    $bolehHapus_Edit = strcasecmp($jabatanUser, $kegiatan->created_by ?? '') === 0
        || strcasecmp($jabatanUser, 'sekretaris') === 0;
@endphp

<div class="lp-pendaftar" x-data="pendaftarIndex()">

    <div class="lp-page-header" style="margin-bottom: 1rem;">
        <h1 style="font-size: 1.1rem; font-weight: 600; margin: 0;">Pendaftar — {{ $kegiatan->judul }}</h1>
        <p style="font-size: .8125rem; color: var(--text-secondary, #6b7280); margin: .2rem 0 0;">
            {{ $kegiatan->kuota_terisi }} {{ $kegiatan->kuota ? 'dari ' . $kegiatan->kuota : '' }} kursi terisi
        </p>
    </div>

    <div class="lp-toolbar">
        <form method="GET" class="lp-filters">
            <input type="text" name="cari" value="{{ request('cari') }}" placeholder="Cari nama / NIK...">
            <select name="status" onchange="this.form.submit()">
                <option value="">Semua status</option>
                <option value="diterima" @selected(request('status') === 'diterima')>Diterima</option>
                <option value="hadir" @selected(request('status') === 'hadir')>Hadir</option>
                <option value="ditolak_kuota" @selected(request('status') === 'ditolak_kuota')>Ditolak (kuota)</option>
            </select>
            <button type="submit" class="lp-btn-ghost">Cari</button>
        </form>

        <div style="display:flex; gap:.5rem;">
            <a href="{{ route('admin.kegiatan.index') }}" class="lp-btn-ghost">&larr; Kembali</a>
            <a href="{{ route('admin.kegiatan.pendaftar.export', $kegiatan) }}" class="lp-btn-ghost">
                <i class="ti ti-download" aria-hidden="true"></i> Export CSV
            </a>
            @if ($bolehHapus_Edit)
                <button type="button" class="lp-btn-ghost" @click="bukaTambahModal()">
                    <i class="ti ti-user-plus" aria-hidden="true"></i> Tambah Pendaftar
                </button>
            @endif
            <a href="{{ route('admin.kegiatan.pendaftar.scan-page', $kegiatan) }}" class="lp-btn-ghost">
                <i class="ti ti-qrcode" aria-hidden="true"></i> Scan Kehadiran
            </a>
        </div>
    </div>

    <div class="lp-table-wrap">
        <table>
            <thead>
                <tr>
                    <th>#</th>
                    <th>Nama</th>
                    <th>NIK</th>
                    <th>Departemen</th>
                    @foreach ($kegiatan->customFields as $field)
                        <th>{{ $field->label }}</th>
                    @endforeach
                    <th>Status</th>
                    <th>Tgl Daftar</th>
                    <th>Aksi</th>
                </tr>
            </thead>
            <tbody>
                @forelse ($pendaftar as $row)
                    <tr>
                        <td>{{ $loop->iteration + ($pendaftar->currentPage() - 1) * $pendaftar->perPage() }}</td>
                        <td>{{ $row->anggota->nama_lengkap ?? '-' }}</td>
                        <td>{{ $row->anggota->nik_karyawan ?? '-' }}</td>
                        <td>{{ $row->anggota->departemen ?? '-' }}</td>
                        @foreach ($kegiatan->customFields as $field)
                            @php $jawaban = $row->custom_values[$field->id] ?? null; @endphp
                            <td>
                                @if ($field->tipe === 'checkbox')
                                    {{ $jawaban ? 'Ya' : 'Tidak' }}
                                @else
                                    {{ is_array($jawaban) ? implode(', ', $jawaban) : ($jawaban ?: '-') }}
                                @endif
                            </td>
                        @endforeach
                        <td>
                            <span class="lp-chip lp-chip-{{ $row->status }}">
                                {{ str_replace('_', ' ', $row->status) }}
                            </span>
                        </td>
                        <td>{{ $row->created_at->format('d-m-Y') }}</td>
                        <td>
                            <div class="lp-aksi">
                                <form method="POST" action="{{ route('admin.kegiatan.pendaftar.hadir', [$kegiatan, $row]) }}">
                                    @csrf
                                    @method('PATCH')
                                    <button type="submit" class="lp-icon-btn" aria-label="Tandai hadir">
                                        <i class="ti ti-check" aria-hidden="true"></i>
                                    </button>
                                </form>

                                @if ($bolehHapus_Edit)
                                    <button
                                        type="button"
                                        class="lp-icon-btn danger"
                                        aria-label="Hapus pendaftar"
                                        @click="hapus({{ $row->id }}, @js($row->anggota->nama ?? 'pendaftar ini'))"
                                    >
                                        <i class="ti ti-trash" aria-hidden="true"></i>
                                    </button>
                                @endif
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="{{ 7 + $kegiatan->customFields->count() }}" class="lp-empty">
                            Belum ada pendaftar untuk kegiatan ini.
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <div style="margin-top: 1rem;">
        {{ $pendaftar->links() }}
    </div>

    <form :action="deleteUrl" method="POST" x-ref="deleteForm" id="pendaftar-delete-form" style="display:none;">
        @csrf
        @method('DELETE')
    </form>

    <div x-show="showTambahModal" x-cloak class="lp-tambah-overlay" @click.self="tutupTambahModal()" @keydown.escape.window="tutupTambahModal()">
    <div class="lp-tambah-modal">
        <div class="lp-tambah-modal__head">
            <h2>Tambah Pendaftar Manual</h2>
            <button type="button" class="lp-icon-btn" @click="tutupTambahModal()" aria-label="Tutup">
                <i class="ti ti-x" aria-hidden="true"></i>
            </button>
        </div>

        @if (session('error'))
            <p class="lp-form__error" style="margin: 0 20px 12px;">{{ session('error') }}</p>
        @endif

        <form method="POST" action="{{ route('admin.kegiatan.pendaftar.store', $kegiatan) }}" class="lp-tambah-modal__body">
            @csrf
            <input type="hidden" name="anggota_id" :value="selectedAnggota?.id">

            <div class="lp-tambah-field" x-show="!selectedAnggota">
                <label>Cari anggota (nama / NIK)</label>
                <input type="text" class="lp-form__input" x-model="cariQuery" @input="cariAnggota()" placeholder="Ketik minimal 2 huruf...">

                <div class="lp-tambah-dropdown" x-show="cariResults.length > 0">
                    <template x-for="a in cariResults" :key="a.id">
                        <button type="button" class="lp-tambah-dropdown__item" @click="pilihAnggota(a)">
                            <span x-text="a.nama"></span>
                            <small x-text="a.nik_karyawan + (a.departemen ? ' · ' + a.departemen : '')"></small>
                        </button>
                    </template>
                </div>

                <p class="lp-editor-hint" x-show="cariQuery.length >= 2 && !cariLoading && cariResults.length === 0" x-cloak>
                    Tidak ditemukan (atau sudah terdaftar di kegiatan ini).
                </p>
            </div>

            <div class="lp-tambah-field" x-show="selectedAnggota" x-cloak>
                <label>Anggota terpilih</label>
                <div class="lp-kg-detail__readonly" style="display:flex; justify-content:space-between; align-items:center;">
                    <span x-text="selectedAnggota ? selectedAnggota.nama + ' — ' + selectedAnggota.nik_karyawan : ''"></span>
                    <button type="button" style="font-size:12px; color:var(--accent); background:none; border:none; cursor:pointer;" @click="selectedAnggota = null; cariQuery = ''">Ganti</button>
                </div>
            </div>

            @foreach ($kegiatan->customFields as $field)
                <div class="lp-tambah-field">
                    <label>
                        {{ $field->label }}
                        @if ($field->wajib) <span style="color:#b91c1c;">*</span> @endif
                    </label>

                    @if ($field->tipe === 'select')
                        <select name="custom_fields[{{ $field->id }}]" class="lp-form__input">
                            <option value=""disabled selected>— Pilih —</option>
                            @foreach (($field->opsi ?? []) as $opsi)
                                <option value="{{ $opsi }}">{{ $opsi }}</option>
                            @endforeach
                        </select>
                    @elseif ($field->tipe === 'number')
                        <input type="number" name="custom_fields[{{ $field->id }}]" class="lp-form__input">
                    @elseif ($field->tipe === 'checkbox')
                        <label style="display:flex; align-items:center; gap:8px; font-size:14px;">
                            <input type="checkbox" name="custom_fields[{{ $field->id }}]" value="1" style="width:auto;"> Ya
                        </label>
                    @else
                        <input type="text" name="custom_fields[{{ $field->id }}]" class="lp-form__input">
                    @endif
                </div>
            @endforeach

            <button type="submit" class="lp-btn lp-btn--primary" style="width:100%; margin-top:8px;" :disabled="!selectedAnggota">
                Daftarkan
            </button>
        </form>
    </div>
</div>
</div>





@endsection

@push('scripts')
<script>
function pendaftarIndex() {
     return {
        deleteUrl: '',
        urlTemplate: '{{ route('admin.kegiatan.pendaftar.destroy', [$kegiatan, '__ID__']) }}',

        // ===== State tambah pendaftar manual =====
        showTambahModal: false,
        cariQuery: '',
        cariResults: [],
        cariLoading: false,
        selectedAnggota: null,
        _cariTimer: null,

        bukaTambahModal() {
            this.showTambahModal = true;
            this.selectedAnggota = null;
            this.cariQuery = '';
            this.cariResults = [];
        },
        tutupTambahModal() {
            this.showTambahModal = false;
        },
        cariAnggota() {
            clearTimeout(this._cariTimer);

            if (this.cariQuery.trim().length < 2) {
                this.cariResults = [];
                return;
            }

            this._cariTimer = setTimeout(async () => {
                this.cariLoading = true;
                try {
                    const res = await fetch(
                        `{{ route('admin.kegiatan.pendaftar.cari-anggota', $kegiatan) }}?q=` + encodeURIComponent(this.cariQuery)
                    );
                    this.cariResults = await res.json();
                } catch (e) {
                    this.cariResults = [];
                } finally {
                    this.cariLoading = false;
                }
            }, 300);
        },
        pilihAnggota(anggota) {
            this.selectedAnggota = anggota;
            this.cariResults = [];
            this.cariQuery = anggota.nama;
        },

        // ===== Hapus pendaftar (sudah ada sebelumnya) =====
        hapus(id, nama) {
            this.deleteUrl = this.urlTemplate.replace('__ID__', id);

            this.$dispatch('open-confirm', {
                title: 'Hapus pendaftar?',
                message: `Pendaftaran atas nama "${nama}" akan dihapus permanen.`,
                confirmLabel: 'Hapus',
                formId: 'pendaftar-delete-form',
            });
        },
    };
}
</script>
@endpush