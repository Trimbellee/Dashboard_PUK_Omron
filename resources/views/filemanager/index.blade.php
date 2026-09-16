@extends('layouts.app')

@section('title','File Manager')

@section('content')


<div style="padding: 0 4px;">
    <div style="display:flex; flex-direction:column; gap:12px; margin-bottom:12px;">

        {{-- TOP --}}
        <div style="display:flex; align-items:center; justify-content:space-between; gap:8px;">

            <h1 style="font-size:1.25rem; font-weight:600; color:var(--text-primary); white-space:nowrap;">
                📁 File Manager
            </h1>

            <div style="display:flex; align-items:center; gap:8px;">

                {{-- VIEW TOGGLE --}}
                <div id="viewButton" data-action="toggle-view"
                    style="display:flex; overflow:hidden; border-radius:999px;
                           border:1px solid var(--border-strong);
                           background:var(--bg-surface);">
                    <button id="listViewBtn"
                        style="width:40px;height:40px;display:flex;align-items:center;
                               justify-content:center;background:none;border:none;
                               cursor:pointer;color:var(--text-secondary);">☰</button>
                    <button id="gridViewBtn"
                        style="width:40px;height:40px;display:flex;align-items:center;
                               justify-content:center;background:none;border:none;
                               cursor:pointer;color:var(--text-secondary);">⊞</button>
                </div>

                {{-- MENU --}}
                <div style="position:relative;">
                    <button data-action="toggle-top-menu"
                        style="width:40px;height:40px;border-radius:50%;
                               background:none;border:1px solid var(--border);
                               display:flex;align-items:center;justify-content:center;
                               font-size:1.4rem;cursor:pointer;color:var(--text-secondary);
                               transition:var(--transition);"
                        onmouseover="this.style.background='var(--bg-elevated)'"
                        onmouseout="this.style.background='none'">
                        ⋮
                    </button>
                    <div id="topMenu"
                        style="display:none;position:absolute;right:0;top:calc(100% + 8px);
                               width:200px;background:var(--bg-surface);
                               border:1px solid var(--border);border-radius:12px;
                               box-shadow:var(--shadow-lg);overflow:hidden;z-index:200;">
                        <form id="uploadForm">
                            <input type="file" id="fileInput" multiple hidden accept=".pdf,.doc,.docx,.xls,.xlsx">
                            <button type="button" data-action="upload-file" class="fm-menu-item">📤 Upload File</button>
                        </form>
                        <button data-action="open-folder-modal" class="fm-menu-item">📁 Folder Baru</button>
                        <a href="/file-manager/trash" class="fm-menu-item" style="color:#ef4444;">🗑 Trash</a>
                    </div>
                </div>

            </div>
        </div>

        {{-- SEARCH --}}
        <div style="position:relative;">
            <input type="text" id="searchInput"
                placeholder="Cari file atau folder..."
                style="width:100%;border-radius:12px;border:1px solid var(--border-strong);
                       background:var(--bg-surface);color:var(--text-primary);
                       padding:10px 16px;font-size:0.875rem;outline:none;
                       font-family:inherit;transition:var(--transition);"
                onfocus="this.style.borderColor='var(--accent)';this.style.boxShadow='0 0 0 3px rgba(37,99,235,.12)'"
                onblur="this.style.borderColor='var(--border-strong)';this.style.boxShadow='none'">
        </div>

    </div>

    {{-- BREADCRUMB --}}
    <div style="display:flex;flex-wrap:wrap;align-items:center;gap:4px;
                font-size:0.875rem;color:var(--text-secondary);margin-bottom:12px;">
        <a href="/file-manager" style="color:var(--accent);text-decoration:none;">Home</a>
        @foreach($breadcrumbs as $b)
            <span style="color:var(--text-muted);">/</span>
            <a href="/file-manager?folder={{ $b->id }}"
               style="color:var(--accent);text-decoration:none;">{{ $b->name }}</a>
        @endforeach
    </div>

    <div style="height:calc(100vh - 220px);display:flex;flex-direction:column;overflow:hidden;">

        <!-- LIST HEADER -->
        <div id="listHeader" style="display:none;position:sticky;top:0;z-index:40;margin-bottom:8px;">
            <div id="headerLayout"
                style="display:grid;grid-template-columns:1fr 40px;align-items:center;
                       gap:16px;font-size:0.875rem;font-weight:500;
                       color:var(--text-secondary);padding:8px 12px;
                       background:var(--bg-surface);border-radius:10px;
                       border:1px solid var(--border);">
                <button data-action="sort" data-field="name"
                    style="display:flex;align-items:center;gap:8px;background:none;border:none;
                           cursor:pointer;color:var(--text-secondary);font-size:0.875rem;
                           font-weight:500;text-align:left;"
                    onmouseover="this.style.color='var(--accent)'"
                    onmouseout="this.style.color='var(--text-secondary)'">
                    <span>Nama</span><span id="nameSortIcon">↕</span>
                </button>
                <button id="dateSortButton" data-action="sort" data-field="date"
                    style="display:none;align-items:center;gap:8px;background:none;border:none;
                           cursor:pointer;color:var(--text-secondary);font-size:0.875rem;
                           font-weight:500;text-align:right;"
                    onmouseover="this.style.color='var(--accent)'"
                    onmouseout="this.style.color='var(--text-secondary)'">
                    <span>Tanggal diubah</span><span id="dateSortIcon">↕</span>
                </button>
                <div></div>
            </div>
        </div>

        <div id="loadingState"
            style="display:none;text-align:center;padding:40px 0;color:var(--text-muted);">
            Loading...
        </div>

        <!-- FILE LIST -->
        <div id="fileScrollArea"
            style="flex:1;overflow-y:auto;overflow-x:hidden;
                   scrollbar-width:thin;scrollbar-color:var(--border-strong) transparent;">
            <div id="virtualWrapper" style="position:relative;">
                <div id="fileList"
                    style="display:grid;grid-template-columns:repeat(2,1fr);gap:12px;font-size:0.875rem;">
                </div>
            </div>
        </div>

    </div>
</div>

{{-- ── FOLDER MODAL ─────────────────────────────────────────── --}}
<div id="folderModal"
    style="position:fixed;inset:0;display:none;align-items:center;
           justify-content:center;background:rgba(0,0,0,.45);
           backdrop-filter:blur(6px);z-index:8000;">
    <div style="background:var(--bg-surface);border:1px solid var(--border);
                border-radius:18px;box-shadow:var(--shadow-lg);
                width:92%;max-width:360px;padding:28px;">
        <h2 style="font-size:1rem;font-weight:600;color:var(--text-primary);margin-bottom:16px;">
            Buat Folder
        </h2>
        <input type="text" id="folderName"
            placeholder="Nama folder"
            style="width:100%;border-radius:9px;border:1px solid var(--border-strong);
                   background:var(--bg-base);color:var(--text-primary);
                   padding:9px 12px;font-size:0.875rem;margin-bottom:16px;
                   font-family:inherit;outline:none;">
        <div style="display:flex;justify-content:flex-end;gap:8px;">
            <button data-action="close-folder-modal" class="btn btn-ghost">Batal</button>
            <button data-action="create-folder" class="btn btn-primary">Simpan</button>
        </div>
    </div>
</div>

{{-- ── RENAME MODAL ─────────────────────────────────────────── --}}
<div id="renameModal"
    style="position:fixed;inset:0;display:none;align-items:center;
           justify-content:center;background:rgba(0,0,0,.45);
           backdrop-filter:blur(6px);z-index:8000;">
    <div style="background:var(--bg-surface);border:1px solid var(--border);
                border-radius:18px;box-shadow:var(--shadow-lg);
                width:92%;max-width:360px;padding:28px;">
        <h2 style="font-size:1rem;font-weight:600;color:var(--text-primary);margin-bottom:16px;">
            Rename
        </h2>
        <input type="text" id="renameInput"
            style="width:100%;border-radius:9px;border:1px solid var(--border-strong);
                   background:var(--bg-base);color:var(--text-primary);
                   padding:9px 12px;font-size:0.875rem;margin-bottom:16px;
                   font-family:inherit;outline:none;">
        <div style="display:flex;justify-content:flex-end;gap:8px;">
            <button data-action="close-rename" class="btn btn-ghost">Batal</button>
            <button data-action="submit-rename" class="btn btn-primary">Simpan</button>
        </div>
    </div>
