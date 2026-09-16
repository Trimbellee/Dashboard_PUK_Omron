@extends('layouts.app')
@section('title', 'Laporan Belanja')

@section('content')
@php
    $bulan = $bulan ?? now()->month;
    $tahun = $tahun ?? now()->year;
@endphp

{{-- ── PAGE HEADER ──────────────────────────────────────────── --}}
<div style="display:flex;align-items:center;justify-content:space-between;flex-wrap:wrap;gap:12px;margin-bottom:20px;">
    <div>
        <h1 style="font-size:20px;font-weight:600;color:var(--text-primary);margin:0 0 2px;">Laporan Bulanan — Belanja Di Koperasi</h1>
        <p style="font-size:12.5px;color:var(--text-muted);margin:0;">Riwayat laporan pengeluaran per bulan</p>
    </div>
    @if(in_array(session('user')['role'], ['Sekretaris','Bendahara']))
    <button onclick="openModalInputLaporan()" class="btn btn-primary">＋ Buat Laporan</button>
    @endif
</div>

{{-- ── SEARCH ───────────────────────────────────────────────── --}}
<div style="margin-bottom:16px;position:relative;max-width:320px;">
    <input id="searchInput" type="text" placeholder="🔎 Cari bulan / tahun..."
        class="form-input" style="padding-right:32px;">
    <button id="searchClear"
        style="position:absolute;right:10px;top:50%;transform:translateY(-50%);background:none;border:none;cursor:pointer;font-size:14px;color:var(--text-muted);display:none;">✕</button>
</div>

{{-- ── TABLE ────────────────────────────────────────────────── --}}
<div style="background:var(--bg-surface);border:1px solid var(--border);border-radius:14px;overflow:hidden;box-shadow:var(--shadow-sm);">
    <table style="width:100%;border-collapse:collapse;font-size:13.5px;">
        <thead>
            <tr style="background:var(--bg-elevated);border-bottom:1px solid var(--border);">
                <th style="padding:12px 16px;text-align:left;font-size:11px;font-weight:600;letter-spacing:0.05em;text-transform:uppercase;color:var(--text-muted);width:60px;">No</th>
                <th style="padding:12px 16px;text-align:left;font-size:11px;font-weight:600;letter-spacing:0.05em;text-transform:uppercase;color:var(--text-muted);">Bulan</th>
                <th style="padding:12px 16px;text-align:right;font-size:11px;font-weight:600;letter-spacing:0.05em;text-transform:uppercase;color:var(--text-muted);">Total</th>
                <th style="padding:12px 16px;text-align:center;font-size:11px;font-weight:600;letter-spacing:0.05em;text-transform:uppercase;color:var(--text-muted);width:120px;">Aksi</th>
            </tr>
        </thead>
        <tbody id="tableBody"></tbody>
    </table>
</div>

<div style="display:flex;flex-wrap:wrap;align-items:center;justify-content:space-between;gap:12px;margin-top:16px;padding:0 4px;">
    <div id="pageInfo" style="font-size:12.5px;color:var(--text-muted);"></div>
    <div id="pagination" style="display:flex;flex-wrap:wrap;gap:4px;"></div>
</div>

{{-- ── MODAL BUAT LAPORAN ───────────────────────────────────── --}}
<div id="modalLaporanBelanja" class="modal-backdrop">
    <div class="modal-box" style="max-width:380px;" onclick="event.stopPropagation()">
        <div class="modal-header">
            <h2 class="modal-title">Buat Laporan Belanja</h2>
            <button class="modal-close" onclick="closeModalInputLaporan()">✕</button>
        </div>
        <form id="formLaporanBelanja">
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
                <input type="number" name="tahun" class="form-input" value="{{ $tahun }}">
            </div>
            <div class="form-actions">
                <button type="button" onclick="closeModalInputLaporan()" class="btn btn-ghost">Batal</button>
                <button type="submit" class="btn btn-primary" id="btnSubmitLaporan">
                    <span id="btnSubmitText">Input Laporan</span>
                    <div id="btnSubmitSpinner" class="spinner" style="display:none;"></div>
                </button>
            </div>
        </form>
    </div>
