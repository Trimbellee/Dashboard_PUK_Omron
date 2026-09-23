{{-- views/anggota/index.blade.php --}}
@extends('layouts.app')

@section('title', 'Database Anggota')

@section('content')

<style>
/* ── STYLING MODERN ─────────────────────────────────────────────── */
:root {
    --table-radius: 14px;
    --table-shadow: 0 1px 3px rgba(0,0,0,0.05), 0 8px 32px rgba(0,0,0,0.04);
    --table-shadow-hover: 0 4px 24px rgba(0,0,0,0.08);
    --transition: all 0.2s cubic-bezier(0.4, 0, 0.2, 1);
}

.page-header {
    display: flex;
    flex-direction: column;
    gap: 16px;
}

.page-header-top {
    display: flex;
    flex-wrap: wrap;
    align-items: flex-start;
    justify-content: space-between;
    gap: 16px;
}

.page-title {
    font-size: 1.25rem;
    font-weight: 700;
    color: var(--text-primary);
    margin: 0 0 4px;
    display: flex;
    align-items: center;
    gap: 10px;
}

.page-title .badge {
    font-size: 0.7rem;
    font-weight: 500;
    padding: 2px 12px;
    border-radius: 20px;
    background: var(--accent-muted);
    color: var(--accent-text);
}

.page-subtitle {
    font-size: 0.875rem;
    color: var(--text-muted);
    margin: 0;
}

/* ── Action Bar ─────────────────────────────────────────────────── */
.action-bar {
    display: flex;
    flex-wrap: wrap;
    align-items: center;
    gap: 10px;
}

.action-bar .btn-group {
    display: flex;
    gap: 6px;
    flex-wrap: wrap;
}

.btn-icon {
    display: inline-flex;
    align-items: center;
    gap: 6px;
    padding: 8px 16px;
    border-radius: 10px;
    font-size: 0.875rem;
    font-weight: 500;
    text-decoration: none;
    border: none;
    cursor: pointer;
    transition: var(--transition);
    font-family: inherit;
}

.btn-icon svg {
    width: 16px;
    height: 16px;
    flex-shrink: 0;
}

.btn-primary-modern {
    background: var(--accent);
    color: #fff;
}

.btn-primary-modern:hover {
    background: #0b32b1;
    transform: translateY(-1px);
    box-shadow: 0 4px 16px rgba(2, 14, 255, 0.3);
}

.btn-success-modern {
    background: #16A34A;
    color: #fff;
}

.btn-success-modern:hover {
    background: #15803D;
    transform: translateY(-1px);
    box-shadow: 0 4px 16px rgba(22, 163, 74, 0.3);
}

.btn-teal-modern {
    background: #059669;
    color: #fff;
}

.btn-teal-modern:hover {
    background: #047857;
    transform: translateY(-1px);
    box-shadow: 0 4px 16px rgba(5, 150, 105, 0.3);
}

.btn-ghost-modern {
    background: transparent;
    color: var(--text-secondary);
    border: 1px solid var(--border);
}

.btn-ghost-modern:hover {
    background: var(--bg-elevated);
    border-color: var(--text-secondary);
}

/* ── Search ────────────────────────────────────────────────────── */
.search-wrapper {
    position: relative;
    width: 280px;
}

.search-wrapper .search-input {
    width: 100%;
    padding: 8px 16px 8px 40px;
    border-radius: 10px;
    border: 1px solid var(--border);
    background: var(--bg-surface);
    color: var(--text-primary);
    font-family: inherit;
    font-size: 0.875rem;
    transition: var(--transition);
    outline: none;
}

.search-wrapper .search-input:focus {
    border-color: var(--accent);
    box-shadow: 0 0 0 3px rgba(var(--accent-rgb), 0.1);
}

.search-wrapper .search-input::placeholder {
    color: var(--text-muted);
}

.search-wrapper .search-icon {
    position: absolute;
    left: 12px;
    top: 50%;
    transform: translateY(-50%);
    color: var(--text-muted);
    pointer-events: none;
    width: 18px;
    height: 18px;
}

.search-wrapper .search-loading {
    position: absolute;
    right: 12px;
    top: 50%;
    transform: translateY(-50%);
    display: none;
}