</div>

{{-- ── CONTEXT MENU ─────────────────────────────────────────── --}}
<div id="contextMenu"
    style="position:fixed;z-index:9999;display:none;
           background:var(--bg-surface);border:1px solid var(--border);
           border-radius:12px;box-shadow:var(--shadow-lg);width:176px;overflow:hidden;">
    <button data-action="cm-open"   class="fm-ctx-item">📂 Buka</button>
    <button data-action="cm-rename" class="fm-ctx-item">✏️ Rename</button>
    <button data-action="cm-delete" class="fm-ctx-item fm-ctx-danger">🗑 Hapus</button>
</div>

{{-- ── PREVIEW MODAL ────────────────────────────────────────── --}}
<div id="previewModal"
    style="position:fixed;inset:0;background:rgba(0,0,0,.75);
           display:none;z-index:9999;align-items:center;justify-content:center;">
    <div style="background:var(--bg-surface);width:90%;height:90%;
                border-radius:16px;position:relative;padding:16px;">
        <button data-action="close-preview"
            style="position:absolute;top:10px;right:12px;font-size:1.25rem;
                   background:none;border:none;cursor:pointer;
                   color:var(--text-secondary);">✖</button>
        <div id="previewContent"
            style="width:100%;height:100%;display:flex;
                   align-items:center;justify-content:center;
                   color:var(--text-muted);">Loading...</div>
    </div>
</div>

{{-- ── CONFIRM MODAL ────────────────────────────────────────── --}}
<div id="confirmModal"
    style="position:fixed;inset:0;background:rgba(0,0,0,.4);
           display:none;z-index:9999;align-items:center;justify-content:center;">
    <div style="background:var(--bg-surface);border:1px solid var(--border);
                border-radius:18px;padding:24px;width:92%;max-width:360px;
                box-shadow:var(--shadow-lg);">
        <h2 style="font-size:1rem;font-weight:600;color:var(--text-primary);margin-bottom:8px;">
            Konfirmasi
        </h2>
        <p id="confirmMessage"
            style="font-size:0.875rem;color:var(--text-secondary);margin-bottom:20px;">
            Yakin?
        </p>
        <div style="display:flex;justify-content:flex-end;gap:8px;">
            <button data-action="close-confirm" class="btn btn-ghost">Batal</button>
            <button id="confirmOkBtn"
                style="background:#DC2626;color:#fff;border:none;border-radius:9px;
                       padding:9px 18px;font-size:0.875rem;font-weight:500;cursor:pointer;">
                Hapus
            </button>
        </div>
    </div>
</div>

<script>
if (window.fmCleanup) window.fmCleanup();

/* ============================================================
   CONSTANTS
   ============================================================ */
const VIRTUAL_BUFFER     = 8;
const POOL_MAX           = 50;
const RAW_DATA_MAX       = 600;
const FOLDER_CACHE_TTL   = 30_000;
const UPLOAD_CONCURRENCY = 3;

// Hanya PDF, Word, dan Excel yang diizinkan
const ALLOWED_EXT  = new Set(['pdf', 'doc', 'docx', 'xls', 'xlsx']);
const MAX_FILE_MB  = 10;

// Label ramah untuk pesan error
const EXT_LABEL = {
    pdf:  'PDF',
    doc:  'Word',
    docx: 'Word',
    xls:  'Excel',
    xlsx: 'Excel',
};

/* ============================================================
   SELECTION ENGINE v2
   ============================================================ */
const SelectionEngine = (() => {
    const sel         = new Set();
    const domSelected = new Set();
    let anchorIndex   = -1;
    let dirtyAdd      = new Set();
    let dirtyRemove   = new Set();
    let rafPending    = false;

    function _toggle(id) { sel.has(id) ? _remove(id) : _add(id); }
    function _add(id) { if (sel.has(id)) return; sel.add(id); dirtyRemove.delete(id); dirtyAdd.add(id); }
    function _remove(id) { if (!sel.has(id)) return; sel.delete(id); dirtyAdd.delete(id); dirtyRemove.add(id); }
    function _clear() { sel.forEach(id => _remove(id)); }

    function flushDOM() {
        rafPending = false;
        dirtyRemove.forEach(id => {
            const node = itemMap.get(id);
            if (node && domSelected.has(id)) node.classList.remove('fm-selected');
            domSelected.delete(id);
        });
        dirtyRemove.clear();
        dirtyAdd.forEach(id => {
            const node = itemMap.get(id);
            if (node) { node.classList.add('fm-selected'); domSelected.add(id); }
        });
        dirtyAdd.clear();
    }
    function scheduleFlush() { if (rafPending) return; rafPending = true; requestAnimationFrame(flushDOM); }

    return {
        has(id)   { return sel.has(id); },
        size()    { return sel.size; },
        toArray() { return [...sel]; },
        handleClick(e, id, index) {
            if (e.shiftKey && anchorIndex !== -1) {
                const start = Math.min(anchorIndex, index), end = Math.max(anchorIndex, index);
                _clear();
                for (let i = start; i <= end; i++) _add(rawData[i].id);
                scheduleFlush(); return;
            }
            if (e.ctrlKey || e.metaKey) { _toggle(id); if (sel.has(id)) anchorIndex = index; scheduleFlush(); return; }
            _clear(); _add(id); anchorIndex = index; scheduleFlush();
        },
        selectOnly(id, index) { _clear(); _add(id); anchorIndex = index; scheduleFlush(); },
        selectAll()           { rawData.forEach(item => _add(item.id)); anchorIndex = 0; scheduleFlush(); },
        clear()               { _clear(); anchorIndex = -1; scheduleFlush(); },
        applyToNode(node, id) {
            if (sel.has(id)) { node.classList.add('fm-selected'); domSelected.add(id); }
            else             { node.classList.remove('fm-selected'); domSelected.delete(id); }
        },
        onNodeRecycled(id) { domSelected.delete(id); },
        reset() { sel.clear(); domSelected.clear(); dirtyAdd.clear(); dirtyRemove.clear(); anchorIndex = -1; rafPending = false; },
    };
})();

/* ============================================================
   GLOBAL STATE
   ============================================================ */
let fetchController = null;
let requestId       = 0;
let moveClipboard   = JSON.parse(localStorage.getItem('fm_clipboard') || '[]');
let viewMode        = localStorage.getItem('fm_view') || 'grid';
let sortField       = 'name';
let sortDirection   = 'az';
let parentId        = "{{ $parentId }}";
let rawData         = [];
let rawMap          = new Map();
let rawIndexMap     = new Map();
let currentPage     = 1;
let isLoading       = false;
let hasMore         = true;
let keyword         = '';
let recycledPool    = [];
let itemMap         = new Map();
let visibleNodeMap  = new Map();
let cachedRowHeight   = 0;
let cachedItemsPerRow = 1;
let cachedItemWidth   = 0;
let layoutViewMode    = '';
let virtualStart      = 0;
let clickTimer         = null;
let touchTimer         = null;
let longPressTriggered = false;
let keyboardIndex      = -1;
let lastTap            = 0;
let lastTapId          = null;
let lastLoadMore       = 0;
let scrollTicking      = false;
let resizeTimer;
let searchTimeout;
let renameId = null;
let cmId = null, cmName = '', cmType = '', cmPath = '', cmUrl = '';

const scrollOptions  = { passive: true };
const passiveOptions = { passive: true };
const folderCache    = new Map();

/* ============================================================
   DOM REFS
   ============================================================ */
