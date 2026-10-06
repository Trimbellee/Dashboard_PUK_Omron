@extends('layouts.app')

@section('title', 'File Manager')

@section('content')

{{-- ── SVG sprite ─────────────────────────────────────────── --}}
<svg width="0" height="0" style="position:absolute" aria-hidden="true" focusable="false">
    <defs>
        <symbol id="i-upload" viewBox="0 0 24 24"><path d="M12 16V4M7 9l5-5 5 5M4 20h16"/></symbol>
        <symbol id="i-folder-plus" viewBox="0 0 24 24"><path d="M3 7a2 2 0 012-2h4l2 2h8a2 2 0 012 2v8a2 2 0 01-2 2H5a2 2 0 01-2-2V7zM12 11v5M9.5 13.5h5"/></symbol>
        <symbol id="i-trash" viewBox="0 0 24 24"><path d="M4 7h16M10 11v6M14 11v6M6 7l1 12a2 2 0 002 2h6a2 2 0 002-2l1-12M9 7V4h6v3"/></symbol>
        <symbol id="i-grid" viewBox="0 0 24 24"><path d="M4 4h6v6H4zM14 4h6v6h-6zM4 14h6v6H4zM14 14h6v6h-6z"/></symbol>
        <symbol id="i-list" viewBox="0 0 24 24"><path d="M4 6h16M4 12h16M4 18h16"/></symbol>
        <symbol id="i-search" viewBox="0 0 24 24"><path d="M11 4a7 7 0 100 14 7 7 0 000-14zM21 21l-4.3-4.3"/></symbol>
        <symbol id="i-more" viewBox="0 0 24 24"><circle cx="12" cy="5" r="1.7" fill="currentColor" stroke="none"/><circle cx="12" cy="12" r="1.7" fill="currentColor" stroke="none"/><circle cx="12" cy="19" r="1.7" fill="currentColor" stroke="none"/></symbol>
        <symbol id="i-download" viewBox="0 0 24 24"><path d="M12 4v12M7 11l5 5 5-5M4 20h16"/></symbol>
        <symbol id="i-edit" viewBox="0 0 24 24"><path d="M4 20h4L19 9a2.8 2.8 0 00-4-4L4 16v4zM13.5 6.5l4 4"/></symbol>
        <symbol id="i-move" viewBox="0 0 24 24"><path d="M3 7a2 2 0 012-2h4l2 2h8a2 2 0 012 2v8a2 2 0 01-2 2H5a2 2 0 01-2-2V7zM10 13h6M13.5 10.5L16 13l-2.5 2.5"/></symbol>
        <symbol id="i-x" viewBox="0 0 24 24"><path d="M6 6l12 12M18 6L6 18"/></symbol>
        <symbol id="i-open" viewBox="0 0 24 24"><path d="M14 4h6v6M20 4l-9 9M18 14v5a1 1 0 01-1 1H5a1 1 0 01-1-1V7a1 1 0 011-1h5"/></symbol>
        <symbol id="i-eye" viewBox="0 0 24 24"><path d="M2 12s3.5-7 10-7 10 7 10 7-3.5 7-10 7S2 12 2 12zM12 9a3 3 0 100 6 3 3 0 000-6z"/></symbol>
        <symbol id="i-chev-r" viewBox="0 0 24 24"><path d="M9 6l6 6-6 6"/></symbol>
        <symbol id="i-chev-l" viewBox="0 0 24 24"><path d="M15 6l-6 6 6 6"/></symbol>
        <symbol id="i-plus" viewBox="0 0 24 24"><path d="M12 5v14M5 12h14"/></symbol>
        <symbol id="i-home" viewBox="0 0 24 24"><path d="M4 11l8-7 8 7M6 10v10h12V10"/></symbol>
        <symbol id="i-check" viewBox="0 0 24 24"><path d="M5 12l4 4 10-10"/></symbol>
    </defs>
</svg>

<div class="fm" id="fm" data-view="grid">

    {{-- ── HEADER ─────────────────────────────────────────── --}}
    <div class="fm-head">
        <div>
            <h1 class="fm-title">File Manager</h1>
            <p class="fm-stats" id="fmStats">&nbsp;</p>
        </div>
        <div class="fm-head-actions">
            <button type="button" class="btn btn-primary fm-hide-md" data-action="upload">
                <svg class="i"><use href="#i-upload"/></svg><span>Upload</span>
            </button>
            <button type="button" class="btn btn-ghost fm-hide-md" data-action="new-folder">
                <svg class="i"><use href="#i-folder-plus"/></svg><span>Folder baru</span>
            </button>
            <a href="/file-manager/trash" class="btn btn-ghost" title="Trash">
                <svg class="i"><use href="#i-trash"/></svg><span class="fm-hide-xs">Trash</span>
            </a>
        </div>
    </div>

    {{-- ── TOOLBAR ────────────────────────────────────────── --}}
    <div class="fm-toolbar" id="fmToolbar">
        <div class="fm-tools" id="fmTools">
            <label class="fm-search" id="searchWrap">
                <svg class="i"><use href="#i-search"/></svg>
                <input type="search" id="searchInput" placeholder="Cari di semua folder…  ( / )" autocomplete="off" aria-label="Cari file atau folder">
                <button type="button" class="fm-search-clear" data-action="clear-search" aria-label="Hapus pencarian">
                    <svg class="i"><use href="#i-x"/></svg>
                </button>
            </label>

            <div class="fm-chips" id="fmChips" role="group" aria-label="Filter jenis file">
                <button type="button" class="fm-chip active" data-action="filter" data-filter="all">Semua</button>
                <button type="button" class="fm-chip" data-action="filter" data-filter="pdf">PDF</button>
                <button type="button" class="fm-chip" data-action="filter" data-filter="word">Word</button>
                <button type="button" class="fm-chip" data-action="filter" data-filter="excel">Excel</button>
            </div>

            <div class="fm-tools-right">
                <select id="sortSelect" class="fm-select" aria-label="Urutkan">
                    <option value="name:asc">Nama (A–Z)</option>
                    <option value="name:desc">Nama (Z–A)</option>
                    <option value="date:desc">Terbaru</option>
                    <option value="date:asc">Terlama</option>
                    <option value="size:desc">Terbesar</option>
                    <option value="size:asc">Terkecil</option>
                </select>
                <div class="fm-seg" role="group" aria-label="Tampilan">
                    <button type="button" id="viewList" data-action="view-list" title="Tampilan daftar" aria-label="Tampilan daftar"><svg class="i"><use href="#i-list"/></svg></button>
                    <button type="button" id="viewGrid" data-action="view-grid" title="Tampilan grid" aria-label="Tampilan grid"><svg class="i"><use href="#i-grid"/></svg></button>
                </div>
            </div>
        </div>

        <div class="fm-bulk" id="bulkBar">
            <button type="button" class="fm-iconbtn" data-action="clear-selection" aria-label="Batalkan pilihan"><svg class="i"><use href="#i-x"/></svg></button>
            <strong><span id="bulkCount">0</span> dipilih</strong>
            <span class="fm-spacer"></span>
            <button type="button" class="btn btn-ghost" data-action="bulk-move"><svg class="i"><use href="#i-move"/></svg><span>Pindahkan</span></button>
            <button type="button" class="btn fm-danger" data-action="bulk-delete"><svg class="i"><use href="#i-trash"/></svg><span>Hapus</span></button>
        </div>
    </div>

    {{-- ── BREADCRUMB ─────────────────────────────────────── --}}
    <div class="fm-crumbs" id="crumbs" role="navigation" aria-label="Lokasi folder"></div>

    {{-- ── LIST PANEL ─────────────────────────────────────── --}}
    <div class="fm-panel">
        <div class="fm-scroll" id="fmScroll">
            <div class="fm-listhead" id="listHead">
                <span></span><span></span>
                <button type="button" data-action="sort-col" data-sort="name">Nama <i data-ind="name"></i></button>
                <button type="button" data-action="sort-col" data-sort="size">Ukuran <i data-ind="size"></i></button>
                <button type="button" data-action="sort-col" data-sort="date">Diubah <i data-ind="date"></i></button>
                <span></span>
            </div>
            <div class="fm-list grid" id="fmList"></div>
            <div class="fm-empty" id="fmEmpty"></div>
            <div class="fm-sentinel" id="fmSentinel"></div>
        </div>

        <div class="fm-dropzone" id="dropOverlay">
            <div>
                <svg class="i fm-drop-icon"><use href="#i-upload"/></svg>
                <strong>Lepas file di sini untuk upload</strong>
                <span>PDF, Word, Excel · maks. 10 MB · PDF dikompres otomatis</span>
            </div>
        </div>
    </div>
</div>

{{-- ── FAB (mobile) ───────────────────────────────────────── --}}
<div class="fm-fabwrap" id="fabWrap">
    <div class="fm-fabmenu" id="fabMenu">
        <button type="button" data-action="upload"><svg class="i"><use href="#i-upload"/></svg>Upload file</button>
        <button type="button" data-action="new-folder"><svg class="i"><use href="#i-folder-plus"/></svg>Folder baru</button>
    </div>
    <button type="button" class="fm-fab" data-action="fab-toggle" aria-label="Tambah"><svg class="i"><use href="#i-plus"/></svg></button>
</div>

<input type="file" id="fileInput" multiple hidden accept=".pdf,.doc,.docx,.xls,.xlsx">

{{-- ── CLIPBOARD PILL ─────────────────────────────────────── --}}
<div class="fm-pill" id="clipPill">
    <span id="clipText"></span>
    <button type="button" class="btn btn-primary" data-action="paste">Tempel di sini</button>
    <button type="button" class="fm-iconbtn" data-action="clear-clip" aria-label="Batal"><svg class="i"><use href="#i-x"/></svg></button>
</div>

{{-- ── UPLOAD PANEL ───────────────────────────────────────── --}}
<div class="fm-up" id="upPanel">
    <div class="fm-up-head">
        <strong id="upTitle">Mengunggah…</strong>
        <span>
            <button type="button" class="fm-iconbtn" data-action="up-toggle" aria-label="Ciutkan"><svg class="i"><use href="#i-chev-l" style="transform:rotate(-90deg);transform-origin:center"/></svg></button>
            <button type="button" class="fm-iconbtn" data-action="up-close" aria-label="Tutup"><svg class="i"><use href="#i-x"/></svg></button>
        </span>
    </div>
    <div class="fm-up-list" id="upList"></div>
</div>

{{-- ── CONTEXT MENU + BOTTOM SHEET ────────────────────────── --}}
<div class="fm-ctx" id="ctx"></div>
<div class="fm-sheet" id="sheet">
    <div class="fm-sheet-bg" data-action="close-menus"></div>
    <div class="fm-sheet-panel">
        <div class="fm-sheet-grab"></div>
        <div class="fm-sheet-title" id="sheetTitle"></div>
        <div id="sheetBody"></div>
    </div>
</div>

{{-- ── MODALS ─────────────────────────────────────────────── --}}
<div class="fm-modal" id="folderModal">
    <div class="fm-box">
        <h2>Folder baru</h2>
        <input type="text" id="folderName" class="fm-input" placeholder="Nama folder" maxlength="255" autocomplete="off">
        <div class="fm-box-actions">
            <button type="button" class="btn btn-ghost" data-action="close-modal" data-modal="folderModal">Batal</button>
            <button type="button" class="btn btn-primary" data-action="create-folder">Buat</button>
        </div>
    </div>
</div>

<div class="fm-modal" id="renameModal">
    <div class="fm-box">
        <h2>Ganti nama</h2>
        <input type="text" id="renameInput" class="fm-input" maxlength="255" autocomplete="off">
        <div class="fm-box-actions">
            <button type="button" class="btn btn-ghost" data-action="close-modal" data-modal="renameModal">Batal</button>
            <button type="button" class="btn btn-primary" data-action="submit-rename">Simpan</button>
        </div>
    </div>