/* ── Alert ─────────────────────────────────────────────────────── */
.alert-modern {
    border-radius: 12px;
    padding: 14px 18px;
    font-size: 0.875rem;
    display: flex;
    align-items: center;
    gap: 12px;
    animation: slideDown 0.3s ease both;
}

.alert-success {
    background: rgba(34, 197, 94, 0.08);
    border: 1px solid rgba(34, 197, 94, 0.2);
    color: #16A34A;
}

.alert-success .alert-icon {
    width: 20px;
    height: 20px;
    flex-shrink: 0;
    color: #16A34A;
}

/* ── Table ─────────────────────────────────────────────────────── */
.table-modern {
    background: var(--bg-surface);
    border: 1px solid var(--border);
    border-radius: var(--table-radius);
    overflow: hidden;
    box-shadow: var(--table-shadow);
    transition: var(--transition);
}

.table-modern:hover {
    box-shadow: var(--table-shadow-hover);
}

.table-modern .table-scroll {
    overflow: auto;
    max-height: 75vh;
}

.table-modern table {
    width: 100%;
    font-size: 0.875rem;
    border-collapse: collapse;
}

.table-modern thead {
    position: sticky;
    top: 0;
    z-index: 20;
}

.table-modern thead tr {
    background: var(--bg-elevated);
    border-bottom: 1px solid var(--border);
}

.table-modern thead th {
    padding: 14px 20px;
    font-weight: 600;
    font-size: 0.75rem;
    text-transform: uppercase;
    letter-spacing: 0.05em;
    color: var(--text-muted);
    text-align: left;
    white-space: nowrap;
}

.table-modern thead th .sort-btn {
    display: inline-flex;
    align-items: center;
    gap: 4px;
    background: none;
    border: none;
    cursor: pointer;
    font-weight: 600;
    font-size: inherit;
    color: var(--text-muted);
    padding: 0;
    font-family: inherit;
    transition: var(--transition);
}

.table-modern thead th .sort-btn:hover {
    color: var(--text-primary);
}

.table-modern thead th .sort-btn .sort-icon {
    font-size: 10px;
    opacity: 0.5;
}

.table-modern thead th .sort-btn .sort-icon.active {
    opacity: 1;
    color: var(--accent);
}

.table-modern tbody tr {
    border-bottom: 1px solid var(--border);
    transition: var(--transition);
}

.table-modern tbody tr:last-child {
    border-bottom: none;
}

.table-modern tbody tr:hover {
    background: var(--bg-elevated);
}

.table-modern tbody td {
    padding: 14px 20px;
    vertical-align: middle;
}

.table-modern .cell-nik {
    font-weight: 500;
    font-family: 'DM Mono', 'Courier New', monospace;
    font-size: 0.8rem;
    color: var(--text-primary);
}

.table-modern .cell-name {
    font-weight: 600;
    color: var(--text-primary);
}

.table-modern .cell-name .sub {
    font-size: 0.75rem;
    font-weight: 400;
    color: var(--text-muted);
    margin-top: 2px;
}

.table-modern .cell-dept {
    color: var(--text-secondary);
}

.table-modern .cell-position {
    color: var(--text-secondary);
}

.table-modern .cell-status {
    display: inline-flex;
    align-items: center;
    gap: 6px;
    padding: 3px 12px;
    border-radius: 20px;
    font-size: 0.75rem;
    font-weight: 500;
}

.table-modern .status-active {
    background: rgba(34, 197, 94, 0.12);
    color: #16A34A;
}

.table-modern .status-inactive {
    background: rgba(239, 68, 68, 0.1);
    color: #DC2626;
}

.table-modern .status-permanen {
    background: rgba(59, 130, 246, 0.1);
    color: #2563EB;
}

.table-modern .status-kontrak {
    background: rgba(245, 158, 11, 0.1);
    color: #D97706;
}

/* ── Action Buttons ───────────────────────────────────────────── */
.action-buttons {
    display: flex;
    align-items: center;
    justify-content: flex-end;
    gap: 6px;
}

