{{-- ===== INVENTARIS BARANG (#inventaris) — khusus anggota yang login, read-only ===== --}}
@if (session('anggota'))
<section id="inventaris" class="lp-section lp-section--alt">
    <div class="lp-container">
        <div class="lp-section__head-row animate-on-scroll">
            <div class="lp-section__head lp-section__head--left">
                <span class="lp-section__eyebrow">📦 Inventaris</span>
                <h2 class="lp-section__title" style="font-size: 32px;">Daftar Inventaris Barang</h2>
                <p class="lp-section__desc" style="max-width: 600px; margin-top: 4px; font-size: 16px;">
                    Data aset dan barang milik PUK yang tercatat dalam sistem inventaris.
                </p>
            </div>
        </div>

        <div class="lp-inv-main">
            <div class="lp-inv-toolbar">
                <div class="lp-inv-search">
                    <span class="lp-inv-search__icon">🔍</span>
                    <input type="text" id="lpInvSearch" placeholder="Cari kode, nama, type, atau kategori...">
                </div>
                <span id="lpInvCount" class="lp-inv-count"></span>
            </div>

            <div class="lp-inv-table-wrap">
                <table class="lp-inv-table">
                    <thead>
                        <tr>
                            <th style="width:48px; text-align:center;">No.</th>
                            <th>Kode</th>
                            <th>Nama Barang</th>
                            <th>Type</th>
                            <th>Kategori</th>
                            <th>Kondisi</th>
                        </tr>
                    </thead>
                    <tbody id="lpInvTbody">
                        <tr><td colspan="6" class="lp-inv-loading">Memuat data...</td></tr>
                    </tbody>
                </table>
            </div>

            <div class="lp-inv-pager" id="lpInvPager" style="display:none;"></div>
        </div>
        {{-- Modal Detail Barang --}}
        <div class="lp-inv-modal-overlay" id="lpInvModalOverlay">
            <div class="lp-inv-modal" role="dialog" aria-modal="true" aria-labelledby="lpInvModalTitle">
                <div class="lp-inv-modal__head">
                    <h3 class="lp-inv-modal__title" id="lpInvModalTitle">Detail Barang</h3>
                    <button type="button" class="lp-inv-modal__close" id="lpInvModalClose" aria-label="Tutup">&times;</button>
                </div>
                <div class="lp-inv-modal__body" id="lpInvModalBody">
                    <!-- diisi oleh JS -->
                </div>
            </div>
        </div>
    </div>
</section>

<script>
    window.LP_INVENTARIS = {
        dataUrl: "{{ route('landing.inventaris.data') }}",
    };
</script>
@push('styles')
    @vite(['resources/css/landing-page/inventaris.css'])
@endpush
@push('scripts')
    @vite(['resources/js/landing-page/inventaris.js'])
@endpush
@endif