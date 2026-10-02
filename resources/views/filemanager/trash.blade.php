@extends('layouts.app')

@section('title', 'Trash')

@section('content')

<div class="tr" id="tr">

    <div class="tr-head">
        <div>
            <h1 class="tr-title">Trash</h1>
            <p class="tr-sub">Item yang dihapus disimpan 30 hari sebelum dihapus permanen.</p>
        </div>
        <div class="tr-actions">
            <a href="/file-manager" class="btn btn-ghost">← Kembali</a>
            <button type="button" class="btn tr-danger" id="emptyBtn">Kosongkan Trash</button>
        </div>
    </div>

    <div class="tr-toolbar">
        <label class="tr-search">
            <input type="search" id="q" placeholder="Cari di Trash…" autocomplete="off" aria-label="Cari di Trash">
        </label>
        <div class="tr-bulk" id="bulk">
            <label class="tr-allwrap"><input type="checkbox" id="chkAll"> <span><b id="selCount">0</b> dipilih</span></label>
            <span class="tr-spacer"></span>
            <button type="button" class="btn btn-ghost" id="bulkRestore">Pulihkan</button>
            <button type="button" class="btn tr-danger" id="bulkDelete">Hapus permanen</button>
        </div>
    </div>

    <div class="tr-panel">
        <div class="tr-listhead" id="listHead">
            <label class="tr-allwrap"><input type="checkbox" id="chkAllHead" aria-label="Pilih semua"></label>
            <span></span><span>Nama</span><span class="tr-hide-sm">Sisa waktu</span><span></span>
        </div>
        <div id="list"></div>
        <div class="tr-empty" id="empty"></div>
    </div>
</div>

<div class="tr-modal" id="confirmModal">
    <div class="tr-box">
        <h2 id="cTitle">Konfirmasi</h2>
        <p id="cMsg"></p>
        <div class="tr-box-actions">
            <button type="button" class="btn btn-ghost" id="cCancel">Batal</button>
            <button type="button" class="btn tr-danger" id="cOk">Hapus</button>
        </div>
    </div>
</div>

