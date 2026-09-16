import * as XLSX from 'xlsx';

/* ── CONFIG (diambil dari data-attributes di Blade) ─────────── */
const root = document.getElementById('belanjaKoperasiApp');

const LIST_BIDANG = ["Sekretaris","Bendahara","Bidang 1","Bidang 2","Bidang 3","Bidang 4","Bidang 5","Bidang 6","GM","KorLap","Dana Musyawarah","Dana Sosial Politik"];

const IS_EDITOR     = root.dataset.isEditor === '1';
const DATA_EXISTING = JSON.parse(root.dataset.existing || '[]');
const BULAN         = parseInt(root.dataset.bulan, 10);
const TAHUN         = parseInt(root.dataset.tahun, 10);
const NAMA_BULAN    = root.dataset.namaBulan;
const SIMPAN_URL    = root.dataset.simpanUrl;
const BACK_URL      = root.dataset.backUrl;
const CSRF_TOKEN    = root.dataset.csrf;

let MODE_EDIT  = root.dataset.isEdit === '1';
let IS_DIRTY   = false, IS_SAVING = false, IS_IMPORTING = false;

/* ── TOAST ───────────────────────────────── */
function showBkToast(msg, type = 'info') {
    const wrap = document.getElementById('toastWrap');
    const t = document.createElement('div');
    t.className = `bk-toast ${type === 'success' ? 'bk-toast-success' : type === 'error' ? 'bk-toast-error' : ''}`;
    t.textContent = msg;
    wrap.appendChild(t);
    const normals = [...wrap.children].filter(el => !el.classList.contains('bk-toast-confirm'));
    if (normals.length > 4) normals[0].remove();
    setTimeout(() => {
        t.style.transition = 'opacity 0.3s';
        t.style.opacity = '0';
        setTimeout(() => t.remove(), 350);
    }, 3000);
}

function showConfirmToast(msg, onConfirm, cls = '') {
    const wrap = document.getElementById('toastWrap');
    document.querySelectorAll(`.bk-toast-confirm.${cls || 'any-confirm'}`).forEach(el => el.remove());
    const t = document.createElement('div');
    t.className = `bk-toast bk-toast-confirm ${cls}`;
    t.innerHTML = `<div style="font-weight:500;margin-bottom:6px;">${msg}</div>
        <div style="display:flex;justify-content:flex-end;gap:8px;margin-top:8px;">
            <button class="btn btn-ghost confirm-cancel" style="padding:4px 12px;font-size:12px;">Batal</button>
            <button class="btn btn-primary confirm-ok" style="padding:4px 12px;font-size:12px;">Lanjut</button>
        </div>`;
    t.querySelector('.confirm-cancel').onclick = () => { t.remove(); IS_IMPORTING = false; };
    t.querySelector('.confirm-ok').onclick = () => { onConfirm(); t.remove(); };
    wrap.appendChild(t);
}

/* ── FORMAT ──────────────────────────────── */
function parseID(val) { if (!val) return 0; return parseInt(String(val).replace(/[^\d]/g, '')) || 0; }
function formatID(val) { return Math.round(val || 0).toString().replace(/\B(?=(\d{3})+(?!\d))/g, '.'); }
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
        if (!val) { box.style.display = 'none'; return; }
        const match = LIST_BIDANG.filter(b => b.toLowerCase().includes(val));
        if (!match.length) { box.style.display = 'none'; return; }
        match.forEach(m => {
            const item = document.createElement('div');
            item.className = 'suggest-item';
            item.textContent = m;
            item.onclick = () => { input.value = m; box.style.display = 'none'; IS_DIRTY = true; };
            box.appendChild(item);
        });
        box.style.display = 'block';
    });
    box._outsideHandler = e => { if (!box.contains(e.target) && e.target !== input) box.style.display = 'none'; };
    document.addEventListener('click', box._outsideHandler);
}