.btn-edit {
    padding: 5px 14px;
    border-radius: 7px;
    font-size: 0.75rem;
    font-weight: 500;
    background: rgba(234, 179, 8, 0.12);
    color: #92400E;
    text-decoration: none;
    transition: var(--transition);
    border: none;
    cursor: pointer;
    font-family: inherit;
}

.btn-edit:hover {
    background: rgba(234, 179, 8, 0.22);
    transform: translateY(-1px);
}

.btn-delete {
    padding: 5px 14px;
    border-radius: 7px;
    font-size: 0.75rem;
    font-weight: 500;
    background: rgba(239, 68, 68, 0.1);
    color: #B91C1C;
    border: none;
    cursor: pointer;
    transition: var(--transition);
    font-family: inherit;
}

.btn-delete:hover {
    background: rgba(239, 68, 68, 0.2);
    transform: translateY(-1px);
}

/* ── Empty State ──────────────────────────────────────────────── */
.empty-state {
    padding: 60px 24px;
    text-align: center;
    color: var(--text-muted);
}

.empty-state .empty-icon {
    font-size: 48px;
    margin-bottom: 12px;
    opacity: 0.6;
}

.empty-state .empty-title {
    font-size: 1rem;
    font-weight: 600;
    color: var(--text-secondary);
    margin-bottom: 4px;
}

/* ── Pagination & Per Page ───────────────────────────────────── */
.table-footer {
    display: flex;
    align-items: center;
    justify-content: space-between;
    flex-wrap: wrap;
    gap: 12px;
    padding: 14px 20px;
    border-top: 1px solid var(--border);
}

.per-page-control {
    display: flex;
    align-items: center;
    gap: 8px;
    font-size: 0.875rem;
    color: var(--text-secondary);
}

.per-page-control select {
    padding: 4px 28px 4px 12px;
    border-radius: 8px;
    border: 1px solid var(--border);
    background: var(--bg-surface);
    color: var(--text-primary);
    font-size: 0.8rem;
    cursor: pointer;
    font-family: inherit;
    appearance: none;
    background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='12' height='12' viewBox='0 0 24 24' fill='none' stroke='%239CA3AF' stroke-width='2'%3E%3Cpolyline points='6 9 12 15 18 9'/%3E%3C/svg%3E");
    background-repeat: no-repeat;
    background-position: right 8px center;
    background-size: 12px;
    transition: var(--transition);
}

.per-page-control select:focus {
    border-color: var(--accent);
    outline: none;
    box-shadow: 0 0 0 3px rgba(var(--accent-rgb), 0.1);
}

/* ── Pagination Links ─────────────────────────────────────────── */
.pagination-modern {
    display: flex;
    gap: 4px;
    align-items: center;
}

.pagination-modern .page-link {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    min-width: 34px;
    height: 34px;
    padding: 0 10px;
    border-radius: 8px;
    border: 1px solid transparent;
    background: transparent;
    color: var(--text-secondary);
    font-size: 0.875rem;
    font-weight: 500;
    text-decoration: none;
    transition: var(--transition);
    cursor: pointer;
    font-family: inherit;
}

.pagination-modern .page-link:hover:not(.active):not(.disabled) {
    background: var(--bg-elevated);
    border-color: var(--border);
}

.pagination-modern .page-link.active {
    background: var(--accent);
    color: #fff;
    border-color: var(--accent);
}

.pagination-modern .page-link.disabled {
    opacity: 0.4;
    cursor: not-allowed;
}

/* ── Animations ────────────────────────────────────────────────── */
@keyframes slideDown {
    from {
        opacity: 0;
        transform: translateY(-12px);
    }
    to {
        opacity: 1;
        transform: translateY(0);
    }
}

@keyframes fadeIn {
    from { opacity: 0; }
    to { opacity: 1; }
}

.fade-in {
    animation: fadeIn 0.3s ease both;
}

/* ── Responsive ────────────────────────────────────────────────── */
@media (max-width: 1024px) {
    .search-wrapper {
        width: 220px;
    }
}

