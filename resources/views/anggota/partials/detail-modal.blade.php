
{{-- ── MODAL DETAIL ─────────────────────────────────────────────── --}}
<div id="detailModal"
     style="display:none;position:fixed;inset:0;z-index:9998;
            background:rgba(0,0,0,0.5);backdrop-filter:blur(4px);
            align-items:center;justify-content:center;padding:20px;">
 
    <div id="detailModalBox"
         style="background:var(--bg-surface);border:1px solid var(--border);border-radius:18px;
                box-shadow:0 24px 64px rgba(0,0,0,0.25);width:100%;max-width:860px;
                max-height:88vh;display:flex;flex-direction:column;
                animation:fadeUp 0.25s ease both;">
 
        {{-- ── Modal Header ──────────────────────────────────────── --}}
        <div style="display:flex;align-items:center;justify-content:space-between;
                    padding:18px 22px;border-bottom:1px solid var(--border);flex-shrink:0;">
            <div style="display:flex;align-items:center;gap:10px;">
                <span id="detailModalIcon"
                      style="width:34px;height:34px;border-radius:9px;display:flex;align-items:center;
                             justify-content:center;font-size:16px;flex-shrink:0;background:var(--accent-muted);">
                    👥
                </span>
                <div>
                    <div id="detailModalTitle"
                         style="font-size:14px;font-weight:600;color:var(--text-primary);line-height:1.3;">
                        Detail Data
                    </div>
                    <div id="detailModalSubtitle"
                         style="font-size:11.5px;color:var(--text-muted);margin-top:1px;">
                    </div>
                </div>
            </div>
            <button onclick="closeDetailModal()"
                    style="width:30px;height:30px;border-radius:7px;border:1px solid var(--border);
                           background:transparent;cursor:pointer;display:flex;align-items:center;
                           justify-content:center;font-size:14px;color:var(--text-muted);
                           transition:all .15s;flex-shrink:0;"
                    onmouseover="this.style.background='var(--bg-elevated)'"
                    onmouseout="this.style.background='transparent'">✕</button>
        </div>
 
        {{-- ── Search + Export ────────────────────────────────────── --}}
        <div style="display:flex;align-items:center;justify-content:space-between;
                    padding:12px 22px;border-bottom:1px solid var(--border);flex-shrink:0;gap:10px;flex-wrap:wrap;">
            <div style="position:relative;flex:1;max-width:280px;">
                <input id="detailSearch" type="text" placeholder="Cari NIK / nama / departemen..."
                       oninput="filterDetailTable()"
                       style="width:100%;padding:7px 12px 7px 32px;border-radius:8px;
                              border:1px solid var(--border-strong);background:var(--bg-base);
                              color:var(--text-primary);font-family:inherit;font-size:13px;outline:none;
                              transition:border-color .15s;"
                       onfocus="this.style.borderColor='var(--accent)'"
                       onblur="this.style.borderColor='var(--border-strong)'">
                <span style="position:absolute;left:10px;top:50%;transform:translateY(-50%);
                             color:var(--text-muted);font-size:13px;pointer-events:none;">🔍</span>
            </div>
            <div style="display:flex;align-items:center;gap:8px;">
                <span id="detailCount"
                      style="font-size:12px;color:var(--text-muted);white-space:nowrap;"></span>
                <button onclick="exportDetailCSV()"
                        style="display:inline-flex;align-items:center;gap:5px;padding:6px 12px;
                               border-radius:8px;border:1px solid var(--border);background:transparent;
                               color:var(--text-secondary);font-size:12px;cursor:pointer;font-family:inherit;
                               transition:all .15s;"
                        onmouseover="this.style.background='var(--bg-elevated)'"
                        onmouseout="this.style.background='transparent'">
                    <svg style="width:13px;height:13px;" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 4v12m0 0l-3-3m3 3l3-3M4 20h16"/>
                    </svg>
                    Export CSV
                </button>
            </div>
        </div>
 
        {{-- ── Table ──────────────────────────────────────────────── --}}
        <div style="flex:1;overflow-y:auto;overflow-x:auto;scrollbar-width:thin;scrollbar-color:var(--border-strong) transparent;">
 
            {{-- Loading state --}}
            <div id="detailLoading"
                 style="display:none;text-align:center;padding:48px 20px;color:var(--text-muted);font-size:13px;">
                <div style="width:28px;height:28px;border:2px solid var(--border-strong);border-top-color:var(--accent);
                            border-radius:50%;animation:spin 0.7s linear infinite;margin:0 auto 12px;"></div>
                Memuat data...
            </div>
 
            {{-- Empty state --}}
            <div id="detailEmpty"
                 style="display:none;text-align:center;padding:48px 20px;">
                <div style="font-size:36px;margin-bottom:10px;">📭</div>
                <div style="font-size:13.5px;color:var(--text-muted);">Tidak ada data ditemukan</div>
            </div>
 
            {{-- Table --}}
            <table id="detailTable"
                   style="width:100%;border-collapse:collapse;font-size:13px;min-width:600px;">
                <thead id="detailThead"
                       style="position:sticky;top:0;z-index:2;background:var(--bg-elevated);">
                </thead>
                <tbody id="detailTbody"></tbody>
            </table>
 
        </div>
 
        {{-- ── Pagination ──────────────────────────────────────────── --}}
        <div style="display:flex;align-items:center;justify-content:space-between;flex-wrap:wrap;
                    gap:10px;padding:12px 22px;border-top:1px solid var(--border);flex-shrink:0;">
            <div style="font-size:12px;color:var(--text-muted);" id="detailPagerInfo"></div>
            <div style="display:flex;align-items:center;gap:6px;" id="detailPager"></div>
        </div>
 
    </div>
