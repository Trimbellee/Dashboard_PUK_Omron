@extends('layouts.app')
@section('title','Laporan Keuangan Bulanan')

@section('content')

@php
    $bulan = $bulan ?? now()->month;
    $tahun = $tahun ?? now()->year;
@endphp

<div>

    {{-- TOMBOL BUAT LAPORAN --}}
    @if(in_array(session('user')['role'], ['Sekretaris','Bendahara']))
    <div style="display:flex;justify-content:flex-end;margin-bottom:12px;">
        <button onclick="openModalBuatLaporan()" class="btn btn-primary">
            + Buat Laporan
        </button>
    </div>
    @endif

    {{-- JUDUL --}}
    <div style="text-align:center;margin-bottom:24px;">
        <h1 style="font-size:1.1rem;font-weight:700;text-transform:uppercase;
                   color:var(--text-primary);margin:0 0 2px;">
            Laporan Bulanan
        </h1>
        <h2 style="font-size:1.1rem;font-weight:700;text-transform:uppercase;
                   color:var(--text-primary);margin:0;">
            Keuangan PUK OMRON
        </h2>
    </div>

    {{-- SEARCH --}}
    <div style="margin-bottom:12px;">
        <div class="lb-search-wrap" style="position:relative;width:100%;max-width:320px;">
            <input id="searchInput" type="text"
                placeholder="🔎 Cari bulan / tahun"
                class="form-input">
            <span id="searchClear"
                style="position:absolute;right:10px;top:50%;transform:translateY(-50%);
                    cursor:pointer;display:none;color:var(--text-muted);font-size:13px;">
                ✕
            </span>
        </div>
    </div>

    {{-- TABLE --}}
    <div class="lb-table-wrap" style="background:var(--bg-surface);border-radius:14px;
                border:1px solid var(--border);box-shadow:var(--shadow-sm);
                overflow:hidden;">
        <table class="lb-table" style="min-width:100%;font-size:0.875rem;border-collapse:collapse;">
            <thead>
                <tr style="background:var(--bg-elevated);">
                    <th style="padding:10px 16px;text-align:left;font-weight:600;
                            color:var(--text-secondary);border-bottom:1px solid var(--border);
                            width:60px;">No.</th>
                    <th style="padding:10px 16px;text-align:left;font-weight:600;
                            color:var(--text-secondary);border-bottom:1px solid var(--border);">
                        Bulan
                    </th>
                    <th style="padding:10px 16px;text-align:center;font-weight:600;
                        color:var(--text-secondary);border-bottom:1px solid var(--border);
                        width:160px;">Status</th>
                    <th style="padding:10px 16px;text-align:center;font-weight:600;
                            color:var(--text-secondary);border-bottom:1px solid var(--border);
                            width:70px;">PDF</th>
                    @if(in_array(session('user')['role'], ['Ketua','Sekretaris','Bendahara']))
                    <th style="padding:10px 16px;text-align:center;font-weight:600;
                            color:var(--text-secondary);border-bottom:1px solid var(--border);
                            width:180px;">Aksi</th>
                    @endif
                </tr>
            </thead>
            <tbody id="tableBody"></tbody>
        </table>
    </div>

    {{-- PAGINATION --}}
    <div style="display:flex;flex-wrap:wrap;justify-content:space-between;
                align-items:center;gap:12px;margin-top:16px;padding:0 4px;">
        <div id="pageInfo"
            style="font-size:0.875rem;color:var(--text-muted);"></div>
        <div id="pagination"
            style="display:flex;flex-wrap:wrap;gap:4px;"></div>
    </div>

</div>

