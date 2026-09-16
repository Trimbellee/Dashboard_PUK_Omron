@extends('layouts.app')
@section('title','Laporan Belanja Koperasi')

@section('content')
@php
    use Carbon\Carbon;
    $namaBulan = Carbon::create()->month($bulan)->translatedFormat('F');
@endphp

{{-- ── PAGE HEADER ──────────────────────────────────────────── --}}
<div style="display:flex;align-items:center;justify-content:space-between;flex-wrap:wrap;gap:12px;margin-bottom:20px;">
    <div>
        <button onclick="handleBackButton()"
            class="btn btn-ghost" style="padding:6px 12px;font-size:12.5px;gap:6px;margin-bottom:8px;">
            ← Kembali
        </button>
        <h1 style="font-size:20px;font-weight:600;color:var(--text-primary);margin:0 0 2px;">Belanja Koperasi</h1>
        <p style="font-size:13px;color:var(--text-muted);margin:0;">{{ $namaBulan }} {{ $tahun }}</p>
    </div>

    @if(in_array(session('user')['role'], ['Sekretaris','Bendahara']))
    <div style="display:flex;align-items:center;gap:10px;flex-wrap:wrap;">
        <div style="display:flex;align-items:center;gap:8px;">
            <input type="file" id="excelBelanja" accept=".xlsx,.xls"
                style="font-size:12.5px;color:var(--text-secondary);">
            <button type="button" onclick="importExcelBelanja()" class="btn btn-ghost" style="gap:6px;border-color:rgba(22,163,74,0.4);color:#16A34A;padding:7px 14px;">
                📥 Import Excel
            </button>
        </div>
        <button type="button" id="btnSimpanLaporan" onclick="handleSimpanBelanja()" class="btn btn-primary" style="gap:8px;">
            <div id="spinnerSimpan" class="spinner" style="display:none;"></div>
            <span id="textSimpan">{{ $isEdit ? 'Update Laporan' : 'Simpan Laporan' }}</span>
        </button>
    </div>
    @endif
</div>

{{-- ── TABLE ────────────────────────────────────────────────── --}}
<div style="background:var(--bg-surface);border:1px solid var(--border);border-radius:14px;overflow:hidden;box-shadow:var(--shadow-sm);overflow-x:auto;">
    <table id="tblBelanjaKoperasi" style="width:100%;border-collapse:collapse;font-size:13px;">
        <thead>
            <tr style="background:var(--bg-elevated);border-bottom:1px solid var(--border);">
                <th style="padding:11px 14px;text-align:center;font-size:11px;font-weight:600;letter-spacing:0.05em;text-transform:uppercase;color:var(--text-muted);width:120px;">Tanggal</th>
                <th style="padding:11px 14px;text-align:left;font-size:11px;font-weight:600;letter-spacing:0.05em;text-transform:uppercase;color:var(--text-muted);">Bidang</th>
                <th style="padding:11px 14px;text-align:left;font-size:11px;font-weight:600;letter-spacing:0.05em;text-transform:uppercase;color:var(--text-muted);">Keperluan</th>
                <th style="padding:11px 14px;text-align:right;font-size:11px;font-weight:600;letter-spacing:0.05em;text-transform:uppercase;color:var(--text-muted);">Biaya</th>
                @if(in_array(session('user')['role'], ['Sekretaris','Bendahara']))
                <th style="padding:11px 14px;width:40px;"></th>
                @endif
            </tr>
        </thead>
        <tbody id="bkTbody">
            <tr class="bk-row">
                <td style="padding:4px 8px;border-bottom:1px solid var(--border);">
                    <input type="date" class="bk-tanggal bk-input" autocomplete="off">
                </td>
                <td style="padding:4px 8px;border-bottom:1px solid var(--border);position:relative;">
                    <input type="text" class="bk-bidang bk-input" autocomplete="off">
                </td>
                <td style="padding:4px 8px;border-bottom:1px solid var(--border);">
                    <input type="text" class="bk-keperluan bk-input" autocomplete="off">
                </td>
                <td style="padding:4px 8px;border-bottom:1px solid var(--border);">
                    <input type="text" inputmode="numeric" value="0" class="bk-biaya bk-input" style="text-align:right;" autocomplete="off">
                </td>
                @if(in_array(session('user')['role'], ['Sekretaris','Bendahara']))
                <td style="padding:4px 8px;border-bottom:1px solid var(--border);text-align:center;">
                    <button type="button" onclick="removeBelanjaRow(this)"
                        style="width:22px;height:22px;border-radius:5px;border:none;background:#EF4444;color:#fff;font-size:12px;cursor:pointer;display:flex;align-items:center;justify-content:center;">✕</button>
                </td>
                @endif
            </tr>
        </tbody>
        <tfoot style="position:sticky;bottom:0;background:var(--bg-surface);border-top:1px solid var(--border);">
            <tr>
                <td colspan="3" style="padding:10px 14px;text-align:right;font-size:12.5px;font-weight:600;color:var(--text-secondary);">TOTAL</td>
                <td style="padding:10px 14px;text-align:right;font-size:14px;font-weight:700;color:var(--text-primary);" id="totalBelanjaKoperasi">0</td>
                @if(in_array(session('user')['role'], ['Sekretaris','Bendahara']))
                <td></td>
                @endif
            </tr>
        </tfoot>
    </table>