/* ── ROW TEMPLATE ────────────────────────── */
function buildRowHtml({ tanggal = '', bidang = '', keperluan = '', biaya = 0 } = {}) {
    return `
        <td style="padding:4px 8px;border-bottom:1px solid var(--border);">
            <input type="date" value="${tanggal}" class="bk-tanggal bk-input" autocomplete="off">
        </td>
        <td style="padding:4px 8px;border-bottom:1px solid var(--border);position:relative;">
            <input type="text" value="${bidang}" class="bk-bidang bk-input" autocomplete="off">
        </td>
        <td style="padding:4px 8px;border-bottom:1px solid var(--border);">
            <input type="text" value="${keperluan}" class="bk-keperluan bk-input" autocomplete="off">
        </td>
        <td style="padding:4px 8px;border-bottom:1px solid var(--border);">
            <input type="text" inputmode="numeric" value="${formatID(biaya)}" class="bk-biaya bk-input" style="text-align:right;" autocomplete="off">
        </td>
        ${IS_EDITOR ? `<td style="padding:4px 8px;border-bottom:1px solid var(--border);text-align:center;">
            <button type="button" class="bk-remove-row" style="width:22px;height:22px;border-radius:5px;border:none;background:#EF4444;color:#fff;font-size:12px;cursor:pointer;display:flex;align-items:center;justify-content:center;">✕</button>
        </td>` : ''}`;
}

/* ── ADD / REMOVE ROW ────────────────────── */
function addBelanjaRow() {
    if (!IS_EDITOR) return;
    const tbody = document.getElementById('bkTbody');
    const tr = document.createElement('tr');
    tr.className = 'bk-row';
    tr.innerHTML = buildRowHtml();
    tbody.appendChild(tr);
    attachBidangSuggest(tr.querySelector('.bk-bidang'));
    tr.querySelector('.bk-tanggal')?.focus();
    hitungBelanja();
    IS_DIRTY = true;
}

function removeBelanjaRow(btn) {
    if (!IS_EDITOR) return;
    const tbody = document.getElementById('bkTbody');
    if (tbody.querySelectorAll('tr').length <= 1) { showBkToast('Minimal 1 baris harus ada.', 'error'); return; }
    const tr = btn.closest('tr');
    const box = tr.querySelector('.suggest-box');
    if (box?._outsideHandler) document.removeEventListener('click', box._outsideHandler);
    tr.remove();
    hitungBelanja();
    IS_DIRTY = true;
}

/* ── COLLECT DATA ────────────────────────── */
function parseLocalDate(s) {
    if (!s) return null;
    const p = s.split('-');
    if (p.length !== 3) return null;
    const d = new Date(+p[0], +p[1] - 1, +p[2]);
    return (d.getFullYear() === +p[0] && d.getMonth() === +p[1] - 1 && d.getDate() === +p[2]) ? d : null;
}

function getBelanjaData() {
    const rows = [];
    let errTanggal = false, errTanggalKosong = false, errBidang = false, errKeperluan = false, errBiaya = false;
    document.querySelectorAll('#bkTbody tr').forEach(tr => {
        tr.classList.remove('bg-error');
        const tanggal   = tr.querySelector('.bk-tanggal')?.value || '';
        const bidang    = tr.querySelector('.bk-bidang')?.value.trim() || '';
        const keperluan = tr.querySelector('.bk-keperluan')?.value.trim() || '';
        const biaya     = parseID(tr.querySelector('.bk-biaya')?.value || 0);
        if (!tanggal && !bidang && !keperluan && biaya === 0) return;
        if (!tanggal) { tr.classList.add('bg-error'); errTanggalKosong = true; return; }
        const d = parseLocalDate(tanggal);
        if (!d || d.getMonth() + 1 !== BULAN || d.getFullYear() !== TAHUN) { tr.classList.add('bg-error'); errTanggal = true; return; }
        if (!LIST_BIDANG.includes(bidang)) { tr.classList.add('bg-error'); errBidang = true; return; }
        if (!keperluan) { tr.classList.add('bg-error'); errKeperluan = true; return; }
        if (biaya <= 0) { tr.classList.add('bg-error'); errBiaya = true; return; }
        rows.push({ bulan: BULAN, tahun: TAHUN, tanggal, bidang, tujuan: 'Koperasi', keperluan, biaya });
    });
    if (errTanggalKosong) { showBkToast('Tanggal wajib diisi.\nCek baris merah.', 'error'); return null; }
    if (errBidang)        { showBkToast(`Bidang tidak valid. Cek baris merah.\n\nBidang valid:\n${LIST_BIDANG.join(', ')}`, 'error'); return null; }
    if (errBiaya)         { showBkToast('Biaya wajib lebih dari 0.\nCek baris merah.', 'error'); return null; }
    if (errKeperluan)     { showBkToast('Keperluan wajib diisi.\nCek baris merah.', 'error'); return null; }
    if (errTanggal)       { showBkToast(`Tanggal tidak sesuai periode laporan.\nSemua tanggal harus di bulan ${NAMA_BULAN} ${TAHUN}.`, 'error'); return null; }
    return rows;
}