</div>

<div class="fm-modal" id="confirmModal">
    <div class="fm-box">
        <h2 id="confirmTitle">Konfirmasi</h2>
        <p id="confirmMsg" class="fm-box-text"></p>
        <div class="fm-box-actions">
            <button type="button" class="btn btn-ghost" data-action="close-modal" data-modal="confirmModal">Batal</button>
            <button type="button" class="btn fm-danger" id="confirmOk" data-action="confirm-ok">Hapus</button>
        </div>
    </div>
</div>

<div class="fm-modal" id="moveModal">
    <div class="fm-box fm-box-md">
        <h2>Pindahkan ke…</h2>
        <div class="fm-tree" id="moveTree"></div>
        <div class="fm-box-actions">
            <button type="button" class="btn btn-ghost" data-action="close-modal" data-modal="moveModal">Batal</button>
            <button type="button" class="btn btn-primary" id="moveOk" data-action="move-ok" disabled>Pindahkan</button>
        </div>
    </div>
</div>

<div class="fm-modal fm-pv" id="previewModal">
    <div class="fm-box fm-box-pv">
        <div class="fm-pv-head">
            <strong id="pvName" class="fm-pv-name"></strong>
            <span class="fm-pv-tools">
                <button type="button" class="fm-iconbtn" id="pvPrev" data-action="pv-prev" aria-label="Sebelumnya"><svg class="i"><use href="#i-chev-l"/></svg></button>
                <button type="button" class="fm-iconbtn" id="pvNext" data-action="pv-next" aria-label="Berikutnya"><svg class="i"><use href="#i-chev-r"/></svg></button>
                <a class="fm-iconbtn" id="pvOpen" target="_blank" rel="noopener" title="Buka di tab baru"><svg class="i"><use href="#i-open"/></svg></a>
                <a class="fm-iconbtn" id="pvDownload" title="Unduh"><svg class="i"><use href="#i-download"/></svg></a>
                <button type="button" class="fm-iconbtn" data-action="close-modal" data-modal="previewModal" aria-label="Tutup"><svg class="i"><use href="#i-x"/></svg></button>
            </span>
        </div>
        <div class="fm-pv-body" id="pvBody"></div>
    </div>
</div>

<div class="fm-modal" id="helpModal">
    <div class="fm-box">
        <h2>Pintasan keyboard</h2>
        <dl class="fm-keys">
            <dt><kbd>/</kbd></dt><dd>Fokus ke pencarian</dd>
            <dt><kbd>Ctrl</kbd>+<kbd>A</kbd></dt><dd>Pilih semua</dd>
            <dt><kbd>Enter</kbd></dt><dd>Buka folder / pratinjau file</dd>
            <dt><kbd>Spasi</kbd></dt><dd>Pratinjau file</dd>
            <dt><kbd>F2</kbd></dt><dd>Ganti nama</dd>
            <dt><kbd>Ctrl</kbd>+<kbd>X</kbd> / <kbd>V</kbd></dt><dd>Potong / tempel (pindahkan)</dd>
            <dt><kbd>Delete</kbd></dt><dd>Hapus ke Trash</dd>
            <dt><kbd>←</kbd><kbd>↑</kbd><kbd>→</kbd><kbd>↓</kbd></dt><dd>Pindah pilihan</dd>
            <dt><kbd>Esc</kbd></dt><dd>Tutup / batalkan pilihan</dd>
        </dl>
        <div class="fm-box-actions">
            <button type="button" class="btn btn-primary" data-action="close-modal" data-modal="helpModal">Tutup</button>
        </div>
    </div>
</div>

<button type="button" class="fm-helpbtn" data-action="help" title="Pintasan keyboard (?)" aria-label="Pintasan keyboard">?</button>