</div>
 
<style>
/* ── Detail modal table ──────────────────────────────────────── */
#detailThead tr {
    border-bottom: 1px solid var(--border);
}
#detailThead th {
    padding: 10px 14px;
    font-size: 11px;
    font-weight: 600;
    letter-spacing: 0.05em;
    text-transform: uppercase;
    color: var(--text-muted);
    text-align: left;
    white-space: nowrap;
    background: var(--bg-elevated);
}
#detailTbody tr {
    border-bottom: 1px solid var(--border);
    transition: background 0.1s;
}
#detailTbody tr:last-child { border-bottom: none; }
#detailTbody tr:hover td   { background: var(--bg-elevated); }
#detailTbody td {
    padding: 11px 14px;
    color: var(--text-secondary);
    white-space: nowrap;
}
#detailTbody td:nth-child(1) {
    font-size: 11.5px;
    color: var(--text-muted);
    width: 40px;
    text-align: center;
}
#detailTbody td:nth-child(2) {
    font-family: 'DM Mono', monospace;
    font-size: 12.5px;
    color: var(--text-primary);
}
#detailTbody td:nth-child(3) {
    font-weight: 500;
    color: var(--text-primary);
    max-width: 220px;
    overflow: hidden;
    text-overflow: ellipsis;
}
 