const el = {
    fileList:       document.getElementById('fileList'),
    loadingState:   document.getElementById('loadingState'),
    contextMenu:    document.getElementById('contextMenu'),
    topMenu:        document.getElementById('topMenu'),
    folderModal:    document.getElementById('folderModal'),
    renameModal:    document.getElementById('renameModal'),
    renameInput:    document.getElementById('renameInput'),
    folderName:     document.getElementById('folderName'),
    previewModal:   document.getElementById('previewModal'),
    previewContent: document.getElementById('previewContent'),
    confirmModal:   document.getElementById('confirmModal'),
    confirmMessage: document.getElementById('confirmMessage'),
    confirmOkBtn:   document.getElementById('confirmOkBtn'),
    searchInput:    document.getElementById('searchInput'),
    nameSortIcon:   document.getElementById('nameSortIcon'),
    dateSortIcon:   document.getElementById('dateSortIcon'),
    listHeader:     document.getElementById('listHeader'),
    headerLayout:   document.getElementById('headerLayout'),
    listViewBtn:    document.getElementById('listViewBtn'),
    gridViewBtn:    document.getElementById('gridViewBtn'),
    dateSortButton: document.getElementById('dateSortButton'),
    fileInput:      document.getElementById('fileInput'),
    fileScrollArea: document.getElementById('fileScrollArea'),
    virtualWrapper: document.getElementById('virtualWrapper'),
};

/* ============================================================
   TOAST — gunakan showToast() dari layouts/app.blade
   ============================================================ */
function fmToast(message, type = 'success') {
    if (typeof showToast === 'function') {
        showToast(message, type === 'error');
    } else {
        alert(message);
    }
}

/* ============================================================
   TOAST CONTAINER (untuk upload progress)
   ============================================================ */
let fmToastContainer;
(() => {
    const root = document.createElement('div');
    root.id = 'fmToastContainer';
    root.style.cssText = 'position:fixed;bottom:16px;left:50%;transform:translateX(-50%);' +
        'display:flex;flex-direction:column;gap:8px;z-index:999999;pointer-events:none;' +
        'width:90%;max-width:380px;';
    document.body.appendChild(root);
    fmToastContainer = root;
})();

/* ============================================================
   CLIPBOARD BADGE
   ============================================================ */
function updateClipboardBadge() {
    document.getElementById('fmClipboardBadge')?.remove();
    if (moveClipboard.length > 0) {
        const badge = document.createElement('div');
        badge.id = 'fmClipboardBadge';
        badge.style.cssText = 'position:fixed;bottom:20px;left:20px;background:var(--bg-elevated);' +
            'color:var(--text-primary);border:1px solid var(--border);' +
            'padding:8px 12px;border-radius:9px;z-index:9999;font-size:13px;' +
            'box-shadow:var(--shadow-md);';
        badge.textContent = `Total: ${moveClipboard.length} item`;
        document.body.appendChild(badge);
    }
}
if (moveClipboard.length > 0) updateClipboardBadge();

/* ============================================================
   CONFIRM MODAL
   ============================================================ */
function openConfirm(message, callback) {
    el.confirmMessage.innerText = message;
    el.confirmModal.style.display = 'flex';
    el.confirmOkBtn.onclick = () => { closeConfirm(); callback?.(); };
}
function closeConfirm() { el.confirmModal.style.display = 'none'; }

/* ============================================================
   HELPERS
   ============================================================ */
