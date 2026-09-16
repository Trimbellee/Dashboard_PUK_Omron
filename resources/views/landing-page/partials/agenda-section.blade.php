{{-- ===== AGENDA KEGIATAN (#agenda) — khusus anggota yang login, read-only ===== --}}
@if (session('anggota'))
<section id="agenda" class="lp-section lp-section--alt">
    <div class="lp-container">
        <div class="lp-section__head-row animate-on-scroll">
            <div class="lp-section__head lp-section__head--left">
                <span class="lp-section__eyebrow">🗓️ Agenda Kegiatan</span>
                <h2 class="lp-section__title" style="font-size: 32px;">Jadwal Kegiatan PUK</h2>
                <p class="lp-section__desc" style="max-width: 600px; margin-top: 4px; font-size: 16px;">
                    Pantau jadwal rapat, aksi, pendidikan, dan agenda lainnya yang sedang dan akan berjalan.
                </p>
            </div>
        </div>

        <div class="lp-agenda-main">
            <div class="lp-agenda-toolbar">
                <div class="lp-agenda-nav">
                    <button class="lp-agenda-nav-btn" id="lpAgendaPrev" aria-label="Bulan sebelumnya">‹</button>
                    <div class="lp-agenda-month-wrapper">
                        <button class="lp-agenda-month-btn" id="lpAgendaMonthBtn">
                            <span id="lpAgendaMonthLabel"></span>
                            <span class="lp-agenda-dropdown-arrow">▾</span>
                        </button>
                        <div class="lp-agenda-month-dropdown" id="lpAgendaMonthDropdown">
                            <div class="lp-agenda-month-dropdown__header">
                                <button type="button" id="lpAgendaPickerPrevYear">‹</button>
                                <span id="lpAgendaPickerYear"></span>
                                <button type="button" id="lpAgendaPickerNextYear">›</button>
                            </div>
                            <div class="lp-agenda-month-grid" id="lpAgendaMonthGrid"></div>
                        </div>
                    </div>
                    <button class="lp-agenda-nav-btn" id="lpAgendaNext" aria-label="Bulan berikutnya">›</button>
                    <button class="lp-agenda-today-btn" id="lpAgendaToday">Hari ini</button>
                </div>
                <div style="display:flex; gap:8px; align-items:center; flex-wrap:wrap;">
                    <div class="lp-agenda-search" id="lpAgendaSearchWrap" style="display:none;">
                        <span class="lp-agenda-search__icon">🔍</span>
                        <input type="text" id="lpAgendaSearch" placeholder="Cari agenda...">
                    </div>
                    <div class="lp-agenda-view-tabs">
                        <button class="lp-agenda-view-tab active" id="lpAgendaTabGrid">📅 Bulan</button>
                        <button class="lp-agenda-view-tab" id="lpAgendaTabList">📋 List</button>
                    </div>
                </div>
            </div>

            <div id="lpAgendaGridView">
                <div class="lp-agenda-grid-head" id="lpAgendaGridHead"></div>
                <div class="lp-agenda-grid-body" id="lpAgendaGridBody"></div>
            </div>
            <div id="lpAgendaListView" style="display:none;">
                <div id="lpAgendaListBody"></div>
                <div class="lp-agenda-pager" id="lpAgendaPager" style="display:none;"></div>
            </div>
        </div>
    </div>
</section>

{{-- Modal detail hari (read-only) --}}
<div class="lp-agenda-modal-backdrop" id="lpAgendaDayModal">
    <div class="lp-agenda-modal-box">
        <div class="lp-agenda-modal-header">
            <h3 class="lp-agenda-modal-title" id="lpAgendaDayModalTitle">Agenda</h3>
            <button class="lp-agenda-modal-close" id="lpAgendaDayModalClose">✕</button>
        </div>
        <div id="lpAgendaDayModalList" class="lp-agenda-daylist"></div>
    </div>
</div>

<div class="lp-agenda-tooltip" id="lpAgendaTooltip"></div>

<script>
    window.LP_AGENDA = {
        dataUrl: "{{ route('landing.agenda.data') }}",
    };
</script>
@push('styles')
    @vite(['resources/css/landing-page/agenda.css'])
@endpush
@push('scripts')
    @vite(['resources/js/landing-page/agenda.js'])
@endpush

@endif