/* ── SIMPAN ──────────────────────────────── */
async function handleSimpanBelanja() {
    if (!IS_EDITOR || IS_SAVING) return;
    const data = getBelanjaData();
    if (!data) return;
    if (!data.length) { showBkToast('Data masih kosong', 'error'); return; }
    const btn = document.getElementById('btnSimpanLaporan');
    const spn = document.getElementById('spinnerSimpan');
    const txt = document.getElementById('textSimpan');
    btn.disabled = true; IS_SAVING = true; spn.style.display = 'block'; txt.textContent = MODE_EDIT ? 'Mengupdate…' : 'Menyimpan…';
    try {
        const res = await fetch(SIMPAN_URL, {
            method: 'POST',
            headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': CSRF_TOKEN },
            body: JSON.stringify({ bulan: BULAN, tahun: TAHUN, rows: data, mode: MODE_EDIT ? 'update' : 'create' })
        });
        const json = await res.json();
        if (json.success) {
            showBkToast(MODE_EDIT ? 'Berhasil diupdate' : 'Berhasil disimpan', 'success');
            MODE_EDIT = true; IS_DIRTY = false; txt.textContent = 'Update Laporan';
        } else {
            showBkToast(json.message || 'Gagal simpan', 'error');
        }
    } catch (err) {
        console.error(err);
        showBkToast('Terjadi error', 'error');
    } finally {
        btn.disabled = false; IS_SAVING = false; spn.style.display = 'none';
        if (txt.textContent.includes('…')) txt.textContent = MODE_EDIT ? 'Update Laporan' : 'Simpan Laporan';
    }
}

/* ── IMPORT EXCEL ────────────────────────── */
function normalizeExcelDate(value) {
    if (typeof value === 'number') {
        const d = XLSX.SSF.parse_date_code(value);
        if (d) return `${d.y}-${String(d.m).padStart(2, '0')}-${String(d.d).padStart(2, '0')}`;
    }
    if (value instanceof Date) return `${value.getFullYear()}-${String(value.getMonth() + 1).padStart(2, '0')}-${String(value.getDate()).padStart(2, '0')}`;
    if (typeof value === 'string') {
        const bulanMap = { jan: '01', feb: '02', mar: '03', apr: '04', mei: '05', jun: '06', jul: '07', agu: '08', sep: '09', okt: '10', nov: '11', des: '12' };
        const m = value.toLowerCase().match(/^(\d{1,2})-([a-z]{3})-(\d{2,4})$/);
        if (m) { let y = m[3]; if (y.length === 2) y = '20' + y; return `${y}-${bulanMap[m[2]]}-${m[1].padStart(2, '0')}`; }
        const nd = new Date(value); if (!isNaN(nd.getTime())) return `${nd.getFullYear()}-${String(nd.getMonth() + 1).padStart(2, '0')}-${String(nd.getDate()).padStart(2, '0')}`;
    }
    return '';
}

