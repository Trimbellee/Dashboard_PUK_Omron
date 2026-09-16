@extends('layouts.app')

@section('title', 'Edit Barang | PUK OMRON')

@push('styles')
    @vite(['resources/css/dashboard/inventaris/edit.css'])
@endpush

@section('content')
<div class="inv-form-shell">
    <div id="inv-flash-data" hidden
         data-success="{{ session('success') }}"
         data-error="{{ session('error') }}"></div>

    <div class="inv-header">
        <div class="inv-header-left">
            <div class="inv-icon">✏️</div>
            <div>
                <h1 class="inv-title">Edit Barang</h1>
                <p class="inv-subtitle inv-form-subtitle">
                    Unit: <strong>{{ $barang->nama_barang }}</strong>
                    @if($barang->kode_barang) <span class="kode-badge">{{ $barang->kode_barang }}</span> @endif
                </p>
            </div>
        </div>
        <a href="{{ route('inventaris.index') }}" class="btn btn-secondary js-cancel-link">← Kembali</a>
    </div>

    <div class="inv-form-card">
        <form action="{{ route('inventaris.update', $barang->id) }}" method="POST">
            @csrf
            @method('PUT')

            <div class="inv-form-grid">
                <div class="form-group">
                    <label class="form-label" for="nama_barang">Nama Barang <span class="required">*</span></label>
                    <input type="text" id="nama_barang" name="nama_barang"
                           class="form-input @error('nama_barang') is-invalid @enderror"
                           value="{{ old('nama_barang', $barang->nama_barang) }}" required>
                    @error('nama_barang') <div class="error-text">{{ $message }}</div> @enderror
                </div>

                <div class="form-group">
                    <label class="form-label" for="type">Type <span class="required">*</span></label>
                    <input type="text" id="type" name="type"
                           class="form-input @error('type') is-invalid @enderror"
                           value="{{ old('type', $barang->type) }}" required>
                    @error('type') <div class="error-text">{{ $message }}</div> @enderror
                </div>

                <div class="form-group">
                    <label class="form-label" for="kategori">Kategori <span class="required">*</span></label>
                    <select id="kategori" name="kategori" class="form-input @error('kategori') is-invalid @enderror" required>
                        <option value="">Pilih Kategori</option>
                        @foreach($kategori as $cat)
                            <option value="{{ $cat }}" {{ old('kategori', $barang->kategori) == $cat ? 'selected' : '' }}>{{ $cat }}</option>
                        @endforeach
                    </select>
                    @error('kategori') <div class="error-text">{{ $message }}</div> @enderror
                </div>

                <div class="form-group">
                    <label class="form-label" for="kondisi">Kondisi <span class="required">*</span></label>
                    <select id="kondisi" name="kondisi" class="form-input @error('kondisi') is-invalid @enderror" required>
                        <option value="baik" {{ old('kondisi', $barang->kondisi) == 'baik' ? 'selected' : '' }}>✅ Baik</option>
                        <option value="rusak" {{ old('kondisi', $barang->kondisi) == 'rusak' ? 'selected' : '' }}>⚠️ Rusak</option>
                        <option value="diperbaiki" {{ old('kondisi', $barang->kondisi) == 'diperbaiki' ? 'selected' : '' }}>🔧 Diperbaiki</option>
                    </select>
                    @error('kondisi') <div class="error-text">{{ $message }}</div> @enderror
                </div>

                <div class="form-group">
                    <label class="form-label" for="status">Status <span class="required">*</span></label>
                    <select id="status" name="status" class="form-input @error('status') is-invalid @enderror" required>
                        <option value="tersedia" {{ old('status', $barang->status) == 'tersedia' ? 'selected' : '' }}>✅ Tersedia</option>
                        <option value="dispose" {{ old('status', $barang->status) == 'dispose' ? 'selected' : '' }}>🗑️ Dispose</option>
                        <option value="dijual" {{ old('status', $barang->status) == 'dijual' ? 'selected' : '' }}>💰 Dijual</option>
                        <option value="hilang" {{ old('status', $barang->status) == 'hilang' ? 'selected' : '' }}>❓ Hilang</option>
                    </select>
                    @error('status')
                        <div class="error-text">{{ $message }}</div>
                    @else
                        <div class="text-muted" style="font-size:12px;">Status tidak lagi diubah otomatis oleh proses pinjam/kembali — ubah manual di sini kalau perlu.</div>
                    @enderror
                </div>

                <div class="form-group">
                    <label class="form-label" for="kode_barang">Kode Barang</label>
                    <input type="text" id="kode_barang" name="kode_barang"
                           class="form-input @error('kode_barang') is-invalid @enderror"
                           value="{{ old('kode_barang', $barang->kode_barang) }}" placeholder="Opsional">
                    @error('kode_barang') <div class="error-text">{{ $message }}</div> @enderror
                </div>

                <div class="form-group">
                    <label class="form-label" for="tanggal_pembelian">Tanggal Pembelian</label>
                    <input type="date" id="tanggal_pembelian" name="tanggal_pembelian"
                           class="form-input @error('tanggal_pembelian') is-invalid @enderror"
                           value="{{ old('tanggal_pembelian', $barang->tanggal_pembelian ? $barang->tanggal_pembelian->format('Y-m-d') : '') }}">
                    @error('tanggal_pembelian') <div class="error-text">{{ $message }}</div> @enderror
                </div>

                <div class="form-group">
                    <label class="form-label" for="harga_perolehan">Harga Perolehan</label>
                    <input type="text" inputmode="numeric" id="harga_perolehan" name="harga_perolehan"
                        class="form-input @error('harga_perolehan') is-invalid @enderror"
                        value="{{ old('harga_perolehan', $barang->harga_perolehan) }}">
                    @error('harga_perolehan') <div class="error-text">{{ $message }}</div> @enderror
                </div>

                <div class="form-group full-width">
                    <label class="form-label" for="keterangan">Keterangan</label>
                    <textarea id="keterangan" name="keterangan"
                              class="form-input @error('keterangan') is-invalid @enderror"
                              rows="3">{{ old('keterangan', $barang->keterangan) }}</textarea>
                    @error('keterangan') <div class="error-text">{{ $message }}</div> @enderror
                </div>
            </div>

            <div class="inv-form-actions">
                <a href="{{ route('inventaris.index') }}" class="btn btn-ghost js-cancel-link">Batal</a>
                <button type="submit" class="btn btn-primary">Update Barang</button>
            </div>
        </form>
    </div>
</div>
@endsection

@push('scripts')
    @vite(['resources/js/dashboard/inventaris/edit.js'])
@endpush
