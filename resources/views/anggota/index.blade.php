@extends('layouts.app')

@section('title', 'Database Anggota')

@section('content')

<div style="max-width:1280px;margin:0 auto;display:flex;flex-direction:column;gap:24px;">

    {{-- HEADER --}}
    <div style="display:flex;flex-direction:column;gap:16px;">
        <div style="display:flex;flex-wrap:wrap;align-items:flex-start;
                    justify-content:space-between;gap:16px;">

            <div>
                <h1 style="font-size:1.25rem;font-weight:700;color:var(--text-primary);margin:0 0 4px;">
                    Database Anggota
                </h1>
                <p id="anggotaTotal"
                    style="font-size:0.875rem;color:var(--text-secondary);margin:0;">
                    Total {{ $anggota->total() }} anggota
                </p>
            </div>

            <div style="display:flex;flex-wrap:wrap;align-items:center;gap:10px;">

                {{-- SEARCH --}}
                <form id="searchForm" method="GET"
                    action="{{ route('anggota.index') }}"
                    style="position:relative;">
                    <input id="searchInput" type="text" name="search"
                        value="{{ request('search') }}"
                        placeholder="Cari nama / NIK / Dept / Jabatan"
                        class="form-input"
                        style="width:280px;padding-right:36px;">

                    <svg id="searchIcon"
                        xmlns="http://www.w3.org/2000/svg"
                        style="width:16px;height:16px;position:absolute;right:10px;
                               top:50%;transform:translateY(-50%);
                               color:var(--text-muted);pointer-events:none;"
                        fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M21 21l-4.35-4.35m1.85-5.15a7 7 0 11-14 0a7 7 0 0114 0z"/>
                    </svg>

                    <div id="searchLoading"
                        style="display:none;position:absolute;right:10px;
                               top:50%;transform:translateY(-50%);">
                        <svg style="animation:spin .7s linear infinite;width:18px;height:18px;
                                    color:var(--accent);"
                            xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                            <circle style="opacity:.25" cx="12" cy="12" r="10"
                                stroke="currentColor" stroke-width="4"></circle>
                            <path style="opacity:.75" fill="currentColor"
                                d="M4 12a8 8 0 018-8v8z"></path>
                        </svg>
                    </div>
                </form>

                {{-- TOMBOL --}}
                <a href="{{ route('anggota.create') }}" class="btn btn-primary">
                    <svg xmlns="http://www.w3.org/2000/svg" style="width:14px;height:14px;"
                        fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M12 4v16m8-8H4"/>
                    </svg>
                    Tambah Anggota
                </a>

                <a href="{{ route('anggota.import.form') }}"
                    style="display:inline-flex;align-items:center;gap:8px;
                           background:#16A34A;color:#fff;text-decoration:none;
                           font-size:0.875rem;font-weight:500;padding:9px 18px;
                           border-radius:9px;border:none;transition:filter .15s;"
                    onmouseover="this.style.filter='brightness(1.1)'"
                    onmouseout="this.style.filter='none'">
                    Import Excel
                </a>

                <a href="{{ route('anggota.export') }}"
                    style="display:inline-flex;align-items:center;gap:8px;
                           background:#059669;color:#fff;text-decoration:none;
                           font-size:0.875rem;font-weight:500;padding:9px 18px;
                           border-radius:9px;border:none;transition:filter .15s;"
                    onmouseover="this.style.filter='brightness(1.1)'"
                    onmouseout="this.style.filter='none'">
                    Export Excel
                </a>
            </div>
        </div>
    </div>
<div>
    <a href="{{ route('anggota.dashboard.index') }}" class="btn btn-ghost" style="text-decoration:none;gap:6px;font-size:13px;">
            <svg style="width:14px;height:14px;" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M4 6h16M4 10h16M4 14h16M4 18h16"/></svg>
            Lihat Grafik Anggota
    </a>

</div>


    {{-- ALERT --}}
    @if(session('success'))
        <div id="successAlert"
            style="border-radius:12px;
                background:rgba(34,197,94,.1);
                border:1px solid rgba(34,197,94,.25);
                padding:12px 16px;
                font-size:0.875rem;
                color:#16A34A;
                transition:opacity .5s ease;">
            {{ session('success') }}
        </div>
    @endif

    {{-- TABLE --}}
    <div id="anggotaTable">
        @include('anggota._table')
    </div>

</div>

<script>
let controller;

