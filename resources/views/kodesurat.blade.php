@extends('layouts.app')

@section('title', 'Kode Penomoran Surat')

@vite(['resources/css/dashboard/kode-surat.css', 'resources/js/dashboard/kode-surat.js'])

@section('content')

{{-- id="ksRoot" membawa URL route ke file JS eksternal via data-attribute --}}
<div id="ksRoot"
     class="ks-wrapper"
     data-generate-url="{{ route('kode-surat.generate') }}"
     data-data-url="{{ route('kode-surat.data') }}">

    {{-- ── FORM GENERATE ─────────────────────────────────────── --}}
    <div class="ks-card">

        <h2 class="ks-card-title">
            Kode Penomoran Surat
        </h2>

        <form id="formKode" class="ks-form">
            @csrf

            {{-- JENIS SURAT --}}
            <div class="form-group" id="wrapperJenis">
                <label class="form-label">Jenis Surat</label>
                <select name="jenis" required class="form-input">
                    <option value="" disabled selected>Pilih Jenis Surat</option>
                    <option value="IN">Internal</option>
                    <option value="EKS">Eksternal</option>
                </select>
            </div>

            {{-- BIDANG --}}
            <div class="form-group">
                <label class="form-label">Bidang</label>
                <select name="bidang" required class="form-input" id="selectBidang">
                    <option value="" disabled selected>Pilih Bidang</option>
                    <option value="SEK">Sekretaris</option>
                    <option value="BID.1">Bidang 1</option>
                    <option value="BID.2">Bidang 2</option>
                    <option value="BID.3">Bidang 3</option>
                    <option value="BID.4">Bidang 4</option>
                    <option value="BID.5">Bidang 5</option>
                    <option value="BID.6">Bidang 6</option>
                    <option value="GM">Garda Metal</option>
                    <option value="KORLAP">KORLAP</option>
                    <option value="SK">Surat Keputusan</option>
                </select>
            </div>

            {{-- BULAN --}}
            <div class="form-group">
                <label class="form-label">Bulan</label>
                <select name="bulan" required class="form-input">
                    <option value="" disabled selected>Pilih Bulan</option>
                    @for($i=1;$i<=12;$i++)
                        <option value="{{ $i }}">{{ $i }}</option>
                    @endfor
                </select>
            </div>

            {{-- TAHUN --}}
            @php $tahunSekarang = date('Y'); @endphp
            <div class="form-group">
                <label class="form-label">Tahun</label>
                <select name="tahun" required class="form-input">
                    <option value="{{ $tahunSekarang - 1 }}">{{ $tahunSekarang - 1 }}</option>
                    <option value="{{ $tahunSekarang }}" selected>{{ $tahunSekarang }}</option>
                    <option value="{{ $tahunSekarang + 1 }}">{{ $tahunSekarang + 1 }}</option>
                </select>
            </div>

            {{-- KETERANGAN --}}
            <div class="form-group ks-form-group--full">
                <label class="form-label">Keterangan</label>
                <input type="text" name="keterangan" required class="form-input"
                    placeholder="Contoh: Proposal Pendidikan Dasar">
            </div>

            {{-- BUTTON --}}
            <div class="ks-form-group--full">
                <button type="submit" id="btnGenerate" class="btn btn-primary">
                    <div id="spinner" class="spinner" style="display:none;"></div>
                    <span id="btnText">Generate</span>
                </button>
            </div>
        </form>

        {{-- HASIL --}}
        <div id="hasilBox" class="ks-result-box">
            <label class="form-label">Nomor Surat:</label>
            <div class="ks-result-row">
                <div id="hasilKode" class="ks-result-code"></div>
                <button id="btnCopy" class="btn btn-ghost">Copy</button>
            </div>
            <p id="copyInfo" class="ks-copy-info">
                ✓ Nomor surat berhasil disalin
            </p>
        </div>
    </div>

    {{-- ── TABLE RIWAYAT ──────────────────────────────────────── --}}
    <div class="ks-card ks-card--history">

        <h3 class="ks-card-title ks-card-title--sm">
            Riwayat Kode Surat
        </h3>

        {{-- FILTER --}}
        <div class="ks-filter-grid">

            <div class="form-group">
                <label class="form-label">Search</label>
                <input type="text" id="searchText" placeholder="Cari no surat..."
                    class="form-input">
            </div>

            <div class="form-group">
                <label class="form-label">Filter Bulan</label>
                <select id="filterBulan" class="form-input">
                    <option value="">Semua Bulan</option>
                    @for($i=1;$i<=12;$i++)
                        <option value="{{ $i }}">{{ $i }}</option>
                    @endfor
                </select>
            </div>

            <div class="form-group">
                <label class="form-label">Filter Tahun</label>
                <input type="number" id="filterTahun" placeholder="{{ date('Y') }}"
                    class="form-input">
            </div>

            <div class="form-group ks-filter-btn-group">
                <button id="btnFilter" class="btn btn-primary ks-filter-btn">
                    <div id="filterSpinner" class="spinner" style="display:none;"></div>
                    <span id="filterText">Terapkan Filter</span>
                </button>
            </div>
        </div>

        <div class="ks-table-wrapper">
            <table class="ks-table">
                <thead>
                    <tr>
                        <th>No</th>
                        <th>No Surat</th>
                        <th>Keterangan</th>
                        <th>Tanggal</th>
                    </tr>
                </thead>
                <tbody id="tableBody"></tbody>
            </table>
        </div>

        <div class="ks-footer-row">
            <div id="pageInfo" class="ks-page-info"></div>
            <div id="pagination" class="ks-pagination"></div>
        </div>
    </div>

</div>

@endsection