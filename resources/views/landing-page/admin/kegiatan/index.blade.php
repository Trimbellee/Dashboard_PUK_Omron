{{--
    resources/views/landing-page/admin/kegiatan/index.blade.php

    List kegiatan admin. Tabular selalu (tanpa card-collapse di mobile),
    mengikuti pola yang sudah dipakai di list Pesan Masuk. Kolom kuota
    dibaca dari accessor Kegiatan::kuota_terisi / kuota_penuh / sisa_kuota.

    Delete pakai dispatch('open-confirm', ...) — pola yang sama dengan
    struktur/node.blade.php, bukan window.confirm() native.
--}}

@extends('landing-page.admin.layouts.app')

@section('title', 'Kegiatan')

@push('page-styles')
<style>
    .lp-kegiatan-index .lp-toolbar {
        display: flex;
        flex-wrap: wrap;
        gap: .6rem;
        align-items: center;
        justify-content: space-between;
        margin-bottom: 1rem;
    }

    .lp-kegiatan-index .lp-filters {
        display: flex;
        flex-wrap: wrap;
        gap: .5rem;
    }

    .lp-kegiatan-index .lp-filters select {
        border: 1px solid var(--border, #d1d5db);
        border-radius: 8px;
        padding: .45rem .65rem;
        font-size: .8125rem;
        background: var(--surface-1, #fff);
    }

    .lp-kegiatan-index .lp-btn-primary {
        background: var(--fill-primary, #4f46e5);
        color: #fff;
        border: none;
        border-radius: 8px;
        padding: .55rem 1rem;
        font-size: .8125rem;
        text-decoration: none;
        display: inline-flex;
        align-items: center;
        gap: .35rem;
    }

    .lp-kegiatan-index .lp-table-wrap {
        background: var(--surface-2, #fff);
        border: 1px solid var(--border, #e5e7eb);
        border-radius: 12px;
        overflow-x: auto;
    }

    .lp-kegiatan-index table {
        width: 100%;
        min-width: 920px;
        border-collapse: collapse;
        font-size: .8125rem;
    }

    .lp-kegiatan-index thead th {
        text-align: left;
        font-size: .72rem;
        letter-spacing: .03em;
        color: var(--text-secondary, #6b7280);
        padding: .75rem .9rem;
        border-bottom: 1px solid var(--border, #e5e7eb);
        white-space: nowrap;
    }

    .lp-kegiatan-index tbody td {
        padding: .75rem .9rem;
        border-bottom: 1px solid var(--border, #f1f2f4);
        vertical-align: middle;
    }

    .lp-kegiatan-index tbody tr:last-child td {
        border-bottom: none;
    }

    .lp-kegiatan-index .lp-judul {
        font-weight: 500;
    }

    .lp-kegiatan-index .lp-sub {
        font-size: .72rem;
        color: var(--text-secondary, #9ca3af);
        margin-top: .15rem;
    }

    .lp-kegiatan-index .lp-badge {
        display: inline-block;
        font-size: .7rem;
        padding: .2rem .55rem;
        border-radius: 999px;
        white-space: nowrap;
    }

    .lp-kegiatan-index .lp-badge-pendidikan { background: var(--bg-tint-violet, #ede9fe); color: var(--text-tint-violet, #6d28d9); }
    .lp-kegiatan-index .lp-badge-seminar    { background: var(--bg-tint-aqua, #cffafe);   color: var(--text-tint-aqua, #0e7490); }
    .lp-kegiatan-index .lp-badge-aksi       { background: var(--bg-tint-orange, #ffedd5); color: var(--text-tint-orange, #c2410c); }
    .lp-kegiatan-index .lp-badge-lainnya    { background: var(--surface-1, #f3f4f6);      color: var(--text-secondary, #4b5563); }

    .lp-kegiatan-index .lp-pill {
        display: inline-block;
        font-size: .7rem;
        padding: .2rem .55rem;
        border-radius: 999px;
        text-transform: capitalize;
    }

    .lp-kegiatan-index .lp-pill-draft   { background: #f3f4f6; color: #4b5563; }
    .lp-kegiatan-index .lp-pill-dibuka  { background: #dcfce7; color: #15803d; }
    .lp-kegiatan-index .lp-pill-ditutup { background: #fee2e2; color: #b91c1c; }
    .lp-kegiatan-index .lp-pill-selesai { background: #e0e7ff; color: #3730a3; }

    .lp-kegiatan-index .lp-kuota-bar {
        width: 90px;
        height: 6px;
        background: var(--surface-1, #f1f2f4);
        border-radius: 4px;
        overflow: hidden;
        margin-bottom: .3rem;
    }

    .lp-kegiatan-index .lp-kuota-bar > div {
        height: 100%;
    }

    .lp-kegiatan-index .lp-kuota-text {
        font-size: .72rem;
        color: var(--text-secondary, #6b7280);
    }

    .lp-kegiatan-index .lp-aksi {
        display: flex;
        gap: .4rem;
        white-space: nowrap;
    }

    .lp-kegiatan-index .lp-icon-btn {
        border: 1px solid var(--border, #d1d5db);
        background: var(--surface-1, #fff);
        border-radius: 6px;
        width: 30px;
        height: 30px;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        text-decoration: none;
        color: inherit;
        cursor: pointer;
    }

    .lp-kegiatan-index .lp-icon-btn.danger {
        color: #b91c1c;
        border-color: #fecaca;
    }

    .lp-kegiatan-index .lp-empty {
        text-align: center;
        padding: 3rem 1rem;
        color: var(--text-secondary, #9ca3af);
    }
</style>
@endpush

@section('content')
<div class="lp-kegiatan-index" x-data="kegiatanIndex()">

    <div class="lp-toolbar">
        <form method="GET" class="lp-filters">
            <select name="jenis" onchange="this.form.submit()">
                <option value="">Semua jenis</option>
                @foreach (['pendidikan' => 'Pendidikan', 'seminar' => 'Seminar', 'aksi' => 'Aksi', 'lainnya' => 'Lainnya'] as $value => $label)
                    <option value="{{ $value }}" @selected(request('jenis') === $value)>{{ $label }}</option>
                @endforeach
            </select>

            <select name="status" onchange="this.form.submit()">
                <option value="">Semua status</option>
                @foreach (['draft' => 'Draft', 'dibuka' => 'Dibuka', 'ditutup' => 'Ditutup', 'selesai' => 'Selesai'] as $value => $label)
                    <option value="{{ $value }}" @selected(request('status') === $value)>{{ $label }}</option>
                @endforeach
            </select>
        </form>

        <a href="{{ route('admin.kegiatan.create') }}" class="lp-btn-primary">
            <i class="ti ti-plus" aria-hidden="true"></i> Tambah Kegiatan
        </a>
    </div>

    <div class="lp-table-wrap">
        <table>
            <thead>
                <tr>
                    <th>No.</th>
                    <th>Kegiatan</th>
                    <th>Jenis / Tingkat</th>
                    <th>Tanggal</th>
                    <th>Kuota</th>
                    <th>Status</th>
                    <th>Pendaftar</th>
                    <th>Aksi</th>
                </tr>
            </thead>
            <tbody>
                @forelse ($kegiatan as $item)
                    @php
                        $sisaKuota = $item->sisa_kuota;
                        $terisi = $item->kuota_terisi;
                        $persen = $item->kuota ? min(100, round($terisi / $item->kuota * 100)) : 0;
                        $barColor = $item->kuota_penuh ? '#dc2626' : ($persen >= 70 ? '#f59e0b' : '#16a34a');
                    @endphp
                    <tr>
                        <td>{{ $loop->iteration + ($kegiatan->currentPage() - 1) * $kegiatan->perPage() }}</td>
                        <td>
                            <div class="lp-judul">{{ $item->judul }}</div>
                            <div class="lp-sub">{{ $item->lokasi ?: '—' }}</div>
                        </td>
                        <td>
                            <span class="lp-badge lp-badge-{{ $item->jenis }}">
                                {{ ucfirst($item->jenis) }}
                            </span>
                            @if ($item->jenis === 'pendidikan' && $item->tingkat_kategori)
                                <div class="lp-sub">
                                    {{ ucfirst($item->tingkat_kategori) }}
                                    @if ($item->tingkat_keterangan)
                                        · {{ $item->tingkat_keterangan }}
                                    @endif
                                </div>
                            @endif
                        </td>
                        <td>
                            {{ $item->tanggal_mulai->translatedFormat('d M Y') }}
                            @if ($item->tanggal_selesai && !$item->tanggal_selesai->equalTo($item->tanggal_mulai))
                                <div class="lp-sub">s/d {{ $item->tanggal_selesai->translatedFormat('d M Y') }}</div>
                            @endif
                        </td>
                        <td>
                            @if ($item->kuota === null)
                                <span class="lp-kuota-text">Tidak dibatasi ({{ $terisi }} terdaftar)</span>
                            @else
                                <div class="lp-kuota-bar">
                                    <div style="width: {{ $persen }}%; background: {{ $barColor }};"></div>
                                </div>
                                <span class="lp-kuota-text">{{ $terisi }} dari {{ $item->kuota }} kursi</span>
                            @endif
                        </td>
                        <td>
                            <span class="lp-pill lp-pill-{{ $item->status }}">{{ $item->status }}</span>
                        </td>
                        <td>
                            <a href="{{ route('admin.kegiatan.pendaftar.index', $item) }}">
                                {{ $item->pendaftar_diterima_count }} orang
                            </a>
                        </td>
                        <td>
                            <div class="lp-aksi">
                @php
                    $jabatanUser = session('anggota.jabatan_puk', '');
                    $bolehHapus_Edit = strcasecmp($jabatanUser, $item->created_by ?? '') === 0
                        || strcasecmp($jabatanUser, 'sekretaris') === 0;
                @endphp

                @if ($bolehHapus_Edit)
                <a href="{{ route('admin.kegiatan.edit', $item) }}" class="lp-icon-btn" aria-label="Edit kegiatan">
                    <i class="ti ti-pencil" aria-hidden="true"></i>
                </a>
                @endif
                <a href="{{ route('admin.kegiatan.pendaftar.index', $item) }}" class="lp-icon-btn" aria-label="Lihat pendaftar">
                    <i class="ti ti-users" aria-hidden="true"></i>
                </a>

                @if ($bolehHapus_Edit && $item->status === 'dibuka')
                    <form method="POST" action="{{ route('admin.kegiatan.tutup', $item) }}" id="tutup-form-{{ $item->id }}" style="display:none;">
                        @csrf
                        @method('PATCH')
                    </form>
                    <button
                        type="button"
                        class="lp-icon-btn"
                        aria-label="Tutup pendaftaran"
                        @click="$dispatch('open-confirm', {
                            message: 'Pendaftaran kegiatan &quot;{{ $item->judul }}&quot; akan ditutup sekarang.',
                            formId: 'tutup-form-{{ $item->id }}',
                            confirmLabel: 'Tutup',
                            variant: 'danger',
                        })"
                    >
                        <i class="ti ti-lock" aria-hidden="true"></i>
                    </button>
                @endif

                @if ($bolehHapus_Edit && $item->status === 'ditutup')
                    <form method="POST" action="{{ route('admin.kegiatan.buka', $item) }}" id="buka-form-{{ $item->id }}" style="display:none;">
                        @csrf
                        @method('PATCH')
                    </form>
                    <button
                        type="button"
                        class="lp-icon-btn"
                        aria-label="Buka kembali pendaftaran"
                        @click="$dispatch('open-confirm', {
                            message: 'Pendaftaran kegiatan &quot;{{ $item->judul }}&quot; akan dibuka kembali.',
                            formId: 'buka-form-{{ $item->id }}',
                            confirmLabel: 'Buka Kembali',
                            variant: 'primary',
                        })"
                    >
                        <i class="ti ti-lock-open" aria-hidden="true"></i>
                    </button>
                @endif

                @if ($bolehHapus_Edit)
                    <button
                        type="button"
                        class="lp-icon-btn danger"
                        aria-label="Hapus kegiatan"
                        @click="hapus('{{ $item->slug }}', @js($item->judul))"
                    >
                        <i class="ti ti-trash" aria-hidden="true"></i>
                    </button>
                @endif
            </div>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="8" class="lp-empty">Belum ada kegiatan. Klik "Tambah Kegiatan" untuk membuat yang pertama.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <div style="margin-top: 1rem;">
        {{ $kegiatan->withQueryString()->links() }}
    </div>

    {{-- Form tersembunyi yang di-submit lewat JS setelah konfirmasi diterima
         dari komponen global open-confirm (lihat struktur/node.blade.php). --}}
    <form :action="deleteUrl" method="POST" id="kegiatan-delete-form" style="display:none;">
        @csrf
        @method('DELETE')
    </form>
</div>
@endsection

@push('scripts')
<script>
function kegiatanIndex() {
    return {
        deleteUrl: '',
        urlTemplate: '{{ route('admin.kegiatan.destroy', '__SLUG__') }}',

        hapus(slug, judul) {
            this.deleteUrl = this.urlTemplate.replace('__SLUG__', slug);

            this.$dispatch('open-confirm', {
                message: `Kegiatan "${judul}" beserta seluruh data pendaftarnya akan dihapus permanen.`,
                formId: 'kegiatan-delete-form',
                confirmLabel: 'Hapus',
                variant: 'danger',
            });
        },
    };
}
</script>
@endpush