@media (max-width: 768px) {
    .page-header-top {
        flex-direction: column;
        align-items: stretch;
    }

    .action-bar {
        flex-direction: column;
        align-items: stretch;
    }

    .action-bar .btn-group {
        width: 100%;
    }

    .action-bar .btn-group .btn-icon {
        flex: 1;
        justify-content: center;
        font-size: 0.8rem;
        padding: 7px 12px;
    }

    .search-wrapper {
        width: 100%;
    }

    .table-modern .table-scroll {
        max-height: 60vh;
    }

    .table-modern thead th,
    .table-modern tbody td {
        padding: 10px 14px;
        font-size: 0.8rem;
    }

    .table-footer {
        flex-direction: column;
        align-items: stretch;
    }

    .per-page-control {
        justify-content: center;
    }

    .pagination-modern {
        justify-content: center;
        flex-wrap: wrap;
    }

    .pagination-modern .page-link {
        min-width: 30px;
        height: 30px;
        font-size: 0.8rem;
        padding: 0 8px;
    }

    .btn-edit,
    .btn-delete {
        font-size: 0.7rem;
        padding: 4px 10px;
    }
}

@media (max-width: 480px) {
    .page-title {
        font-size: 1rem;
    }

    .page-title .badge {
        font-size: 0.6rem;
        padding: 1px 10px;
    }

    .table-modern thead th,
    .table-modern tbody td {
        padding: 8px 10px;
        font-size: 0.75rem;
    }

    .table-modern .cell-nik {
        font-size: 0.7rem;
    }

    .action-buttons {
        flex-direction: column;
        gap: 4px;
    }

    .btn-edit,
    .btn-delete {
        width: 100%;
        text-align: center;
    }
}
</style>

<div class="page-header">

    {{-- ── HEADER ─────────────────────────────────────────────────── --}}
    <div class="page-header-top">
        <div>
            <h1 class="page-title">
                👥 Database Anggota
                <span class="badge" id="anggotaTotal">
                    Total {{ $anggota->total() }} anggota
                </span>
            </h1>
            <p class="page-subtitle">
                Kelola data anggota secara terpusat
            </p>
        </div>

        {{-- BUTTONS --}}
        <div class=" btn-group">
                <a href="{{ route('anggota.create') }}" class="btn-icon btn-primary-modern">
                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 4v16m8-8H4"/>
                    </svg>
                    Tambah Anggota
                </a>

                <a href="{{ route('anggota.import.form') }}" class="btn-icon btn-success-modern">
                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-8l-4-4m0 0L8 8m4-4v12"/>
                    </svg>
                    Import Excel
                </a>

                <a href="{{ route('anggota.export') }}" class="btn-icon btn-teal-modern">
                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 10v6m0 0l-3-3m3 3l3-3m3 4H6m12 0a3 3 0 01-3 3H9a3 3 0 01-3-3"/>
                    </svg>
                    Export Excel
                </a>
            </div>

        <div class="action-bar">
            {{-- SEARCH --}}
            <div class="search-wrapper">
                <svg class="search-icon" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M21 21l-4.35-4.35m1.85-5.15a7 7 0 11-14 0a7 7 0 0114 0z"/>
                </svg>
                <input id="searchInput" type="text" name="search"
                    value="{{ request('search') }}"
                    placeholder="Cari NIK / Nama / Dept / Jabatan"
                    class="search-input"
                    autocomplete="off">
                <div id="searchLoading" class="search-loading">
                    <svg style="width:18px;height:18px;animation:spin 0.7s linear infinite;color:var(--accent);" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                        <circle style="opacity:.25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                        <path style="opacity:.75" fill="currentColor" d="M4 12a8 8 0 018-8v8z"></path>
                    </svg>
                </div>
            </div>
           
            <div>
                <a href="{{ route('anggota.dashboard.index') }}" class="btn-icon btn-ghost-modern">
                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M9 17V7m0 10a2 2 0 01-2 2H5a2 2 0 01-2-2V7a2 2 0 012-2h2a2 2 0 012 2m0 10a2 2 0 002 2h2a2 2 0 002-2M9 7a2 2 0 012-2h2a2 2 0 012 2m0 10V7m0 10a2 2 0 002 2h2a2 2 0 002-2V7a2 2 0 00-2-2h-2a2 2 0 00-2 2"/>
                    </svg>
                    Lihat grafik anggota
                </a>
            </div>
            <div>
                <a href="{{ route('anggota.rfid-pairing') }}" class="btn-icon btn-ghost-modern">
                    <svg xmlns="http://www.w3.org/2000/svg"
                        width="18"
                        height="18"
                        fill="none"
                        viewBox="0 0 24 24"
                        stroke="currentColor"
                        stroke-width="2">
                        <rect x="3" y="5" width="18" height="14" rx="2"></rect>
                        <path stroke-linecap="round" stroke-linejoin="round" d="M8 9a3 3 0 0 1 0 6"></path>
                        <path stroke-linecap="round" stroke-linejoin="round" d="M11 7a6 6 0 0 1 0 10"></path>
                        <path stroke-linecap="round" stroke-linejoin="round" d="M14 5a9 9 0 0 1 0 14"></path>
                    </svg>
                    Input NFC
                </a>
            </div>
        </div>
    </div>

    {{-- ── ALERT ─────────────────────────────────────────────────── --}}
    @if(session('success'))
        <div id="successAlert" class="alert-modern alert-success">
            <svg class="alert-icon" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                <path stroke-linecap="round" stroke-linejoin="round" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
            </svg>
            <span>{{ session('success') }}</span>
        </div>
    @endif

    {{-- ── TABLE ─────────────────────────────────────────────────── --}}
    <div id="anggotaTable" class="fade-in">
        @include('anggota._table')
    </div>