function escapeHtml(text) {
    return (text || '').replace(/&/g,'&amp;').replace(/</g,'&lt;')
        .replace(/>/g,'&gt;').replace(/"/g,'&quot;').replace(/'/g,'&#039;');
}

function getFileIcon(name) {
    const ext = name.split('.').pop().toLowerCase();
    if (ext === 'pdf')
        return `<img src="https://cdn-icons-png.flaticon.com/512/337/337946.png" style="width:40px;height:40px;object-fit:contain;">`;
    if (['doc','docx'].includes(ext))
        return `<img src="https://cdn-icons-png.flaticon.com/512/337/337932.png" style="width:40px;height:40px;object-fit:contain;">`;
    if (['xls','xlsx'].includes(ext))
        return `<img src="https://cdn-icons-png.flaticon.com/512/337/337958.png" style="width:40px;height:40px;object-fit:contain;">`;
    return '📄';
}

function getItemStyle() {
    if (viewMode === 'grid') {
        return 'background:var(--bg-surface);border:1px solid var(--border);' +
               'display:flex;align-items:center;justify-content:space-between;' +
               'gap:12px;padding:12px;border-radius:14px;';
    }
    return 'border-bottom:1px solid var(--border);background:var(--bg-surface);' +
           'display:flex;align-items:center;justify-content:space-between;' +
           'gap:12px;padding:10px 12px;';
}

function getFileNameStyle() {
    return 'overflow:hidden;text-overflow:ellipsis;white-space:nowrap;' +
           'font-size:0.875rem;width:100%;color:var(--text-primary);';
}

function renderListDate(item) {
    if (viewMode !== 'list' || window.innerWidth < 768) return '';
    return `<div style="font-size:0.875rem;color:var(--text-secondary);text-align:right;">${item.updated_at_formatted ?? '-'}</div>`;
}

/* ============================================================
   VIEW / SORT
   ============================================================ */
function toggleView() {
    viewMode = viewMode === 'grid' ? 'list' : 'grid';
    localStorage.setItem('fm_view', viewMode);
    updateViewUI();
    calculateLayoutCache(true);
    renderVirtualized();
}

function updateViewUI() {
    const { listViewBtn: lbtn, gridViewBtn: gbtn, listHeader: header, dateSortButton: dsb, fileList } = el;

    const activeStyle = 'background:var(--accent-muted);color:var(--accent-text);';
    lbtn.style.cssText = 'width:40px;height:40px;display:flex;align-items:center;justify-content:center;background:none;border:none;cursor:pointer;color:var(--text-secondary);';
    gbtn.style.cssText = lbtn.style.cssText;

    if (viewMode === 'grid') {
        fileList.style.cssText = 'display:grid;grid-template-columns:repeat(2,1fr);gap:10px;font-size:0.875rem;position:relative;width:100%;';
        gbtn.style.cssText += activeStyle;
        header.style.display = 'none';
        dsb.style.display = 'none';
    } else {
        fileList.style.cssText = 'display:flex;flex-direction:column;gap:0;font-size:0.875rem;position:relative;width:100%;';
        lbtn.style.cssText += activeStyle;
        header.style.display = 'block';
        updateHeaderForViewport();
    }
    updateSortIcons();
}

function sortBy(field) {
    sortField === field ? (sortDirection = sortDirection === 'az' ? 'za' : 'az') : (sortField = field, sortDirection = 'az');
    updateSortIcons();
    loadData(true);
}

function updateSortIcons() {
    el.nameSortIcon.innerHTML = '↕';
    el.dateSortIcon.innerHTML = '↕';
    const arrow = sortDirection === 'az' ? '↑' : '↓';
    if (sortField === 'name') el.nameSortIcon.innerHTML = arrow;
    if (sortField === 'date') el.dateSortIcon.innerHTML = arrow;
}

function updateHeaderForViewport() {
    const isMobile = window.innerWidth < 768;
    el.dateSortButton.style.display = isMobile ? 'none' : 'flex';
    el.headerLayout.style.gridTemplateColumns = isMobile ? '1fr 40px' : '1fr 120px 40px';
}

/* ============================================================
   VIRTUAL RENDERER
   ============================================================ */
function createReusableNode() {
    const div = document.createElement('div');
    div.className = 'fm-item-box';
    div.style.transform = 'translate3d(0,0,0)';
    div.innerHTML = `
        <div style="display:flex;align-items:center;gap:12px;min-width:0;flex:1;">
            <div class="fm-icon" style="flex-shrink:0;"></div>
            <div class="fm-content" style="width:100%;min-width:0;"></div>
        </div>
        <button type="button" aria-label="Menu file" tabindex="0" class="fm-menu-dot">⋮</button>`;
    return div;
}

function recycleNode(node) {
    const id = node.dataset.id ? parseInt(node.dataset.id) : null;
    node.removeAttribute('data-id');   node.removeAttribute('data-name');
    node.removeAttribute('data-raw-name'); node.removeAttribute('data-type');
    node.removeAttribute('data-search'); node.removeAttribute('data-path');
    node.removeAttribute('data-url');
    node.className = 'fm-item-box'; node.style.transform = ''; node.style.width = '';
    node.title = ''; node._w = null;
    node.classList.remove('fm-selected');
    if (id !== null) SelectionEngine.onNodeRecycled(id);
    if (recycledPool.length < POOL_MAX) recycledPool.push(node);
}

function updateReusableNode(node, item) {
    const safeName = escapeHtml(item.name);
    node.dataset.id      = item.id;
    node.dataset.name    = safeName;
    node.dataset.rawName = item.name;
    node.dataset.type    = item.type;
    node.dataset.search  = safeName.toLowerCase();
    node.title           = safeName;
    item.path ? (node.dataset.path = item.path) : delete node.dataset.path;
    item.url  ? (node.dataset.url  = item.url)  : delete node.dataset.url;

    node.style.cssText = 'position:absolute;' + getItemStyle();
    SelectionEngine.applyToNode(node, item.id);

    const iconEl    = node.querySelector('.fm-icon');
    const contentEl = node.querySelector('.fm-content');
    iconEl.innerHTML = item.type === 'folder' ? '📁' : getFileIcon(item.name);

    if (viewMode === 'list' && window.innerWidth >= 768) {
        contentEl.style.cssText = 'display:grid;grid-template-columns:1fr 120px;align-items:center;width:100%;gap:16px;min-width:0;';
        contentEl.innerHTML = `<div style="${getFileNameStyle()}">${safeName}</div>${renderListDate(item)}`;
    } else {
        contentEl.style.cssText = 'width:100%;min-width:0;';
        contentEl.innerHTML = `<div style="${getFileNameStyle()}">${safeName}</div>`;
    }
}

function renderEmptyState() {
    visibleNodeMap.forEach(node => { node.remove(); recycleNode(node); });
    visibleNodeMap.clear();
    document.getElementById('fmEmptyState')?.remove();
    const empty = document.createElement('div');
    empty.id = 'fmEmptyState';
    empty.style.cssText = 'position:absolute;inset:0;display:flex;flex-direction:column;' +
        'align-items:center;justify-content:center;padding:64px 0;color:var(--text-muted);';
    empty.innerHTML = `<div style="font-size:3rem;margin-bottom:12px;">📂</div><div style="font-size:0.875rem;">Folder kosong</div>`;
    el.fileList.appendChild(empty);
    el.virtualWrapper.style.height = '0px';
    itemMap.clear();
}

function cleanupEmptyState() { document.getElementById('fmEmptyState')?.remove(); }

function renderVisibleItems(visibleItems) {
    const nextVisibleMap = new Map();
    const gap            = viewMode === 'grid' ? 8 : 0;
    const ITEM_SPACING   = viewMode === 'list' ? 10 : 8;

    visibleItems.forEach((item, i) => {
        let node = visibleNodeMap.get(item.id);
        if (!node) { node = recycledPool.pop() || createReusableNode(); node.style.position = 'absolute'; el.fileList.appendChild(node); }
        updateReusableNode(node, item);

        const realIndex = virtualStart + i;
        const row = Math.floor(realIndex / cachedItemsPerRow);
        const col = realIndex % cachedItemsPerRow;
        const top = row * (cachedRowHeight + ITEM_SPACING);

        if (viewMode === 'grid') {
            const left = col * (cachedItemWidth + gap);
            node.style.transform = `translate3d(${left}px,${top}px,0)`;
            if (node._w !== cachedItemWidth) { node.style.width = cachedItemWidth + 'px'; node._w = cachedItemWidth; }
        } else {
            node.style.transform = `translate3d(0px,${top}px,0)`;
            if (node._w !== '100%') { node.style.width = '100%'; node._w = '100%'; }
        }

        nextVisibleMap.set(item.id, node);
        itemMap.set(item.id, node);
    });
    return nextVisibleMap;
}

function cleanupInvisibleNodes(nextVisibleMap) {
    visibleNodeMap.forEach((node, id) => {
        if (nextVisibleMap.has(id)) return;
        node.remove(); recycleNode(node); itemMap.delete(id);
    });
    visibleNodeMap = nextVisibleMap;
}

function updateWrapperHeight(totalRows) {
    const ITEM_SPACING = viewMode === 'list' ? 10 : 8;
    el.virtualWrapper.style.height = (totalRows * (cachedRowHeight + ITEM_SPACING)) + 'px';
}

function renderVirtualized() {
    const scrollTop      = el.fileScrollArea.scrollTop;
    const viewportHeight = el.fileScrollArea.clientHeight;
    const totalRows      = Math.ceil(rawData.length / cachedItemsPerRow);
    const visibleRows    = Math.ceil(viewportHeight / (cachedRowHeight || 88));
    const startRow       = Math.max(0, Math.floor(scrollTop / (cachedRowHeight || 88)) - VIRTUAL_BUFFER);
    const endRow         = Math.min(totalRows, startRow + visibleRows + VIRTUAL_BUFFER * 2);

    virtualStart     = startRow * cachedItemsPerRow;
    const virtualEnd = Math.min(rawData.length, endRow * cachedItemsPerRow);

    if (rawData.length === 0) { renderEmptyState(); return; }
    cleanupEmptyState();

    const visibleItems   = rawData.slice(virtualStart, virtualEnd);
    const nextVisibleMap = renderVisibleItems(visibleItems);
    cleanupInvisibleNodes(nextVisibleMap);
    updateWrapperHeight(totalRows);
}

function calculateLayoutCache(force = false) {
    cachedItemsPerRow = getGridColumnCount();
    if (force || layoutViewMode !== viewMode) {
        const sample = el.fileList.querySelector('.fm-item-box');
        cachedRowHeight = sample ? sample.offsetHeight : (viewMode === 'grid' ? 88 : 58);
        layoutViewMode  = viewMode;
    }
    const containerWidth = el.fileList.clientWidth || el.fileList.offsetWidth || 1;
    const gap            = viewMode === 'grid' ? 8 : 0;
    cachedItemWidth      = viewMode === 'grid'
        ? (containerWidth - (cachedItemsPerRow - 1) * gap) / cachedItemsPerRow
        : containerWidth;
}

function getGridColumnCount() {
    if (viewMode !== 'grid') return 1;
    const w = el.fileList.clientWidth;
    if (w >= 1280) return 6;
    if (w >= 1024) return 5;
    if (w >= 768)  return 4;
    if (w >= 640)  return 3;
    return 2;
}

/* ============================================================
   INDEX MAP
   ============================================================ */
function buildIndexMap(fromIndex = 0) {
    for (let i = fromIndex; i < rawData.length; i++) {
        rawMap.set(rawData[i].id, rawData[i]);
        rawIndexMap.set(rawData[i].id, i);
    }
}
function rebuildIndexMap() {
    rawMap.clear(); rawIndexMap.clear();
    rawData.forEach((item, i) => { rawMap.set(item.id, item); rawIndexMap.set(item.id, i); });
}

/* ============================================================
   FOLDER CACHE
   ============================================================ */
function cacheKey(page) { return `${parentId}|${keyword}|${sortField}|${sortDirection}|${page}`; }
function getCached(page) {
    const c = folderCache.get(cacheKey(page));
    if (!c) return null;
    if (Date.now() - c.ts > FOLDER_CACHE_TTL) { folderCache.delete(cacheKey(page)); return null; }
    return c;
}
function setCache(page, data, hasMoreFlag) {
    if (keyword) return;
    folderCache.set(cacheKey(page), { data, hasMore: hasMoreFlag, ts: Date.now() });
}
function invalidateCache() { folderCache.clear(); }

/* ============================================================
   DATA LOADING
   ============================================================ */
async function loadData(reset = true) {
    if (reset && fetchController) fetchController.abort();
    if (isLoading && !reset) return;
    if (!hasMore && !reset) return;

    const currentRequestId = ++requestId;
    fetchController = new AbortController();
    isLoading       = true;

    if (reset) {
        currentPage = 1; rawData = []; hasMore = true;
        itemMap.clear(); rawMap.clear(); rawIndexMap.clear();
        visibleNodeMap.forEach(node => { node.remove(); recycleNode(node); });
        visibleNodeMap.clear();
        SelectionEngine.reset();
    }

    el.loadingState.style.display = 'block';

    try {
        const cached = getCached(currentPage);
        let pageData, hasMoreFlag;

        if (cached) {
            pageData    = cached.data;
            hasMoreFlag = cached.hasMore;
        } else {
            const params = new URLSearchParams({
                folder: parentId ?? '', page: currentPage, search: keyword,
                sort_field: sortField, sort_direction: sortDirection === 'az' ? 'asc' : 'desc',
            });
            const res  = await fetch(`/file-manager/data?${params}`, { signal: fetchController.signal, cache: 'no-store' });
            const json = await res.json();
            if (currentRequestId !== requestId) return;
            pageData    = json.data;
            hasMoreFlag = json.has_more;
            setCache(currentPage, pageData, hasMoreFlag);
        }

        if (currentRequestId !== requestId) return;

        const prevLen = rawData.length;
        if (reset) {
            rawData = [...pageData];
        } else {
            rawData.push(...pageData);
            if (rawData.length > RAW_DATA_MAX) {
                const overflow = rawData.length - RAW_DATA_MAX;
                const removed  = rawData.splice(0, overflow);
                removed.forEach(item => { rawMap.delete(item.id); rawIndexMap.delete(item.id); });
                rebuildIndexMap();
            }
        }

        buildIndexMap(reset ? 0 : prevLen);
        hasMore = hasMoreFlag;
        calculateLayoutCache();
        if (reset) renderVirtualized();
        currentPage++;

    } catch (err) {
        if (err.name !== 'AbortError') { console.error(err); fmToast('Gagal memuat data', 'error'); }
    } finally {
        if (currentRequestId === requestId) { isLoading = false; el.loadingState.style.display = 'none'; }
    }
}

/* ============================================================
   NAVIGATION
   ============================================================ */
function openFolder(id) { window.location.assign(`/file-manager?folder=${id}`); }
function openFile(url) {
    if (!url || typeof url !== 'string') return;
    try { new URL(url, window.location.origin); }
    catch { fmToast('URL file tidak valid', 'error'); return; }
    window.open(encodeURI(url), '_blank', 'noopener,noreferrer');
}

/* ============================================================
   CLICK / TOUCH
   ============================================================ */
function handleItemClick(e, id) {
    if (longPressTriggered) { longPressTriggered = false; return; }
    if (!rawMap.has(id)) return;
    const index = rawIndexMap.get(id) ?? -1;
    if (window.innerWidth < 768) { SelectionEngine.handleClick(e, id, index); return; }
    clearTimeout(clickTimer);
    clickTimer = setTimeout(() => SelectionEngine.handleClick(e, id, index), 180);
}
function handleItemDoubleClick(callback) { clearTimeout(clickTimer); callback?.(); }
function handleTouchStart(e, id, name, type, path = '', url = '') {
    longPressTriggered = false;
    touchTimer = setTimeout(() => {
        longPressTriggered = true;
        e.preventDefault();
        openContextMenu({ preventDefault(){}, pageX: e.touches[0].pageX, pageY: e.touches[0].pageY }, id, name, type, path, url);
        navigator.vibrate?.(30);
    }, 500);
}
function handleTouchEnd() { clearTimeout(touchTimer); }

/* ============================================================
   FILE LIST EVENTS
   ============================================================ */
function handleFileListClick(e) {
    const btn = e.target.closest('.fm-menu-dot');
    if (btn) {
        e.stopPropagation();
        const box = btn.closest('.fm-item-box');
        openItemMenu(e, parseInt(box.dataset.id), box.dataset.name, box.dataset.type, box.dataset.path, box.dataset.url);
        return;
    }
    const box = e.target.closest('.fm-item-box');
    if (!box) return;
    const id = parseInt(box.dataset.id);
    handleItemClick(e, id);
    const now = Date.now();
    if (lastTapId === id && now - lastTap < 280) {
        clearTimeout(clickTimer);
        if (window.innerWidth < 768)
            box.dataset.type === 'folder' ? openFolder(id) : openFile(box.dataset.url);
    }
    lastTap = now; lastTapId = id;
}
function handleFileListDoubleClick(e) {
    const box = e.target.closest('.fm-item-box');
    if (!box) return;
    const id = parseInt(box.dataset.id);
    handleItemDoubleClick(() => { box.dataset.type === 'folder' ? openFolder(id) : openFile(box.dataset.url); });
}
function handleFileListTouchStart(e) {
    const box = e.target.closest('.fm-item-box');
    if (!box) return;
    handleTouchStart(e, parseInt(box.dataset.id), box.dataset.name, box.dataset.type, box.dataset.path, box.dataset.url);
}
function handleFileListContextMenu(e) {
    const box = e.target.closest('.fm-item-box');
    if (!box) return;
    e.preventDefault();
    openContextMenu(e, parseInt(box.dataset.id), box.dataset.name, box.dataset.type, box.dataset.path, box.dataset.url);
}

el.fileList.addEventListener('click',       handleFileListClick);
el.fileList.addEventListener('dblclick',    handleFileListDoubleClick);
el.fileList.addEventListener('touchstart',  handleFileListTouchStart, passiveOptions);
el.fileList.addEventListener('touchend',    handleTouchEnd, passiveOptions);
el.fileList.addEventListener('touchmove',   handleTouchEnd, passiveOptions);
el.fileList.addEventListener('touchcancel', handleTouchEnd, passiveOptions);
el.fileList.addEventListener('contextmenu', handleFileListContextMenu);

/* ============================================================
   DOCUMENT EVENTS
   ============================================================ */
function handleDocumentClick(e) {
    const actionEl = e.target.closest('[data-action]');
    if (actionEl) {
        switch (actionEl.dataset.action) {
            case 'toggle-view':        toggleView(); break;
            case 'toggle-top-menu':    toggleTopMenu(); break;
            case 'upload-file':        el.fileInput.click(); closeTopMenu(); break;
            case 'open-folder-modal':  openFolderModal(); closeTopMenu(); break;
            case 'bulk-delete':        bulkDelete(); break;
            case 'close-folder-modal': closeFolderModal(); break;
            case 'create-folder':      createFolder(); break;
            case 'close-rename':       closeRename(); break;
            case 'submit-rename':      submitRename(); break;
            case 'close-preview':      closePreview(); break;
            case 'close-confirm':      closeConfirm(); break;
            case 'sort':               sortBy(actionEl.dataset.field); break;
            case 'cm-open':            cmOpen(); break;
            case 'cm-rename':          cmRename(); break;
            case 'cm-move':            cmMove(); break;
            case 'cm-delete':          cmDelete(); break;
            case 'cm-bulk-delete':     cmBulkDelete(); break;
            case 'paste-here':         pasteHere(); break;
        }
        return;
    }
    if (!e.target.closest('#topMenu') && !e.target.closest('[data-action="toggle-top-menu"]')) closeTopMenu();
    if (!e.target.closest('#contextMenu')) closeContextMenu();
    if (!e.target.closest('.fm-item-box') && !e.target.closest('#bulkBar')) SelectionEngine.clear();
}

function handleDocumentKeydown(e) {
    const tag = document.activeElement.tagName;
    if (tag === 'INPUT' || tag === 'TEXTAREA') return;
    if (e.key === 'Delete' && SelectionEngine.size() > 0) bulkDelete();
    if ((e.ctrlKey || e.metaKey) && e.key.toLowerCase() === 'a') { e.preventDefault(); SelectionEngine.selectAll(); }
    if (e.key === 'ArrowDown') { e.preventDefault(); if (keyboardIndex < rawData.length - 1) selectByKeyboard(keyboardIndex + 1); }
    if (e.key === 'ArrowUp')   { e.preventDefault(); if (keyboardIndex > 0) selectByKeyboard(keyboardIndex - 1); }
    if (e.key === 'Enter' && SelectionEngine.size() === 1) {
        const item = rawMap.get(SelectionEngine.toArray()[0]);
        if (!item) return;
        item.type === 'folder' ? openFolder(item.id) : openFile(item.url);
    }
    if (e.key === 'Escape') {
        closeFolderModal(); closeRename(); closePreview(); closeContextMenu(); closeConfirm();
        if (moveClipboard.length > 0) {
            moveClipboard = []; localStorage.removeItem('fm_clipboard');
            updateClipboardBadge(); fmToast('Pemindahan dibatalkan', 'info');
        }
    }
    if (e.code === 'Space' && SelectionEngine.size() === 1) {
        e.preventDefault();
        const id   = SelectionEngine.toArray()[0];
        const node = itemMap.get(id);
        if (node?.dataset.path) previewFile(id, node.dataset.path, node.dataset.rawName);
    }
}

function handleDocumentContextMenu(e) {
    if (e.target.closest('.fm-item-box') || e.target.closest('#contextMenu')) return;
    e.preventDefault();
    if (moveClipboard.length === 0) return;
    const menu = el.contextMenu;
    menu.innerHTML = `<button data-action="paste-here" class="fm-ctx-item">📥 Tempel di sini (${moveClipboard.length})</button>`;
    positionMenu(menu, e.pageX, e.pageY);
    menu.style.display = 'block';
}

document.addEventListener('click',       handleDocumentClick);
document.addEventListener('keydown',     handleDocumentKeydown);
document.addEventListener('contextmenu', handleDocumentContextMenu);

/* ============================================================
   SCROLL & RESIZE
   ============================================================ */
function handleScroll() {
    if (scrollTicking) return;
    scrollTicking = true;
    requestAnimationFrame(() => {
        renderVirtualized();
        const nearBottom = el.fileScrollArea.scrollTop + el.fileScrollArea.clientHeight >= el.fileScrollArea.scrollHeight - 300;
        if (nearBottom) {
            const now = Date.now();
            if (now - lastLoadMore > 300) { lastLoadMore = now; loadData(false); }
        }
        scrollTicking = false;
    });
}

el.fileScrollArea.addEventListener('scroll', handleScroll, scrollOptions);
el.fileScrollArea.addEventListener('scroll', closeContextMenu, { passive: true });

window.addEventListener('resize', () => {
    clearTimeout(resizeTimer);
    resizeTimer = setTimeout(() => {
        calculateLayoutCache(false);
        updateViewUI();
        if (viewMode === 'list') updateHeaderForViewport();
        renderVirtualized();
    }, 80);
});

/* ============================================================
   CONTEXT MENU
   ============================================================ */
function positionMenu(menu, x, y) {
    menu.style.left = Math.max(10, Math.min(x, window.innerWidth  - 200)) + 'px';
    menu.style.top  = Math.max(10, Math.min(y, window.innerHeight - 200)) + 'px';
}

function openContextMenu(e, id, name, type, path = '', url = '') {
    e.preventDefault();
    if (!SelectionEngine.has(id)) { SelectionEngine.clear(); SelectionEngine.selectOnly(id, rawIndexMap.get(id) ?? -1); }
    cmId = id; cmName = name; cmType = type; cmPath = path; cmUrl = url;

    const menu     = el.contextMenu;
    const pasteBtn = moveClipboard.length > 0
        ? `<div style="height:1px;background:var(--border);margin:4px 0;"></div>
           <button data-action="paste-here" class="fm-ctx-item">📥 Tempel di sini (${moveClipboard.length})</button>`
        : '';

    menu.innerHTML = SelectionEngine.size() > 1
        ? `<button data-action="cm-move" class="fm-ctx-item">📂 Pindahkan (${SelectionEngine.size()})</button>
           <button data-action="cm-bulk-delete" class="fm-ctx-item fm-ctx-danger">🗑 Hapus ${SelectionEngine.size()} item</button>${pasteBtn}`
        : `<button data-action="cm-open"   class="fm-ctx-item">📂 Buka</button>
           <button data-action="cm-rename" class="fm-ctx-item">✏️ Rename</button>
           <button data-action="cm-move"   class="fm-ctx-item">📂 Pindahkan</button>
           <button data-action="cm-delete" class="fm-ctx-item fm-ctx-danger">🗑 Hapus</button>${pasteBtn}`;

    positionMenu(menu, e.pageX, e.pageY);
    menu.style.display = 'block';
}

function openItemMenu(e, id, name, type, path = '', url = '') {
    e.preventDefault(); e.stopPropagation();
    openContextMenu({ preventDefault(){}, pageX: e.pageX, pageY: e.pageY }, id, name, type, path, url);
}

function closeContextMenu(e) {
    if (e?.target?.closest?.('#contextMenu')) return;
    el.contextMenu.style.display = 'none';
}

function cmOpen()   { closeContextMenu(); cmType === 'folder' ? openFolder(cmId) : openFile(cmUrl); }
function cmRename() { closeContextMenu(); openRename(cmId, cmName); }
function cmDelete() { closeContextMenu(); deleteItem(cmId); }
function cmMove() {
    closeContextMenu();
    if (SelectionEngine.size() === 0 && cmId) SelectionEngine.selectOnly(cmId, rawIndexMap.get(cmId) ?? -1);
    if (SelectionEngine.size() === 0) { fmToast('Tidak ada item dipilih', 'warning'); return; }
    moveClipboard = SelectionEngine.toArray();
    localStorage.setItem('fm_clipboard', JSON.stringify(moveClipboard));
    updateClipboardBadge();
    fmToast(moveClipboard.length + ' item siap dipindahkan', 'info');
}
function cmBulkDelete() { bulkDelete(); }

/* ============================================================
   KEYBOARD NAV
   ============================================================ */
function selectByKeyboard(index) {
    const item = rawData[index];
    if (!item) return;
    keyboardIndex = index;
    SelectionEngine.selectOnly(item.id, index);
    itemMap.get(item.id)?.scrollIntoView({ block: 'nearest' });
}

/* ============================================================
   FOLDER MODAL
   ============================================================ */
function openFolderModal() {
    el.folderModal.style.display = 'flex';
    document.body.style.overflow = 'hidden';
    setTimeout(() => el.folderName.focus(), 100);
}
function closeFolderModal() {
    el.folderModal.style.display = 'none';
    document.body.style.overflow = '';
}

function createFolder() {
    const name = el.folderName.value.trim();
    if (!name) { fmToast('Nama folder wajib diisi', 'warning'); return; }
    closeFolderModal();

    const tempId   = 'temp_' + Date.now();
    const tempItem = { id: tempId, name, type: 'folder', updated_at_formatted: 'Baru saja' };
    rawData.unshift(tempItem);
    rebuildIndexMap();
    renderVirtualized();
    el.folderName.value = '';

    fetch('/file-manager/folder', {
        method:  'POST',
        headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content },
        body:    JSON.stringify({ name, parent_id: parentId }),
    })
    .then(r => r.json())
    .then(res => {
        invalidateCache();
        if (!res.success) {
            rawData = rawData.filter(i => i.id !== tempId);
            rebuildIndexMap(); renderVirtualized();
            fmToast(res.message, 'error'); return;
        }
        loadData(true);
    })
    .catch(() => {
        rawData = rawData.filter(i => i.id !== tempId);
        rebuildIndexMap(); renderVirtualized();
        fmToast('Gagal membuat folder', 'error');
    });
}