{{-- ── MODAL BUAT LAPORAN ──────────────────────────────────── --}}
<div id="modalBuatLaporan"
    style="position:fixed;inset:0;background:rgba(0,0,0,.45);
           backdrop-filter:blur(6px);display:none;align-items:center;
           justify-content:center;z-index:8000;padding:20px;">

    <div style="background:var(--bg-surface);border:1px solid var(--border);
                border-radius:18px;box-shadow:var(--shadow-lg);
                width:100%;max-width:380px;padding:28px;"
         onclick="event.stopPropagation()">

        <div style="display:flex;align-items:center;justify-content:space-between;
                    margin-bottom:20px;">
            <h2 style="font-size:1rem;font-weight:600;color:var(--text-primary);margin:0;">
                Buat Laporan Bendahara
            </h2>
            <button onclick="closeModalBuatLaporan()"
                style="width:28px;height:28px;border-radius:7px;
                       border:1px solid var(--border);background:none;
                       cursor:pointer;font-size:14px;color:var(--text-muted);
                       display:flex;align-items:center;justify-content:center;">
                ✕
            </button>
        </div>

        <form id="formBuatLaporan">

            <div class="form-group">
                <label class="form-label">Bulan</label>
                <select name="bulan" class="form-input" style='cursor:pointer;
                        background-image:url("data:image/svg+xml,%3Csvg xmlns=%27http://www.w3.org/2000/svg%27 width=%2716%27 height=%2716%27 viewBox=%270 0 24 24%27 fill=%27none%27 stroke=%27%239CA3AF%27 stroke-width=%272%27%3E%3Cpolyline points=%276 9 12 15 18 9%27/%3E%3C/svg%3E");
                        background-repeat:no-repeat;
                        background-position:right 10px center;
                        padding-right:32px;'
                    >
                    @for($i=1;$i<=12;$i++)
                        <option value="{{ $i }}" @if($i==$bulan) selected @endif>
                            {{ \Carbon\Carbon::create()->month($i)->translatedFormat('F') }}
                        </option>
                    @endfor
                </select>
            </div>

            <div class="form-group">
                <label class="form-label">Tahun</label>
                <input type="number" name="tahun" class="form-input"
                    value="{{ $tahun }}">
            </div>

            <div class="form-actions">
                <button type="button" onclick="closeModalBuatLaporan()"
                    class="btn btn-ghost">
                    Batal
                </button>
                <button type="submit" id="btnBuatLaporan" class="btn btn-primary">
                    <span id="spinBuatLaporan" class="spinner" style="display:none;"></span>
                    <span id="textBuatLaporan">Buat Laporan</span>
                </button>
            </div>
        </form>
    </div>
</div>

{{-- ── TOAST STYLE ─────────────────────────────────────────── --}}
<style>
#toastWrap {
    position: fixed;
    top: 20px;
    left: 50%;
    transform: translateX(-50%);
    display: flex;
    flex-direction: column;
    align-items: center;
    gap: 8px;
    z-index: 999999;
    pointer-events: none;
}

.toast-item {
    display: flex;
    align-items: center;
    gap: 10px;
    min-width: 280px;
    max-width: 420px;
    padding: 12px 16px;
    border-radius: 12px;
    box-shadow: 0 8px 24px rgba(0,0,0,.16), 0 2px 6px rgba(0,0,0,.08);
    font-size: 13px;
    font-weight: 500;
    color: #fff;
    pointer-events: auto;
    animation: toastIn .28s cubic-bezier(.34,1.56,.64,1);
}

.toast-item.toast-out {
    animation: toastOut .22s ease forwards;
}

.toast-item .toast-icon {
    flex-shrink: 0;
    width: 20px;
    height: 20px;
    display: flex;
    align-items: center;
    justify-content: center;
}

.toast-item .toast-msg {
    flex: 1;
    line-height: 1.4;
    white-space: pre-line;
}

