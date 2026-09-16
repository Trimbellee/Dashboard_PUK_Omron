@extends('layouts.app')

@section('title', 'Tambah Barang | PUK OMRON')

@push('styles')
    @vite(['resources/css/dashboard/inventaris/create.css'])
@endpush

@section('content')
<div class="inv-form-shell">
    <div id="inv-flash-data" hidden
         data-success="{{ session('success') }}"
         data-error="{{ session('error') }}"></div>

    <div class="inv-header">
        <div class="inv-header-left">
            <div class="inv-icon">➕</div>
            <div>
                <h1 class="inv-title">Tambah Barang</h1>
                <p class="inv-subtitle">Setiap unit dicatat sebagai baris terpisah dengan kode barangnya sendiri</p>
            </div>
        </div>
        <a href="{{ route('inventaris.index') }}" class="btn btn-secondary js-cancel-link">← Kembali</a>
    </div>

    <div class="inv-form-card">
        <form action="{{ route('inventaris.store') }}" method="POST">
            @csrf

            <div class="inv-form-grid">
                <div class="form-group">
                    <label class="form-label" for="nama_barang">Nama Barang <span class="required">*</span></label>
                    <input type="text" id="nama_barang" name="nama_barang"
                           class="form-input @error('nama_barang') is-invalid @enderror"
                           value="{{ old('nama_barang') }}" placeholder="Masukkan nama barang" required>
                    @error('nama_barang') <div class="error-text">{{ $message }}</div> @enderror
                </div>

                <div class="form-group">
                    <label class="form-label" for="type">Type <span class="required">*</span></label>
                    <input type="text" id="type" name="type"
                           class="form-input @error('type') is-invalid @enderror"
                           value="{{ old('type') }}" placeholder="Contoh: Samsung A55, Epson L120" required>
                    @error('type') <div class="error-text">{{ $message }}</div> @enderror
                </div>

                <div class="form-group">
                    <label class="form-label" for="kategori">Kategori <span class="required">*</span></label>
                    <select id="kategori" name="kategori" class="form-input @error('kategori') is-invalid @enderror" required>
                        <option value="">Pilih Kategori</option>
                        @foreach($kategori as $cat)
                            <option value="{{ $cat }}" {{ old('kategori') == $cat ? 'selected' : '' }}>{{ $cat }}</option>
                        @endforeach
                    </select>
                    @error('kategori') <div class="error-text">{{ $message }}</div> @enderror
                </div>

                <div class="form-group">
                    <label class="form-label" for="jumlah_unit">
                        Jumlah Unit Ditambahkan <span class="required">*</span>
                    </label>
                    <input type="number" id="jumlah_unit" name="jumlah_unit"
                           class="form-input @error('jumlah_unit') is-invalid @enderror"
                           value="{{ old('jumlah_unit', 1) }}" min="1" max="50" required>
                    @error('jumlah_unit')
                        <div class="error-text">{{ $message }}</div>
                    @else
                        <div class="text-muted" style="font-size:12px;">
                            Isi lebih dari 1 untuk membuat beberapa unit identik sekaligus — masing-masing
                            akan jadi baris tersendiri dengan kode barang sendiri (bukan satu baris dengan stok banyak).
                        </div>
                    @enderror
                </div>

                <div class="form-group">
                    <label class="form-label" for="kondisi">Kondisi <span class="required">*</span></label>
                    <select id="kondisi" name="kondisi" class="form-input @error('kondisi') is-invalid @enderror" required>
                        <option value="baik" {{ old('kondisi', 'baik') == 'baik' ? 'selected' : '' }}>✅ Baik</option>
                        <option value="rusak" {{ old('kondisi') == 'rusak' ? 'selected' : '' }}>⚠️ Rusak</option>
                        <option value="diperbaiki" {{ old('kondisi') == 'diperbaiki' ? 'selected' : '' }}>🔧 Diperbaiki</option>
                    </select>   
                    @error('kondisi') <div class="error-text">{{ $message }}</div> @enderror
                </div>

                <div class="form-group">
                    <label class="form-label" for="status">Status <span class="required">*</span></label>
                    <select id="status" name="status" class="form-input @error('status') is-invalid @enderror" required>
                        <option value="tersedia" {{ old('status', 'tersedia') == 'tersedia' ? 'selected' : '' }}>✅ Tersedia</option>
                        <option value="dispose" {{ old('status') == 'dispose' ? 'selected' : '' }}>🗑️ Dispose</option>
                        <option value="dijual" {{ old('status') == 'dijual' ? 'selected' : '' }}>💰 Dijual</option>
                        <option value="hilang" {{ old('status') == 'hilang' ? 'selected' : '' }}>❓ Hilang</option>
                    </select>
                    @error('status') <div class="error-text">{{ $message }}</div> @enderror
                </div>

                <div class="form-group" id="kodeBarangGroup">
                    <label class="form-label" for="kode_barang">
                        Kode Barang <span class="optional" id="kodeBarangHint">(opsional, otomatis kalau kosong)</span>
                    </label>
                    <input type="text" id="kode_barang" name="kode_barang"
                           class="form-input @error('kode_barang') is-invalid @enderror"
                           value="{{ old('kode_barang') }}" placeholder="Otomatis dari nama barang">
                    @error('kode_barang')
                        <div class="error-text">{{ $message }}</div>
                    @else
                        <div class="text-muted" style="font-size:12px;" id="kodeBarangNote">
                            Hanya berlaku kalau Jumlah Unit = 1. Untuk lebih dari 1 unit, kode dibuat otomatis
                            per unit supaya tetap unik.
                        </div>
                    @enderror
                </div>

                <div class="form-group">
                    <label class="form-label" for="tanggal_pembelian">Tanggal Pembelian</label>
                    <input type="date" id="tanggal_pembelian" name="tanggal_pembelian"
                           class="form-input @error('tanggal_pembelian') is-invalid @enderror"
                           value="{{ old('tanggal_pembelian') }}">
                    @error('tanggal_pembelian') <div class="error-text">{{ $message }}</div> @enderror
                </div>

                <div class="form-group">
                    <label class="form-label" for="harga_perolehan">Harga Perolehan <span class="optional">(per unit)</span></label>
                    <input type="text" inputmode="numeric" id="harga_perolehan" name="harga_perolehan"
                        class="form-input @error('harga_perolehan') is-invalid @enderror"
                        value="{{ old('harga_perolehan') }}" placeholder="0">
                    @error('harga_perolehan') <div class="error-text">{{ $message }}</div> @enderror
                </div>

                <div class="form-group full-width">
                    <label class="form-label" for="keterangan">Keterangan</label>
                    <textarea id="keterangan" name="keterangan"
                              class="form-input @error('keterangan') is-invalid @enderror" rows="3"
                              placeholder="Tambahkan keterangan jika diperlukan">{{ old('keterangan') }}</textarea>
                    @error('keterangan') <div class="error-text">{{ $message }}</div> @enderror
                </div>
            </div>

            <div class="inv-form-actions">
                <a href="{{ route('inventaris.index') }}" class="btn btn-ghost js-cancel-link">Batal</a>
                <button type="submit" class="btn btn-primary">Simpan Barang</button>
            </div>
        </form>
    </div>
</div>
@endsection

@push('scripts')
    @vite(['resources/js/dashboard/inventaris/create.js'])
@endpush