el.folderName.addEventListener('keydown', e => { if (e.key === 'Enter') { e.preventDefault(); createFolder(); } });

/* ============================================================
   RENAME MODAL
   ============================================================ */
function openRename(id, name) {
    renameId = id;
    el.renameInput.value = name;
    el.renameModal.style.display = 'flex';
    document.body.style.overflow = 'hidden';
    setTimeout(() => { el.renameInput.focus(); el.renameInput.select(); }, 100);
}
function closeRename() {
    el.renameModal.style.display = 'none';
    document.body.style.overflow = '';
}

function submitRename() {
    const name = el.renameInput.value.trim();
    if (!name) { fmToast('Nama tidak boleh kosong', 'warning'); return; }
    const item    = rawMap.get(renameId);
    const oldName = item?.name;
    closeRename();

    if (item) {
        item.name = name;
        const node = itemMap.get(renameId);
        if (node) {
            const safe = escapeHtml(name);
            node.dataset.rawName = name; node.dataset.name = safe;
            node.dataset.search  = safe.toLowerCase(); node.title = safe;
            const nameEl = node.querySelector('.fm-content div');
            if (nameEl) nameEl.textContent = name;
        }
    }

    fetch(`/file-manager/rename/${renameId}`, {
        method:  'POST',
        headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content },
        body:    JSON.stringify({ name }),
    })
    .then(r => r.json())
    .then(res => {
        invalidateCache();
        if (!res.success) {
            if (item) item.name = oldName;
            const node = itemMap.get(renameId);
            if (node && oldName) {
                const safe = escapeHtml(oldName);
                node.dataset.rawName = oldName; node.dataset.name = safe;
                const nameEl = node.querySelector('.fm-content div');
                if (nameEl) nameEl.textContent = oldName;
            }
            fmToast(res.message, 'error'); return;
        }
        loadData(true);
    });
}

