{{--
    resources/views/landing-page/admin/kegiatan/index.blade.php

    List kegiatan admin. Tabular selalu (geser horizontal di layar kecil).
    CSS: resources/css/admin/kegiatan/index.css
    Kolom kuota dibaca dari accessor Kegiatan::kuota_terisi / kuota_penuh / sisa_kuota.
    Aksi hapus/tutup/buka pakai dispatch('open-confirm', ...) dari layout.
--}}

@extends('landing-page.admin.layouts.app')

@section('title', 'Kegiatan')

@push('page-styles')
    @vite('resources/css/admin/kegiatan/index.css')
@endpush

@section('content')
@php
    $jabatanUser = session('anggota.jabatan_puk', '');
@endphp

<div class="kg-index" x-data="kegiatanIndex()">

    <div class="kg-toolbar">
        <form method="GET" class="kg-filters">
            <select name="jenis" onchange="this.form.submit()" aria-label="Filter jenis">
                <option value="">Semua jenis</option>
                @foreach (['pendidikan' => 'Pendidikan', 'seminar' => 'Seminar', 'aksi' => 'Aksi', 'lainnya' => 'Lainnya'] as $value => $label)
                    <option value="{{ $value }}" @selected(request('jenis') === $value)>{{ $label }}</option>
                @endforeach
            </select>

            <select name="status" onchange="this.form.submit()" aria-label="Filter status">
                <option value="">Semua status</option>
                @foreach (['draft' => 'Draft', 'dibuka' => 'Dibuka', 'ditutup' => 'Ditutup', 'selesai' => 'Selesai'] as $value => $label)
                    <option value="{{ $value }}" @selected(request('status') === $value)>{{ $label }}</option>
                @endforeach
            </select>
        </form>

        <a href="{{ route('admin.kegiatan.create') }}" class="kg-btn">
            <i class="ti ti-plus" aria-hidden="true"></i> Tambah Kegiatan
        </a>
    </div>

    <div class="kg-table-wrap">
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
                        $terisi = $item->kuota_terisi;
                        $persen = $item->kuota ? min(100, round($terisi / $item->kuota * 100)) : 0;
                        $barColor = $item->kuota_penuh
                            ? 'var(--kg-bar-full)'
                            : ($persen >= 70 ? 'var(--kg-bar-warn)' : 'var(--kg-bar-ok)');
                        $bolehHapusEdit = strcasecmp($jabatanUser, $item->created_by ?? '') === 0
                            || strcasecmp($jabatanUser, 'sekretaris') === 0;
                    @endphp
                    <tr>
                        <td class="kg-col-no">{{ $loop->iteration + ($kegiatan->currentPage() - 1) * $kegiatan->perPage() }}</td>
                        <td>
                            <div class="kg-judul">{{ $item->judul }}</div>
                            <div class="kg-sub">{{ $item->lokasi ?: '—' }}</div>
                        </td>
                        <td>
                            <span class="kg-badge kg-badge--{{ $item->jenis }}">{{ ucfirst($item->jenis) }}</span>
                            @if ($item->jenis === 'pendidikan' && $item->tingkat_kategori)
                                <div class="kg-sub">
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
                                <div class="kg-sub">s/d {{ $item->tanggal_selesai->translatedFormat('d M Y') }}</div>
                            @endif
                        </td>
                        <td>
                            @if ($item->kuota === null)
                                <span class="kg-kuota-text">Tidak dibatasi ({{ $terisi }} terdaftar)</span>
                            @else
                                <div class="kg-kuota-bar">
                                    <div style="width: {{ $persen }}%; background: {{ $barColor }};"></div>
                                </div>
                                <span class="kg-kuota-text">{{ $terisi }} dari {{ $item->kuota }} kursi</span>
                            @endif
                        </td>
                        <td>
                            <span class="kg-pill kg-pill--{{ $item->status }}">{{ $item->status }}</span>
                        </td>
                        <td>
                            <a class="kg-link" href="{{ route('admin.kegiatan.pendaftar.index', $item) }}">
                                {{ $item->pendaftar_diterima_count }} orang
                            </a>
                        </td>
                        <td>
                            <div class="kg-aksi">
                                @if ($bolehHapusEdit)
                                    <a href="{{ route('admin.kegiatan.edit', $item) }}" class="kg-icon-btn" aria-label="Edit kegiatan" title="Edit">
                                        <i class="ti ti-pencil" aria-hidden="true"></i>
                                    </a>
                                @endif

                                <a href="{{ route('admin.kegiatan.pendaftar.index', $item) }}" class="kg-icon-btn" aria-label="Lihat pendaftar" title="Pendaftar">
                                    <i class="ti ti-users" aria-hidden="true"></i>
                                </a>

                                @if ($bolehHapusEdit && $item->status === 'dibuka')
                                    <form method="POST" action="{{ route('admin.kegiatan.tutup', $item) }}" id="tutup-form-{{ $item->id }}" style="display:none;">
                                        @csrf
                                        @method('PATCH')
                                    </form>
                                    <button type="button" class="kg-icon-btn" aria-label="Tutup pendaftaran" title="Tutup pendaftaran"
                                        @click="$dispatch('open-confirm', {
                                            message: @js('Pendaftaran kegiatan "' . $item->judul . '" akan ditutup sekarang.'),
                                            formId: 'tutup-form-{{ $item->id }}',
                                            confirmLabel: 'Tutup',
                                            variant: 'danger',
                                        })">
                                        <i class="ti ti-lock" aria-hidden="true"></i>
                                    </button>
                                @endif

                                @if ($bolehHapusEdit && $item->status === 'ditutup')
                                    <form method="POST" action="{{ route('admin.kegiatan.buka', $item) }}" id="buka-form-{{ $item->id }}" style="display:none;">
                                        @csrf
                                        @method('PATCH')
                                    </form>
                                    <button type="button" class="kg-icon-btn" aria-label="Buka kembali pendaftaran" title="Buka kembali"
                                        @click="$dispatch('open-confirm', {
                                            message: @js('Pendaftaran kegiatan "' . $item->judul . '" akan dibuka kembali.'),
                                            formId: 'buka-form-{{ $item->id }}',
                                            confirmLabel: 'Buka Kembali',
                                            variant: 'primary',
                                        })">
                                        <i class="ti ti-lock-open" aria-hidden="true"></i>
                                    </button>
                                @endif

                                @if ($bolehHapusEdit)
                                    <button type="button" class="kg-icon-btn kg-icon-btn--danger" aria-label="Hapus kegiatan" title="Hapus"
                                        @click="hapus('{{ $item->slug }}', @js($item->judul))">
                                        <i class="ti ti-trash" aria-hidden="true"></i>
                                    </button>
                                @endif
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="8" class="kg-empty">Belum ada kegiatan. Klik "Tambah Kegiatan" untuk membuat yang pertama.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <div class="kg-pager">
        {{ $kegiatan->withQueryString()->links() }}
    </div>

    {{-- Form tersembunyi, di-submit setelah konfirmasi dari komponen global open-confirm. --}}
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