/* Badge keterangan */
.detail-badge {
    display: inline-flex;
    align-items: center;
    padding: 3px 9px;
    border-radius: 5px;
    font-size: 11.5px;
    font-weight: 500;
}
.detail-badge-masuk  { background: rgba(22,163,74,0.1);  color: #16A34A; }
.detail-badge-keluar { background: rgba(220,38,38,0.1);  color: #DC2626; }
 
/* Pager buttons */
#detailPager button {
    width: 30px; height: 30px;
    border-radius: 7px;
    border: 1px solid var(--border);
    background: transparent;
    color: var(--text-secondary);
    font-size: 13px;
    cursor: pointer;
    transition: all 0.12s;
    font-family: inherit;
    display: inline-flex;
    align-items: center;
    justify-content: center;
}
#detailPager button:hover  { background: var(--bg-elevated); color: var(--text-primary); }
#detailPager button.active { background: var(--accent); color: #fff; border-color: var(--accent); }
#detailPager button:disabled { opacity: 0.35; cursor: not-allowed; }
 
@keyframes spin { to { transform: rotate(360deg); } }
</style>
 
<script>
/* ════════════════════════════════════════════════════════════════
   DETAIL MODAL — custom, tanpa Bootstrap
════════════════════════════════════════════════════════════════ */
(function() {
    'use strict';
    
    /* ── helper ───────────────────────────────────────────────── */
    function fmtMasaKerja(tglMulai) {
        if (!tglMulai) return '—';

        const start = new Date(tglMulai);
        const now   = new Date();

        let years  = now.getFullYear() - start.getFullYear();
        let months = now.getMonth() - start.getMonth();
        let days   = now.getDate() - start.getDate();

        if (days < 0) {
            months--;
            const prevMonth = new Date(now.getFullYear(), now.getMonth(), 0);
            days += prevMonth.getDate();
        }

        if (months < 0) {
            years--;
            months += 12;
        }

        if (years < 0) return '—';

        const parts = [];
        if (years > 0) parts.push(`${years} thn`);
        if (months > 0) parts.push(`${months} bln`);
        if (days > 0) parts.push(`${days} hari`);

        return parts.length ? parts.join(' ') : '< 1 hari';
    }

    function fmtUmur(tglLahir) {
        if (!tglLahir) return '—';
        const start = new Date(tglLahir);
        const now   = new Date();

        let years  = now.getFullYear() - start.getFullYear();
        let months = now.getMonth()    - start.getMonth();
        let days   = now.getDate()     - start.getDate();

        if (days < 0) {
            months--;
            const prevMonth = new Date(now.getFullYear(), now.getMonth(), 0);
            days += prevMonth.getDate();
        }
        if (months < 0) { years--; months += 12; }
        if (years < 0)  return '—';

        const parts = [];
        if (years  > 0) parts.push(`${years} thn`);
        if (months > 0) parts.push(`${months} bln`);
        if (days   > 0) parts.push(`${days} hari`);

        return parts.length ? parts.join(' ') : '< 1 hari';
    }
    /* ── state ───────────────────────────────────────────────── */
    let allRows     = [];   // semua data dari server
    let filteredRows = [];  // setelah filter search
    let currentPage = 1;
    const PER_PAGE  = 15;
 
    let currentTitle    = '';
    let currentType     = '';
    let currentParams   = {};
 
    /* ── DOM refs ─────────────────────────────────────────────── */
    const modal       = () => document.getElementById('detailModal');
    const loadingEl   = () => document.getElementById('detailLoading');
    const emptyEl     = () => document.getElementById('detailEmpty');
    const tableEl     = () => document.getElementById('detailTable');
    const theadEl     = () => document.getElementById('detailThead');
    const tbodyEl     = () => document.getElementById('detailTbody');
    const countEl     = () => document.getElementById('detailCount');
    const pagerEl     = () => document.getElementById('detailPager');
    const pagerInfoEl = () => document.getElementById('detailPagerInfo');
    const searchEl    = () => document.getElementById('detailSearch');
 
    /* ── Open / Close ─────────────────────────────────────────── */
    function openDetailModal() {
        const m = modal();
        m.style.display = 'flex';
        document.body.style.overflow = 'hidden';
    }
 
    window.closeDetailModal = function() {
        modal().style.display = 'none';
        document.body.style.overflow = '';
        allRows = []; filteredRows = []; currentPage = 1;
        if (searchEl()) searchEl().value = '';
    };
 
    /* Backdrop click */
    modal()?.addEventListener('click', function(e) {
        if (e.target === this) window.closeDetailModal();
    });
 
    /* ESC key */
    document.addEventListener('keydown', e => {
        if (e.key === 'Escape' && modal()?.style.display === 'flex') {
            window.closeDetailModal();
        }
    });
 
    /* ── Buat header tabel ───────────────────────────────────── */
    function buildThead(type) {
        const cols = (type === 'umur')
            ? ['#', 'NIK', 'Nama Lengkap', 'Departemen', 'Status', 'Umur']
            : (type === 'masa_kerja')
            ? ['#', 'NIK', 'Nama Lengkap', 'Departemen', 'Status', 'Masa Kerja']
            : (type === 'departemen')
            ? ['#', 'NIK', 'Nama Lengkap', 'Departemen', 'Status', 'Masa Kerja']
            : ['#', 'NIK', 'Nama Lengkap', 'Departemen', 'Status', 'Keterangan'];
 
        theadEl().innerHTML = `<tr>${cols.map(c =>
            `<th>${c}</th>`
        ).join('')}</tr>`;
    }
 
    /* ── Render satu halaman ──────────────────────────────────── */
    function renderPage() {
        const tbody  = tbodyEl();
        const start  = (currentPage - 1) * PER_PAGE;
        const end    = Math.min(start + PER_PAGE, filteredRows.length);
        const page   = filteredRows.slice(start, end);
 
        tbody.innerHTML = '';
 
        if (!page.length) {
            emptyEl().style.display  = 'flex';
            tableEl().style.display  = 'none';
            countEl().textContent    = '';
            pagerInfoEl().textContent = '';
            pagerEl().innerHTML      = '';
            return;
        }
 
        emptyEl().style.display = 'none';
        tableEl().style.display = '';
 
        const frag = document.createDocumentFragment();
 
        page.forEach((row, i) => {
            const tr  = document.createElement('tr');
            const idx = start + i + 1;
 
            // Kolom ke-6 tergantung type
            let col6 = '—';
            if (currentType === 'umur') {
                const fmt = fmtUmur(row.tanggal_lahir);
                col6 = `<span style="font-weight:500;color:var(--text-primary);">${fmt}</span>`;
            } else if (currentType === 'masa_kerja' || currentType === 'departemen') {
                const fmt = fmtMasaKerja(row.tanggal_mulai_bekerja);
                col6 = `<span style="font-weight:500;color:var(--text-primary);">${fmt}</span>`;
            } else {
                // masuk / keluar
                const ket = (row.keterangan ?? '').toLowerCase();
                const badgeCls = ket === 'masuk' ? 'detail-badge-masuk' : 'detail-badge-keluar';
                col6 = `<span class="detail-badge ${badgeCls}">${row.keterangan ?? '—'}</span>`;
            }
 
            tr.innerHTML = `
                <td>${idx}</td>
                <td>${row.nik_karyawan ?? '—'}</td>
                <td>${row.nama_lengkap ?? '—'}</td>
                <td style="color:var(--text-secondary);">${row.departemen ?? '—'}</td>
                <td style="color:var(--text-secondary);">${row.status_karyawan ?? '—'}</td>
                <td>${col6}</td>`;
            frag.appendChild(tr);
        });
 
        tbody.appendChild(frag);
 
        // Count & pager info
        countEl().textContent = `${filteredRows.length.toLocaleString('id-ID')} data`;
        pagerInfoEl().textContent =
            `Menampilkan ${start + 1}–${end} dari ${filteredRows.length.toLocaleString('id-ID')} data`;
 
        buildPager();
    }
 
    /* ── Pagination ───────────────────────────────────────────── */
    function buildPager() {
        const totalPages = Math.ceil(filteredRows.length / PER_PAGE);
        const pager      = pagerEl();
        pager.innerHTML  = '';
 
        if (totalPages <= 1) return;
 
        const make = (label, page, active = false, disabled = false) => {
            const b = document.createElement('button');
            b.innerHTML = label;
            if (active)   b.classList.add('active');
            if (disabled) b.disabled = true;
            b.onclick = () => { currentPage = page; renderPage(); };
            pager.appendChild(b);
        };
 
        make('‹', currentPage - 1, false, currentPage === 1);
 
        const MAX  = 5;
        let start  = Math.max(1, currentPage - Math.floor(MAX / 2));
        let end    = Math.min(totalPages, start + MAX - 1);
        if (end - start < MAX - 1) start = Math.max(1, end - MAX + 1);
 
        if (start > 1)            { make(1, 1); if (start > 2) { const s = document.createElement('span'); s.textContent = '…'; s.style.cssText = 'padding:0 4px;color:var(--text-muted);font-size:12px;'; pager.appendChild(s); } }
        for (let p = start; p <= end; p++) make(p, p, p === currentPage);
        if (end < totalPages)     { if (end < totalPages - 1) { const s = document.createElement('span'); s.textContent = '…'; s.style.cssText = 'padding:0 4px;color:var(--text-muted);font-size:12px;'; pager.appendChild(s); } make(totalPages, totalPages); }
 
        make('›', currentPage + 1, false, currentPage === totalPages);
    }
 
    /* ── Search / filter ──────────────────────────────────────── */
    window.filterDetailTable = function() {
        const q = (searchEl()?.value ?? '').toLowerCase().trim();
        filteredRows = q
            ? allRows.filter(r =>
                (r.nik_karyawan  ?? '').toLowerCase().includes(q) ||
                (r.nama_lengkap  ?? '').toLowerCase().includes(q) ||
                (r.departemen    ?? '').toLowerCase().includes(q) ||
                (r.status_karyawan ?? '').toLowerCase().includes(q)
              )
            : [...allRows];
        currentPage = 1;
        renderPage();
    };
 
    /* ── Export CSV ───────────────────────────────────────────── */
    window.exportDetailCSV = function() {
        if (!filteredRows.length) return;
 
        const headers = currentType === 'umur'
            ? ['No','NIK','Nama Lengkap','Departemen','Status Karyawan','Umur']
            : (currentType === 'masa_kerja' || currentType === 'departemen')
            ? ['No','NIK','Nama Lengkap','Departemen','Status Karyawan','Masa Kerja']
            : ['No','NIK','Nama Lengkap','Departemen','Status Karyawan','Keterangan'];
 
        const rows = filteredRows.map((r, i) => {
            let col6 = r.keterangan ?? '';
            if (currentType === 'umur') col6 = fmtUmur(r.tanggal_lahir);
            if (currentType === 'masa_kerja' || currentType === 'departemen')    col6 = fmtMasaKerja(r.tanggal_mulai_bekerja);
 
            return [
                i + 1,
                r.nik_karyawan  ?? '',
                r.nama_lengkap  ?? '',
                r.departemen    ?? '',
                r.status_karyawan ?? '',
                col6,
            ].map(v => `"${String(v).replace(/"/g,'""')}"`).join(',');
        });
 
        const csv  = [headers.join(','), ...rows].join('\n');
        const blob = new Blob(['\uFEFF' + csv], { type: 'text/csv;charset=utf-8;' });
        const url  = URL.createObjectURL(blob);
        const a    = document.createElement('a');
        a.href     = url;
        a.download = `${currentTitle.replace(/\s+/g,'_')}.csv`;
        a.click();
        URL.revokeObjectURL(url);
    };
 
    /* ── Main entry point (dipanggil dari onclick chart) ──────── */
    window.showDetail = async function(params, title, subtitle = '', icon = '👥') {
        currentParams = params;
        currentType   = params.type;
        currentTitle  = title;
 
        // Update header
        document.getElementById('detailModalTitle').textContent    = title;
        document.getElementById('detailModalSubtitle').textContent = subtitle;
        document.getElementById('detailModalIcon').textContent     = icon;
 
        // Reset
        allRows = []; filteredRows = []; currentPage = 1;
        if (searchEl()) searchEl().value = '';
        tbodyEl().innerHTML      = '';
        pagerEl().innerHTML      = '';
        pagerInfoEl().textContent = '';
        countEl().textContent    = '';
 
        buildThead(currentType);
        loadingEl().style.display = 'flex';
        emptyEl().style.display   = 'none';
        tableEl().style.display   = 'none';
 
        openDetailModal();
 
        try {
            const qs = new URLSearchParams({
                ...params,
                draw: 1, start: 0, length: 9999   // ambil semua, paginasi di client
            });
 
            const res  = await fetch(`{{ route('anggota.dashboard.detail') }}?${qs}`, {
                headers: {
                    'X-CSRF-TOKEN': PHP.csrfToken,
                    'Accept'      : 'application/json',
                }
            });
 
            const json = await res.json();
 
            // DataTables server-side format: { data: [...] }
            allRows = json.data ?? [];

            // Sort sesuai type
            if (currentType === 'departemen') {
                allRows.sort((a, b) => {
                    const statusCmp = (b.status_karyawan ?? '').localeCompare(a.status_karyawan ?? '', 'id');
                    if (statusCmp !== 0) return statusCmp;
                    const da = a.tanggal_mulai_bekerja ? new Date(a.tanggal_mulai_bekerja) : null;
                    const db = b.tanggal_mulai_bekerja ? new Date(b.tanggal_mulai_bekerja) : null;
                    if (!da && !db) return 0;
                    if (!da) return 1;
                    if (!db) return -1;
                    return da - db;
                });
            } else if (currentType === 'umur') {
                allRows.sort((a, b) => {
                    const da = a.tanggal_lahir ? new Date(a.tanggal_lahir) : null;
                    const db = b.tanggal_lahir ? new Date(b.tanggal_lahir) : null;
                    if (!da && !db) return 0;
                    if (!da) return 1;
                    if (!db) return -1;
                    return da - db;
                });
            } else if (currentType === 'masa_kerja') {
                allRows.sort((a, b) => {
                    const da = a.tanggal_mulai_bekerja ? new Date(a.tanggal_mulai_bekerja) : null;
                    const db = b.tanggal_mulai_bekerja ? new Date(b.tanggal_mulai_bekerja) : null;
                    if (!da && !db) return 0;
                    if (!da) return 1;
                    if (!db) return -1;
                    return da - db; // mulai lebih awal = masa kerja lebih lama = tampil duluan
                });
            } else if (currentType === 'masuk' || currentType === 'keluar') {
                allRows.sort((a, b) =>
                    (a.nama_lengkap ?? '').localeCompare(b.nama_lengkap ?? '', 'id')
                );
            }

            filteredRows = [...allRows];
 
            loadingEl().style.display = 'none';
            renderPage();
 
        } catch(e) {
            console.error(e);
            loadingEl().style.display = 'none';
            emptyEl().style.display   = 'block';
        }
    };
 
})();

document.addEventListener('DOMContentLoaded', () => {
    const modal = document.getElementById('detailModal');

    if (modal) {
        document.body.appendChild(modal);
    }
});
</script>