<script>
(function () {
'use strict';
const $ = id => document.getElementById(id);
const CSRF = document.querySelector('meta[name="csrf-token"]')?.content || '';
let items = @json($items);
const sel = new Set();
let query = '';

function toast(m, err) { if (typeof showToast === 'function') showToast(m, !!err); }
function esc(s) {
    return String(s ?? '').replace(/&/g, '&amp;').replace(/</g, '&lt;').replace(/>/g, '&gt;').replace(/"/g, '&quot;').replace(/'/g, '&#039;');
}
function fmtSize(b) {
    if (b === null || b === undefined || b === '') return '';
    b = Number(b);
    if (b < 1024) return b + ' B';
    if (b < 1048576) return (b / 1024).toFixed(b < 10240 ? 1 : 0) + ' KB';
    return (b / 1048576).toFixed(1) + ' MB';
}
async function api(method, url) {
    try {
        const res = await fetch(url, { method: method, headers: { 'X-CSRF-TOKEN': CSRF, 'Accept': 'application/json' } });
        const j = await res.json().catch(() => ({}));
        if (j.success === undefined) j.success = res.ok;
        return j;
    } catch (e) { return { success: false }; }
}

const FOLDER = '<svg class="tr-folder" viewBox="0 0 24 24"><path d="M3 6.5A2.5 2.5 0 015.5 4h3.4c.5 0 1 .2 1.4.6L12 6.3h6.5A2.5 2.5 0 0121 8.8v8.7a2.5 2.5 0 01-2.5 2.5h-13A2.5 2.5 0 013 17.5v-11z"/></svg>';
const KINDS = { pdf: ['PDF', 'k-pdf'], doc: ['DOC', 'k-word'], docx: ['DOC', 'k-word'], xls: ['XLS', 'k-excel'], xlsx: ['XLS', 'k-excel'] };
function icon(it) {
    if (it.type === 'folder') return FOLDER;
    const k = KINDS[it.ext] || [(it.ext || 'FILE').toUpperCase().slice(0, 4), 'k-other'];
    return '<span class="tr-badge ' + k[1] + '">' + k[0] + '</span>';
}

function visible() {
    const q = query.toLowerCase();
    return q ? items.filter(i => i.name.toLowerCase().includes(q)) : items;
}
function render() {
    const list = visible();
    $('list').innerHTML = list.map(it => {
        const days = it.remaining;
        const meta = 'Dihapus ' + esc(it.deleted) + (it.type === 'file' && it.size !== null ? ' • ' + fmtSize(it.size) : '');
        return '<div class="tr-row' + (sel.has(it.id) ? ' sel' : '') + '" data-id="' + it.id + '">'
            + '<label class="tr-allwrap"><input type="checkbox" data-chk ' + (sel.has(it.id) ? 'checked' : '') + ' aria-label="Pilih"></label>'
            + '<div class="tr-ico">' + icon(it) + '</div>'
            + '<div class="tr-meta"><div class="tr-name" title="' + esc(it.name) + '">' + esc(it.name) + '</div><div class="tr-info">' + meta + '</div></div>'
            + '<div class="tr-left tr-hide-sm' + (days <= 3 ? ' warn' : '') + '">' + days + ' hari lagi</div>'
            + '<div class="tr-rowact"><button type="button" class="btn btn-ghost" data-restore>Pulihkan</button><button type="button" class="tr-iconbtn" data-del title="Hapus permanen" aria-label="Hapus permanen">🗑</button></div>'
            + '</div>';
    }).join('');

    const e = $('empty');
    if (!list.length) {
        e.innerHTML = items.length
            ? '<div class="tr-eico">🔎</div><b>Tidak ada hasil</b><span>Tidak ada item yang cocok dengan pencarian.</span>'
            : '<div class="tr-eico">🗑️</div><b>Trash kosong</b><span>Item yang dihapus akan muncul di sini sebelum dihapus permanen.</span>';
        e.classList.add('show');
    } else {
        e.classList.remove('show');
    }
    $('listHead').style.display = list.length ? '' : 'none';
    $('emptyBtn').disabled = !items.length;
    syncBulk();
}
function syncBulk() {
    const n = sel.size;
    $('bulk').classList.toggle('show', n > 0);
    $('selCount').textContent = n;
    const list = visible();
    const all = list.length > 0 && list.every(i => sel.has(i.id));
    $('chkAll').checked = all;
    $('chkAllHead').checked = all;
}
function removeLocal(ids) {
    const s = new Set(ids);
    items = items.filter(i => !s.has(i.id));
    ids.forEach(id => sel.delete(id));
    render();
}

/* ---- confirm modal ---- */
let cb = null;
function confirmBox(title, msg, ok, fn) {
    $('cTitle').textContent = title; $('cMsg').textContent = msg; $('cOk').textContent = ok;
    cb = fn; $('confirmModal').classList.add('open');
}
function closeConfirm() { $('confirmModal').classList.remove('open'); cb = null; }
$('cCancel').onclick = closeConfirm;
$('cOk').onclick = () => { const f = cb; closeConfirm(); if (f) f(); };
$('confirmModal').addEventListener('click', e => { if (e.target.id === 'confirmModal') closeConfirm(); });
document.addEventListener('keydown', e => { if (e.key === 'Escape') closeConfirm(); });

/* ---- aksi ---- */
async function restoreIds(ids) {
    const done = [];
    for (const id of ids) { const r = await api('POST', '/file-manager/restore/' + id); if (r.success) done.push(id); }
    if (done.length) { removeLocal(done); toast(done.length + ' item dipulihkan'); }
    if (done.length < ids.length) toast('Sebagian item gagal dipulihkan', true);
}
function deleteIds(ids) {
    const what = ids.length === 1 ? 'Item ini' : ids.length + ' item';
    confirmBox('Hapus permanen?', what + ' akan dihapus selamanya dan tidak bisa dikembalikan.', 'Hapus permanen', async () => {
        const done = [];
        for (const id of ids) { const r = await api('DELETE', '/file-manager/force-delete/' + id); if (r.success) done.push(id); }
        if (done.length) { removeLocal(done); toast(done.length + ' item dihapus permanen'); }
        if (done.length < ids.length) toast('Sebagian item gagal dihapus', true);
    });
}

$('list').addEventListener('click', e => {
    const row = e.target.closest('.tr-row');
    if (!row) return;
    const id = Number(row.dataset.id);
    if (e.target.closest('[data-restore]')) { restoreIds([id]); return; }
    if (e.target.closest('[data-del]')) { deleteIds([id]); return; }
    if (e.target.closest('[data-chk]')) {
        e.target.closest('[data-chk]').checked ? sel.add(id) : sel.delete(id);
        row.classList.toggle('sel', sel.has(id));
        syncBulk();
    }
});
function toggleAll(checked) {
    visible().forEach(i => checked ? sel.add(i.id) : sel.delete(i.id));
    render();
}
$('chkAll').onchange = e => toggleAll(e.target.checked);
$('chkAllHead').onchange = e => toggleAll(e.target.checked);
$('bulkRestore').onclick = () => restoreIds(Array.from(sel));
$('bulkDelete').onclick = () => deleteIds(Array.from(sel));
$('emptyBtn').onclick = () => {
    if (!items.length) return;
    confirmBox('Kosongkan Trash?', 'Semua item di Trash akan dihapus permanen dan tidak bisa dikembalikan.', 'Kosongkan', async () => {
        const r = await api('DELETE', '/file-manager/empty-trash');
        if (r.success) { items = []; sel.clear(); render(); toast('Trash dikosongkan'); }
        else toast('Gagal mengosongkan Trash', true);
    });
};
let t = null;
$('q').addEventListener('input', e => { clearTimeout(t); t = setTimeout(() => { query = e.target.value.trim(); render(); }, 200); });

render();
})();
</script>

<style>
.tr { max-width: 980px; margin: 0 auto; display: flex; flex-direction: column; gap: 12px; }
.tr .btn { display: inline-flex; align-items: center; gap: 6px; }
.tr-head { display: flex; align-items: center; justify-content: space-between; gap: 12px; flex-wrap: wrap; }
.tr-title { font-size: 1.25rem; font-weight: 600; color: var(--text-primary); margin: 0; }
.tr-sub { font-size: .8125rem; color: var(--text-muted); margin: 2px 0 0; }
.tr-actions { display: flex; gap: 8px; }
.tr-danger { background: #DC2626; border: 1px solid #DC2626; color: #fff; border-radius: 9px; padding: 8px 14px; font-size: .875rem; font-weight: 500; cursor: pointer; font-family: inherit; }
.tr-danger:hover { background: #B91C1C; }
.tr-danger:disabled { opacity: .45; cursor: default; }
.tr-toolbar { position: relative; min-height: 44px; }
.tr-search input { width: 100%; max-width: 360px; height: 40px; padding: 0 16px; border-radius: 999px; border: 1px solid var(--border-strong); background: var(--bg-surface); color: var(--text-primary); font-size: .875rem; font-family: inherit; outline: none; -webkit-appearance: none; appearance: none; }
.tr-search input:focus { border-color: var(--accent); box-shadow: 0 0 0 3px rgba(37,99,235,.12); }
.tr-bulk { display: none; position: absolute; inset: 0; align-items: center; gap: 8px; padding: 4px 10px; border-radius: 12px; border: 1px solid var(--accent); background: var(--accent-muted); color: var(--text-primary); }
.tr-bulk.show { display: flex; }
.tr-spacer { flex: 1; }
.tr-allwrap { display: inline-flex; align-items: center; gap: 8px; font-size: .875rem; cursor: pointer; }
.tr-allwrap input { width: 17px; height: 17px; accent-color: var(--accent); cursor: pointer; }

.tr-panel { background: var(--bg-surface); border: 1px solid var(--border); border-radius: 16px; overflow: hidden; }
.tr-listhead, .tr-row { display: grid; grid-template-columns: 24px 44px minmax(0,1fr) 110px auto; align-items: center; gap: 10px; padding: 10px 16px; }
.tr-listhead { font-size: .75rem; font-weight: 600; text-transform: uppercase; letter-spacing: .04em; color: var(--text-muted); border-bottom: 1px solid var(--border); }
.tr-row { border-bottom: 1px solid var(--border); transition: background .15s; content-visibility: auto; contain-intrinsic-size: 64px; }
.tr-row:last-child { border-bottom: none; }
.tr-row:hover { background: rgba(37,99,235,.04); }
.tr-row.sel { background: var(--accent-muted); }
.tr-ico { display: flex; align-items: center; justify-content: center; }
.tr-folder { width: 32px; height: 32px; fill: #F59E0B; }
.tr-badge { display: inline-flex; align-items: center; justify-content: center; width: 34px; height: 40px; border-radius: 8px; font-size: 10px; font-weight: 700; letter-spacing: .04em; }
.k-pdf { background: #FEE2E2; color: #B91C1C; }
.k-word { background: #DBEAFE; color: #1D4ED8; }
.k-excel { background: #DCFCE7; color: #15803D; }
.k-other { background: var(--bg-elevated); color: var(--text-muted); }
.tr-meta { min-width: 0; }
.tr-name { font-size: .875rem; font-weight: 500; color: var(--text-primary); overflow: hidden; text-overflow: ellipsis; white-space: nowrap; }
.tr-info { font-size: .75rem; color: var(--text-muted); margin-top: 2px; overflow: hidden; text-overflow: ellipsis; white-space: nowrap; }
.tr-left { font-size: .8125rem; color: var(--text-secondary); white-space: nowrap; }
.tr-left.warn { color: #DC2626; font-weight: 600; }
.tr-rowact { display: flex; align-items: center; gap: 6px; }
.tr-iconbtn { width: 36px; height: 36px; border-radius: 50%; border: none; background: none; cursor: pointer; font-size: 1rem; }
.tr-iconbtn:hover { background: rgba(239,68,68,.12); }

.tr-empty { display: none; flex-direction: column; align-items: center; gap: 6px; padding: 64px 16px; text-align: center; color: var(--text-muted); font-size: .875rem; }
.tr-empty.show { display: flex; }
.tr-empty b { color: var(--text-primary); font-size: 1rem; }
.tr-eico { font-size: 3rem; line-height: 1; margin-bottom: 6px; }

.tr-modal { position: fixed; inset: 0; z-index: 9000; display: none; align-items: center; justify-content: center; background: rgba(0,0,0,.45); backdrop-filter: blur(6px); }
.tr-modal.open { display: flex; }
.tr-box { background: var(--bg-surface); border: 1px solid var(--border); border-radius: 18px; box-shadow: var(--shadow-lg); width: 92%; max-width: 400px; padding: 24px; }
.tr-box h2 { font-size: 1rem; font-weight: 600; color: var(--text-primary); margin: 0 0 8px; }
.tr-box p { font-size: .875rem; color: var(--text-secondary); margin: 0 0 20px; line-height: 1.6; }
.tr-box-actions { display: flex; justify-content: flex-end; gap: 8px; }

@media (max-width: 639px) {
    .tr-hide-sm { display: none; }
    .tr-listhead, .tr-row { grid-template-columns: 24px 40px minmax(0,1fr) auto; padding: 10px 12px; }
    .tr-rowact .btn { padding: 6px 10px; font-size: .8125rem; }
    .tr-bulk .btn { padding: 6px 10px; font-size: .8125rem; }
}
</style>

@endsection