function importExcelBelanja() {
    if (!IS_EDITOR || IS_IMPORTING) return;
    const file = document.getElementById('excelBelanja').files[0];
    if (!file) { showBkToast('Pilih file excel dulu', 'error'); return; }
    const ext = file.name.split('.').pop().toLowerCase();
    if (!['xls', 'xlsx'].includes(ext)) { showBkToast('Format harus .xlsx atau .xls', 'error'); return; }
    IS_IMPORTING = true;
    const reader = new FileReader();
    reader.onload = e => {
        const wb = XLSX.read(e.target.result, { type: 'array', cellDates: true });
        const rows = XLSX.utils.sheet_to_json(wb.Sheets[wb.SheetNames[0]]);
        if (!rows.length) { IS_IMPORTING = false; showBkToast('Data excel kosong', 'error'); return; }
        const nk = key => key.toString().trim().toLowerCase().replace(/:/g, '');
        let errT = [], errB = [], errK = [], errBi = [];
        rows.forEach((r, i) => {
            const c = {}; Object.keys(r).forEach(k => c[nk(k)] = r[k]);
            const tgl = normalizeExcelDate(c.tanggal || '');
            const d = tgl ? parseLocalDate(tgl) : null;
            if (!d || d.getMonth() + 1 !== BULAN || d.getFullYear() !== TAHUN) errT.push(`Row ${i + 2}`);
            if (!LIST_BIDANG.includes((c.bidang || '').toString().trim())) errB.push(`Row ${i + 2}`);
            if (!(c.keperluan || '').toString().trim()) errK.push(`Row ${i + 2}`);
            if (parseID(c.biaya || 0) <= 0) errBi.push(`Row ${i + 2}`);
        });
        if (errT.length)  { IS_IMPORTING = false; showBkToast(`Tanggal tidak sesuai periode:\n${errT.join(', ')}`, 'error'); return; }
        if (errK.length)  { IS_IMPORTING = false; showBkToast(`Keperluan kosong:\n${errK.join(', ')}`, 'error'); return; }
        if (errBi.length) { IS_IMPORTING = false; showBkToast(`Biaya harus diisi:\n${errBi.join(', ')}`, 'error'); return; }
        if (errB.length)  { IS_IMPORTING = false; showBkToast(`Bidang tidak valid:\n${errB.join(', ')}`, 'error'); return; }
        const tbody = document.getElementById('bkTbody');
        const hasData = [...tbody.querySelectorAll('tr')].some(tr => [...tr.querySelectorAll('input')].some(inp => inp.value.trim() && inp.value !== '0'));
        if (hasData) {
            showConfirmToast('Data tabel saat ini akan diganti. Lanjut import?', () => { clearTbody(tbody); prosesImportRows(rows, tbody); IS_IMPORTING = false; }, 'confirm-import');
        } else {
            prosesImportRows(rows, tbody); IS_IMPORTING = false;
        }
    };
    reader.onerror = () => { IS_IMPORTING = false; showBkToast('Gagal membaca file', 'error'); };
    reader.readAsArrayBuffer(file);
}

function clearTbody(tbody) {
    tbody.querySelectorAll('.suggest-box').forEach(box => { if (box._outsideHandler) document.removeEventListener('click', box._outsideHandler); });
    tbody.innerHTML = '';
}

function prosesImportRows(rows, tbody) {
    const nk = key => key.toString().trim().toLowerCase().replace(/:/g, '');
    rows.forEach(r => {
        const c = {}; Object.keys(r).forEach(k => c[nk(k)] = r[k]);
        const tanggal   = normalizeExcelDate(c.tanggal || '');
        const bidang    = (c.bidang || '').toString().trim();
        const keperluan = (c.keperluan || '').toString().trim();
        const biaya     = parseID(c.biaya || 0);
        const tr = document.createElement('tr');
        tr.className = 'bk-row';
        tr.innerHTML = buildRowHtml({ tanggal, bidang, keperluan, biaya });
        tbody.appendChild(tr);
        attachBidangSuggest(tr.querySelector('.bk-bidang'));
    });
    hitungBelanja();
    document.getElementById('excelBelanja').value = '';
    IS_DIRTY = true;
    showBkToast(`Import berhasil. ${rows.length} baris dimasukkan.\nJangan lupa simpan laporan.`, 'success');
}