el.renameInput.addEventListener('keydown', e => { if (e.key === 'Enter') { e.preventDefault(); submitRename(); } });

/* ============================================================
   DELETE
   ============================================================ */
function deleteItem(id) {
    openConfirm('Yakin hapus item ini?', () => {
        fetch(`/file-manager/delete/${id}`, {
            method: 'DELETE',
            headers: { 'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content },
        })
        .then(r => r.json())
        .then(() => { invalidateCache(); loadData(true); });
    });
}

function bulkDelete() {
    const ids = SelectionEngine.toArray();
    if (ids.length === 0) return;
    openConfirm(`Hapus ${ids.length} item?`, () => {
        fetch('/file-manager/bulk-delete', {
            method:  'DELETE',
            headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content },
            body:    JSON.stringify({ ids }),
        })
        .then(r => r.json())
        .then(res => {
            if (!res.success) { fmToast(res.message || 'Gagal menghapus', 'error'); return; }
            invalidateCache(); SelectionEngine.reset(); loadData(true);
            fmToast('Berhasil dihapus', 'success');
        })
        .catch(err => { console.error(err); fmToast('Terjadi kesalahan', 'error'); });
    });
}

/* ============================================================
   MOVE / PASTE
   ============================================================ */
function pasteHere() {
    closeContextMenu();
    fetch('/file-manager/move', {
        method:  'POST',
        headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content },
        body:    JSON.stringify({ ids: moveClipboard, target_id: parentId || null }),
    })
    .then(r => r.json())
    .then(res => {
        moveClipboard = []; SelectionEngine.reset();
        localStorage.removeItem('fm_clipboard');
        updateClipboardBadge(); invalidateCache();
        if (res.success) { loadData(true); fmToast('Berhasil dipindahkan', 'success'); }
        else { fmToast(res.message || 'Gagal pindah', 'error'); }
    })
    .catch(err => { console.error(err); fmToast('Error saat pindah', 'error'); });
}