<script>
(function () {
'use strict';
if (window.fmCleanup) { try { window.fmCleanup(); } catch (e) {} }

/* ============================================================
   CONFIG & STATE
   ============================================================ */
const $ = id => document.getElementById(id);
const CSRF = document.querySelector('meta[name="csrf-token"]')?.content || '';
const ALLOWED = new Set(['pdf', 'doc', 'docx', 'xls', 'xlsx']);
const MAX_MB = 10;
const CONCURRENCY = 3;
const CACHE_TTL = 20000;
let lastPointer = 'mouse';   // 'mouse' | 'touch' | 'pen' — alat yang terakhir dipakai
const isTouchPointer = () => lastPointer === 'touch';

// state double-tap untuk mode sentuh
let lastTapId = null;
let lastTapAt = 0;
let singleTapTimer = null;
const DOUBLE_TAP_MS = 320;
const initFolder = @json($parentId ?? null);

function readJSON(key, fallback) {
    try { const v = JSON.parse(localStorage.getItem(key)); return v ?? fallback; } catch (e) { return fallback; }
}

const S = {
    folder: initFolder ? Number(initFolder) : null,
    crumbs: @json($breadcrumbs ?? []),
    items: [], page: 1, hasMore: true, loading: false, error: false,
    q: '', filter: 'all',
    sort: localStorage.getItem('fm_sort') || 'name',
    dir: localStorage.getItem('fm_dir') || 'asc',
    view: localStorage.getItem('fm_view') || (window.innerWidth < 640 ? 'list' : 'grid'),
    sel: new Set(), anchor: -1,
    clip: readJSON('fm_clip', []),
};
if (!['name', 'date', 'size'].includes(S.sort)) S.sort = 'name';
if (!['asc', 'desc'].includes(S.dir)) S.dir = 'asc';
if (!['grid', 'list'].includes(S.view)) S.view = 'grid';
if (!Array.isArray(S.clip)) S.clip = [];

const byId = new Map();
const nodes = new Map();
const cache = new Map();
let reqId = 0, ctrl = null;
let menuIds = [];

const el = {
    fm: $('fm'), stats: $('fmStats'), toolbar: $('fmToolbar'), search: $('searchInput'), searchWrap: $('searchWrap'),
    sort: $('sortSelect'), bulk: $('bulkBar'), crumbs: $('crumbs'), scroll: $('fmScroll'), list: $('fmList'),
    empty: $('fmEmpty'), sentinel: $('fmSentinel'), drop: $('dropOverlay'), ctx: $('ctx'), sheet: $('sheet'),
    fileInput: $('fileInput'), fabMenu: $('fabMenu'), upPanel: $('upPanel'), upList: $('upList'), pill: $('clipPill'),
    folderName: $('folderName'), renameInput: $('renameInput'),
};

/* ============================================================
   HELPERS
   ============================================================ */
function toast(msg, type = 'success') {
    if (typeof showToast === 'function') showToast(msg, type === 'error');
    else console.log(msg);
}
function esc(s) {
    return String(s ?? '').replace(/&/g, '&amp;').replace(/</g, '&lt;').replace(/>/g, '&gt;').replace(/"/g, '&quot;').replace(/'/g, '&#039;');
}
function extOf(name) { const i = String(name).lastIndexOf('.'); return i < 0 ? '' : String(name).slice(i + 1).toLowerCase(); }
function fmtSize(b) {
    if (b === null || b === undefined || b === '') return '—';
    b = Number(b);
    if (b < 1024) return b + ' B';
    if (b < 1048576) return (b / 1024).toFixed(b < 10240 ? 1 : 0) + ' KB';
    if (b < 1073741824) return (b / 1048576).toFixed(1) + ' MB';
    return (b / 1073741824).toFixed(2) + ' GB';
}
function fmtDate(it, short) {
    const d = it.updated_at ? new Date(it.updated_at) : null;
    if (!d || isNaN(d)) return it.updated_at_formatted || '—';
    return short
        ? d.toLocaleDateString('id-ID', { day: '2-digit', month: 'short', year: 'numeric' })
        : d.toLocaleString('id-ID', { day: '2-digit', month: 'short', year: 'numeric', hour: '2-digit', minute: '2-digit' });
}
async function api(method, url, body) {
    const opts = { method, headers: { 'X-CSRF-TOKEN': CSRF, 'Accept': 'application/json' } };
    if (body !== undefined) { opts.headers['Content-Type'] = 'application/json'; opts.body = JSON.stringify(body); }
    try {
        const res = await fetch(url, opts);
        const json = await res.json().catch(() => ({}));
        if (json.success === undefined) json.success = res.ok;
        if (!json.success && !json.message) json.message = 'Terjadi kesalahan (' + res.status + ')';
        return json;
    } catch (e) {
        return { success: false, message: 'Koneksi terputus' };
    }
}

/* ============================================================
   ICONS
   ============================================================ */
const FOLDER_SVG = '<svg class="fm-folder" viewBox="0 0 24 24" aria-hidden="true"><path d="M3 6.5A2.5 2.5 0 015.5 4h3.4c.5 0 1 .2 1.4.6L12 6.3h6.5A2.5 2.5 0 0121 8.8v8.7a2.5 2.5 0 01-2.5 2.5h-13A2.5 2.5 0 013 17.5v-11z"/></svg>';
const KINDS = {
    pdf: { label: 'PDF', cls: 'k-pdf' }, doc: { label: 'DOC', cls: 'k-word' }, docx: { label: 'DOC', cls: 'k-word' },
    xls: { label: 'XLS', cls: 'k-excel' }, xlsx: { label: 'XLS', cls: 'k-excel' },
};
function iconHTML(it) {
    if (it.type === 'folder') return FOLDER_SVG;
    const k = KINDS[it.ext] || { label: (it.ext || 'FILE').toUpperCase().slice(0, 4), cls: 'k-other' };
    return '<span class="fm-badge ' + k.cls + '">' + k.label + '</span>';
}

/* ============================================================
   RENDER
   ============================================================ */
function itemHTML(it) {
    const sel = S.sel.has(it.id) ? ' fm-selected' : '';
    const drop = it.type === 'folder' ? ' fm-droptarget' : '';
    const size = it.type === 'folder' ? '—' : fmtSize(it.size);
    const thumb = iconHTML(it);
    const sub = it.location
        ? '<div class="fm-sub loc">' + esc(it.location) + '</div>'
        : '<div class="fm-sub">' + [it.type === 'file' ? size : 'Folder', fmtDate(it, true)].join(' • ') + '</div>';
    return '<div class="fm-item' + sel + drop + '" data-id="' + it.id + '" data-type="' + it.type + '" draggable="true" title="' + esc(it.name) + '">'
        + '<button type="button" class="fm-check" data-check aria-label="Pilih"><svg class="i"><use href="#i-check"/></svg></button>'
        + '<div class="fm-thumb">' + thumb + '</div>'
        + '<div class="fm-meta"><div class="fm-name">' + esc(it.name) + '</div>' + sub + '</div>'
        + '<div class="fm-col fm-size">' + size + '</div>'
        + '<div class="fm-col fm-date">' + fmtDate(it, false) + '</div>'
        + '<button type="button" class="fm-more" data-more aria-label="Menu"><svg class="i"><use href="#i-more"/></svg></button>'
        + '</div>';
}
function appendItems(arr) {
    if (!arr.length) return;
    const tmp = document.createElement('div');
    tmp.innerHTML = arr.map(itemHTML).join('');
    const frag = document.createDocumentFragment();
    Array.from(tmp.children).forEach(n => { nodes.set(Number(n.dataset.id), n); frag.appendChild(n); });
    el.list.appendChild(frag);
}
function rerender() {
    el.list.innerHTML = '';
    nodes.clear();
    appendItems(S.items);
}
function showSkeleton() {
    const n = S.view === 'grid' ? 10 : 8;
    let h = '';
    for (let i = 0; i < n; i++) {
        h += '<div class="fm-item fm-skel"><div class="fm-thumb"></div><div class="fm-meta"><div class="fm-sk w60"></div><div class="fm-sk w30"></div></div></div>';
    }
    el.list.innerHTML = h;
}
function emptyHTML(icon, title, text, cta) {
    return '<div class="fm-empty-icon">' + icon + '</div><strong>' + title + '</strong><span>' + text + '</span>' + (cta ? '<div class="fm-empty-cta">' + cta + '</div>' : '');
}
function updateEmpty() {
    const e = el.empty;
    if (S.loading) { e.classList.remove('show'); return; }
    if (S.error) {
        e.innerHTML = emptyHTML('⚠️', 'Gagal memuat data', 'Periksa koneksi Anda lalu coba lagi.', '<button type="button" class="btn btn-primary" data-action="retry">Coba lagi</button>');
        e.classList.add('show'); return;
    }
    if (S.items.length) { e.classList.remove('show'); return; }
    if (S.q) e.innerHTML = emptyHTML('🔎', 'Tidak ada hasil', 'Tidak ada file atau folder yang cocok dengan “' + esc(S.q) + '”.');
    else if (S.filter !== 'all') e.innerHTML = emptyHTML('🗂️', 'Tidak ada file jenis ini', 'Coba pilih filter lain atau upload file baru.');
    else e.innerHTML = emptyHTML('📂', 'Folder ini masih kosong', 'Seret file ke sini, atau gunakan tombol di bawah.',
        '<button type="button" class="btn btn-primary" data-action="upload"><svg class="i"><use href="#i-upload"/></svg><span>Upload file</span></button>'
        + '<button type="button" class="btn btn-ghost" data-action="new-folder"><svg class="i"><use href="#i-folder-plus"/></svg><span>Folder baru</span></button>');
    e.classList.add('show');
}
function renderStats(st) {
    el.stats.textContent = st.files + ' file • ' + fmtSize(st.size) + ' digunakan';
}
function renderCrumbs() {
    if (S.q) {
        el.crumbs.innerHTML = '<span class="fm-crumb-text">Hasil pencarian “' + esc(S.q) + '” di semua folder</span>'
            + '<button type="button" class="fm-crumb" data-action="clear-search">Kembali</button>';
        return;
    }
    let h = '<button type="button" class="fm-crumb fm-droptarget' + (S.crumbs.length ? '' : ' current') + '" data-action="crumb" data-go="" data-target=""><svg class="i"><use href="#i-home"/></svg>Home</button>';
    S.crumbs.forEach((c, i) => {
        const last = i === S.crumbs.length - 1;
        h += '<svg class="i sep"><use href="#i-chev-r"/></svg><button type="button" class="fm-crumb fm-droptarget' + (last ? ' current' : '') + '" data-action="crumb" data-go="' + c.id + '" data-target="' + c.id + '">' + esc(c.name) + '</button>';
    });
    el.crumbs.innerHTML = h;
    el.crumbs.scrollLeft = el.crumbs.scrollWidth;
}
function updateViewUI() {
    el.fm.dataset.view = S.view;
    el.list.className = 'fm-list ' + S.view;
    $('viewList').classList.toggle('active', S.view === 'list');
    $('viewGrid').classList.toggle('active', S.view === 'grid');
    el.sort.value = S.sort + ':' + S.dir;
    document.querySelectorAll('[data-ind]').forEach(i => {
        i.textContent = i.dataset.ind === S.sort ? (S.dir === 'asc' ? '↑' : '↓') : '';
    });
}

/* ============================================================
   DATA LOADING
   ============================================================ */
async function load(reset = true) {
    if (reset) { if (ctrl) ctrl.abort(); }
    else if (S.loading || !S.hasMore) return;

    const my = ++reqId;
    ctrl = new AbortController();
    S.loading = true;
    S.error = false;

    if (reset) {
        S.page = 1; S.hasMore = true; S.items = [];
        byId.clear(); nodes.clear();
        S.sel = new Set(); S.anchor = -1; syncSel();
        el.empty.classList.remove('show');
        showSkeleton();
    } else {
        el.sentinel.classList.add('loading');
    }

    try {
        const key = JSON.stringify([S.folder, S.q, S.filter, S.sort, S.dir, S.page]);
        const hit = cache.get(key);
        let json;
        if (hit && Date.now() - hit.ts < CACHE_TTL) {
            json = hit.json;
        } else {
            const params = new URLSearchParams({
                folder: S.folder ?? '', page: S.page, search: S.q, filter: S.filter,
                sort_field: S.sort, sort_direction: S.dir,
            });
            const res = await fetch('/file-manager/data?' + params, { signal: ctrl.signal, cache: 'no-store', headers: { 'Accept': 'application/json' } });
            if (!res.ok) throw new Error('HTTP ' + res.status);
            json = await res.json();
            cache.set(key, { json: json, ts: Date.now() });
        }
        if (my !== reqId) return;

        if (json.breadcrumbs) { S.crumbs = json.breadcrumbs; renderCrumbs(); }
        if (json.stats) renderStats(json.stats);

        const fresh = json.data || [];
        if (reset) el.list.innerHTML = '';
        fresh.forEach(i => byId.set(i.id, i));
        S.items.push(...fresh);
        appendItems(fresh);
        S.hasMore = !!json.has_more;
        S.page++;
    } catch (err) {
        if (err.name === 'AbortError') return;
        console.error(err);
        if (my === reqId) {
            S.error = true;
            if (reset) el.list.innerHTML = '';
            toast('Gagal memuat data', 'error');
        }
    } finally {
        if (my === reqId) {
            S.loading = false;
            el.sentinel.classList.remove('loading');
            updateEmpty();
            requestAnimationFrame(maybeLoadMore);
        }
    }
}
function maybeLoadMore() {
    if (!S.hasMore || S.loading) return;
    const r = el.sentinel.getBoundingClientRect();
    const sr = el.scroll.getBoundingClientRect();
    if (r.top < sr.bottom + 300) load(false);
}
function invalidate() { cache.clear(); }
function reload() { invalidate(); return load(true); }

/* ============================================================
   NAVIGATION
   ============================================================ */
function goFolder(id, push = true) {
    S.folder = id ?? null;
    S.q = ''; el.search.value = ''; el.searchWrap.classList.remove('has-val');
    if (push) history.pushState({ folder: S.folder }, '', S.folder ? '/file-manager?folder=' + S.folder : '/file-manager');
    renderCrumbs();
    load(true);
}
function onPopState() {
    const f = new URLSearchParams(location.search).get('folder');
    goFolder(f ? Number(f) : null, false);
}
function openFileInNewTab(it) {
    if (!it || !it.url) return;
    if (it.ext !== 'pdf') { downloadItem(it.id); return; }
    window.open(it.url, '_blank', 'noopener');
}
function activate(it) {
    if (!it) return;
    if (it.type === 'folder') goFolder(it.id);
    else openFileInNewTab(it);
}

/* ============================================================
   SELECTION
   ============================================================ */
function syncSel() {
    nodes.forEach((n, id) => n.classList.toggle('fm-selected', S.sel.has(id)));
    const n = S.sel.size;
    el.bulk.classList.toggle('show', n > 0);
    el.toolbar.classList.toggle('has-sel', n > 0);
    el.list.classList.toggle('select-mode', n > 0);
    $('bulkCount').textContent = n;
}
function idxOf(id) { return S.items.findIndex(i => i.id === id); }
function clickSelect(id, e) {
    const idx = idxOf(id);
    if (e.shiftKey && S.anchor >= 0) {
        const a = Math.min(S.anchor, idx), b = Math.max(S.anchor, idx);
        const next = new Set((e.ctrlKey || e.metaKey) ? S.sel : []);
        for (let i = a; i <= b; i++) next.add(S.items[i].id);
        S.sel = next;
    } else if (e.ctrlKey || e.metaKey) {
        S.sel.has(id) ? S.sel.delete(id) : S.sel.add(id);
        S.anchor = idx;
    } else {
        S.sel = new Set([id]); S.anchor = idx;
    }
    syncSel();
}
function toggleSel(id) {
    S.sel.has(id) ? S.sel.delete(id) : S.sel.add(id);
    S.anchor = idxOf(id);
    syncSel();
}
function clearSel() { S.sel = new Set(); S.anchor = -1; syncSel(); }

/* ============================================================
   LIST EVENTS (click, dblclick, context, long-press)
   ============================================================ */
let longPressed = false, lpTimer = null, lpStart = null;

/* tooltip nama file (layar sentuh tidak punya hover) */
let tipEl = null, tipTimer = null;
function showNameTip(node, it, withHint) {
    hideNameTip();
    tipEl = document.createElement('div');
    tipEl.className = 'fm-tip';
    tipEl.innerHTML = '<div class="fm-tip-name">' + esc(it.name) + '</div>'
        + (withHint ? '<div class="fm-tip-hint">Ketuk dua kali untuk membuka</div>' : '');
    document.body.appendChild(tipEl);

    const r = node.getBoundingClientRect();
    const w = tipEl.offsetWidth, h = tipEl.offsetHeight;
    let left = r.left + r.width / 2 - w / 2;
    left = Math.max(8, Math.min(left, window.innerWidth - w - 8));
    let top = r.top - h - 8;                                   // di atas item
    if (top < 8) top = Math.min(r.bottom + 8, window.innerHeight - h - 8);   // kalau tidak muat, di bawah
    tipEl.style.left = left + 'px';
    tipEl.style.top = top + 'px';

    const el2 = tipEl;
    requestAnimationFrame(() => el2.classList.add('show'));
    tipTimer = setTimeout(hideNameTip, 2800);
}
function hideNameTip() {
    clearTimeout(tipTimer);
    if (tipEl) { tipEl.remove(); tipEl = null; }
}

function onListClick(e) {
    const node = e.target.closest('.fm-item');
    if (!node || node.classList.contains('fm-skel')) return;
    const id = Number(node.dataset.id);
    if (longPressed) { longPressed = false; return; }

    // titik tiga → menu
    if (e.target.closest('[data-more]')) {
        const ids = (S.sel.has(id) && S.sel.size > 1) ? Array.from(S.sel) : [id];
        const r = e.target.closest('[data-more]').getBoundingClientRect();
        showMenu(ids, r.right - 210, r.bottom + 4);
        return;
    }

    // bulatan → pilih / batal pilih
    if (e.target.closest('[data-check]')) { toggleSel(id); return; }

    // Ctrl/Shift + klik tetap bisa memilih banyak item (desktop)
    if (!isTouchPointer() && (e.ctrlKey || e.metaKey || e.shiftKey)) { clickSelect(id, e); return; }

    const it = byId.get(id);
    if (!it) return;

    if (isTouchPointer()) {
        // sedang mode pilih: ketuk = pilih / batal pilih
        if (S.sel.size) { hideNameTip(); toggleSel(id); return; }

        // folder: ketuk satu kali membuka
        if (it.type === 'folder') { hideNameTip(); goFolder(it.id); return; }

        // file: ketukan pertama menampilkan nama lengkap, ketukan kedua membuka
        const now = Date.now();
        if (lastTapId === id && now - lastTapAt < 400) {
            lastTapId = null;
            hideNameTip();
            localStorage.setItem('fm_tap_hint_done', '1');
            openFileInNewTab(it);
        } else {
            lastTapId = id;
            lastTapAt = now;
            showNameTip(node, it, !localStorage.getItem('fm_tap_hint_done'));
        }
        return;
    }
    // desktop: klik sekali tidak melakukan apa-apa (buka dengan klik dua kali)
}

function onListDblClick(e) {
    if (isTouchPointer()) return;
    const node = e.target.closest('.fm-item');
    if (!node || e.target.closest('[data-more],[data-check]')) return;
    activate(byId.get(Number(node.dataset.id)));
}

function onContextCapture(e) {
    if (!el.scroll.contains(e.target)) return;      // hanya di area daftar
    const node = e.target.closest('.fm-item');

    if (isTouchPointer()) { if (node) e.preventDefault(); return; }

    if (node && !node.classList.contains('fm-skel')) {
        e.preventDefault();
        const id = Number(node.dataset.id);
        const ids = (S.sel.has(id) && S.sel.size > 1) ? Array.from(S.sel) : [id];
        showMenu(ids, e.clientX, e.clientY);
        return;
    }
    if (S.clip.length) {                             // area kosong: menu tempel
        e.preventDefault();
        closeMenus();
        el.ctx.innerHTML = menuBtn('paste', 'move', 'Tempel di sini (' + S.clip.length + ')');
        placeCtx(e.clientX, e.clientY);
    }
}

function onTouchStart(e) {
    const n = e.target.closest('.fm-item');
    if (!n || n.classList.contains('fm-skel') || e.target.closest('[data-more]')) return;
    longPressed = false;
    const t = e.touches[0];
    lpStart = { x: t.clientX, y: t.clientY };
    lpTimer = setTimeout(() => {
        longPressed = true;
        toggleSel(Number(n.dataset.id));
        if (navigator.vibrate) navigator.vibrate(20);
    }, 450);
}
function onTouchMove(e) {
    if (!lpStart) return;
    const t = e.touches[0];
    if (Math.abs(t.clientX - lpStart.x) > 10 || Math.abs(t.clientY - lpStart.y) > 10) clearTimeout(lpTimer);
}
function onTouchEnd() { clearTimeout(lpTimer); setTimeout(() => { longPressed = false; }, 350); }

/* ============================================================
   MENUS (desktop context menu / mobile bottom sheet)
   ============================================================ */
function menuBtn(act, icon, label, cls) {
    return '<button type="button" class="fm-mi ' + (cls || '') + '" data-act="' + act + '"><svg class="i"><use href="#i-' + icon + '"/></svg><span>' + label + '</span></button>';
}
function menuHTML(ids) {
    if (ids.length > 1) {
        return menuBtn('move', 'move', 'Pindahkan ' + ids.length + ' item…') + '<div class="fm-sep"></div>' + menuBtn('delete', 'trash', 'Hapus ' + ids.length + ' item', 'danger');
    }
    const it = byId.get(ids[0]);
    if (!it) return '';
    let h = it.type === 'folder' ? menuBtn('open', 'open', 'Buka') : menuBtn('open', 'eye', 'Pratinjau');
    if (it.type === 'file') h += menuBtn('download', 'download', 'Unduh');
    if (it.location) h += menuBtn('locate', 'home', 'Buka lokasi');
    h += menuBtn('rename', 'edit', 'Ganti nama') + menuBtn('move', 'move', 'Pindahkan ke…') + '<div class="fm-sep"></div>' + menuBtn('delete', 'trash', 'Hapus', 'danger');
    return h;
}
function placeCtx(x, y) {
    el.ctx.style.display = 'block';
    const w = el.ctx.offsetWidth, h = el.ctx.offsetHeight;
    el.ctx.style.left = Math.max(8, Math.min(x, window.innerWidth - w - 8)) + 'px';
    el.ctx.style.top = Math.max(8, Math.min(y, window.innerHeight - h - 8)) + 'px';
}
function showMenu(ids, x, y) {
    closeMenus();
    if (!ids.length) return;
    menuIds = ids;
    if (window.innerWidth < 768) {
        const one = ids.length === 1 ? byId.get(ids[0]) : null;
        $('sheetTitle').textContent = one ? one.name : ids.length + ' item dipilih';
        $('sheetBody').innerHTML = menuHTML(ids);
        el.sheet.classList.add('open');
        return;
    }
    el.ctx.innerHTML = menuHTML(ids);
    placeCtx(x, y);
}
function closeMenus() {
    el.ctx.style.display = 'none';
    el.sheet.classList.remove('open');
     menuIds = [];
}
function runAct(act, ids) {
    ids = ids && ids.length ? ids : (menuIds.length ? menuIds : Array.from(S.sel));
    const it = ids.length === 1 ? byId.get(ids[0]) : null;
    switch (act) {
        case 'open': activate(it); break;
        case 'download': if (it) downloadItem(it.id); break;
        case 'locate': if (it) goFolder(it.parent_id ?? null); break;
        case 'rename': if (it) openRename(it.id); break;
        case 'move': openMove(ids); break;
        case 'delete': confirmDelete(ids); break;
        case 'paste': paste(); break;
    }
}
function downloadItem(id) {
    const a = document.createElement('a');
    a.href = '/file-manager/download/' + id;
    document.body.appendChild(a); a.click(); a.remove();
}

/* ============================================================
   MODALS
   ============================================================ */
function openModal(id) { $(id).classList.add('open'); document.body.style.overflow = 'hidden'; }
function closeModal(id) {
    $(id).classList.remove('open');
    if (id === 'previewModal') $('pvBody').innerHTML = '';
    if (!document.querySelector('.fm-modal.open')) document.body.style.overflow = '';
}
function closeAllModals() { document.querySelectorAll('.fm-modal.open').forEach(m => closeModal(m.id)); }
function anyModalOpen() { return !!document.querySelector('.fm-modal.open'); }

let confirmCb = null;
function openConfirm(title, msg, okLabel, cb) {
    $('confirmTitle').textContent = title;
    $('confirmMsg').textContent = msg;
    $('confirmOk').textContent = okLabel;
    confirmCb = cb;
    openModal('confirmModal');
    setTimeout(() => $('confirmOk').focus(), 30);
}

/* ---- folder baru ---- */
function openFolderModal() {
    el.folderName.value = '';
    openModal('folderModal');
    setTimeout(() => el.folderName.focus(), 50);
}
async function createFolder() {
    const name = el.folderName.value.trim();
    if (!name) { toast('Nama folder wajib diisi', 'error'); return; }
    const r = await api('POST', '/file-manager/folder', { name: name, parent_id: S.folder });
    if (!r.success) { toast(r.message, 'error'); return; }
    closeModal('folderModal');
    toast('Folder dibuat');
    reload();
}

/* ---- rename ---- */
let renameId = null;
function openRename(id) {
    const it = byId.get(id);
    if (!it) return;
    renameId = id;
    el.renameInput.value = it.name;
    openModal('renameModal');
    setTimeout(() => {
        el.renameInput.focus();
        const dot = it.type === 'file' ? it.name.lastIndexOf('.') : -1;
        el.renameInput.setSelectionRange(0, dot > 0 ? dot : it.name.length);
    }, 50);
}
async function submitRename() {
    const it = byId.get(renameId);
    if (!it) return;
    let name = el.renameInput.value.trim();
    if (!name) { toast('Nama tidak boleh kosong', 'error'); return; }
    if (it.type === 'file' && it.ext && !name.toLowerCase().endsWith('.' + it.ext)) name += '.' + it.ext;
    if (name === it.name) { closeModal('renameModal'); return; }
    const r = await api('POST', '/file-manager/rename/' + renameId, { name: name });
    if (!r.success) { toast(r.message, 'error'); return; }
    closeModal('renameModal');
    toast('Nama diubah');
    reload();
}

/* ---- hapus ---- */
function confirmDelete(ids) {
    if (!ids.length) return;
    const it = ids.length === 1 ? byId.get(ids[0]) : null;
    const what = it ? '"' + it.name + '"' : ids.length + ' item';
    openConfirm('Pindahkan ke Trash?', what + ' akan dipindahkan ke Trash dan dihapus permanen otomatis setelah 30 hari.', 'Hapus', async () => {
        const r = ids.length > 1
            ? await api('DELETE', '/file-manager/bulk-delete', { ids: ids })
            : await api('DELETE', '/file-manager/delete/' + ids[0]);
        if (!r.success) { toast(r.message || 'Gagal menghapus', 'error'); return; }
        toast('Dipindahkan ke Trash');
        reload();
    });
}

/* ---- pindah (dialog pohon folder) ---- */
let moveIds = [], moveTarget;
async function openMove(ids) {
    if (!ids.length) return;
    moveIds = ids; moveTarget = undefined;
    $('moveOk').disabled = true;
    $('moveOk').textContent = 'Pindahkan';
    $('moveTree').innerHTML = '<div class="fm-tree-msg">Memuat folder…</div>';
    openModal('moveModal');
    try {
        const res = await fetch('/file-manager/folders-tree', { headers: { 'Accept': 'application/json' } });
        if (!res.ok) throw new Error('HTTP ' + res.status);
        const tree = await res.json();
        const blocked = new Set(ids);
        const build = (list, depth) => list.filter(n => !blocked.has(n.id)).map(n => {
            const kids = (n.children || []).filter(c => !blocked.has(c.id));
            return '<div class="fm-tn"><div class="fm-trow" style="padding-left:' + (10 + depth * 18) + 'px" data-pick="' + n.id + '" data-name="' + esc(n.name) + '">'
                + '<button type="button" class="fm-tgl' + (kids.length ? '' : ' off') + '" data-toggle aria-label="Buka"><svg class="i"><use href="#i-chev-r"/></svg></button>'
                + '<span class="fm-tico">' + FOLDER_SVG + '</span><span class="fm-tname">' + esc(n.name) + '</span></div>'
                + (kids.length ? '<div class="fm-tkids" hidden>' + build(kids, depth + 1) + '</div>' : '') + '</div>';
        }).join('');
        $('moveTree').innerHTML = '<div class="fm-tn"><div class="fm-trow" data-pick="root" data-name="Home" style="padding-left:10px"><span class="fm-tgl off"></span><span class="fm-tico"><svg class="i"><use href="#i-home"/></svg></span><span class="fm-tname">Home</span></div></div>' + build(tree, 0);
    } catch (e) {
        $('moveTree').innerHTML = '<div class="fm-tree-msg">Gagal memuat folder.</div>';
    }
}
function onTreeClick(e) {
    const tgl = e.target.closest('[data-toggle]');
    const row = e.target.closest('.fm-trow');
    if (!row) return;
    if (tgl && !tgl.classList.contains('off')) {
        const kids = row.parentElement.querySelector(':scope > .fm-tkids');
        if (kids) { kids.hidden = !kids.hidden; tgl.classList.toggle('open', !kids.hidden); }
        return;
    }
    const pick = row.dataset.pick;
    $('moveTree').querySelectorAll('.fm-trow.picked').forEach(r => r.classList.remove('picked'));
    row.classList.add('picked');
    moveTarget = pick === 'root' ? null : Number(pick);
    $('moveOk').disabled = false;
    $('moveOk').textContent = 'Pindahkan ke “' + (row.dataset.name || 'Home') + '”';
}
async function doMove() {
    if (moveTarget === undefined) return;
    closeModal('moveModal');
    await moveTo(moveIds, moveTarget);
}
async function moveTo(ids, target) {
    const r = await api('POST', '/file-manager/move', { ids: ids, target_id: target });
    if (r.success) toast('Berhasil dipindahkan'); else toast(r.message || 'Gagal memindahkan', 'error');
    reload();
    return r;
}

/* ---- clipboard (potong / tempel) ---- */
function updateClipPill() {
    el.pill.classList.toggle('show', S.clip.length > 0);
    $('clipText').textContent = S.clip.length + ' item dipotong';
}
function cut() {
    if (!S.sel.size) return;
    S.clip = Array.from(S.sel);
    localStorage.setItem('fm_clip', JSON.stringify(S.clip));
    updateClipPill();
    toast(S.clip.length + ' item dipotong — buka folder tujuan lalu tempel');
}
async function paste() {
    if (!S.clip.length) return;
    const ids = S.clip;
    clearClip();
    await moveTo(ids, S.folder);
}
function clearClip() {
    S.clip = [];
    localStorage.removeItem('fm_clip');
    updateClipPill();
}

/* ---- preview ---- */
let previewId = null;
function openPreview(id) {
    const it = byId.get(id);
    if (!it || it.type !== 'file') return;
    previewId = id;
    $('pvName').textContent = it.name;
    $('pvDownload').href = '/file-manager/download/' + id;
    const url = it.url ? encodeURI(it.url) : '';
    $('pvOpen').href = url || '#';
    $('pvBody').innerHTML = (it.ext === 'pdf' && url)
        ? '<iframe src="' + esc(url) + '" title="' + esc(it.name) + '"></iframe>'
        : '<div class="fm-pv-none">' + iconHTML(it) + '<strong>' + esc(it.name) + '</strong><span>' + fmtSize(it.size) + ' • Pratinjau tidak tersedia untuk jenis file ini</span>'
          + '<a class="btn btn-primary" href="/file-manager/download/' + id + '"><svg class="i"><use href="#i-download"/></svg><span>Unduh file</span></a></div>';
    const files = S.items.filter(i => i.type === 'file');
    const pos = files.findIndex(i => i.id === id);
    $('pvPrev').disabled = pos <= 0;
    $('pvNext').disabled = pos < 0 || pos >= files.length - 1;
    openModal('previewModal');
}
function stepPreview(d) {
    const files = S.items.filter(i => i.type === 'file');
    const pos = files.findIndex(i => i.id === previewId);
    const n = files[pos + d];
    if (n) openPreview(n.id);
}

/* ============================================================
   VIEW / SORT / FILTER / SEARCH
   ============================================================ */
function setView(v) {
    if (S.view === v) return;
    S.view = v;
    localStorage.setItem('fm_view', v);
    updateViewUI();
    rerender();
}
function applySort(field, dir) {
    S.sort = field; S.dir = dir;
    localStorage.setItem('fm_sort', field);
    localStorage.setItem('fm_dir', dir);
    updateViewUI();
    load(true);
}
function sortBy(field) {
    if (S.sort === field) applySort(field, S.dir === 'asc' ? 'desc' : 'asc');
    else applySort(field, field === 'name' ? 'asc' : 'desc');
}
function setFilter(f) {
    S.filter = f;
    document.querySelectorAll('.fm-chip').forEach(c => c.classList.toggle('active', c.dataset.filter === f));
    load(true);
}
function clearSearch() {
    el.search.value = '';
    el.searchWrap.classList.remove('has-val');
    if (S.q) { S.q = ''; renderCrumbs(); load(true); }
}
let searchTimer = null;
function onSearchInput() {
    clearTimeout(searchTimer);
    el.searchWrap.classList.toggle('has-val', el.search.value.length > 0);
    const v = el.search.value.trim();
    if (v.length === 1) return;
    searchTimer = setTimeout(() => {
        if (v === S.q) return;
        S.q = v;
        renderCrumbs();
        load(true);
    }, 350);
}

/* ============================================================
   UPLOAD (XHR + progress, 3 paralel)
   ============================================================ */
const upQueue = [];
let upActive = 0, upSeq = 0, upOk = 0, upFail = 0, upTotal = 0, upHideTimer = null;

function enqueueFiles(fileList, folder) {
    const accepted = [];
    Array.from(fileList).forEach(f => {
        const ext = extOf(f.name);
        if (!ALLOWED.has(ext)) { toast('"' + f.name + '" ditolak — hanya PDF, Word, dan Excel', 'error'); return; }
        if (f.size > MAX_MB * 1048576) { toast('"' + f.name + '" ditolak — ' + (f.size / 1048576).toFixed(1) + ' MB melebihi batas ' + MAX_MB + ' MB', 'error'); return; }
        accepted.push(f);
    });
    if (!accepted.length) return;

    clearTimeout(upHideTimer);
    if (!upActive && !upQueue.length) { upOk = 0; upFail = 0; upTotal = 0; el.upList.innerHTML = ''; }
    el.upPanel.classList.add('show');
    el.upPanel.classList.remove('min');

    accepted.forEach(f => {
        const id = ++upSeq;
        upTotal++;
        el.upList.insertAdjacentHTML('beforeend',
            '<div class="fm-up-row" id="up_' + id + '"><div class="fm-up-top"><span class="fm-up-name">' + esc(f.name) + '</span><span class="fm-up-st">Menunggu…</span></div><div class="fm-up-bar"><i></i></div></div>');
        upQueue.push({ id: id, f: f, folder: folder });
    });
    updUpTitle();
    pump();
}
function updUpTitle() {
    const done = upOk + upFail;
    $('upTitle').textContent = done < upTotal ? 'Mengunggah ' + done + ' / ' + upTotal : 'Selesai • ' + upOk + ' berhasil' + (upFail ? ' • ' + upFail + ' gagal' : '');
}
function pump() {
    while (upActive < CONCURRENCY && upQueue.length) {
        const job = upQueue.shift();
        upActive++;
        uploadOne(job).then(() => {
            upActive--;
            updUpTitle();
            if (!upActive && !upQueue.length) onUploadsDone(); else pump();
        });
    }
}
function uploadOne(job) {
    return new Promise(resolve => {
        const row = $('up_' + job.id);
        const bar = row.querySelector('.fm-up-bar i');
        const st = row.querySelector('.fm-up-st');
        const isPdf = extOf(job.f.name) === 'pdf';
        const fail = msg => { upFail++; row.classList.add('err'); st.textContent = '✕ ' + msg; bar.style.width = '100%'; };

        const fd = new FormData();
        fd.append('file', job.f);
        fd.append('parent_id', job.folder ?? '');

        const xhr = new XMLHttpRequest();
        xhr.open('POST', '/file-manager/upload');
        xhr.setRequestHeader('X-CSRF-TOKEN', CSRF);
        xhr.setRequestHeader('Accept', 'application/json');
        xhr.upload.onprogress = e => {
            if (!e.lengthComputable) return;
            const p = Math.round(e.loaded / e.total * 100);
            bar.style.width = p + '%';
            st.textContent = p < 100 ? p + '%' : (isPdf ? 'Mengoptimalkan PDF…' : 'Menyimpan…');
        };
        xhr.onload = () => {
            let j = {};
            try { j = JSON.parse(xhr.responseText); } catch (e) {}
            if (xhr.status >= 200 && xhr.status < 300 && j.success) {
                upOk++; row.classList.add('ok'); st.textContent = '✓ Selesai'; bar.style.width = '100%';
            } else {
                fail(j.message || (xhr.status === 413 ? 'File terlalu besar' : 'Ditolak server'));
            }
            resolve();
        };
        xhr.onerror = () => { fail('Koneksi terputus'); resolve(); };
        xhr.send(fd);
    });
}
function onUploadsDone() {
    updUpTitle();
    if (upOk && !upFail) toast(upOk + ' file berhasil diupload');
    else if (upOk && upFail) toast(upOk + ' berhasil, ' + upFail + ' gagal', 'error');
    reload();
    upHideTimer = setTimeout(() => { if (!upActive && !upQueue.length && !upFail) el.upPanel.classList.remove('show'); }, 6000);
}

/* ============================================================
   DRAG & DROP (upload dari luar + pindah antar folder)
   ============================================================ */
let dragDepth = 0, dragIds = [];
const isFileDrag = e => e.dataTransfer && Array.from(e.dataTransfer.types || []).includes('Files');
const dropTargetId = t => t.classList.contains('fm-item') ? Number(t.dataset.id) : (t.dataset.target ? Number(t.dataset.target) : null);
function clearHot() { document.querySelectorAll('.fm-drop-hot').forEach(x => x.classList.remove('fm-drop-hot')); }

function onDragEnter(e) { if (!isFileDrag(e)) return; dragDepth++; el.drop.classList.add('show'); }
function onDragLeave(e) { if (!isFileDrag(e)) return; dragDepth = Math.max(0, dragDepth - 1); if (!dragDepth) el.drop.classList.remove('show'); }
function onDragOver(e) {
    if (dragIds.length) {
        clearHot();
        const t = e.target.closest ? e.target.closest('.fm-droptarget') : null;
        if (t && !dragIds.includes(dropTargetId(t))) { e.preventDefault(); e.dataTransfer.dropEffect = 'move'; t.classList.add('fm-drop-hot'); }
        return;
    }
    if (isFileDrag(e)) {
        e.preventDefault();
        const t = e.target.closest ? e.target.closest('.fm-item[data-type="folder"]') : null;
        clearHot();
        if (t) t.classList.add('fm-drop-hot');
    }
}
function onDrop(e) {
    if (dragIds.length) {
        const t = e.target.closest ? e.target.closest('.fm-droptarget') : null;
        clearHot();
        if (!t) return;
        const target = dropTargetId(t);
        if (dragIds.includes(target)) return;
        e.preventDefault();
        const ids = dragIds; dragIds = [];
        moveTo(ids, target);
        return;
    }
    if (!isFileDrag(e)) return;
    e.preventDefault();
    dragDepth = 0; el.drop.classList.remove('show');
    const t = e.target.closest ? e.target.closest('.fm-item[data-type="folder"]') : null;
    clearHot();
    enqueueFiles(e.dataTransfer.files, t ? Number(t.dataset.id) : S.folder);
}
function onItemDragStart(e) {
    const n = e.target.closest('.fm-item');
    if (!n || n.classList.contains('fm-skel')) return;
    const id = Number(n.dataset.id);
    dragIds = S.sel.has(id) ? Array.from(S.sel) : [id];
    e.dataTransfer.setData('application/x-fm-ids', JSON.stringify(dragIds));
    e.dataTransfer.effectAllowed = 'move';
}

function onItemDragEnd() { dragIds = []; clearHot(); }

/* ============================================================
   KEYBOARD
   ============================================================ */
function gridCols() {
    if (S.view === 'list') return 1;
    const items = Array.from(el.list.children).filter(n => n.classList.contains('fm-item'));
    if (!items.length) return 1;
    const top = items[0].offsetTop;
    let c = 0;
    while (c < items.length && items[c].offsetTop === top) c++;
    return Math.max(1, c);
}
function moveFocus(delta) {
    if (!S.items.length) return;
    let idx = S.sel.size === 1 ? idxOf(Array.from(S.sel)[0]) : -1;
    idx = idx < 0 ? 0 : Math.max(0, Math.min(S.items.length - 1, idx + delta));
    const it = S.items[idx];
    S.sel = new Set([it.id]); S.anchor = idx; syncSel();
    const n = nodes.get(it.id);
    if (n) n.scrollIntoView({ block: 'nearest' });
}
function onKey(e) {
    const tag = (document.activeElement && document.activeElement.tagName) || '';
    const typing = tag === 'INPUT' || tag === 'TEXTAREA' || tag === 'SELECT';

    if (e.key === 'Escape') {
        if (el.ctx.style.display === 'block' || el.sheet.classList.contains('open')) { closeMenus(); return; }
        if (anyModalOpen()) { closeAllModals(); return; }
        if (typing) { document.activeElement.blur(); return; }
        clearSel();
        return;
    }
    if (anyModalOpen()) {
        if ($('previewModal').classList.contains('open') && !typing) {
            if (e.key === 'ArrowLeft') stepPreview(-1);
            if (e.key === 'ArrowRight') stepPreview(1);
        }
        return;
    }
    if (typing) return;

    const mod = e.ctrlKey || e.metaKey;
    const k = e.key.toLowerCase();
    if (mod && k === 'a') { e.preventDefault(); S.sel = new Set(S.items.map(i => i.id)); syncSel(); }
    else if (mod && k === 'x') { e.preventDefault(); cut(); }
    else if (mod && k === 'v') { e.preventDefault(); paste(); }
    else if (e.key === 'Delete' && S.sel.size) confirmDelete(Array.from(S.sel));
    else if (e.key === 'F2' && S.sel.size === 1) { e.preventDefault(); openRename(Array.from(S.sel)[0]); }
    else if (e.key === 'Enter' && S.sel.size === 1) activate(byId.get(Array.from(S.sel)[0]));
    else if (e.key === ' ' && S.sel.size === 1) {
        e.preventDefault();
        const it = byId.get(Array.from(S.sel)[0]);
        if (it && it.type === 'file') openPreview(it.id);
    }
    else if (e.key === '/') { e.preventDefault(); el.search.focus(); }
    else if (e.key === '?') openModal('helpModal');
    else if (e.key === 'ArrowRight') { e.preventDefault(); moveFocus(1); }
    else if (e.key === 'ArrowLeft') { e.preventDefault(); moveFocus(-1); }
    else if (e.key === 'ArrowDown') { e.preventDefault(); moveFocus(gridCols()); }
    else if (e.key === 'ArrowUp') { e.preventDefault(); moveFocus(-gridCols()); }
}

/* ============================================================
   GLOBAL ACTION DELEGATION
   ============================================================ */
function handleAction(act, node) {
    switch (act) {
        case 'upload': el.fabMenu.classList.remove('open'); el.fileInput.click(); break;
        case 'new-folder': el.fabMenu.classList.remove('open'); openFolderModal(); break;
        case 'fab-toggle': el.fabMenu.classList.toggle('open'); break;
        case 'view-list': setView('list'); break;
        case 'view-grid': setView('grid'); break;
        case 'clear-search': clearSearch(); break;
        case 'clear-selection': clearSel(); break;
        case 'bulk-move': openMove(Array.from(S.sel)); break;
        case 'bulk-delete': confirmDelete(Array.from(S.sel)); break;
        case 'close-modal': closeModal(node.dataset.modal); break;
        case 'create-folder': createFolder(); break;
        case 'submit-rename': submitRename(); break;
        case 'confirm-ok': { const cb = confirmCb; confirmCb = null; closeModal('confirmModal'); if (cb) cb(); break; }
        case 'move-ok': doMove(); break;
        case 'pv-prev': stepPreview(-1); break;
        case 'pv-next': stepPreview(1); break;
        case 'paste': paste(); break;
        case 'clear-clip': clearClip(); break;
        case 'up-toggle': el.upPanel.classList.toggle('min'); break;
        case 'up-close': el.upPanel.classList.remove('show'); break;
        case 'help': openModal('helpModal'); break;
        case 'retry': load(true); break;
        case 'close-menus': closeMenus(); break;
        case 'crumb': goFolder(node.dataset.go ? Number(node.dataset.go) : null); break;
        case 'sort-col': sortBy(node.dataset.sort); break;
        case 'filter': setFilter(node.dataset.filter); break;
    }
}
function onDocClick(e) {
    if (!e.target.closest('.fm-item')) hideNameTip();
    const a = e.target.closest('[data-action]');
    if (a) { handleAction(a.dataset.action, a); return; }

    const m = e.target.closest('[data-act]');
    if (m && (el.ctx.contains(m) || el.sheet.contains(m))) {
        const act = m.dataset.act;
        const ids = menuIds.slice();     // <-- SIMPAN
        closeMenus();                     // menuIds direset di sini, tapi `ids` lokal masih punya
        runAct(act, ids);                 // <-- kirim ids sebagai argumen
        return;
    }
    if (!e.target.closest('#ctx') && !e.target.closest('.fm-sheet-panel') && !e.target.closest('[data-more]')) closeMenus();
    if (!e.target.closest('#fabWrap')) el.fabMenu.classList.remove('open');
    if (e.target.closest('#fmScroll') && !e.target.closest('.fm-item')) clearSel();
    if (e.target.classList && e.target.classList.contains('fm-modal')) closeModal(e.target.id);
}

/* ============================================================
   WIRING
   ============================================================ */
const ac = new AbortController();
const on = (t, ev, fn, opt) => t.addEventListener(ev, fn, Object.assign({ signal: ac.signal }, opt || {}));

on(document, 'click', onDocClick);
on(document, 'keydown', onKey);
on(document, 'dragenter', onDragEnter);
on(document, 'dragleave', onDragLeave);
on(document, 'dragover', onDragOver);
on(document, 'drop', onDrop);
on(window, 'popstate', onPopState);
on(window, 'resize', closeMenus);

on(el.list, 'click', onListClick);
on(el.list, 'dblclick', onListDblClick);
on(el.list, 'dragstart', onItemDragStart);
on(el.list, 'dragend', onItemDragEnd);
on(el.list, 'touchstart', onTouchStart, { passive: true });
on(el.list, 'touchmove', onTouchMove, { passive: true });
on(el.list, 'touchend', onTouchEnd, { passive: true });
on(el.list, 'touchcancel', onTouchEnd, { passive: true });
on(el.scroll, 'scroll', () => { closeMenus(); hideNameTip(); }, { passive: true });
on(el.ctx, 'contextmenu', e => e.preventDefault());
on($('moveTree'), 'click', onTreeClick);
on(window, 'contextmenu', onContextCapture, { capture: true });
on(document, 'pointerdown', e => { lastPointer = e.pointerType || 'mouse'; }, { capture: true, passive: true });

on(el.search, 'input', onSearchInput);
on(el.sort, 'change', () => { const p = el.sort.value.split(':'); applySort(p[0], p[1]); });
on(el.fileInput, 'change', () => { enqueueFiles(el.fileInput.files, S.folder); el.fileInput.value = ''; });
on(el.folderName, 'keydown', e => { if (e.key === 'Enter') { e.preventDefault(); createFolder(); } });
on(el.renameInput, 'keydown', e => { if (e.key === 'Enter') { e.preventDefault(); submitRename(); } });

const io = new IntersectionObserver(es => { if (es[0].isIntersecting) maybeLoadMore(); }, { root: el.scroll, rootMargin: '300px' });
io.observe(el.sentinel);

window.fmCleanup = function () {
    ac.abort(); io.disconnect();
    if (ctrl) ctrl.abort();
        clearTimeout(searchTimer); clearTimeout(lpTimer); clearTimeout(upHideTimer); clearTimeout(singleTapTimer);
    hideNameTip();
    document.body.style.overflow = '';
};

/* ============================================================
   INIT
   ============================================================ */
history.replaceState({ folder: S.folder }, '');
updateViewUI();
renderCrumbs();
updateClipPill();
load(true);
})();
</script>

<style>
/* Semua warna memakai CSS variable dari layouts/app.blade */
.fm { --fm-offset: 120px; display: flex; flex-direction: column; gap: 12px; height: calc(100vh - var(--fm-offset)); height: calc(100dvh - var(--fm-offset)); min-height: 440px; }
.fm .btn { display: inline-flex; align-items: center; gap: 6px; }
.i { width: 18px; height: 18px; flex-shrink: 0; fill: none; stroke: currentColor; stroke-width: 2; stroke-linecap: round; stroke-linejoin: round; }

/* header */
.fm-head { display: flex; align-items: center; justify-content: space-between; gap: 12px; }
.fm-title { font-size: 1.25rem; font-weight: 600; color: var(--text-primary); margin: 0; }
.fm-stats { font-size: .75rem; color: var(--text-muted); margin: 2px 0 0; }
.fm-head-actions { display: flex; gap: 8px; align-items: center; }

/* toolbar */
.fm-toolbar { position: relative; }
.fm-tools { display: flex; flex-wrap: wrap; align-items: center; gap: 8px; }
.fm-toolbar.has-sel .fm-tools { display: none; }
.fm-search { position: relative; flex: 1 1 240px; display: flex; align-items: center; min-width: 0; }
.fm-search > .i { position: absolute; left: 12px; color: var(--text-muted); pointer-events: none; }
.fm-search input { width: 100%; height: 40px; padding: 0 38px 0 38px; border-radius: 999px; border: 1px solid var(--border-strong); background: var(--bg-surface); color: var(--text-primary); font-size: .875rem; font-family: inherit; outline: none; transition: var(--transition); -webkit-appearance: none; appearance: none; }
.fm-search input:focus { border-color: var(--accent); box-shadow: 0 0 0 3px rgba(37,99,235,.12); }
.fm-search input::-webkit-search-cancel-button { display: none; }
.fm-search-clear { position: absolute; right: 6px; width: 28px; height: 28px; border: none; background: none; border-radius: 50%; color: var(--text-muted); cursor: pointer; display: none; align-items: center; justify-content: center; }
.fm-search.has-val .fm-search-clear { display: flex; }
.fm-chips { display: flex; gap: 6px; overflow-x: auto; scrollbar-width: none; }
.fm-chips::-webkit-scrollbar { display: none; }
.fm-chip { height: 34px; padding: 0 14px; border-radius: 999px; border: 1px solid var(--border-strong); background: var(--bg-surface); color: var(--text-secondary); font-size: .8125rem; font-family: inherit; cursor: pointer; white-space: nowrap; transition: var(--transition); }
.fm-chip:hover { background: var(--bg-elevated); }
.fm-chip.active { background: var(--accent-muted); color: var(--accent-text); border-color: var(--accent); font-weight: 600; }
.fm-tools-right { display: flex; gap: 8px; margin-left: auto; align-items: center; }
.fm-select { height: 40px; padding: 0 12px; border-radius: 10px; border: 1px solid var(--border-strong); background: var(--bg-surface); color: var(--text-primary); font-size: .8125rem; font-family: inherit; cursor: pointer; outline: none; }
.fm-seg { display: flex; border: 1px solid var(--border-strong); border-radius: 10px; overflow: hidden; background: var(--bg-surface); }
.fm-seg button { width: 40px; height: 38px; border: none; background: none; color: var(--text-secondary); cursor: pointer; display: flex; align-items: center; justify-content: center; }
.fm-seg button.active { background: var(--accent-muted); color: var(--accent-text); }

.fm-bulk { display: none; align-items: center; gap: 8px; min-height: 44px; padding: 4px 8px; border-radius: 12px; border: 1px solid var(--accent); background: var(--accent-muted); color: var(--text-primary); }
.fm-bulk.show { display: flex; }
.fm-spacer { flex: 1; }
.fm-danger { background: #DC2626; border: 1px solid #DC2626; color: #fff; border-radius: 9px; padding: 8px 14px; font-size: .875rem; font-weight: 500; cursor: pointer; font-family: inherit; }
.fm-danger:hover { background: #B91C1C; }
.fm-iconbtn { width: 36px; height: 36px; border-radius: 50%; border: none; background: none; color: var(--text-secondary); cursor: pointer; display: inline-flex; align-items: center; justify-content: center; text-decoration: none; transition: var(--transition); }
.fm-iconbtn:hover { background: var(--bg-elevated); color: var(--text-primary); }
.fm-iconbtn:disabled { opacity: .35; cursor: default; }

/* breadcrumb */
.fm-crumbs { display: flex; align-items: center; gap: 2px; overflow-x: auto; white-space: nowrap; scrollbar-width: none; font-size: .875rem; min-height: 34px; }
.fm-crumbs::-webkit-scrollbar { display: none; }
.fm-crumb { display: inline-flex; align-items: center; gap: 6px; background: none; border: none; padding: 6px 10px; border-radius: 8px; color: var(--text-secondary); cursor: pointer; font-family: inherit; font-size: inherit; flex-shrink: 0; }
.fm-crumb:hover { background: var(--bg-elevated); color: var(--text-primary); }
.fm-crumb.current { color: var(--text-primary); font-weight: 600; }
.fm-crumbs .sep { width: 14px; height: 14px; color: var(--text-muted); flex-shrink: 0; }
.fm-crumb-text { color: var(--text-secondary); padding: 6px 10px; }

/* panel + scroll */
.fm-panel { position: relative; flex: 1; min-height: 0; display: flex; flex-direction: column; background: var(--bg-surface); border: 1px solid var(--border); border-radius: 16px; overflow: hidden; }
.fm-scroll { flex: 1; overflow-y: auto; overflow-x: hidden; scrollbar-width: thin; scrollbar-color: var(--border-strong) transparent; }
.fm-list { padding: 12px; }
.fm-sentinel { height: 1px; }
.fm-sentinel.loading { height: 36px; text-align: center; font-size: .75rem; color: var(--text-muted); }
.fm-sentinel.loading::after { content: 'Memuat…'; }

/* list header (hanya mode daftar) */
.fm-listhead { display: none; position: sticky; top: 0; z-index: 5; background: var(--bg-surface); border-bottom: 1px solid var(--border); padding: 8px 20px; gap: 8px; align-items: center; grid-template-columns: 22px 38px minmax(0,1fr) 100px 170px 36px; font-size: .75rem; font-weight: 600; text-transform: uppercase; letter-spacing: .04em; }
.fm[data-view="list"] .fm-listhead { display: grid; }
.fm-listhead button { background: none; border: none; text-align: left; padding: 0; font: inherit; color: var(--text-muted); cursor: pointer; text-transform: inherit; letter-spacing: inherit; }
.fm-listhead button:hover { color: var(--accent); }
.fm-listhead i { font-style: normal; }

/* item dasar */
.fm-item { position: relative; cursor: pointer; user-select: none; -webkit-user-select: none; -webkit-touch-callout: none; transition: background-color .15s, border-color .15s, box-shadow .15s; content-visibility: auto; }
.fm-item { touch-action: manipulation; }
.fm-item:hover { background: rgba(37,99,235,.05); }
.fm-item.fm-selected { background: var(--accent-muted); box-shadow: inset 0 0 0 1.5px var(--accent); }
.fm-name { font-size: .875rem; color: var(--text-primary); overflow: hidden; text-overflow: ellipsis; white-space: nowrap; font-weight: 500; }
.fm-sub { font-size: .75rem; color: var(--text-muted); margin-top: 2px; overflow: hidden; text-overflow: ellipsis; white-space: nowrap; }
.fm-col { font-size: .8125rem; color: var(--text-secondary); white-space: nowrap; }
.fm-meta { min-width: 0; }
.fm-check { width: 22px; height: 22px; border-radius: 50%; border: 1.5px solid var(--border-strong); background: var(--bg-surface); color: transparent; display: flex; align-items: center; justify-content: center; padding: 0; cursor: pointer; opacity: 0; transition: opacity .15s, background .15s; }
.fm-check .i { width: 13px; height: 13px; stroke-width: 3; }
.fm-item:hover .fm-check, .select-mode .fm-check { opacity: 1; }
.fm-selected .fm-check { opacity: 1; background: var(--accent); border-color: var(--accent); color: #fff; }
.fm-more { width: 32px; height: 32px; border-radius: 50%; border: none; background: none; color: var(--text-muted); cursor: pointer; display: flex; align-items: center; justify-content: center; opacity: 0; transition: opacity .15s, background .15s; }
.fm-item:hover .fm-more, .fm-item:focus-within .fm-more { opacity: 1; }
.fm-more:hover { background: var(--bg-elevated); color: var(--text-primary); }
@media (hover: none) { .fm-more { opacity: 1; } }
@media (hover: none) { .fm-check { opacity: 1; } }

.fm-drop-hot { background: var(--accent-muted) !important; outline: 2px dashed var(--accent); outline-offset: -2px; }
.fm-folder { fill: #F59E0B; }
.fm-badge { display: inline-flex; align-items: center; justify-content: center; font-weight: 700; letter-spacing: .04em; }
.k-pdf { background: #FEE2E2; color: #B91C1C; }
.k-word { background: #DBEAFE; color: #1D4ED8; }
.k-excel { background: #DCFCE7; color: #15803D; }
.k-other { background: var(--bg-elevated); color: var(--text-muted); }

/* GRID */
.fm-list.grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(168px, 1fr)); gap: 12px; }
.fm-list.grid .fm-item { display: flex; flex-direction: column; border: 1px solid var(--border); border-radius: 14px; background: var(--bg-surface); overflow: hidden; contain-intrinsic-size: 190px; }
.fm-list.grid .fm-item:hover { border-color: rgba(37,99,235,.35); box-shadow: var(--shadow-sm); }
.fm-list.grid .fm-item.fm-selected { border-color: var(--accent); }
.fm-list.grid .fm-thumb { aspect-ratio: 4 / 3; display: flex; align-items: center; justify-content: center; background: var(--bg-elevated); overflow: hidden; }
.fm-list.grid .fm-folder { width: 64px; height: 64px; }
.fm-list.grid .fm-badge { width: 56px; height: 68px; border-radius: 12px; font-size: 14px; }
.fm-list.grid .fm-meta { padding: 10px 12px 12px; }
.fm-list.grid .fm-col { display: none; }
.fm-list.grid .fm-check { position: absolute; top: 8px; left: 8px; z-index: 2; }
.fm-list.grid .fm-more { position: absolute; top: 6px; right: 6px; z-index: 2; background: var(--bg-surface); box-shadow: var(--shadow-sm); }

/* LIST */
.fm-list.list { display: flex; flex-direction: column; padding: 4px 12px 12px; }
.fm-list.list .fm-item { display: grid; grid-template-columns: 22px 38px minmax(0,1fr) 100px 170px 36px; align-items: center; gap: 8px; padding: 6px 8px; border-radius: 10px; border-bottom: 1px solid var(--border); contain-intrinsic-size: 56px; }
.fm-list.list .fm-thumb { display: flex; align-items: center; justify-content: center; }
.fm-list.list .fm-folder { width: 30px; height: 30px; }
.fm-list.list .fm-badge { width: 32px; height: 38px; border-radius: 8px; font-size: 10px; }
.fm-list.list .fm-sub:not(.loc) { display: none; }
.fm-list.list .fm-size { text-align: left; }

/* skeleton */
.fm-skel { pointer-events: none; }
.fm-skel .fm-thumb { background: var(--bg-elevated); animation: fmPulse 1.2s ease-in-out infinite; }
.fm-sk { height: 10px; border-radius: 6px; background: var(--bg-elevated); margin: 8px 0 0; animation: fmPulse 1.2s ease-in-out infinite; }
.fm-sk.w60 { width: 60%; } .fm-sk.w30 { width: 30%; }
.fm-list.list .fm-skel { grid-template-columns: 38px 1fr; height: 52px; }
.fm-list.list .fm-skel .fm-thumb { width: 32px; height: 38px; border-radius: 8px; }
@keyframes fmPulse { 0%,100% { opacity: 1; } 50% { opacity: .45; } }

/* empty */
.fm-empty { display: none; flex-direction: column; align-items: center; justify-content: center; gap: 6px; padding: 56px 16px; text-align: center; color: var(--text-muted); font-size: .875rem; }
.fm-empty.show { display: flex; }
.fm-empty strong { color: var(--text-primary); font-size: 1rem; }
.fm-empty-icon { font-size: 3rem; line-height: 1; margin-bottom: 6px; }
.fm-empty-cta { display: flex; gap: 8px; margin-top: 14px; flex-wrap: wrap; justify-content: center; }

/* dropzone */
.fm-dropzone { position: absolute; inset: 0; display: none; align-items: center; justify-content: center; text-align: center; background: rgba(37,99,235,.10); border: 2px dashed var(--accent); border-radius: 16px; z-index: 20; pointer-events: none; color: var(--accent-text); }
.fm-dropzone.show { display: flex; }
.fm-dropzone div { display: flex; flex-direction: column; gap: 6px; align-items: center; }
.fm-drop-icon { width: 44px; height: 44px; }
.fm-dropzone span { font-size: .8125rem; color: var(--text-secondary); }

/* context menu + sheet */
.fm-ctx { position: fixed; z-index: 9000; display: none; min-width: 200px; padding: 6px; background: var(--bg-surface); border: 1px solid var(--border); border-radius: 12px; box-shadow: var(--shadow-lg); }
.fm-mi { width: 100%; display: flex; align-items: center; gap: 12px; padding: 9px 12px; border: none; background: none; border-radius: 8px; font-size: .875rem; color: var(--text-secondary); cursor: pointer; text-align: left; font-family: inherit; }
.fm-mi:hover { background: var(--bg-elevated); color: var(--text-primary); }
.fm-mi.danger { color: #EF4444; }
.fm-mi.danger:hover { background: rgba(239,68,68,.1); }
.fm-sep { height: 1px; background: var(--border); margin: 4px 6px; }
.fm-sheet { position: fixed; inset: 0; z-index: 9000; display: none; }
.fm-sheet.open { display: block; }
.fm-sheet-bg { position: absolute; inset: 0; background: rgba(0,0,0,.45); }
.fm-sheet-panel { position: absolute; left: 0; right: 0; bottom: 0; background: var(--bg-surface); border-radius: 20px 20px 0 0; padding: 8px 12px calc(16px + env(safe-area-inset-bottom)); box-shadow: var(--shadow-lg); animation: fmSlide .2s ease; max-height: 80vh; overflow-y: auto; }
.fm-sheet-grab { width: 40px; height: 4px; border-radius: 4px; background: var(--border-strong); margin: 4px auto 10px; }
.fm-sheet-title { font-size: .875rem; font-weight: 600; color: var(--text-primary); padding: 0 12px 8px; overflow: hidden; text-overflow: ellipsis; white-space: nowrap; }
.fm-sheet .fm-mi { padding: 14px 12px; font-size: .9375rem; }
@keyframes fmSlide { from { transform: translateY(24px); opacity: 0; } to { transform: none; opacity: 1; } }

/* modal */
.fm-modal { position: fixed; inset: 0; z-index: 8000; display: none; align-items: center; justify-content: center; background: rgba(0,0,0,.45); backdrop-filter: blur(6px); }
.fm-modal.open { display: flex; }
.fm-box { background: var(--bg-surface); border: 1px solid var(--border); border-radius: 18px; box-shadow: var(--shadow-lg); width: 92%; max-width: 400px; padding: 24px; animation: fmPop .15s ease; max-height: 90vh; display: flex; flex-direction: column; }
.fm-box-md { max-width: 460px; }
.fm-box h2 { font-size: 1rem; font-weight: 600; color: var(--text-primary); margin: 0 0 14px; }
.fm-box-text { font-size: .875rem; color: var(--text-secondary); margin: 0 0 18px; line-height: 1.6; word-break: break-word; }
.fm-input { width: 100%; border-radius: 10px; border: 1px solid var(--border-strong); background: var(--bg-base); color: var(--text-primary); padding: 10px 12px; font-size: .875rem; font-family: inherit; outline: none; margin-bottom: 16px; }
.fm-input:focus { border-color: var(--accent); box-shadow: 0 0 0 3px rgba(37,99,235,.12); }
.fm-box-actions { display: flex; justify-content: flex-end; gap: 8px; margin-top: 4px; }
@keyframes fmPop { from { opacity: 0; transform: scale(.96) translateY(8px); } to { opacity: 1; transform: none; } }

/* tree */
.fm-tree { flex: 1; min-height: 160px; max-height: 50vh; overflow-y: auto; border: 1px solid var(--border); border-radius: 12px; padding: 6px; margin-bottom: 14px; }
.fm-tree-msg { padding: 24px; text-align: center; color: var(--text-muted); font-size: .875rem; }
.fm-trow { display: flex; align-items: center; gap: 6px; padding: 8px 10px; border-radius: 8px; cursor: pointer; font-size: .875rem; color: var(--text-secondary); }
.fm-trow:hover { background: var(--bg-elevated); }
.fm-trow.picked { background: var(--accent-muted); color: var(--accent-text); font-weight: 600; }
.fm-tgl { width: 22px; height: 22px; border: none; background: none; padding: 0; color: var(--text-muted); cursor: pointer; display: flex; align-items: center; justify-content: center; flex-shrink: 0; }
.fm-tgl.off { visibility: hidden; pointer-events: none; }
.fm-tgl .i { width: 14px; height: 14px; transition: transform .15s; }
.fm-tgl.open .i { transform: rotate(90deg); }
.fm-tico { display: flex; width: 20px; height: 20px; flex-shrink: 0; }
.fm-tico .fm-folder { width: 20px; height: 20px; }
.fm-tname { overflow: hidden; text-overflow: ellipsis; white-space: nowrap; }

/* preview */
.fm-box-pv { width: min(1100px, 96vw); max-width: none; height: 92vh; max-height: none; padding: 0; overflow: hidden; }
.fm-pv-head { display: flex; align-items: center; gap: 8px; padding: 8px 8px 8px 16px; border-bottom: 1px solid var(--border); }
.fm-pv-name { flex: 1; min-width: 0; font-size: .875rem; color: var(--text-primary); overflow: hidden; text-overflow: ellipsis; white-space: nowrap; }
.fm-pv-tools { display: flex; align-items: center; gap: 2px; }
.fm-pv-body { flex: 1; min-height: 0; background: var(--bg-elevated); }
.fm-pv-body iframe { width: 100%; height: 100%; border: none; background: #fff; }
.fm-pv-none { height: 100%; display: flex; flex-direction: column; align-items: center; justify-content: center; gap: 10px; text-align: center; padding: 24px; color: var(--text-muted); font-size: .875rem; }
.fm-pv-none strong { color: var(--text-primary); word-break: break-word; }
.fm-pv-none .fm-badge { width: 64px; height: 76px; border-radius: 14px; font-size: 15px; }

/* upload panel + pill */
.fm-up { position: fixed; right: 20px; bottom: 20px; width: 340px; max-width: calc(100vw - 24px); background: var(--bg-surface); border: 1px solid var(--border); border-radius: 14px; box-shadow: var(--shadow-lg); z-index: 8500; display: none; overflow: hidden; }
.fm-up.show { display: block; }
.fm-up-head { display: flex; align-items: center; justify-content: space-between; padding: 8px 8px 8px 14px; font-size: .875rem; color: var(--text-primary); border-bottom: 1px solid var(--border); }
.fm-up.min .fm-up-list { display: none; }
.fm-up-list { max-height: 260px; overflow-y: auto; padding: 8px 14px 12px; }
.fm-up-row { padding: 8px 0; }
.fm-up-top { display: flex; justify-content: space-between; gap: 10px; font-size: .8125rem; margin-bottom: 6px; }
.fm-up-name { overflow: hidden; text-overflow: ellipsis; white-space: nowrap; color: var(--text-primary); }
.fm-up-st { color: var(--text-muted); white-space: nowrap; font-size: .75rem; }
.fm-up-bar { height: 5px; border-radius: 999px; background: var(--border-strong); overflow: hidden; }
.fm-up-bar i { display: block; height: 100%; width: 0; background: var(--accent); border-radius: 999px; transition: width .15s; }
.fm-up-row.ok .fm-up-bar i { background: #16A34A; }
.fm-up-row.ok .fm-up-st { color: #16A34A; }
.fm-up-row.err .fm-up-bar i { background: #DC2626; }
.fm-up-row.err .fm-up-st { color: #DC2626; white-space: normal; text-align: right; }
.fm-pill { position: fixed; left: 50%; bottom: 20px; transform: translateX(-50%); z-index: 8400; display: none; align-items: center; gap: 10px; padding: 8px 8px 8px 16px; background: var(--bg-surface); border: 1px solid var(--border); border-radius: 999px; box-shadow: var(--shadow-lg); font-size: .875rem; color: var(--text-primary); }
.fm-pill.show { display: flex; }

/* fab + help */
.fm-fabwrap { display: none; position: fixed; right: 18px; bottom: calc(20px + env(safe-area-inset-bottom)); z-index: 7000; }
.fm-fab { width: 56px; height: 56px; border-radius: 50%; border: none; background: var(--accent); color: #fff; box-shadow: var(--shadow-lg); display: flex; align-items: center; justify-content: center; cursor: pointer; }
.fm-fab .i { width: 24px; height: 24px; }
.fm-fabmenu { display: none; position: absolute; right: 0; bottom: 66px; flex-direction: column; gap: 8px; align-items: flex-end; }
.fm-fabmenu.open { display: flex; }
.fm-fabmenu button { display: flex; align-items: center; gap: 10px; padding: 10px 16px; border-radius: 999px; border: 1px solid var(--border); background: var(--bg-surface); color: var(--text-primary); box-shadow: var(--shadow-md); font-size: .875rem; font-family: inherit; cursor: pointer; white-space: nowrap; }
.fm-helpbtn { position: fixed; left: 16px; bottom: 16px; width: 30px; height: 30px; border-radius: 50%; border: 1px solid var(--border); background: var(--bg-surface); color: var(--text-muted); font-size: .8125rem; cursor: pointer; z-index: 6000; }
.fm-keys { display: grid; grid-template-columns: auto 1fr; gap: 8px 16px; margin: 0 0 16px; font-size: .8125rem; color: var(--text-secondary); }
.fm-keys dt { white-space: nowrap; } .fm-keys dd { margin: 0; }
kbd { background: var(--bg-elevated); border: 1px solid var(--border-strong); border-bottom-width: 2px; border-radius: 5px; padding: 1px 6px; font-size: .75rem; font-family: inherit; color: var(--text-primary); margin: 0 1px; }

button:focus-visible, input:focus-visible, select:focus-visible, a:focus-visible { outline: 2px solid var(--accent); outline-offset: 2px; }

/* responsif */
@media (max-width: 767px) {
    .fm { --fm-offset: 100px; }
    .fm-hide-md { display: none !important; }
    .fm-fabwrap { display: block; }
    .fm-helpbtn { display: none; }
    .fm-chips { order: 3; width: 100%; }
    .fm-tools-right { margin-left: 0; width: 100%; }
    .fm-select { flex: 1; }
    .fm-list.grid { grid-template-columns: repeat(2, minmax(0, 1fr)); gap: 10px; }
    .fm-list { padding: 10px; }
    .fm-up { right: 12px; left: 12px; width: auto; max-width: none; bottom: 88px; }
    .fm-pill { bottom: 88px; width: calc(100vw - 24px); justify-content: space-between; }
    .fm-box-pv { width: 100vw; height: 100dvh; border-radius: 0; max-height: none; }
}
@media (max-width: 639px) {
    .fm-hide-xs { display: none; }
    .fm-listhead { display: none !important; }
    .fm-list.list .fm-item { grid-template-columns: 22px 38px minmax(0,1fr) 36px; }
    .fm-list.list .fm-col { display: none; }
    .fm-list.list .fm-sub:not(.loc) { display: block; }
}
@media (min-width: 1280px) { .fm-list.grid { grid-template-columns: repeat(auto-fill, minmax(188px, 1fr)); } }
@media (prefers-reduced-motion: reduce) { .fm *, .fm-modal *, .fm-sheet * { animation-duration: .001ms !important; transition-duration: .001ms !important; } }

/* ── nama file panjang di layar kecil ── */
@media (max-width: 767px) {
    .fm-name { font-size:12px;  white-space: normal; display: -webkit-box; -webkit-box-orient: vertical; -webkit-line-clamp: 3; line-clamp: 3; overflow: hidden; overflow-wrap: anywhere; line-height: 1.35; }
    .fm-list.grid .fm-name { -webkit-line-clamp: 3; line-clamp: 3; }
    .fm-list.grid .fm-thumb { aspect-ratio: 2 / 1; }
    .fm-list.grid .fm-item { contain-intrinsic-size: auto 170px; }
    .fm-list.list .fm-item { contain-intrinsic-size: auto 72px; }
    .fm-pv-name { white-space: normal; display: -webkit-box; -webkit-box-orient: vertical; -webkit-line-clamp: 2; line-clamp: 2; overflow-wrap: anywhere; }
}

/* judul menu bawah: tampilkan nama lengkap (maks. 3 baris) */
.fm-sheet-title { white-space: normal; display: -webkit-box; -webkit-box-orient: vertical; -webkit-line-clamp: 3; line-clamp: 3; overflow-wrap: anywhere; line-height: 1.4; }

/* ── layar kecil: thumbnail & info (ukuran, tanggal) lebih kecil dari nama ── */
@media (max-width: 767px) {
    .fm { -webkit-text-size-adjust: 100%; text-size-adjust: 100%; }

    /* ukuran & tanggal: 10px, nama file tetap 12px */
    .fm-sub, .fm-size, .fm-date { font-size: 10px; line-height: 1.3; }
    .fm-sub { margin-top: 1px; }

    /* tampilan daftar: kolom thumbnail lebih sempit, baris lebih rapat */
    .fm-list.list .fm-item { grid-template-columns: 22px 30px minmax(0,1fr) 72px 128px 36px; gap: 6px; contain-intrinsic-size: auto 60px; }
    .fm-list.list .fm-badge { width: 26px; height: 32px; border-radius: 6px; font-size: 8px; }
    .fm-list.list .fm-folder { width: 24px; height: 24px; }
    .fm-listhead { grid-template-columns: 22px 30px minmax(0,1fr) 72px 128px 36px; gap: 6px; padding: 8px 18px; font-size: 10px; }
    .fm-list.list .fm-skel { grid-template-columns: 30px 1fr; height: 46px; }
    .fm-list.list .fm-skel .fm-thumb { width: 26px; height: 32px; }

    /* tampilan grid: badge dan ikon folder lebih kecil */
    .fm-list.grid .fm-badge { width: 40px; height: 48px; border-radius: 9px; font-size: 11px; }
    .fm-list.grid .fm-folder { width: 44px; height: 44px; }
}

/* HP (di bawah 640px): kolom ukuran & tanggal disembunyikan, infonya ada di baris bawah nama */
@media (max-width: 639px) {
    .fm-list.list .fm-item { grid-template-columns: 22px 30px minmax(0,1fr) 36px; }
}

/* tooltip nama file (ketuk di layar sentuh) */
.fm-tip { position: fixed; left: 0; top: 0; z-index: 9500; max-width: min(320px, calc(100vw - 16px)); padding: 8px 12px; border-radius: 10px; background: #111827; color: #fff; box-shadow: var(--shadow-lg); font-size: 12px; line-height: 1.4; pointer-events: none; opacity: 0; transform: translateY(4px); transition: opacity .15s ease, transform .15s ease; }
.fm-tip.show { opacity: 1; transform: none; }
.fm-tip-name { font-weight: 600; overflow-wrap: anywhere; display: -webkit-box; -webkit-box-orient: vertical; -webkit-line-clamp: 5; line-clamp: 5; overflow: hidden; }
.fm-tip-hint { margin-top: 4px; font-size: 10.5px; color: #9CA3AF; }
</style>

@endsection