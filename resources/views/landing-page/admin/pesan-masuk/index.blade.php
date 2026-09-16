@extends('landing-page.admin.layouts.app')

@push('page-styles')
    @vite(['resources/css/admin/admin-pesan-masuk.css'])
    <style>[x-cloak] { display: none !important; }</style>
@endpush

@section('title', 'Pesan Masuk')

@section('content')

@php
    $isSekretaris = strtolower(trim(session('anggota.jabatan_puk', ''))) === 'sekretaris';
@endphp

<div class="pm-wrapper" x-data="{ detailOpen: false, detail: {} }">

    {{-- HEADER --}}
    <div class="pm-header">
        <div>
            <h1 class="pm-header__title">Pesan Masuk</h1>
            <p class="pm-header__sub">Pesan/aspirasi yang dikirim anggota melalui formulir kontak di landing page.</p>
        </div>
        <div class="pm-stats">
            <span class="pm-stats__item">
                <strong>{{ $pesanMasuk->total() }}</strong> Total
            </span>
            <span class="pm-stats__item pm-stats__item--unread">
                <strong id="pm-unread-count">{{ $totalBelumDibaca }}</strong> Belum dibaca
            </span>
        </div>
    </div>

    {{-- FILTER STATUS --}}
    <div class="pm-filter">
        <a href="{{ route('admin.pesan-masuk.index', ['status' => 'all']) }}"
        class="pm-filter__item {{ $status === 'all' ? 'pm-filter__item--active' : '' }}">
            Semua <span class="pm-filter__count">{{ $totalSemua }}</span>
        </a>
        <a href="{{ route('admin.pesan-masuk.index', ['status' => 'unread']) }}"
        class="pm-filter__item {{ $status === 'unread' ? 'pm-filter__item--active' : '' }}">
            Belum dibaca <span class="pm-filter__count">{{ $totalBelumDibaca }}</span>
        </a>
        <a href="{{ route('admin.pesan-masuk.index', ['status' => 'read']) }}"
        class="pm-filter__item {{ $status === 'read' ? 'pm-filter__item--active' : '' }}">
            Sudah dibaca <span class="pm-filter__count">{{ $totalSudahDibaca }}</span>
        </a>
    </div>

    {{-- TABLE --}}
    <div class="pm-table-wrap">
        <table class="pm-table">
            <thead>
                <tr>
                    <th scope="col" style="width:36px;"></th>
                    <th scope="col" style="width:48px;">No</th>
                    <th scope="col">Pengirim</th>
                    <th scope="col">Email</th>
                    <th scope="col">Pesan</th>
                    <th scope="col">Tanggal</th>
                    @if($isSekretaris)
                        <th scope="col" style="width:80px;">Aksi</th>
                    @endif
                </tr>
            </thead>
            <tbody>
                @forelse ($pesanMasuk as $item)
                <tr class="pm-row {{ $item->is_read ? '' : 'pm-row--unread' }}"
                    id="pm-row-{{ $item->id }}"
                    data-read="{{ $item->is_read ? '1' : '0' }}"
                    @click="detailOpen = true; detail = {
                        nama: @js($item->nama),
                        nik: @js($item->nik_karyawan ?? '-'),
                        departemen: @js($item->departemen ?? '-'),
                        email: @js($item->email),
                        pesan: @js($item->pesan),
                        tanggal: @js($item->created_at->translatedFormat('d F Y, H:i'))
                    };
                    handleRowOpen({{ $item->id }})">
                    
                    <td class="pm-td-dot">
                        <span class="pm-dot {{ $item->is_read ? '' : 'pm-dot--unread' }}" aria-hidden="true"></span>
                    </td>
                    <td class="pm-td-no">{{ $pesanMasuk->firstItem() + $loop->index }}</td>
                        <td class="pm-td-pengirim">
                            <span class="pm-nama">{{ $item->nama }}</span>
                            <span class="pm-subteks">{{ $item->nik_karyawan ?? '-' }} &middot; {{ $item->departemen ?? '-' }}</span>
                        </td>
                        <td class="pm-td-email">{{ $item->email }}</td>
                        <td class="pm-td-pesan">{{ \Illuminate\Support\Str::limit($item->pesan, 60) }}</td>
                        <td class="pm-td-tanggal">{{ $item->created_at->translatedFormat('d M Y, H:i') }}</td>
                        @if($isSekretaris)
                            <td class="pm-td-aksi" @click.stop>
                                <form action="{{ route('admin.pesan-masuk.destroy', $item->id) }}" method="POST" id="delete-pesan-{{ $item->id }}">
                                    @csrf
                                    @method('DELETE')
                                </form>
                                <button type="button" class="pm-btn-hapus"
                                        @click="$dispatch('open-confirm', {
                                            message: 'Hapus pesan dari {{ addslashes($item->nama) }}? Pesan yang dihapus tidak dapat dikembalikan.',
                                            formId: 'delete-pesan-{{ $item->id }}'
                                        })"
                                        aria-label="Hapus pesan">
                                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M3 6h18M8 6V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2m3 0-1 14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2L4 6"/></svg>
                                </button>
                            </td>
                        @endif
                    </tr>
                @empty
                    <tr>
                        <td colspan="{{ $isSekretaris ? 7 : 6 }}" style="text-align:center; padding:40px 0; color:var(--text-secondary);">
                            @if($status === 'unread')
                                Tidak ada pesan yang belum dibaca.
                            @elseif($status === 'read')
                                Belum ada pesan yang sudah dibaca.
                            @else
                                Belum ada pesan masuk.
                            @endif
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <div class="pm-pagination">
        {{ $pesanMasuk->links() }}
    </div>

    {{-- MODAL DETAIL PESAN --}}
    <div x-show="detailOpen" x-cloak x-transition class="pm-modal-overlay" @click.self="detailOpen = false" @keydown.escape.window="detailOpen = false">
        <div class="pm-modal" x-transition>
            <button type="button" class="pm-modal__close" @click="detailOpen = false" aria-label="Tutup">&times;</button>
            <div class="pm-modal__body">
                <span class="pm-modal__label">Dari</span>
                <h3 class="pm-modal__nama" x-text="detail.nama"></h3>
                <p class="pm-modal__meta">
                    <span x-text="detail.nik"></span> &middot;
                    <span x-text="detail.departemen"></span> &middot;
                    <a :href="'mailto:' + detail.email" x-text="detail.email"></a>
                </p>
                <div class="pm-modal__divider"></div>
                <p class="pm-modal__pesan" x-text="detail.pesan"></p>
                <span class="pm-modal__tanggal" x-text="detail.tanggal"></span>
            </div>
        </div>
    </div>
