@extends('landing-page.admin.layouts.app')

@push('page-styles')
    @vite('resources/css/admin/admin-absensi.css')
@endpush


@section('title', 'Daftar Hadir Kegiatan')

@section('content')


<div class="abs-wrap">

    <div class="abs-header">
        <div>
            <h1>Daftar Hadir Kegiatan</h1>
            <p>Absensi digital untuk kegiatan tanpa pendaftaran (konsolidasi, rapat umum, dll).</p>
        </div>
        <a href="{{ route('admin.sesi-absensi.create') }}" class="lp-btn lp-btn--primary">+ Buat Sesi Baru</a>
    </div>

    @if(session('success'))
        <div class="abs-alert abs-alert--success">{{ session('success') }}</div>
    @endif
    @if(session('error'))
        <div class="abs-alert abs-alert--error">{{ session('error') }}</div>
    @endif

    <div class="abs-table-wrap">
        <table class="abs-table">
            <thead>
                <tr>
                    <th class="abs-col-no">No.</th>
                    <th>Judul</th>
                    <th>Lokasi</th>
                    <th>Status</th>
                    <th>Hadir</th>
                    <th>Dibuat</th>
                    <th></th>
                </tr>
            </thead>
            <tbody>
                @forelse($sesiList as $sesi)
                <tr>
                    <td class="abs-col-no">{{ $loop->iteration + ($sesiList->currentPage() - 1) * $sesiList->perPage() }}</td>
                    <td style="font-weight:600;">{{ $sesi->judul }}</td>
                    <td style="color:var(--text-secondary);">{{ $sesi->lokasi ?: '—' }}</td>
                    <td>
                        @if($sesi->status === 'buka')
                            <span class="abs-pill abs-pill--buka">Buka</span>
                        @else
                            <span class="abs-pill abs-pill--tutup">Tutup</span>
                        @endif
                    </td>
                    <td style="color:var(--text-secondary);">{{ $sesi->entri_count }} orang</td>
                    <td style="white-space:nowrap;">
                        <div style="font-size:.85rem;color:var(--text-primary);font-weight:600;">{{ $sesi->dibuat_oleh ?: '—' }}</div>
                        <div style="font-size:.78rem;color:var(--text-secondary);margin-top:2px;">{{ $sesi->created_at->translatedFormat('d M Y, H:i') }}</div>
                    </td>
                    <td style="text-align:right;">
                        <a href="{{ route('admin.sesi-absensi.show', $sesi) }}" class="abs-icon-link">Buka →</a>
                    </td>
                </tr>
                @empty
                <tr>
                    <td colspan="7" class="abs-empty">Belum ada sesi absensi.</td>
                </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    @if($sesiList->hasPages())
        <div class="lp-pagination">{{ $sesiList->links() }}</div>
    @endif

</div>

@endsection