/* ============================================================
   UPLOAD — validasi client + concurrent 3 paralel
   Hanya PDF, Word (doc/docx), Excel (xls/xlsx) — maks 10 MB
   ============================================================ */
el.fileInput.addEventListener('change', async function () {
    const allFiles = Array.from(this.files);
    if (allFiles.length === 0) return;

    // ── Validasi client-side per file ──────────────────────
    const accepted = [];

    for (const f of allFiles) {
        const ext    = f.name.split('.').pop().toLowerCase();
        const sizeMb = f.size / 1024 / 1024;

        if (!ALLOWED_EXT.has(ext)) {
            fmToast(
                `❌ "${f.name}" ditolak — hanya PDF, Word, dan Excel yang diizinkan`,
                'error'
            );
            continue;
        }

        if (sizeMb > MAX_FILE_MB) {
            fmToast(
                `❌ "${f.name}" ditolak — ukuran ${sizeMb.toFixed(1)} MB melebihi batas ${MAX_FILE_MB} MB`,
                'error'
            );
            continue;
        }

        accepted.push(f);
    }

    if (accepted.length === 0) { this.value = ''; return; }

    // ── Progress toast ──────────────────────────────────────
    let successCount = 0, failedCount = 0;
    const totalFiles = accepted.length;
    const progressId = 'fm_upload_' + Date.now();

    fmToastContainer.insertAdjacentHTML('beforeend', `
        <div id="${progressId}"
            style="background:var(--bg-elevated);border:1px solid var(--border);
                   border-radius:14px;box-shadow:var(--shadow-lg);
                   padding:16px;pointer-events:auto;color:var(--text-primary);">
            <div style="display:flex;align-items:center;justify-content:space-between;gap:12px;margin-bottom:12px;">
                <span style="font-size:0.875rem;font-weight:600;">Uploading Files</span>
                <span id="${progressId}_count" style="font-size:0.75rem;color:var(--text-muted);">0 / ${totalFiles}</span>
            </div>
            <div style="width:100%;height:6px;background:var(--border-strong);border-radius:999px;overflow:hidden;margin-bottom:10px;">
                <div id="${progressId}_bar" style="height:100%;background:var(--accent);border-radius:999px;width:0%;transition:width .2s;"></div>
            </div>
            <div id="${progressId}_text" style="font-size:0.75rem;color:var(--text-secondary);">Menyiapkan upload...</div>
        </div>`);

    const progressBar   = document.getElementById(progressId + '_bar');
    const progressCount = document.getElementById(progressId + '_count');
    const progressText  = document.getElementById(progressId + '_text');
    const progressToast = document.getElementById(progressId);
    const csrf          = document.querySelector('meta[name="csrf-token"]').content;

    // ── Upload satu file, tangkap pesan error dari server ──
    async function uploadOne(file) {
        const fd = new FormData();
        fd.append('file', file);
        fd.append('parent_id', parentId);

        try {
            const res  = await fetch('/file-manager/upload', {
                method:  'POST',
                headers: { 'X-CSRF-TOKEN': csrf },
                body:    fd,
            });

            // Tangkap JSON dari response (200 atau 422)
            const json = await res.json();

            if (res.ok && json.success) {
                successCount++;
            } else {
                failedCount++;
                // Tampilkan pesan spesifik dari server
                const reason = json.message || (res.status === 422 ? 'File tidak valid' : 'Upload ditolak server');
                fmToast(`❌ Gagal "${file.name}" — ${reason}`, 'error');
            }
        } catch {
            failedCount++;
            fmToast(`❌ Gagal "${file.name}" — koneksi terputus`, 'error');
        }

        const done = successCount + failedCount;
        progressBar.style.width   = Math.round((done / totalFiles) * 100) + '%';
        progressCount.innerText   = `${done} / ${totalFiles}`;
        progressText.innerText    = `Berhasil: ${successCount} • Gagal: ${failedCount}`;
    }

    // ── Proses batch concurrent ─────────────────────────────
    for (let i = 0; i < accepted.length; i += UPLOAD_CONCURRENCY) {
        const batch = accepted.slice(i, i + UPLOAD_CONCURRENCY);
        progressText.textContent = batch.map(f => f.name).join(', ');
        await Promise.all(batch.map(uploadOne));
    }

    progressBar.style.width = '100%';
    progressText.innerText  = `Selesai • ${successCount} berhasil • ${failedCount} gagal`;
    setTimeout(() => progressToast?.remove(), 3000);

    invalidateCache();
    loadData(true);

    if (successCount > 0 && failedCount === 0)
        fmToast(`✅ ${successCount} file berhasil diupload`, 'success');
    else if (successCount > 0 && failedCount > 0)
        fmToast(`⚠️ ${successCount} berhasil, ${failedCount} gagal`, 'warning');
    // Jika semua gagal, toast error sudah ditampilkan per-file di uploadOne()

    this.value = '';
});