</div>

{{-- ── SCRIPTS ─────────────────────────────────────────────────── --}}
<script>
let controller;

async function loadData(url) {
    const table   = document.getElementById('anggotaTable');
    const loading = document.getElementById('searchLoading');
    const icon    = document.querySelector('.search-wrapper .search-icon');

    if (controller) controller.abort();
    controller = new AbortController();

    loading.style.display = 'block';
    if (icon) icon.style.display = 'none';

    try {
        const response = await fetch(url, {
            headers: { 'X-Requested-With': 'XMLHttpRequest' },
            signal: controller.signal
        });
        const html = await response.text();
        table.innerHTML = html;
        updateTotal();
        // Re-trigger fade animation
        table.classList.remove('fade-in');
        void table.offsetWidth;
        table.classList.add('fade-in');
    } catch(error) {
        if (error.name !== 'AbortError') console.error(error);
    } finally {
        loading.style.display = 'none';
        if (icon) icon.style.display = '';
    }
}

document.addEventListener('DOMContentLoaded', () => {
    const input = document.getElementById('searchInput');
    let timer;
    input.addEventListener('input', function () {
        clearTimeout(timer);
        timer = setTimeout(() => {
            const url = `{{ route('anggota.index') }}?search=${encodeURIComponent(this.value)}`;
            window.history.pushState({}, '', url);
            loadData(url);
        }, 300);
    });
});

function updateTotal() {
    const meta = document.getElementById('ajaxMeta');
    if (!meta) return;
    const badge = document.getElementById('anggotaTotal');
    if (badge) {
        badge.textContent = `Total ${meta.dataset.total} anggota`;
    }
}
</script>

<script>
document.addEventListener('click', function(e) {
    const btn = e.target.closest('.sort-btn');
    if (!btn) return;
    const meta       = document.getElementById('ajaxMeta');
    const newSort    = btn.dataset.sort;
    const currentSort = meta.dataset.sort;
    const currentDir  = meta.dataset.dir;
    let newDir = 'asc';
    if (currentSort === newSort) newDir = currentDir === 'asc' ? 'desc' : 'asc';
    const params = new URLSearchParams(window.location.search);
    params.set('sort', newSort);
    params.set('dir', newDir);
    const url = `{{ route('anggota.index') }}?${params.toString()}`;
    window.history.pushState({}, '', url);
    loadData(url);
});
</script>

<script>
document.addEventListener('click', async function(e) {
    const link = e.target.closest('#anggotaTable .pagination-modern a, #anggotaTable nav a');
    if (!link) return;
    e.preventDefault();
    try {
        const response = await fetch(link.href, {
            headers: { 'X-Requested-With': 'XMLHttpRequest' }
        });
        const html = await response.text();
        document.getElementById('anggotaTable').innerHTML = html;
        updateTotal();
        window.history.pushState({}, '', link.href);
    } catch(error) { console.error(error); }
});
</script>