/* ── BACK ────────────────────────────────── */
function handleBackButton() {
    if (!IS_DIRTY) { goBackNow(); return; }
    showConfirmToast('Perubahan belum disimpan. Yakin kembali?', () => { IS_DIRTY = false; goBackNow(); }, 'confirm-back');
}
function goBackNow() { window.location.href = BACK_URL; }

/* ── EVENTS (delegasi, tidak pakai onclick inline) ──────────── */
document.addEventListener('input', e => {
    if (e.target.matches('.bk-biaya')) { formatInput(e.target); hitungBelanja(); IS_DIRTY = true; }
    if (e.target.matches('.bk-tanggal,.bk-bidang,.bk-keperluan')) IS_DIRTY = true;
});

document.addEventListener('keydown', e => {
    if (e.target.matches('.bk-biaya')) {
        if (!e.ctrlKey && !e.metaKey && !/[0-9]/.test(e.key) && !['Backspace', 'Delete', 'ArrowLeft', 'ArrowRight', 'Tab', 'Home', 'End'].includes(e.key)) e.preventDefault();
    }
    if (e.key === 'Enter' && e.target.tagName === 'INPUT' && e.target.closest('#tblBelanjaKoperasi tbody')) {
        if (document.querySelector('.suggest-box[style*="block"]')) return;
        const inputs = [...document.querySelectorAll('#tblBelanjaKoperasi tbody input:not(:disabled)')];
        const idx = inputs.indexOf(e.target);
        if (idx >= 0) {
            e.preventDefault();
            const next = inputs[idx + 1];
            if (next) { next.focus(); next.select?.(); }
            else { addBelanjaRow(); setTimeout(() => document.querySelector('#tblBelanjaKoperasi tbody tr:last-child .bk-tanggal')?.focus()); }
        }
    }
    if (IS_EDITOR && e.ctrlKey && e.key.toLowerCase() === 's') { e.preventDefault(); handleSimpanBelanja(); }
});

document.addEventListener('paste', e => {
    if (e.target.matches('.bk-biaya')) {
        e.preventDefault();
        e.target.value = formatID(parseID((e.clipboardData || window.clipboardData).getData('text')));
        hitungBelanja();
        IS_DIRTY = true;
    }
});

document.addEventListener('click', e => {
    if (e.target.closest('.bk-remove-row')) removeBelanjaRow(e.target.closest('.bk-remove-row'));
    if (e.target.closest('#btnTambahBaris')) addBelanjaRow();
    if (e.target.closest('#btnBackBelanja')) handleBackButton();
    if (e.target.closest('#btnImportExcel')) importExcelBelanja();
});

document.getElementById('btnSimpanLaporan')?.addEventListener('click', handleSimpanBelanja);

window.addEventListener('beforeunload', e => { if (IS_DIRTY) { e.preventDefault(); e.returnValue = ''; } });

/* ── INIT ────────────────────────────────── */
document.addEventListener('DOMContentLoaded', () => {
    document.querySelectorAll('.bk-bidang').forEach(el => attachBidangSuggest(el));

    if (DATA_EXISTING.length) {
        const tbody = document.getElementById('bkTbody');
        clearTbody(tbody);
        DATA_EXISTING.forEach(row => {
            const tr = document.createElement('tr');
            tr.className = 'bk-row';
            tr.innerHTML = buildRowHtml({ tanggal: row.tanggal ?? '', bidang: row.bidang ?? '', keperluan: row.keperluan ?? '', biaya: row.biaya || 0 });
            tbody.appendChild(tr);
            attachBidangSuggest(tr.querySelector('.bk-bidang'));
        });
        hitungBelanja();
    }

    if (!IS_EDITOR) {
        document.querySelectorAll('#tblBelanjaKoperasi input').forEach(el => { el.disabled = true; el.readOnly = true; });
    }
});