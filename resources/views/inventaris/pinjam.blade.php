@extends('layouts.app')

@section('title', 'Form Peminjaman | PUK OMRON')

@push('styles')
    @vite(['resources/css/dashboard/inventaris/pinjam.css'])
@endpush

@section('content')
<div class="inv-form-shell">
    <div id="inv-flash-data" hidden
         data-success="{{ session('success') }}"
         data-error="{{ session('error') }}"></div>

    <div class="inv-header">
        <div class="inv-header-left">
            <div class="inv-icon">📋</div>
            <div>
                <h1 class="inv-title">Form Peminjaman</h1>
                <p class="inv-subtitle">Pinjam satu unit barang berikut</p>
            </div>
        </div>
        <a href="{{ route('inventaris.index') }}" class="btn btn-secondary">← Kembali</a>
    </div>

    <div class="inv-form-card">
        <div class="pinjam-info-panel">
            <h3 class="pinjam-info-title">📦 Informasi Barang</h3>
            <div class="pinjam-info-grid">
                <div class="pinjam-info-item">
                    <span>Nama Barang</span>
                    <div class="value">{{ $barang->nama_barang }}</div>
                </div>
                <div class="pinjam-info-item">
                    <span>Type</span>
                    <div class="value">{{ $barang->type }}</div>
                </div>
                <div class="pinjam-info-item">
                    <span>Kategori</span>
                    <div class="value">{{ $barang->kategori }}</div>
                </div>
                <div class="pinjam-info-item">
                    <span>Kode Barang</span>
                    <div class="value"><span class="kode-badge">{{ $barang->kode_barang ?? '-' }}</span></div>
                </div>
                <div class="pinjam-info-item">
                    <span>Kondisi</span>
                    <div class="value">
                        @php $kondisiColors = ['baik' => 'success', 'rusak' => 'danger', 'perbaikan' => 'warning']; @endphp
                        <span class="badge badge-{{ $kondisiColors[$barang->kondisi] ?? 'secondary' }}">{{ ucfirst($barang->kondisi) }}</span>
                    </div>
                </div>
            </div>
        </div>

        <form action="{{ route('inventaris.pinjam.store') }}" method="POST">
            @csrf
            <input type="hidden" name="barang_id" value="{{ $barang->id }}">

            <div class="inv-form-grid">
                <div class="form-group">
                    <label class="form-label" for="peminjam_nama">Nama Peminjam <span class="required">*</span></label>
                    <input type="text" id="peminjam_nama" name="peminjam_nama"
                           class="form-input @error('peminjam_nama') is-invalid @enderror"
                           value="{{ old('peminjam_nama') }}" placeholder="Masukkan nama peminjam" required>
                    @error('peminjam_nama') <div class="error-text">{{ $message }}</div> @enderror
                </div>

                <div class="form-group">
                    <label class="form-label" for="peminjam_kontak">Kontak Peminjam</label>
                    <input type="text" id="peminjam_kontak" name="peminjam_kontak"
                           class="form-input @error('peminjam_kontak') is-invalid @enderror"
                           value="{{ old('peminjam_kontak') }}" placeholder="No. HP / WA / Email">
                    @error('peminjam_kontak') <div class="error-text">{{ $message }}</div> @enderror
                </div>

                <div class="form-group">
                    <label class="form-label" for="tanggal_pinjam">Tanggal Pinjam <span class="required">*</span></label>
                    <input type="date" id="tanggal_pinjam" name="tanggal_pinjam"
                           class="form-input @error('tanggal_pinjam') is-invalid @enderror"
                           value="{{ old('tanggal_pinjam', date('Y-m-d')) }}" required>
                    @error('tanggal_pinjam') <div class="error-text">{{ $message }}</div> @enderror
                </div>

                <div class="form-group">
                    <label class="form-label" for="tanggal_kembali">Tanggal Kembali <span class="required">*</span></label>
                    <input type="date" id="tanggal_kembali" name="tanggal_kembali"
                           class="form-input @error('tanggal_kembali') is-invalid @enderror"
                           value="{{ old('tanggal_kembali', date('Y-m-d', strtotime('+3 days'))) }}" required>
                    @error('tanggal_kembali') <div class="error-text">{{ $message }}</div> @enderror
                </div>

                <div class="form-group full-width">
                    <label class="form-label" for="keterangan">Keterangan</label>
                    <textarea id="keterangan" name="keterangan"
                              class="form-input @error('keterangan') is-invalid @enderror" rows="3"
                              placeholder="Tambahkan keterangan jika diperlukan">{{ old('keterangan') }}</textarea>
                    @error('keterangan') <div class="error-text">{{ $message }}</div> @enderror
                </div>
            </div>

            <div class="pinjam-officer-bar">
                <span class="label">Diproses oleh:</span>
                <span class="value">{{ session('user')['username'] }} ({{ session('user')['role'] }})</span>
            </div>

            <div class="inv-form-actions">
                <a href="{{ route('inventaris.index') }}" class="btn btn-ghost">Batal</a>
                <button type="submit" class="btn btn-primary">📋 Pinjam Barang</button>
            </div>
        </form>
    </div>
</div>
@endsection

@push('scripts')
    @vite(['resources/js/dashboard/inventaris/pinjam.js'])
@endpush