</div>

<style>
/* Table rows */
#tableBody tr { border-bottom:1px solid var(--border);transition:background 0.1s; }
#tableBody tr:last-child { border-bottom:none; }
#tableBody tr:hover td { background:var(--bg-elevated); }
#tableBody td { padding:13px 16px;color:var(--text-secondary); }

/* Pagination */
#pagination button { display:inline-flex;align-items:center;justify-content:center;padding:5px 10px;border-radius:8px;border:1px solid var(--border);background:transparent;color:var(--text-secondary);font-size:13px;cursor:pointer;transition:all 0.15s;font-family:inherit; }
#pagination button:hover { background:var(--bg-elevated);color:var(--text-primary); }
#pagination button.active { background:var(--accent);color:#fff;border-color:var(--accent); }

/* nav-link spinner */
.lb-nav-btn { display:inline-flex;align-items:center;gap:6px;padding:6px 14px;border-radius:8px;font-size:12.5px;font-weight:500;color:#fff;text-decoration:none;transition:all 0.15s;background:var(--accent); }
.lb-nav-btn:hover { filter:brightness(1.08); }
.lb-nav-btn.loading { opacity:0.7;pointer-events:none; }

mark { background:#FEF08A;border-radius:2px;padding:0 1px; }
</style>

<script>
const laporanData = @json($laporanList ?? []);

/* ── MODAL ───────────────────────────────── */
function openModalInputLaporan() {
    document.getElementById('modalLaporanBelanja').classList.add('open');
    document.body.style.overflow = 'hidden';
}
function closeModalInputLaporan() {
    document.getElementById('modalLaporanBelanja').classList.remove('open');
    document.body.style.overflow = '';
}
document.getElementById('modalLaporanBelanja')?.addEventListener('click', e => {
    if (e.target === document.getElementById('modalLaporanBelanja')) closeModalInputLaporan();
});

document.getElementById('formLaporanBelanja').addEventListener('submit', function(e) {
    e.preventDefault();
    const btn = document.getElementById('btnSubmitLaporan');
    const txt = document.getElementById('btnSubmitText');
    const spn = document.getElementById('btnSubmitSpinner');
    btn.disabled=true; txt.textContent='Memproses…'; spn.style.display='block';
    const fd = new FormData(this);
    closeModalInputLaporan();
    window.location.href = `{{ route('belanja-koperasi.index') }}?bulan=${fd.get('bulan')}&tahun=${fd.get('tahun')}`;
});

/* ── SEARCH ENGINE ───────────────────────── */
const namaBulan = ['Jan','Feb','Mar','Apr','Mei','Jun','Jul','Agu','Sep','Okt','Nov','Des'];
const formatBulan = (b, t) => `${namaBulan[b-1]} ${t}`;
function normalize(text) {
    return text.toLowerCase()
        .replace('februari','feb').replace('oktober','okt').replace('desember','des');
}
function highlight(text, kw) {
    if (!kw) return text;
    return text.replace(new RegExp(`(${kw})`,'gi'),'<mark>$1</mark>');
}

let masterData  = [...laporanData].reverse();
let dataList    = [...masterData];
let currentPage = 1;
const perPage   = 12;
let keywordCache = '';

const searchInput = document.getElementById('searchInput');
const searchClear = document.getElementById('searchClear');

let searchTimer = null;
searchInput.addEventListener('input', function() {
    clearTimeout(searchTimer);
    searchTimer = setTimeout(() => applySearch(this.value), 120);
});
searchClear.onclick = () => { searchInput.value=''; applySearch(''); searchInput.focus(); };
document.addEventListener('keydown', e => {
    if (e.key === '/' && document.activeElement.tagName !== 'INPUT') { e.preventDefault(); searchInput.focus(); }
    if (e.key === 'Escape' && (document.activeElement===searchInput || searchInput.value)) {
        searchInput.value=''; applySearch(''); searchInput.blur();
    }
});

function applySearch(keyword) {
    keyword = normalize(keyword.trim());
    keywordCache = keyword;
    dataList = !keyword ? [...masterData] : masterData.filter(row => {
        const b = formatBulan(row.bulan, row.tahun);
        return normalize(b).includes(keyword) || String(row.bulan).includes(keyword) || String(row.tahun).includes(keyword);
    });
    currentPage = 1;
    renderTable(); renderPagination();
    searchClear.style.display = keyword ? 'block' : 'none';
}

/* ── RENDER TABLE ────────────────────────── */
const tbody = document.getElementById('tableBody');

function renderTable() {
    tbody.innerHTML = '';
    if (!dataList.length) {
        tbody.innerHTML = `<tr><td colspan="4" style="text-align:center;padding:40px;color:var(--text-muted);">${keywordCache ? 'Tidak ada data yang cocok' : 'Belum ada data'}</td></tr>`;
        document.getElementById('pageInfo').textContent = '';
        return;
    }
    const start = (currentPage-1)*perPage;
    const end   = start + perPage;
    const pData = dataList.slice(start, end);
    const frag  = document.createDocumentFragment();

    pData.forEach((row, i) => {
        const bulanText = formatBulan(row.bulan, row.tahun);
        const tr = document.createElement('tr');
        tr.innerHTML = `
            <td style="color:var(--text-muted);">${start+i+1}</td>
            <td style="font-weight:500;color:var(--text-primary);">${highlight(bulanText, keywordCache)}</td>
            <td style="text-align:right;font-weight:600;color:var(--text-primary);">Rp${Number(row.total).toLocaleString('id-ID')}</td>
            <td style="text-align:center;">
                <a class="lb-nav-btn" href="{{ route('belanja-koperasi.index') }}?bulan=${row.bulan}&tahun=${row.tahun}">
                    <span class="lb-spin" style="display:none;width:13px;height:13px;border:2px solid rgba(255,255,255,0.3);border-top-color:#fff;border-radius:50%;animation:spin 0.7s linear infinite;"></span>
                    {{ in_array(session('user')['role'], ['Sekretaris','Bendahara']) ? 'Edit' : 'Lihat' }}
                </a>
            </td>`;
        frag.appendChild(tr);
    });
    tbody.appendChild(frag);

    const total = dataList.length;
    document.getElementById('pageInfo').textContent =
        `Menampilkan ${start+1}–${Math.min(end,total)} dari ${total} data`;
}

/* Nav spinner */
document.addEventListener('click', e => {
    const btn = e.target.closest('.lb-nav-btn');
    if (!btn || btn.classList.contains('loading')) return;
    btn.classList.add('loading');
    const spn = btn.querySelector('.lb-spin');
    if (spn) spn.style.display = 'inline-block';
    setTimeout(() => { btn.classList.remove('loading'); if (spn) spn.style.display='none'; }, 5000);
});

/* ── PAGINATION ──────────────────────────── */
function renderPagination() {
    const pager = document.getElementById('pagination');
    pager.innerHTML = '';
    const totalPage = Math.ceil(dataList.length / perPage);
    if (totalPage <= 1) return;
    const frag = document.createDocumentFragment();
    const btn = (page, label=page, active=false) => {
        const b = document.createElement('button');
        b.innerHTML = String(label);
        b.onclick = () => goPage(page);
        if (active) b.classList.add('active');
        frag.appendChild(b);
    };
    const dots = () => { const s = document.createElement('span'); s.textContent='…'; s.style.cssText='padding:0 4px;color:var(--text-muted);'; frag.appendChild(s); };
    btn(currentPage-1,'◀');
    let start = Math.max(1,currentPage-2), end = Math.min(totalPage,currentPage+2);
    if (start>1) { btn(1); if (start>2) dots(); }
    for (let i=start;i<=end;i++) btn(i,i,i===currentPage);
    if (end<totalPage) { if (end<totalPage-1) dots(); btn(totalPage); }
    btn(currentPage+1,'▶');
    pager.appendChild(frag);
}

function goPage(page) {
    const totalPage = Math.ceil(dataList.length/perPage);
    if (page<1||page>totalPage) return;
    currentPage = page;
    renderTable(); renderPagination();
    window.scrollTo({top:0,behavior:'smooth'});
}

/* init */
renderTable();
renderPagination();
</script>

@endsection