</div>

@if(in_array(session('user')['role'], ['Sekretaris','Bendahara']))
<button type="button" onclick="addBelanjaRow()"
    class="btn btn-ghost" style="margin-top:10px;padding:6px 14px;font-size:12.5px;gap:6px;">
    ＋ Tambah Baris
</button>
@endif

{{-- Toast wrap --}}
<div id="toastWrap"
    style="position:fixed;top:80px;left:50%;transform:translateX(-50%);display:flex;flex-direction:column;align-items:center;gap:8px;z-index:999999;pointer-events:none;min-width:280px;max-width:380px;"></div>

<style>
/* BK table input */
.bk-input {
    width:100%;border:none;outline:none;background:transparent;
    font-family:inherit;font-size:13px;line-height:1.4;
    color:var(--text-primary);padding:3px 2px;
}
.bk-input:focus { background:var(--accent-muted);border-radius:5px; }
.bk-input:disabled { opacity:1;cursor:not-allowed;color:var(--text-secondary); }

#tblBelanjaKoperasi tbody tr { border-bottom:1px solid var(--border); }
#tblBelanjaKoperasi tbody tr:hover td { background:var(--bg-elevated); }
#tblBelanjaKoperasi td { vertical-align:middle; }

/* Suggest box */
.suggest-box {
    position:absolute;top:100%;left:0;right:0;
    background:var(--bg-surface);border:1px solid var(--border);border-radius:8px;
    box-shadow:var(--shadow-md);z-index:50;max-height:160px;overflow-y:auto;
    font-size:13px;
}
.suggest-item { padding:8px 12px;cursor:pointer;color:var(--text-secondary);transition:background 0.1s; }
.suggest-item:hover { background:var(--bg-elevated);color:var(--text-primary); }