/* ============================================================
   PREVIEW
   ============================================================ */
function previewFile(id, path, name) {
    const ext     = name.split('.').pop().toLowerCase();
    const safeUrl = encodeURI(`/storage/${path}`);
    let content;
    if (ext === 'pdf')
        content = `<iframe src="${safeUrl}" style="width:100%;height:100%;"></iframe>`;
    else
        content = `<div style="text-align:center;color:var(--text-muted);">
                       <div style="font-size:3rem;margin-bottom:12px;">📄</div>
                       <div style="margin-bottom:16px;">Preview tidak tersedia untuk tipe file ini</div>
                       <a href="${safeUrl}" target="_blank" rel="noopener noreferrer"
                          style="color:var(--accent);text-decoration:none;font-size:0.875rem;">
                           ⬇ Download file
                       </a>
                   </div>`;
    el.previewContent.innerHTML = content;
    el.previewModal.style.display = 'flex';
    document.body.style.overflow = 'hidden';
}
function closePreview() {
    el.previewModal.style.display = 'none';
    el.previewContent.innerHTML = '';
    document.body.style.overflow = '';
}

/* ============================================================
   TOP MENU
   ============================================================ */
function toggleTopMenu() {
    el.topMenu.style.display = el.topMenu.style.display === 'none' || !el.topMenu.style.display ? 'block' : 'none';
}
function closeTopMenu() { el.topMenu.style.display = 'none'; }

/* ============================================================
   MODAL BACKDROP CLOSE
   ============================================================ */
[
    [el.folderModal, closeFolderModal],
    [el.renameModal, closeRename],
    [el.previewModal, closePreview],
    [el.confirmModal, closeConfirm],
].forEach(([modal, fn]) => {
    modal.addEventListener('click', e => { if (e.target === modal) fn(); });
});

/* ============================================================
   SEARCH
   ============================================================ */
el.searchInput.addEventListener('input', function () {
    clearTimeout(searchTimeout);
    const value = this.value.trim();
    if (value.length > 0 && value.length < 2) return;
    searchTimeout = setTimeout(() => {
        if (keyword === value) return;
        keyword = value;
        loadData(true);
    }, 450);
});

/* ============================================================
   INIT
   ============================================================ */
function initFileManager() {
    updateViewUI();
    calculateLayoutCache(true);
    requestAnimationFrame(() => loadData(true));
}

if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', initFileManager);
} else {
    initFileManager();
}

/* ============================================================
   CLEANUP
   ============================================================ */
window.fmCleanup = function () {
    document.removeEventListener('click',       handleDocumentClick);
    document.removeEventListener('keydown',     handleDocumentKeydown);
    document.removeEventListener('contextmenu', handleDocumentContextMenu);
    el.fileList?.removeEventListener('click',       handleFileListClick);
    el.fileList?.removeEventListener('dblclick',    handleFileListDoubleClick);
    el.fileList?.removeEventListener('touchstart',  handleFileListTouchStart, passiveOptions);
    el.fileList?.removeEventListener('touchend',    handleTouchEnd, passiveOptions);
    el.fileList?.removeEventListener('touchmove',   handleTouchEnd, passiveOptions);
    el.fileList?.removeEventListener('touchcancel', handleTouchEnd, passiveOptions);
    el.fileList?.removeEventListener('contextmenu', handleFileListContextMenu);
    el.fileScrollArea?.removeEventListener('scroll', handleScroll, scrollOptions);
    fetchController?.abort();
    clearTimeout(clickTimer); clearTimeout(touchTimer);
    clearTimeout(searchTimeout); clearTimeout(resizeTimer);
    recycledPool.length = 0;
    itemMap.clear(); visibleNodeMap.clear();
    rawMap.clear(); rawIndexMap.clear();
    folderCache.clear();
    SelectionEngine.reset();
};
</script>

<style>
/*
 * SEMUA WARNA PAKAI CSS VARIABLE DARI layouts/app.blade
 * Tidak ada hardcode warna / dark: prefix Tailwind
 */

.fm-item-box {
    width:100%; min-height:64px; position:relative;
    user-select:none; cursor:pointer;
    transition:background-color .15s ease, border-color .15s ease, box-shadow .15s ease;
    border-radius:12px;
}
.fm-item-box:hover {
    background:rgba(37,99,235,.06) !important;
    border-color:rgba(37,99,235,.2) !important;
}
.fm-item-box.fm-selected {
    background:rgba(37,99,235,.12) !important;
    border-color:var(--accent) !important;
    box-shadow:inset 0 0 0 1.5px var(--accent);
}

/* Virtual wrapper */
#virtualWrapper { position:relative; width:100%; }
#fileList       { position:relative; width:100%; display:block !important; }

/* Menu dots */
.fm-menu-dot {
    width:32px; height:32px; border-radius:50%;
    background:none; border:none; cursor:pointer;
    display:flex; align-items:center; justify-content:center;
    font-size:1.25rem; color:var(--text-muted);
    flex-shrink:0; transition:background .15s;
}
.fm-menu-dot:hover { background:var(--bg-elevated); color:var(--text-primary); }

/* Context menu items */
.fm-ctx-item {
    width:100%; text-align:left; padding:10px 16px;
    font-size:0.875rem; background:none; border:none; cursor:pointer;
    color:var(--text-secondary); display:block; transition:background .12s;
    font-family:inherit;
}
.fm-ctx-item:hover     { background:var(--bg-elevated); color:var(--text-primary); }
.fm-ctx-danger         { color:#EF4444; }
.fm-ctx-danger:hover   { background:rgba(239,68,68,.1); color:#DC2626; }

/* Top menu items */
.fm-menu-item {
    width:100%; display:block; text-align:left;
    padding:12px 16px; font-size:0.875rem;
    background:none; border:none; cursor:pointer;
    color:var(--text-secondary); transition:background .12s;
    text-decoration:none; font-family:inherit;
}
.fm-menu-item:hover { background:var(--bg-elevated); color:var(--text-primary); }

/* Modal animations */
#folderModal  > div,
#renameModal  > div,
#previewModal > div { animation: fmModalPop .15s ease; }
@keyframes fmModalPop {
    from { opacity:0; transform:scale(.96) translateY(8px); }
    to   { opacity:1; transform:scale(1) translateY(0); }
}

/* Focus */
button:focus-visible, input:focus-visible {
    outline:2px solid var(--accent); outline-offset:2px;
}

/* Mobile */
@media (max-width: 640px) {
    .fm-item-box { min-height:60px; }
    #contextMenu { width:180px !important; }
}
@media (min-width: 640px) {
    #fileList { grid-template-columns: repeat(3,1fr) !important; }
}
@media (min-width: 768px) {
    #fileList { grid-template-columns: repeat(4,1fr) !important; }
}
@media (min-width: 1024px) {
    #fileList { grid-template-columns: repeat(5,1fr) !important; }
}
@media (min-width: 1280px) {
    #fileList { grid-template-columns: repeat(6,1fr) !important; }
}
</style>

@endsection