.toast-success { background: linear-gradient(135deg,#16a34a,#15803d); }
.toast-error   { background: linear-gradient(135deg,#dc2626,#b91c1c); }
.toast-warning { background: linear-gradient(135deg,#d97706,#b45309); }
.toast-info    { background: linear-gradient(135deg,#334155,#1e293b); }

@keyframes toastIn {
    from { opacity:0; transform:translateY(-16px) scale(.96); }
    to   { opacity:1; transform:translateY(0) scale(1); }
}

@keyframes toastOut {
    from { opacity:1; transform:translateY(0) scale(1); }
    to   { opacity:0; transform:translateY(-10px) scale(.96); }
}

@media (max-width: 640px) {

    .lb-search-wrap {
        max-width: 100% !important;
    }

    .lb-table-wrap {
        border: none !important;
        box-shadow: none !important;
        background: transparent !important;
        overflow: visible !important;
    }

    .lb-table thead {
        display: none;
    }

    .lb-table, .lb-table tbody, .lb-table tr, .lb-table td {
        display: block;
        width: 100%;
    }

    .lb-table tr {
        background: var(--bg-surface);
        border: 1px solid var(--border);
        border-radius: 12px;
        box-shadow: var(--shadow-sm);
        margin-bottom: 10px;
        padding: 4px 14px;
    }

    .lb-table tr:hover {
        background: var(--bg-surface) !important;
    }

    .lb-table td {
        border: none !important;
        padding: 8px 0 !important;
        border-bottom: 1px solid var(--border) !important;
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: 10px;
    }

    .lb-table td:last-child {
        border-bottom: none !important;
    }

    .lb-table td[data-label]::before {
        content: attr(data-label);
        font-weight: 600;
        font-size: 10px;
        color: var(--text-muted);
        text-transform: uppercase;
        letter-spacing: .03em;
        flex-shrink: 0;
    }

    .lb-td-no {
        display: none !important;
    }

    .lb-td-aksi {
        flex-direction: column;
        align-items: flex-start !important;
    }

    .lb-td-aksi > div {
        width: 100%;
        justify-content: flex-end !important;
    }
}
</style>

{{-- ── TOAST SYSTEM ────────────────────────────────────────── --}}
<script>
if (!document.getElementById('toastWrap')) {
    const toastRoot = document.createElement('div');
    toastRoot.id = 'toastWrap';
    document.body.appendChild(toastRoot);
}

const TOAST_ICONS = {
    success: '<svg viewBox="0 0 20 20" fill="currentColor" width="20" height="20"><path fill-rule="evenodd" d="M16.7 5.3a1 1 0 010 1.4l-7.5 7.5a1 1 0 01-1.4 0L3.3 9.7a1 1 0 111.4-1.4L8.5 12l6.8-6.7a1 1 0 011.4 0z" clip-rule="evenodd"/></svg>',
    error:   '<svg viewBox="0 0 20 20" fill="currentColor" width="20" height="20"><path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.7 7.3a1 1 0 011.4 0L10 8.6l.9-.9a1 1 0 111.4 1.4l-.9.9.9.9a1 1 0 01-1.4 1.4l-.9-.9-.9.9a1 1 0 01-1.4-1.4l.9-.9-.9-.9a1 1 0 010-1.4z" clip-rule="evenodd"/></svg>',
    warning: '<svg viewBox="0 0 20 20" fill="currentColor" width="20" height="20"><path fill-rule="evenodd" d="M8.3 3.3a2 2 0 013.4 0l6.5 11.2a2 2 0 01-1.7 3H3.5a2 2 0 01-1.7-3L8.3 3.3zM10 7a1 1 0 00-1 1v3a1 1 0 002 0V8a1 1 0 00-1-1zm0 7a1 1 0 100 2 1 1 0 000-2z" clip-rule="evenodd"/></svg>',
    info:    '<svg viewBox="0 0 20 20" fill="currentColor" width="20" height="20"><path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM9 9a1 1 0 012 0v4a1 1 0 01-2 0V9zm1-5a1.2 1.2 0 100 2.4A1.2 1.2 0 0010 4z" clip-rule="evenodd"/></svg>'
};

function showToast(msg, type = 'success', duration = 3200) {
    const wrap = document.getElementById('toastWrap');
    if (!wrap) return;

    const toast = document.createElement('div');
    toast.className = `toast-item toast-${type}`;
    toast.innerHTML = `
        <span class="toast-icon">${TOAST_ICONS[type] || TOAST_ICONS.info}</span>
        <span class="toast-msg">${msg}</span>
    `;
    wrap.appendChild(toast);

    const items = [...wrap.children];
    if (items.length > 4) items[0].remove();

    setTimeout(() => {
        toast.classList.add('toast-out');
        setTimeout(() => toast.remove(), 220);
    }, duration);
}
</script>

<script>
const laporanData = @json($laporanList ?? []);
</script>

<script>
/* ── MODAL BUAT LAPORAN ─────────────────────────────────────── */
const _modalBuat = document.getElementById('modalBuatLaporan');

function openModalBuatLaporan() {
    _modalBuat.style.display = 'flex';
    document.body.style.overflow = 'hidden';
}
function closeModalBuatLaporan() {
    _modalBuat.style.display = 'none';
    document.body.style.overflow = '';
}

_modalBuat.addEventListener('click', e => {
    if (e.target === _modalBuat) closeModalBuatLaporan();
});

document.getElementById('formBuatLaporan').addEventListener('submit', function(e) {
    e.preventDefault();
    const btn  = document.getElementById('btnBuatLaporan');
    const spin = document.getElementById('spinBuatLaporan');
    const text = document.getElementById('textBuatLaporan');

    spin.style.display = 'block';
    text.textContent   = 'Memproses...';
    btn.disabled       = true;

    const fd   = new FormData(this);
    const bln  = fd.get('bulan');
    const thn  = fd.get('tahun');

    closeModalBuatLaporan();
    window.location.href =
        `{{ route('laporan-bendahara.index') }}?bulan=${bln}&tahun=${thn}`;
});
</script>

<script>
/* ── TABLE & PAGINATION ──────────────────────────────────── */
const tbody = document.getElementById('tableBody');
const pager = document.getElementById('pagination');
const info  = document.getElementById('pageInfo');

let currentPage = 1;
const perPage   = 12;

let masterData = [...laporanData].reverse();
let dataList   = [...masterData];

const rowPool  = [];
let poolReady  = false;

const namaBulan = ['Jan','Feb','Mar','Apr','Mei','Jun',
                   'Jul','Agu','Sep','Okt','Nov','Des'];

function formatBulan(b, t) {
    return `${namaBulan[b-1]} ${t}`;
}

/* ── ROW TEMPLATE ────────────────────────────────────────── */
function createRowElement() {
    const tr = document.createElement('tr');
    tr.style.cssText = 'transition:background .12s;';
    tr.onmouseover = () => tr.style.background = 'var(--bg-elevated)';
    tr.onmouseout  = () => tr.style.background = '';

    tr.innerHTML = `
        <td class="lb-no lb-td-no" style="padding:10px 16px;color:var(--text-secondary);border-top:1px solid var(--border);"></td>
        <td class="lb-td-bulan" data-label="Bulan" style="padding:10px 16px;color:var(--text-primary);border-top:1px solid var(--border);">
            <div style="display:flex;align-items:center;gap:6px;">
                <span class="lb-bulan"></span>
                <span class="lb-reopen-badge"
                    style="display:none;align-items:center;gap:3px;
                        padding:2px 7px;border-radius:20px;
                        background:#fef3c7;color:#92400e;
                        font-size:10px;font-weight:600;white-space:nowrap;cursor:help;">
                    <i class="ti ti-history" style="font-size:11px;"></i>
                    Pernah Dibuka
                </span>
            </div>
        </td>
        <td class="lb-td-status" data-label="Status" style="padding:10px 16px;border-top:1px solid var(--border);text-align:center;">
            <span class="lb-status-badge" style="padding:3px 10px;border-radius:20px;font-size:11px;font-weight:600;white-space:nowrap;"></span>
        </td>
        <td class="lb-td-pdf" data-label="PDF" style="padding:10px 16px;border-top:1px solid var(--border);text-align:center;">
            <a target="_blank" class="btnLihat"
                title="Lihat PDF"
                style="width:32px;height:32px;border-radius:8px;
                    background:#059669;color:#fff;display:inline-flex;
                    align-items:center;justify-content:center;
                    text-decoration:none;pointer-events:none;
                    opacity:.4;cursor:not-allowed;">
                <i class="ti ti-file-type-pdf" style="font-size:16px;"></i>
            </a>
        </td>
        @if(in_array(session('user')['role'], ['Ketua','Sekretaris','Bendahara']))
        <td class="lb-td-aksi" data-label="Aksi" style="padding:10px 16px;border-top:1px solid var(--border);">
            <div style="display:flex;gap:8px;justify-content:center;flex-wrap:wrap;">
                <a class="btnDetail"
                    title="Lihat Detail Laporan"
                    style="display:none;width:32px;height:32px;border-radius:8px;
                        background:#64748b;color:#fff;display:inline-flex;
                        align-items:center;justify-content:center;
                        text-decoration:none;position:relative;">
                    <span class="lb-detail-spinner"
                        style="display:none;width:12px;height:12px;
                            border:2px solid rgba(255,255,255,.3);
                            border-top-color:#fff;border-radius:50%;
                            animation:spin .7s linear infinite;
                            position:absolute;">
                    </span>
                    <i class="ti ti-eye lb-detail-icon" style="font-size:16px;"></i>
                </a>
                @if(in_array(session('user')['role'], ['Sekretaris','Bendahara']))
                <a class="btnEdit"
                    title="Edit Laporan"
                    style="width:32px;height:32px;border-radius:8px;
                        background:var(--accent);color:#fff;display:inline-flex;
                        align-items:center;justify-content:center;
                        text-decoration:none;position:relative;">
                    <span class="lb-spinner"
                        style="display:none;width:12px;height:12px;
                            border:2px solid rgba(255,255,255,.3);
                            border-top-color:#fff;border-radius:50%;
                            animation:spin .7s linear infinite;
                            position:absolute;">
                    </span>
                    <i class="ti ti-pencil lb-edit-icon" style="font-size:16px;"></i>
                </a>
                @endif
                @if(session('user')['role'] === 'Bendahara')
                <button type="button" class="btnSubmit"
                    title="Ajukan ke Sekretaris"
                    style="display:none;width:32px;height:32px;border-radius:8px;
                        border:none;background:#0891b2;color:#fff;align-items:center;
                        justify-content:center;cursor:pointer;position:relative;">
                    <span class="lb-submit-spinner"
                        style="display:none;width:12px;height:12px;
                            border:2px solid rgba(255,255,255,.3);
                            border-top-color:#fff;border-radius:50%;
                            animation:spin .7s linear infinite;
                            position:absolute;">
                    </span>
                    <i class="ti ti-send lb-submit-icon" style="font-size:16px;"></i>
                </button>
                @endif
                @if(in_array(session('user')['role'], ['Sekretaris','Ketua']))
                <a class="btnProses"
                    title="Proses Laporan"
                    style="display:none;width:32px;height:32px;border-radius:8px;
                        background:#7c3aed;color:#fff;display:inline-flex;
                        align-items:center;justify-content:center;
                        text-decoration:none;position:relative;">
                    <span class="lb-proses-spinner"
                        style="display:none;width:12px;height:12px;
                            border:2px solid rgba(255,255,255,.3);
                            border-top-color:#fff;border-radius:50%;
                            animation:spin .7s linear infinite;
                            position:absolute;">
                    </span>
                    <i class="ti ti-arrow-right lb-proses-icon" style="font-size:16px;"></i>
                </a>
                @endif
            </div>
        </td>
        @endif`;
    return tr;
}

function initPool() {
    if (poolReady) return;
    const frag = document.createDocumentFragment();
    for (let i = 0; i < perPage; i++) {
        const tr = createRowElement();
        rowPool.push(tr);
        frag.appendChild(tr);
    }
    tbody.appendChild(frag);
    poolReady = true;
}

/* ── SEARCH ──────────────────────────────────────────────── */
const searchInput = document.getElementById('searchInput');
const searchClear = document.getElementById('searchClear');

function applySearch(kw) {
    kw = kw.toLowerCase().trim();
    dataList = kw
        ? masterData.filter(r => {
            const b = formatBulan(r.bulan, r.tahun).toLowerCase();
            return b.includes(kw) || String(r.tahun).includes(kw) || String(r.bulan).includes(kw);
          })
        : [...masterData];
    currentPage = 1;
    renderTable();
    renderPagination();
    searchClear.style.display = kw ? 'block' : 'none';
}

searchInput.addEventListener('input', function() { applySearch(this.value); });
searchClear.onclick = () => { searchInput.value = ''; applySearch(''); };

const statusConfig = {
    draft:                { label: 'Draft',              bg: '#e5e7eb', color: '#374151' },
    revisi:               { label: 'Perlu Revisi',        bg: '#fee2e2', color: '#991b1b' },
    menunggu_sekretaris:  { label: 'Menunggu Sekretaris', bg: '#fef3c7', color: '#92400e' },
    menunggu_ketua:       { label: 'Menunggu Ketua',      bg: '#dbeafe', color: '#1e40af' },
    selesai:              { label: 'Selesai',             bg: '#dcfce7', color: '#166534' },
};

const userRole = @json(session('user')['role'] ?? null);

/* ── RENDER TABLE ────────────────────────────────────────── */
function renderTable() {
    initPool();
    const start    = (currentPage - 1) * perPage;
    const end      = start + perPage;
    const pageData = dataList.slice(start, end);

    rowPool.forEach((tr, i) => {
        const row = pageData[i];
        if (!row) { tr.style.display = 'none'; return; }
        tr.style.display = '';

        tr.querySelector('.lb-no').textContent    = start + i + 1;
        tr.querySelector('.lb-bulan').textContent = formatBulan(row.bulan, row.tahun);

        const status = row.status || 'draft';
        const statusKey = (status === 'draft' && row.perlu_revisi) ? 'revisi' : status;
        const cfg = statusConfig[statusKey] || statusConfig.draft;
        const badge = tr.querySelector('.lb-status-badge');
        badge.textContent = cfg.label;
        badge.style.background = cfg.bg;
        badge.style.color = cfg.color;

        const btnEdit = tr.querySelector('.btnEdit');
        if (btnEdit) {
            const canEdit = status === 'draft';

            if (canEdit) {
                btnEdit.href = `{{ route('laporan-bendahara.index') }}?bulan=${row.bulan}&tahun=${row.tahun}`;
                btnEdit.style.display = 'inline-flex';
            } else {
                btnEdit.style.display = 'none';
            }
        }
        const btnLihat = tr.querySelector('.btnLihat');
        if (btnLihat) {
            if (row.pdf_url) {
                btnLihat.href               = row.pdf_url;
                btnLihat.style.opacity      = '1';
                btnLihat.style.cursor       = 'pointer';
                btnLihat.style.pointerEvents = 'auto';
                btnLihat.onmouseover = () => btnLihat.style.background = '#047857';
                btnLihat.onmouseout  = () => btnLihat.style.background = '#059669';
            } else {
                btnLihat.removeAttribute('href');
                btnLihat.style.opacity      = '.4';
                btnLihat.style.cursor       = 'not-allowed';
                btnLihat.style.pointerEvents = 'none';
                btnLihat.onmouseover = null;
                btnLihat.onmouseout  = null;
            }
        }

        
        const btnSubmit = tr.querySelector('.btnSubmit');
        if (btnSubmit) {
            if (status === 'draft' && !row.perlu_revisi) {
                btnSubmit.style.display = 'inline-flex';
                btnSubmit.dataset.bulan = row.bulan;
                btnSubmit.dataset.tahun = row.tahun;
            } else {
                btnSubmit.style.display = 'none';
            }
        }

        const btnProses = tr.querySelector('.btnProses');
        if (btnProses) {
            const canActSekretaris = userRole === 'Sekretaris' && status === 'menunggu_sekretaris';
            const canActKetua      = userRole === 'Ketua' && status === 'menunggu_ketua';
            const canAct = canActSekretaris || canActKetua;

            if (canAct) {
                btnProses.href = `{{ route('laporan-bendahara.index') }}?bulan=${row.bulan}&tahun=${row.tahun}`;
                btnProses.style.display = 'inline-flex';
            } else {
                btnProses.style.display = 'none';
            }
        }

        const btnDetail = tr.querySelector('.btnDetail');
        if (btnDetail) {
            const editVisible   = tr.querySelector('.btnEdit')?.style.display === 'inline-flex';
            const prosesVisible = tr.querySelector('.btnProses')?.style.display === 'inline-flex';

            if (!editVisible && !prosesVisible) {
                btnDetail.href = `{{ route('laporan-bendahara.index') }}?bulan=${row.bulan}&tahun=${row.tahun}`;
                btnDetail.style.display = 'inline-flex';
            } else {
                btnDetail.style.display = 'none';
            }
        }

        const reopenBadge = tr.querySelector('.lb-reopen-badge');
        if (reopenBadge) {
            if (row.pernah_dibuka_kembali) {
                reopenBadge.style.display = 'inline-flex';

                const oleh    = row.dibuka_kembali_oleh || 'Ketua';
                const tanggal = row.dibuka_kembali_at || '';

                reopenBadge.title = tanggal
                    ? `Dibuka kembali oleh ${oleh} pada ${tanggal}`
                    : `Dibuka kembali oleh ${oleh}`;
            } else {
                reopenBadge.style.display = 'none';
            }
        }
    });

    const total = dataList.length;
    info.innerText = `Menampilkan ${start + 1} – ${Math.min(end, total)} dari ${total} data`;
    info.style.color = 'var(--text-muted)';
}

/* ── PAGINATION ──────────────────────────────────────────── */
function renderPagination() {
    pager.innerHTML = '';
    const totalPage = Math.ceil(dataList.length / perPage);
    if (totalPage <= 1) return;

    function addBtn(label, page, active = false, disabled = false) {
        const b = document.createElement('button');
        b.innerHTML = label;
        b.disabled  = disabled;
        b.onclick   = () => goPage(page);
        b.style.cssText = `
            padding:4px 12px;border:1px solid var(--border-strong);
            border-radius:8px;font-size:0.8125rem;cursor:pointer;
            background:${active ? 'var(--accent)' : 'var(--bg-surface)'};
            color:${active ? '#fff' : 'var(--text-secondary)'};
            font-family:inherit;transition:all .12s;
            ${disabled ? 'opacity:.4;cursor:not-allowed;' : ''}`;
        b.onmouseover = () => { if (!active && !disabled) b.style.background = 'var(--bg-elevated)'; };
        b.onmouseout  = () => { if (!active && !disabled) b.style.background = 'var(--bg-surface)'; };
        pager.appendChild(b);
    }

    addBtn('◀', currentPage - 1, false, currentPage === 1);
    for (let i = 1; i <= totalPage; i++) addBtn(i, i, i === currentPage);
    addBtn('▶', currentPage + 1, false, currentPage === totalPage);
}

function goPage(page) {
    const totalPage = Math.ceil(dataList.length / perPage);
    if (page < 1 || page > totalPage) return;
    currentPage = page;
    renderTable();
    renderPagination();
}

renderTable();
renderPagination();
</script>

<script>
/* ── NAVIGASI EDIT / PROSES DENGAN SPINNER ────────────────── */
let navTriggered = false;

function handleRowNavClick(e, btnClass, spinClass, iconSelector) {
    const btn = e.target.closest(btnClass);
    if (!btn || btn.classList.contains('lb-loading')) return null;
    e.preventDefault();
    navTriggered = true;
    btn.classList.add('lb-loading');

    const spin = btn.querySelector(spinClass);
    const icon = btn.querySelector(iconSelector);
    spin.style.display = 'block';
    icon.style.visibility = 'hidden';
    btn.style.cursor    = 'wait';
    btn.style.opacity   = '.8';
    btn.style.pointerEvents = 'none';

    const target = btn.href;
    setTimeout(() => { window.location.href = target; }, 120);

    setTimeout(() => {
        if (document.visibilityState === 'visible' && navTriggered) {
            btn.classList.remove('lb-loading');
            spin.style.display     = 'none';
            icon.style.visibility  = 'visible';
            btn.style.cursor       = 'pointer';
            btn.style.opacity      = '1';
            btn.style.pointerEvents = 'auto';
            showToast('Jaringan internet lambat. Silakan coba lagi.', 'warning');
        }
    }, 30000);

    return btn;
}

document.getElementById('tableBody').addEventListener('click', function(e) {
    handleRowNavClick(e, '.btnEdit', '.lb-spinner', '.lb-edit-icon');
});

document.getElementById('tableBody').addEventListener('click', function(e) {
    handleRowNavClick(e, '.btnProses', '.lb-proses-spinner', '.lb-proses-icon');
});

document.getElementById('tableBody').addEventListener('click', function(e) {
    handleRowNavClick(e, '.btnDetail', '.lb-detail-spinner', '.lb-detail-icon');
});

document.getElementById('tableBody').addEventListener('click', async function(e) {
    const btn = e.target.closest('.btnSubmit');
    if (!btn || btn.disabled) return;

    const ok = await showConfirm(
        'Data tidak bisa diubah lagi setelah diajukan.',
        { title: 'Ajukan laporan ini ke Sekretaris?', okText: 'Ya, Ajukan' }
    );
    if (!ok) return;

    const bulan = btn.dataset.bulan;
    const tahun = btn.dataset.tahun;

    const spin = btn.querySelector('.lb-submit-spinner');
    const icon = btn.querySelector('.lb-submit-icon');

    btn.disabled = true;
    spin.style.display = 'block';
    icon.style.visibility = 'hidden';

    try {
        const res = await fetch("{{ route('laporan-bendahara.submit-bendahara') }}", {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': '{{ csrf_token() }}'
            },
            body: JSON.stringify({ bulan, tahun })
        });

        const json = await res.json();
        if (!json.success) throw new Error(json.message || 'Gagal mengajukan laporan');

        const row = masterData.find(r => r.bulan == bulan && r.tahun == tahun);
        if (row) {
            row.status = 'menunggu_sekretaris';
            row.perlu_revisi = false;
        }

        applySearch(searchInput.value);
        showToast(json.message, 'success');

    } catch (err) {
        showToast(err.message || 'Terjadi kesalahan', 'error');
    } finally {
        btn.disabled = false;
        spin.style.display = 'none';
        icon.style.visibility = 'visible';
    }
});

document.addEventListener('visibilitychange', () => {
    if (document.visibilityState === 'hidden') navTriggered = false;
});
</script>

{{-- ── MODAL CONFIRM (dipakai untuk konfirmasi Ajukan) ────────── --}}
<div id="modalConfirm"
    style="position:fixed;inset:0;background:rgba(0,0,0,.45);
           backdrop-filter:blur(6px);display:none;align-items:center;
           justify-content:center;z-index:8200;padding:20px;">

    <div id="modalConfirmBox"
        style="background:var(--bg-surface);border:1px solid var(--border);
                border-radius:18px;box-shadow:var(--shadow-lg);
                width:100%;max-width:400px;padding:28px;
                transform:scale(.96);opacity:0;transition:transform .18s ease,opacity .18s ease;">

        <div style="display:flex;align-items:flex-start;gap:14px;margin-bottom:20px;">
            <div id="confirmIconWrap"
                style="flex-shrink:0;width:40px;height:40px;border-radius:50%;
                       display:flex;align-items:center;justify-content:center;
                       background:#fef3c7;color:#b45309;">
                <svg viewBox="0 0 20 20" fill="currentColor" width="22" height="22">
                    <path fill-rule="evenodd" d="M8.3 3.3a2 2 0 013.4 0l6.5 11.2a2 2 0 01-1.7 3H3.5a2 2 0 01-1.7-3L8.3 3.3zM10 7a1 1 0 00-1 1v3a1 1 0 002 0V8a1 1 0 00-1-1zm0 7a1 1 0 100 2 1 1 0 000-2z" clip-rule="evenodd"/>
                </svg>
            </div>
            <div>
                <h2 id="confirmTitle"
                    style="font-size:0.95rem;font-weight:600;color:var(--text-primary);margin:0 0 4px;">
                    Konfirmasi
                </h2>
                <p id="confirmMessage"
                    style="font-size:0.8125rem;color:var(--text-muted);margin:0;line-height:1.5;">
                </p>
            </div>
        </div>

        <div class="form-actions">
            <button type="button" id="confirmBtnCancel" class="btn btn-ghost">
                Batal
            </button>
            <button type="button" id="confirmBtnOk" class="btn btn-primary">
                Ya, Lanjutkan
            </button>
        </div>
    </div>
</div>

<script>
const _modalConfirm    = document.getElementById('modalConfirm');
const _modalConfirmBox = document.getElementById('modalConfirmBox');
const _confirmTitle    = document.getElementById('confirmTitle');
const _confirmMessage  = document.getElementById('confirmMessage');
const _confirmBtnOk    = document.getElementById('confirmBtnOk');
const _confirmBtnCancel= document.getElementById('confirmBtnCancel');

let _confirmResolver = null;

function showConfirm(message, options = {}) {
    const {
        title = 'Konfirmasi',
        okText = 'Ya, Lanjutkan',
        cancelText = 'Batal',
        danger = false,
    } = options;

    _confirmTitle.textContent   = title;
    _confirmMessage.textContent = message;
    _confirmBtnOk.textContent   = okText;
    _confirmBtnCancel.textContent = cancelText;

    _confirmBtnOk.className = danger ? 'btn' : 'btn btn-primary';
    if (danger) {
        _confirmBtnOk.style.background = '#dc2626';
        _confirmBtnOk.style.color = '#fff';
    } else {
        _confirmBtnOk.style.background = '';
        _confirmBtnOk.style.color = '';
    }

    _modalConfirmBox.style.transform = 'scale(.96)';
    _modalConfirmBox.style.opacity = '0';

    _modalConfirm.style.display = 'flex';
    document.body.style.overflow = 'hidden';

    requestAnimationFrame(() => {
        requestAnimationFrame(() => {
            _modalConfirmBox.style.transform = 'scale(1)';
            _modalConfirmBox.style.opacity = '1';
        });
    });

    return new Promise(resolve => {
        _confirmResolver = resolve;
    });
}

function _closeConfirm(result) {
    _modalConfirmBox.style.transform = 'scale(.96)';
    _modalConfirmBox.style.opacity = '0';

    setTimeout(() => {
        _modalConfirm.style.display = 'none';
        document.body.style.overflow = '';
    }, 150);

    if (_confirmResolver) {
        _confirmResolver(result);
        _confirmResolver = null;
    }
}

_confirmBtnOk.addEventListener('click', () => _closeConfirm(true));
_confirmBtnCancel.addEventListener('click', () => _closeConfirm(false));
_modalConfirm.addEventListener('click', e => {
    if (e.target === _modalConfirm) _closeConfirm(false);
});
</script>

<script>
document.addEventListener('DOMContentLoaded', () => {
    ['modalBuatLaporan', 'modalConfirm', 'toastWrap'].forEach(id => {
        const el = document.getElementById(id);
        if (el) document.body.appendChild(el);
    });
});
</script>

@endsection