</div>
@endsection
@push('scripts')
<script>
    const csrfMeta = document.querySelector('meta[name="csrf-token"]');
    const csrfToken = csrfMeta ? csrfMeta.content : '';
    const MARK_READ_BASE_URL = '{{ url("landing/admin/pesan-masuk") }}';

    if (!csrfToken) {
        console.error('CSRF meta tag tidak ditemukan di halaman ini.');
    }

    function handleRowOpen(id) {
        const row = document.getElementById(`pm-row-${id}`);
        if (row && row.dataset.read === '0') {
            markAsRead(id);
        }
    }

    function markAsRead(id) {
        const row = document.getElementById(`pm-row-${id}`);
        if (row) row.dataset.read = '1';

        fetch(`${MARK_READ_BASE_URL}/${id}/baca`, {
            method: 'PATCH',
            headers: {
                'X-CSRF-TOKEN': csrfToken,
                'Accept': 'application/json',
            },
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                if (row) {
                    row.classList.remove('pm-row--unread');
                    const dot = row.querySelector('.pm-dot');
                    if (dot) dot.classList.remove('pm-dot--unread');
                }
                const counter = document.getElementById('pm-unread-count');
                if (counter) {
                    const current = parseInt(counter.textContent, 10) || 0;
                    counter.textContent = Math.max(current - 1, 0);
                }
            } else if (row) {
                row.dataset.read = '0';
            }
        })
        .catch((err) => {
            console.error('markAsRead gagal:', err);
            if (row) row.dataset.read = '0';
            if (typeof showToast === 'function') {
                showToast('error', 'Gagal menandai pesan.');
            }
        });
    }
</script>
@endpush