<script>
window.addEventListener('popstate', function () { loadData(window.location.href); });
</script>

<script>
document.addEventListener('change', function(e) {
    if (e.target.id !== 'perPageSelect') return;
    const params = new URLSearchParams(window.location.search);
    params.set('per_page', e.target.value);
    params.delete('page');
    const url = `{{ route('anggota.index') }}?${params.toString()}`;
    window.history.pushState({}, '', url);
    loadData(url);
});
</script>

<script>
document.addEventListener('DOMContentLoaded', function () {
    const alert = document.getElementById('successAlert');

    if (alert) {
        setTimeout(() => {
            alert.style.opacity = '0';
            alert.style.transition = 'opacity 0.5s ease';
            setTimeout(() => {
                alert.remove();
            }, 500);
        }, 3000);
    }
});

// Add spin animation keyframes
const style = document.createElement('style');
style.textContent = `
    @keyframes spin {
        to { transform: rotate(360deg); }
    }
`;
document.head.appendChild(style);
</script>

{{-- ── MODAL KONFIRMASI HAPUS ──────────────────────────────────── --}}
<div id="deleteModal"
     style="display:none;position:fixed;inset:0;z-index:9999;
            background:rgba(0,0,0,0.5);backdrop-filter:blur(4px);
            align-items:center;justify-content:center;padding:20px;">

    <div style="background:var(--bg-surface);border:1px solid var(--border);border-radius:20px;
                box-shadow:0 24px 64px rgba(0,0,0,0.2);width:100%;max-width:420px;
                animation:fadeUp 0.2s ease both;">

        <div style="padding:32px 28px 0;text-align:center;">
            <div style="width:56px;height:56px;border-radius:16px;
                        background:rgba(239,68,68,0.08);
                        display:flex;align-items:center;justify-content:center;
                        margin:0 auto 16px;">
                <svg style="width:26px;height:26px;color:#DC2626;" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round"
                          d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/>
                </svg>
            </div>
            <div style="font-size:16px;font-weight:600;color:var(--text-primary);margin-bottom:8px;">
                Hapus Anggota
            </div>
            <div style="font-size:13px;color:var(--text-muted);line-height:1.6;">
                Yakin ingin menghapus <strong id="deleteNama" style="color:var(--text-primary);"></strong> secara permanen?
                <br>Data yang dihapus <strong style="color:#DC2626;">tidak dapat dikembalikan</strong>.
            </div>
        </div>

        <div style="display:flex;gap:10px;padding:24px 28px 28px;">
            <button onclick="closeDeleteModal()"
                    style="flex:1;padding:10px;border-radius:10px;border:1px solid var(--border);
                           background:transparent;color:var(--text-secondary);font-size:13px;
                           cursor:pointer;font-family:inherit;transition:var(--transition);"
                    onmouseover="this.style.background='var(--bg-elevated)'"
                    onmouseout="this.style.background='transparent'">
                Batal
            </button>
            <button id="deleteConfirmBtn" onclick="doHapus()"
                    style="flex:1;padding:10px;border-radius:10px;border:none;
                           background:#DC2626;color:#fff;font-size:13px;font-weight:600;
                           cursor:pointer;font-family:inherit;transition:var(--transition);
                           display:flex;align-items:center;justify-content:center;gap:6px;"
                    onmouseover="this.style.background='#B91C1C'"
                    onmouseout="this.style.background='#DC2626'">
                <svg style="width:14px;height:14px;" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                    <path stroke-linecap="round" stroke-linejoin="round"
                          d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6"/>
                </svg>
                Hapus Permanen
            </button>
        </div>

    </div>
</div>

