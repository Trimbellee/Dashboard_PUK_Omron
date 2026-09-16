{{-- ===== GRAFIK KEUANGAN (#keuangan) — khusus anggota yang login ===== --}}
@if (session('anggota'))
<section id="keuangan" class="lp-section lp-section--alt">
    <div class="lp-container">
        <div class="lp-section__head-row animate-on-scroll">
            <div class="lp-section__head lp-section__head--left">
                <span class="lp-section__eyebrow">💰 Transparansi Keuangan</span>
                <h2 class="lp-section__title" style="font-size: 32px;">Dana Masuk, Dana Keluar &amp; Breakdown Dana</h2>
                <p class="lp-section__desc" style="max-width: 600px; margin-top: 4px; font-size: 16px;">
                    Ringkasan laporan keuangan PUK yang sudah selesai diverifikasi.
                </p>
            </div>

            <div class="lp-keu__year">
                <label for="lpKeuTahun">Tahun</label>
                <select id="lpKeuTahun">
                    @foreach ($tahunTersediaKeuangan as $t)
                        <option value="{{ $t }}" {{ $t === $tahunAktifKeuangan ? 'selected' : '' }}>{{ $t }}</option>
                    @endforeach
                </select>
            </div>
        </div>

        <div id="lpKeuLoading" class="lp-keu__loading" style="display:none;">Memuat data...</div>

        {{-- ── GRAFIK DEBIT VS KREDIT ── --}}
        <div class="lp-keu-card animate-on-scroll">
            <div class="lp-keu-card__header">
                <div>
                    <span class="lp-keu-card__title" id="lpKeuTrendTitle">Dana Masuk vs Dana Keluar per Jenis Dana</span>
                    <span id="lpKeuBadge" class="lp-keu-badge">📋 Memuat info laporan...</span>
                </div>
                <div class="lp-keu-legend">
                    <span><i style="background:#3d9a5e;"></i>Dana Masuk</span>
                    <span><i style="background:#e24b4a;"></i>Dana Keluar</span>
                    <span><i class="lp-keu-legend__line" style="background:#7f77dd;"></i>Saldo akhir</span>
                </div>
            </div>
            <div class="lp-keu-chart-wrap" style="height:320px;">
                <canvas id="lpKeuTrendChart"></canvas>
            </div>
        </div>

        {{-- ── BREAKDOWN PER JENIS ── --}}
        <div class="lp-keu-card animate-on-scroll delay-2">
            <div class="lp-keu-card__header">
                <span class="lp-keu-card__title">Breakdown per Jenis Dana</span>
                <select id="lpKeuJenisFilter" class="lp-keu-select"></select>
            </div>
            <div id="lpKeuJenisStats" class="lp-keu-stats"></div>
            <div class="lp-keu-chart-wrap" style="height:320px;">
                <canvas id="lpKeuBreakdownChart"></canvas>
            </div>
            <p class="lp-keu__hint">Klik batang <strong>Dana Keluar</strong> pada grafik untuk melihat rincian.</p>
        </div>
    </div>
</section>

{{-- ── MODAL: RINGKASAN KAS (klik titik pada grafik tren) ── --}}
<div id="lpKasModal" class="lp-keu-modal-overlay" style="display:none;">
    <div class="lp-keu-modal">
        <div class="lp-keu-modal__header">
            <div>
                <div class="lp-keu-modal__title" id="lpKasModalTitle">Ringkasan Kas</div>
                <div class="lp-keu-modal__subtitle" id="lpKasModalSubtitle"></div>
            </div>
            <button type="button" class="lp-keu-modal__close" onclick="window.lpCloseKasModal()">✕</button>
        </div>
        <div class="lp-keu-modal__body">
            <div id="lpKasModalEmpty" class="lp-keu-modal__empty" style="display:none;">Tidak ada data tersedia</div>
            <table class="lp-keu-table">
                <thead><tr id="lpKasModalThead"></tr></thead>
                <tbody id="lpKasModalTbody"></tbody>
                <tfoot id="lpKasModalTfoot"></tfoot>
            </table>
        </div>
    </div>
</div>

{{-- ── MODAL: DETAIL JENIS DANA (klik bar Kredit di breakdown) ── --}}
<div id="lpJenisModal" class="lp-keu-modal-overlay" style="display:none;">
    <div class="lp-keu-modal lp-keu-modal--wide">
        <div class="lp-keu-modal__header">
            <div>
                <div class="lp-keu-modal__title" id="lpJenisModalTitle">Detail Jenis Dana</div>
                <div class="lp-keu-modal__subtitle" id="lpJenisModalSubtitle"></div>
            </div>
            <button type="button" class="lp-keu-modal__close" onclick="window.lpCloseJenisModal()">✕</button>
        </div>

        <div class="lp-keu-modal__toolbar">
            <div class="lp-keu-search">
                <span class="lp-keu-search__icon">🔍</span>
                <input type="text" id="lpJenisSearch" placeholder="Cari nama / keperluan / sumber...">
            </div>
            <span id="lpJenisCount" class="lp-keu-count"></span>
        </div>

        <div class="lp-keu-modal__body">
            <div id="lpJenisModalLoading" class="lp-keu-modal__empty">Memuat data...</div>
            <div id="lpJenisModalEmpty" class="lp-keu-modal__empty" style="display:none;">Tidak ada rincian untuk jenis dana ini</div>
            <table class="lp-keu-table" id="lpJenisModalTable" style="display:none;">
                <thead>
                    <tr>
                        <th style="width:36px;text-align:center;">#</th>
                        <th>Nama</th>
                        <th>Keperluan</th>
                        <th style="text-align:right;">Realisasi</th>
                        <th style="text-align:center;">Tanggal</th>
                        <th style="text-align:center;">Sumber</th>
                    </tr>
                </thead>
                <tbody id="lpJenisModalTbody"></tbody>
                <tfoot id="lpJenisModalTfoot"></tfoot>
            </table>
        </div>

        <div class="lp-keu-modal__footer">
            <div id="lpJenisPagerInfo" class="lp-keu-pager-info"></div>
            <div class="lp-keu-modal__footer-actions">
                <div id="lpJenisPager" class="lp-keu-pager"></div>
                <button type="button" class="lp-btn lp-btn--ghost" onclick="window.lpExportJenisCSV()">⬇ Unduh CSV</button>
            </div>
        </div>
    </div>
</div>

{{-- Jembatan data awal dari PHP ke keuangan.js --}}
<script>
    window.LP_KEUANGAN = {
        tahunAktif: @json($tahunAktifKeuangan),
        debitKreditUrl: "{{ route('landing.keuangan.debit-kredit') }}",
        detailJenisUrl: "{{ route('landing.keuangan.detail-jenis') }}",
        realisasiBidangUrl: "{{ route('landing.keuangan.realisasi-bidang') }}",
        dataAwal: @json($debitKreditAwal),
    };
</script>
@push('styles')
    @vite(['resources/css/landing-page/keuangan.css'])
@endpush
@push('scripts')
    @vite(['resources/js/landing-page/keuangan.js'])
@endpush
@endif