async function loadData(url) {
    const table   = document.getElementById('anggotaTable');
    const loading = document.getElementById('searchLoading');
    const icon    = document.getElementById('searchIcon');

    if (controller) controller.abort();
    controller = new AbortController();

    loading.style.display = 'block';
    icon.style.display    = 'none';

    try {
        const response = await fetch(url, {
            headers: { 'X-Requested-With': 'XMLHttpRequest' },
            signal: controller.signal
        });
        const html = await response.text();
        table.innerHTML = html;
        updateTotal();
    } catch(error) {
        if (error.name !== 'AbortError') console.error(error);
    } finally {
        loading.style.display = 'none';
        icon.style.display    = '';
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
    document.getElementById('anggotaTotal').innerText =
        `Total ${meta.dataset.total} anggota`;
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
    const link = e.target.closest('#anggotaTable .pagination a, #anggotaTable nav a');
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

            setTimeout(() => {
                alert.remove();
            }, 500); // menunggu animasi fade selesai

        }, 3000); 
    }
});
</script>
{{-- ── MODAL KONFIRMASI HAPUS ──────────────────────────────────── --}}
<div id="deleteModal"
     style="display:none;position:fixed;inset:0;z-index:9999;
            background:rgba(0,0,0,0.45);backdrop-filter:blur(3px);
            align-items:center;justify-content:center;padding:20px;">

    <div style="background:var(--bg-surface);border:1px solid var(--border);border-radius:20px;
                box-shadow:0 20px 60px rgba(0,0,0,0.25);width:100%;max-width:400px;
                animation:fadeUp .2s ease both;">

        {{-- Icon --}}
        <div style="padding:28px 28px 0;text-align:center;">
            <div style="width:52px;height:52px;border-radius:14px;
                        background:rgba(239,68,68,.1);
                        display:flex;align-items:center;justify-content:center;
                        margin:0 auto 16px;">
                <svg style="width:24px;height:24px;color:#DC2626;" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round"
                          d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/>
                </svg>
            </div>
            <div style="font-size:15px;font-weight:600;color:var(--text-primary);margin-bottom:8px;">
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
                           cursor:pointer;font-family:inherit;transition:all .15s;"
                    onmouseover="this.style.background='var(--bg-elevated)'"
                    onmouseout="this.style.background='transparent'">
                Batal
            </button>
            <button id="deleteConfirmBtn" onclick="doHapus()"
                    style="flex:1;padding:10px;border-radius:10px;border:none;
                           background:#DC2626;color:#fff;font-size:13px;font-weight:600;
                           cursor:pointer;font-family:inherit;transition:all .15s;
                           display:flex;align-items:center;justify-content:center;gap:6px;"
                    onmouseover="this.style.background='#B91C1C'"
                    onmouseout="this.style.background='#DC2626'">
                <svg style="width:13px;height:13px;" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
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
            background:#16A34A;color:#fff;padding:12px 18px;border-radius:12px;
            font-size:13px;font-weight:500;box-shadow:0 8px 24px rgba(0,0,0,0.2);
            align-items:center;gap:8px;
            animation:fadeUp .25s ease both;max-width:320px;">
    <svg style="width:16px;height:16px;flex-shrink:0;" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
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

// Backdrop click
document.getElementById('deleteModal').addEventListener('click', function(e) {
    if (e.target === this) closeDeleteModal();
});

// ESC
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
    btn.innerHTML = `<svg style="width:14px;height:14px;animation:spin .7s linear infinite;" fill="none" viewBox="0 0 24 24">
        <circle style="opacity:.25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
        <path style="opacity:.75" fill="currentColor" d="M4 12a8 8 0 018-8v8z"></path>
    </svg> Menghapus...`;

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

        // Reload tabel via AJAX
        const url = window.location.href;
        const response = await fetch(url, {
            headers: { 'X-Requested-With': 'XMLHttpRequest' }
        });
        const html = await response.text();
        document.getElementById('anggotaTable').innerHTML = html;
        updateTotal();

        // Tampilkan toast
        showDeleteToast('Anggota berhasil dihapus');

    } catch(e) {
        console.error(e);
        showDeleteToast('Gagal menghapus data', true);
    } finally {
        btn.disabled = false;
        btn.style.opacity = '1';
        btn.innerHTML = `<svg style="width:13px;height:13px;" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
            <path stroke-linecap="round" stroke-linejoin="round" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6"/>
        </svg> Hapus Permanen`;
    }
}

function showDeleteToast(msg, isError = false) {
    const toast = document.getElementById('deleteToast');
    document.getElementById('deleteToastMsg').textContent = msg;
    toast.style.background = isError ? '#DC2626' : '#16A34A';
    toast.style.display = 'flex';

    clearTimeout(_deleteTimer);
    _deleteTimer = setTimeout(() => {
        toast.style.opacity = '0';
        toast.style.transition = 'opacity .4s ease';
        setTimeout(() => {
            toast.style.display = 'none';
            toast.style.opacity = '1';
            toast.style.transition = '';
        }, 400);
    }, 3000);
}
document.addEventListener('DOMContentLoaded', () => {
    ['deleteModal', 'deleteToast'].forEach(id => {
        const el = document.getElementById(id);
        if (el) document.body.appendChild(el);
    });
});
</script>
@endsection