{{-- ── TOAST ────────────────────────────────────────────────────── --}}
<div id="deleteToast"
     style="display:none;position:fixed;bottom:24px;right:24px;z-index:99999;
            background:#16A34A;color:#fff;padding:14px 20px;border-radius:12px;
            font-size:13px;font-weight:500;box-shadow:0 8px 32px rgba(0,0,0,0.2);
            align-items:center;gap:10px;
            animation:slideUp 0.3s ease both;max-width:380px;">
    <svg style="width:18px;height:18px;flex-shrink:0;" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
        <path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"/>
    </svg>
    <span id="deleteToastMsg"></span>
</div>

<script>
let _deleteUrl  = null;
let _deleteTimer = null;

function confirmHapus(url, nama) {
    _deleteUrl = url;
    document.getElementById('deleteNama').textContent = nama;
    const modal = document.getElementById('deleteModal');
    modal.style.display = 'flex';
    document.body.style.overflow = 'hidden';
}

function closeDeleteModal() {
    document.getElementById('deleteModal').style.display = 'none';
    document.body.style.overflow = '';
    _deleteUrl = null;
}

document.getElementById('deleteModal').addEventListener('click', function(e) {
    if (e.target === this) closeDeleteModal();
});

document.addEventListener('keydown', e => {
    if (e.key === 'Escape' && document.getElementById('deleteModal').style.display === 'flex') {
        closeDeleteModal();
    }
});

async function doHapus() {
    if (!_deleteUrl) return;

    const btn = document.getElementById('deleteConfirmBtn');
    btn.disabled = true;
    btn.style.opacity = '0.6';
    btn.innerHTML = `
        <svg style="width:16px;height:16px;animation:spin 0.7s linear infinite;" fill="none" viewBox="0 0 24 24">
            <circle style="opacity:.25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
            <path style="opacity:.75" fill="currentColor" d="M4 12a8 8 0 018-8v8z"></path>
        </svg>
        Menghapus...
    `;

    try {
        const res = await fetch(_deleteUrl, {
            method: 'POST',
            headers: {
                'X-CSRF-TOKEN': '{{ csrf_token() }}',
                'X-HTTP-Method-Override': 'DELETE',
                'X-Requested-With': 'XMLHttpRequest',
                'Accept': 'application/json',
            }
        });

        if (!res.ok) throw new Error('Gagal menghapus');

        closeDeleteModal();

        const url = window.location.href;
        const response = await fetch(url, {
            headers: { 'X-Requested-With': 'XMLHttpRequest' }
        });
        const html = await response.text();
        document.getElementById('anggotaTable').innerHTML = html;
        updateTotal();

        showDeleteToast('Anggota berhasil dihapus');

    } catch(e) {
        console.error(e);
        showDeleteToast('Gagal menghapus data', true);
    } finally {
        btn.disabled = false;
        btn.style.opacity = '1';
        btn.innerHTML = `
            <svg style="width:14px;height:14px;" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                <path stroke-linecap="round" stroke-linejoin="round" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6"/>
            </svg>
            Hapus Permanen
        `;
    }
}

function showDeleteToast(msg, isError = false) {
    const toast = document.getElementById('deleteToast');
    const msgEl = document.getElementById('deleteToastMsg');
    if (!toast || !msgEl) return;
    
    msgEl.textContent = msg;
    toast.style.background = isError ? '#DC2626' : '#16A34A';
    toast.style.display = 'flex';

    clearTimeout(_deleteTimer);
    _deleteTimer = setTimeout(() => {
        toast.style.opacity = '0';
        toast.style.transition = 'opacity 0.4s ease';
        setTimeout(() => {
            toast.style.display = 'none';
            toast.style.opacity = '1';
            toast.style.transition = '';
        }, 400);
    }, 3000);
}

// Ensure modal and toast are in body
document.addEventListener('DOMContentLoaded', () => {
    ['deleteModal', 'deleteToast'].forEach(id => {
        const el = document.getElementById(id);
        if (el) document.body.appendChild(el);
    });
});
</script>

{{-- Add fadeUp and slideUp keyframes if not exists --}}
<style>
@keyframes fadeUp {
    from { opacity: 0; transform: scale(0.96) translateY(8px); }
    to { opacity: 1; transform: scale(1) translateY(0); }
}

@keyframes slideUp {
    from { opacity: 0; transform: translateY(20px); }
    to { opacity: 1; transform: translateY(0); }
}
</style>

@endsection