/* Toast */
.bk-toast {
    background:#111827;color:#F9FAFB;padding:11px 16px;border-radius:10px;
    font-size:13px;box-shadow:var(--shadow-lg);pointer-events:auto;
    border:1px solid rgba(255,255,255,0.08);white-space:pre-line;
    animation:toastIn 0.2s ease;
}
.bk-toast-success { background:#166534; }
.bk-toast-error   { background:#7F1D1D; }
.bk-toast-confirm { background:var(--bg-surface);color:var(--text-primary);border:1px solid var(--border-strong); }

@keyframes toastIn { from{opacity:0;transform:translateY(-10px)scale(.95)} to{opacity:1;transform:none} }

tr.bg-error td { background:rgba(239,68,68,0.1); }
</style>

<script>
const LIST_BIDANG = ["Sekretaris","Bendahara","Bidang 1","Bidang 2","Bidang 3","Bidang 4","Bidang 5","Bidang 6","GM","KorLap","Dana Musyawarah","Dana Sosial Politik"];
const IS_EDITOR   = @json(in_array(session('user')['role'], ['Sekretaris','Bendahara']));
const DATA_EXISTING = @json($dataBelanja ?? []);
let MODE_EDIT = {{ $isEdit ? 'true' : 'false' }};
let IS_DIRTY = false, IS_SAVING = false, IS_IMPORTING = false;

/* ── TOAST ───────────────────────────────── */
function showBkToast(msg, type='info') {
    const wrap = document.getElementById('toastWrap');
    const t    = document.createElement('div');
    t.className = `bk-toast ${type==='success'?'bk-toast-success':type==='error'?'bk-toast-error':''}`;
    t.textContent = msg;
    wrap.appendChild(t);
    const normals = [...wrap.children].filter(el => !el.classList.contains('bk-toast-confirm'));
    if (normals.length > 4) normals[0].remove();
    setTimeout(() => { t.style.transition='opacity 0.3s'; t.style.opacity='0'; setTimeout(()=>t.remove(),350); }, 3000);
}

function showConfirmToast(msg, onConfirm, cls='') {
    const wrap = document.getElementById('toastWrap');
    document.querySelectorAll(`.bk-toast-confirm.${cls||'any-confirm'}`).forEach(el=>el.remove());
    const t = document.createElement('div');
    t.className = `bk-toast bk-toast-confirm ${cls}`;
    t.innerHTML = `<div style="font-weight:500;margin-bottom:6px;">${msg}</div>
        <div style="display:flex;justify-content:flex-end;gap:8px;margin-top:8px;">
            <button onclick="this.closest('.bk-toast').remove();IS_IMPORTING=false;" class="btn btn-ghost" style="padding:4px 12px;font-size:12px;">Batal</button>
            <button class="btn btn-primary confirm-ok" style="padding:4px 12px;font-size:12px;">Lanjut</button>
        </div>`;
    t._cb = onConfirm;
    t.querySelector('.confirm-ok').onclick = () => { onConfirm(); t.remove(); };
    wrap.appendChild(t);
}

/* ── FORMAT ──────────────────────────────── */
function parseID(val) { if(!val) return 0; return parseInt(String(val).replace(/[^\d]/g,'')) || 0; }
function formatID(val) { return Math.round(val||0).toString().replace(/\B(?=(\d{3})+(?!\d))/g,'.'); }
function formatInput(el) { el.value = formatID(parseID(el.value)); }

/* ── TOTAL ───────────────────────────────── */
function hitungBelanja() {
    let total = 0;
    document.querySelectorAll('.bk-biaya').forEach(el => total += parseID(el.value));
    document.getElementById('totalBelanjaKoperasi').textContent = formatID(total);
}

/* ── SUGGEST BIDANG ──────────────────────── */
function attachBidangSuggest(input) {
    if (input.dataset.suggestAttached) return;
    input.dataset.suggestAttached = '1';
    const td = input.parentElement;
    td.style.position = 'relative';
    const box = document.createElement('div');
    box.className = 'suggest-box';
    box.style.display = 'none';
    td.appendChild(box);
    input.addEventListener('input', () => {
        const val = input.value.toLowerCase();
        box.innerHTML = '';
        if (!val) { box.style.display='none'; return; }
        const match = LIST_BIDANG.filter(b => b.toLowerCase().includes(val));
        if (!match.length) { box.style.display='none'; return; }
        match.forEach(m => {
            const item = document.createElement('div');
            item.className = 'suggest-item';
            item.textContent = m;
            item.onclick = () => { input.value=m; box.style.display='none'; IS_DIRTY=true; };
            box.appendChild(item);
        });
        box.style.display = 'block';
    });
    box._outsideHandler = e => { if (!box.contains(e.target) && e.target!==input) box.style.display='none'; };
    document.addEventListener('click', box._outsideHandler);
}

/* ── ADD / REMOVE ROW ────────────────────── */
function addBelanjaRow() {
    if (!IS_EDITOR) return;
    const tbody = document.getElementById('bkTbody');
    const tr = document.createElement('tr');
    tr.className = 'bk-row';
    tr.innerHTML = `
        <td style="padding:4px 8px;border-bottom:1px solid var(--border);">
            <input type="date" class="bk-tanggal bk-input" autocomplete="off">
        </td>
        <td style="padding:4px 8px;border-bottom:1px solid var(--border);position:relative;">
            <input type="text" class="bk-bidang bk-input" autocomplete="off">
        </td>
        <td style="padding:4px 8px;border-bottom:1px solid var(--border);">
            <input type="text" class="bk-keperluan bk-input" autocomplete="off">
        </td>
        <td style="padding:4px 8px;border-bottom:1px solid var(--border);">
            <input type="text" inputmode="numeric" value="0" class="bk-biaya bk-input" style="text-align:right;" autocomplete="off">
        </td>
        ${IS_EDITOR ? `<td style="padding:4px 8px;border-bottom:1px solid var(--border);text-align:center;">
            <button type="button" onclick="removeBelanjaRow(this)" style="width:22px;height:22px;border-radius:5px;border:none;background:#EF4444;color:#fff;font-size:12px;cursor:pointer;display:flex;align-items:center;justify-content:center;">✕</button>
        </td>` : ''}`;
    tbody.appendChild(tr);
    attachBidangSuggest(tr.querySelector('.bk-bidang'));
    tr.querySelector('.bk-tanggal')?.focus();
    hitungBelanja(); IS_DIRTY=true;
}

function removeBelanjaRow(btn) {
    if (!IS_EDITOR) return;
    const tbody = document.getElementById('bkTbody');
    if (tbody.querySelectorAll('tr').length <= 1) { showBkToast('Minimal 1 baris harus ada.','error'); return; }
    const tr = btn.closest('tr');
    const box = tr.querySelector('.suggest-box');
    if (box?._outsideHandler) document.removeEventListener('click', box._outsideHandler);
    tr.remove(); hitungBelanja(); IS_DIRTY=true;
}

/* ── COLLECT DATA ────────────────────────── */
function parseLocalDate(s) {
    if (!s) return null;
    const p = s.split('-');
    if (p.length !== 3) return null;
    const d = new Date(+p[0], +p[1]-1, +p[2]);
    return (d.getFullYear()===+p[0] && d.getMonth()===+p[1]-1 && d.getDate()===+p[2]) ? d : null;
}

function getBelanjaData() {
    const rows = [];
    let errTanggal=false, errTanggalKosong=false, errBidang=false, errKeperluan=false, errBiaya=false;
    document.querySelectorAll('#bkTbody tr').forEach(tr => {
        tr.classList.remove('bg-error');
        const tanggal   = tr.querySelector('.bk-tanggal')?.value || '';
        const bidang    = tr.querySelector('.bk-bidang')?.value.trim() || '';
        const keperluan = tr.querySelector('.bk-keperluan')?.value.trim() || '';
        const biaya     = parseID(tr.querySelector('.bk-biaya')?.value||0);
        if (!tanggal && !bidang && !keperluan && biaya===0) return;
        if (!tanggal) { tr.classList.add('bg-error'); errTanggalKosong=true; return; }
        const d = parseLocalDate(tanggal);
        if (!d || d.getMonth()+1 !== {{ $bulan }} || d.getFullYear() !== {{ $tahun }}) { tr.classList.add('bg-error'); errTanggal=true; return; }
        if (!LIST_BIDANG.includes(bidang)) { tr.classList.add('bg-error'); errBidang=true; return; }
        if (!keperluan) { tr.classList.add('bg-error'); errKeperluan=true; return; }
        if (biaya<=0)   { tr.classList.add('bg-error'); errBiaya=true; return; }
        rows.push({ bulan:{{ $bulan }}, tahun:{{ $tahun }}, tanggal, bidang, tujuan:'Koperasi', keperluan, biaya });
    });
    if (errTanggalKosong) { showBkToast('Tanggal wajib diisi.\nCek baris merah.','error'); return null; }
    if (errBidang)        { showBkToast(`Bidang tidak valid. Cek baris merah.\n\nBidang valid:\n${LIST_BIDANG.join(', ')}`,'error'); return null; }
    if (errBiaya)         { showBkToast('Biaya wajib lebih dari 0.\nCek baris merah.','error'); return null; }
    if (errKeperluan)     { showBkToast('Keperluan wajib diisi.\nCek baris merah.','error'); return null; }
    if (errTanggal)       { showBkToast(`Tanggal tidak sesuai periode laporan.\nSemua tanggal harus di bulan {{ $namaBulan }} {{ $tahun }}.`,'error'); return null; }
    return rows;
}

/* ── SIMPAN ──────────────────────────────── */
async function handleSimpanBelanja() {
    if (!IS_EDITOR || IS_SAVING) return;
    const data = getBelanjaData();
    if (!data) return;
    if (!data.length) { showBkToast('Data masih kosong','error'); return; }
    const btn = document.getElementById('btnSimpanLaporan');
    const spn = document.getElementById('spinnerSimpan');
    const txt = document.getElementById('textSimpan');
    btn.disabled=true; IS_SAVING=true; spn.style.display='block'; txt.textContent=MODE_EDIT?'Mengupdate…':'Menyimpan…';
    try {
        const res  = await fetch("{{ url('/belanja-koperasi/simpan') }}", {
            method:'POST',
            headers:{'Content-Type':'application/json','X-CSRF-TOKEN':'{{ csrf_token() }}'},
            body:JSON.stringify({bulan:{{ $bulan }},tahun:{{ $tahun }},rows:data,mode:MODE_EDIT?'update':'create'})
        });
        const json = await res.json();
        if (json.success) { showBkToast(MODE_EDIT?'Berhasil diupdate':'Berhasil disimpan','success'); MODE_EDIT=true; IS_DIRTY=false; txt.textContent='Update Laporan'; }
        else showBkToast(json.message||'Gagal simpan','error');
    } catch(err) { console.error(err); showBkToast('Terjadi error','error'); }
    finally { btn.disabled=false; IS_SAVING=false; spn.style.display='none'; if(txt.textContent.includes('…')) txt.textContent=MODE_EDIT?'Update Laporan':'Simpan Laporan'; }
}

/* ── IMPORT EXCEL ────────────────────────── */
function normalizeExcelDate(value) {
    if (typeof value==='number') {
        const d = XLSX.SSF.parse_date_code(value);
        if (d) return `${d.y}-${String(d.m).padStart(2,'0')}-${String(d.d).padStart(2,'0')}`;
    }
    if (value instanceof Date) return `${value.getFullYear()}-${String(value.getMonth()+1).padStart(2,'0')}-${String(value.getDate()).padStart(2,'0')}`;
    if (typeof value==='string') {
        const bulanMap={jan:'01',feb:'02',mar:'03',apr:'04',mei:'05',jun:'06',jul:'07',agu:'08',sep:'09',okt:'10',nov:'11',des:'12'};
        const m = value.toLowerCase().match(/^(\d{1,2})-([a-z]{3})-(\d{2,4})$/);
        if (m) { let y=m[3]; if(y.length===2) y='20'+y; return `${y}-${bulanMap[m[2]]}-${m[1].padStart(2,'0')}`; }
        const nd=new Date(value); if(!isNaN(nd.getTime())) return `${nd.getFullYear()}-${String(nd.getMonth()+1).padStart(2,'0')}-${String(nd.getDate()).padStart(2,'0')}`;
    }
    return '';
}

function importExcelBelanja() {
    if (!IS_EDITOR || IS_IMPORTING) return;
    const file = document.getElementById('excelBelanja').files[0];
    if (!file) { showBkToast('Pilih file excel dulu','error'); return; }
    const ext = file.name.split('.').pop().toLowerCase();
    if (!['xls','xlsx'].includes(ext)) { showBkToast('Format harus .xlsx atau .xls','error'); return; }
    IS_IMPORTING=true;
    const reader = new FileReader();
    reader.onload = e => {
        const wb   = XLSX.read(e.target.result,{type:'array',cellDates:true});
        const rows = XLSX.utils.sheet_to_json(wb.Sheets[wb.SheetNames[0]]);
        if (!rows.length) { IS_IMPORTING=false; showBkToast('Data excel kosong','error'); return; }
        const nk = key => key.toString().trim().toLowerCase().replace(/:/g,'');
        let errT=[],errB=[],errK=[],errBi=[];
        rows.forEach((r,i) => {
            const c={};Object.keys(r).forEach(k=>c[nk(k)]=r[k]);
            const tgl = normalizeExcelDate(c.tanggal||'');
            const d = tgl ? parseLocalDate(tgl) : null;
            if (!d || d.getMonth()+1 !== {{ $bulan }} || d.getFullYear() !== {{ $tahun }}) errT.push(`Row ${i+2}`);
            if (!LIST_BIDANG.includes((c.bidang||'').toString().trim())) errB.push(`Row ${i+2}`);
            if (!(c.keperluan||'').toString().trim()) errK.push(`Row ${i+2}`);
            if (parseID(c.biaya||0) <= 0) errBi.push(`Row ${i+2}`);
        });
        if (errT.length)  { IS_IMPORTING=false; showBkToast(`Tanggal tidak sesuai periode:\n${errT.join(', ')}`, 'error'); return; }
        if (errK.length)  { IS_IMPORTING=false; showBkToast(`Keperluan kosong:\n${errK.join(', ')}`, 'error'); return; }
        if (errBi.length) { IS_IMPORTING=false; showBkToast(`Biaya harus diisi:\n${errBi.join(', ')}`, 'error'); return; }
        if (errB.length)  { IS_IMPORTING=false; showBkToast(`Bidang tidak valid:\n${errB.join(', ')}`, 'error'); return; }
        const tbody = document.getElementById('bkTbody');
        const hasData = [...tbody.querySelectorAll('tr')].some(tr => [...tr.querySelectorAll('input')].some(inp=>inp.value.trim()&&inp.value!=='0'));
        if (hasData) {
            showConfirmToast('Data tabel saat ini akan diganti. Lanjut import?', () => { clearTbody(tbody); prosesImportRows(rows,tbody); IS_IMPORTING=false; }, 'confirm-import');
        } else {
            prosesImportRows(rows,tbody); IS_IMPORTING=false;
        }
    };
    reader.onerror = () => { IS_IMPORTING=false; showBkToast('Gagal membaca file','error'); };
    reader.readAsArrayBuffer(file);
}

function clearTbody(tbody) {
    tbody.querySelectorAll('.suggest-box').forEach(box => { if(box._outsideHandler) document.removeEventListener('click',box._outsideHandler); });
    tbody.innerHTML='';
}

function prosesImportRows(rows, tbody) {
    const nk = key => key.toString().trim().toLowerCase().replace(/:/g,'');
    rows.forEach(r => {
        const c={};Object.keys(r).forEach(k=>c[nk(k)]=r[k]);
        const tanggal=normalizeExcelDate(c.tanggal||'');
        const bidang=(c.bidang||'').toString().trim();
        const keperluan=(c.keperluan||'').toString().trim();
        const biaya=parseID(c.biaya||0);
        const tr=document.createElement('tr');tr.className='bk-row';
        tr.innerHTML=`
            <td style="padding:4px 8px;border-bottom:1px solid var(--border);">
                <input type="date" value="${tanggal}" class="bk-tanggal bk-input">
            </td>
            <td style="padding:4px 8px;border-bottom:1px solid var(--border);position:relative;">
                <input type="text" value="${bidang}" class="bk-bidang bk-input">
            </td>
            <td style="padding:4px 8px;border-bottom:1px solid var(--border);">
                <input type="text" value="${keperluan}" class="bk-keperluan bk-input">
            </td>
            <td style="padding:4px 8px;border-bottom:1px solid var(--border);">
                <input type="text" value="${formatID(biaya)}" class="bk-biaya bk-input" style="text-align:right;">
            </td>
            ${IS_EDITOR ? `<td style="padding:4px 8px;border-bottom:1px solid var(--border);text-align:center;">
                <button type="button" onclick="removeBelanjaRow(this)" style="width:22px;height:22px;border-radius:5px;border:none;background:#EF4444;color:#fff;font-size:12px;cursor:pointer;display:flex;align-items:center;justify-content:center;">✕</button>
            </td>` : ''}`;
        tbody.appendChild(tr);
        attachBidangSuggest(tr.querySelector('.bk-bidang'));
    });
    hitungBelanja();
    document.getElementById('excelBelanja').value='';
    IS_DIRTY=true;
    showBkToast(`Import berhasil. ${rows.length} baris dimasukkan.\nJangan lupa simpan laporan.`,'success');
}

/* ── EVENTS ──────────────────────────────── */
document.addEventListener('input', e => {
    if (e.target.matches('.bk-biaya')) { formatInput(e.target); hitungBelanja(); IS_DIRTY=true; }
    if (e.target.matches('.bk-tanggal,.bk-bidang,.bk-keperluan')) IS_DIRTY=true;
});
document.addEventListener('keydown', e => {
    if (e.target.matches('.bk-biaya')) {
        if (!e.ctrlKey && !e.metaKey && !/[0-9]/.test(e.key) && !['Backspace','Delete','ArrowLeft','ArrowRight','Tab','Home','End'].includes(e.key)) e.preventDefault();
    }
    if (e.key==='Enter' && e.target.tagName==='INPUT' && e.target.closest('#tblBelanjaKoperasi tbody')) {
        if (document.querySelector('.suggest-box[style*="block"]')) return;
        const inputs=[...document.querySelectorAll('#tblBelanjaKoperasi tbody input:not(:disabled)')];
        const idx=inputs.indexOf(e.target);
        if (idx >= 0) { e.preventDefault(); const next=inputs[idx+1]; if(next){next.focus();next.select?.();}else{addBelanjaRow();setTimeout(()=>document.querySelector('#tblBelanjaKoperasi tbody tr:last-child .bk-tanggal')?.focus());} }
    }
    if (IS_EDITOR && e.ctrlKey && e.key.toLowerCase()==='s') { e.preventDefault(); handleSimpanBelanja(); }
});
document.addEventListener('paste', e => {
    if (e.target.matches('.bk-biaya')) { e.preventDefault(); e.target.value=formatID(parseID((e.clipboardData||window.clipboardData).getData('text'))); hitungBelanja(); IS_DIRTY=true; }
});

/* ── BACK ────────────────────────────────── */
function handleBackButton() {
    if (!IS_DIRTY) { goBackNow(); return; }
    showConfirmToast('Perubahan belum disimpan. Yakin kembali?', () => { IS_DIRTY=false; goBackNow(); }, 'confirm-back');
}
function goBackNow() { window.location.href="{{ route('laporan-belanja.index') }}"; }

window.addEventListener('beforeunload', e => { if (IS_DIRTY) { e.preventDefault(); e.returnValue=''; } });

/* ── INIT ────────────────────────────────── */
document.addEventListener('DOMContentLoaded', () => {
    document.querySelectorAll('.bk-bidang').forEach(el => attachBidangSuggest(el));

    if (DATA_EXISTING.length) {
        const tbody = document.getElementById('bkTbody');
        clearTbody(tbody);
        DATA_EXISTING.forEach(row => {
            const tr=document.createElement('tr');tr.className='bk-row';
            tr.innerHTML=`
                <td style="padding:4px 8px;border-bottom:1px solid var(--border);">
                    <input type="date" value="${row.tanggal??''}" class="bk-tanggal bk-input">
                </td>
                <td style="padding:4px 8px;border-bottom:1px solid var(--border);position:relative;">
                    <input type="text" value="${row.bidang??''}" class="bk-bidang bk-input">
                </td>
                <td style="padding:4px 8px;border-bottom:1px solid var(--border);">
                    <input type="text" value="${row.keperluan??''}" class="bk-keperluan bk-input">
                </td>
                <td style="padding:4px 8px;border-bottom:1px solid var(--border);">
                    <input type="text" value="${formatID(row.biaya||0)}" class="bk-biaya bk-input" style="text-align:right;">
                </td>
                ${IS_EDITOR ? `<td style="padding:4px 8px;border-bottom:1px solid var(--border);text-align:center;">
                    <button type="button" onclick="removeBelanjaRow(this)" style="width:22px;height:22px;border-radius:5px;border:none;background:#EF4444;color:#fff;font-size:12px;cursor:pointer;display:flex;align-items:center;justify-content:center;">✕</button>
                </td>` : ''}`;
            tbody.appendChild(tr);
            attachBidangSuggest(tr.querySelector('.bk-bidang'));
        });
        hitungBelanja();
    }

    if (!IS_EDITOR) {
        document.querySelectorAll('#tblBelanjaKoperasi input').forEach(el => { el.disabled=true; el.readOnly=true; });
        window.addBelanjaRow=()=>{}; window.removeBelanjaRow=()=>{}; window.importExcelBelanja=()=>{}; window.handleSimpanBelanja=()=>{};
    }
});
</script>

{{-- XLSX CDN --}}
<script src="https://cdnjs.cloudflare.com/ajax/libs/xlsx/0.18.5/xlsx.full.min.js"></script>

@endsection