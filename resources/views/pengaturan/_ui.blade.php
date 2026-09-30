{{-- resources/views/pengaturan/_ui.blade.php
     Gaya + helper bersama untuk halaman Pengaturan (Insentif, Ringkasan Kas, Bidang).
     Dipanggil dengan: @include('pengaturan._ui')
     Jika navbar layout Anda menempel di atas, atur jarak bar --}}

<style>
:root{
    color-scheme:light;
    --ui-accent:#2f5bea; --ui-accent-d:#2447c4; --ui-accent-soft:#eaf0ff;
    --ui-ink:#0f172a; --ui-mute:#64748b; --ui-faint:#94a3b8; --ui-line:#e2e8f0; --ui-line-2:#cbd5e1;
    --ui-page:#f1f5f9; --ui-surface:#ffffff; --ui-bg:#f8fafc; --ui-hover:#fbfdff; --ui-seg:#eef2f7;
    --ui-bar-bg:rgba(255,255,255,.9); --ui-track:#cbd5e1;
    --ui-ok:#16a34a; --ui-ok-soft:#dcfce7;
    --ui-bad:#dc2626; --ui-bad-soft:#fef2f2; --ui-bad-line:#fecaca; --ui-bad-text:#b91c1c;
    --ui-shadow:rgba(15,23,42,.06);
}
/* mode gelap mengikuti pengaturan sistem perangkat */
@media (prefers-color-scheme:dark){
:root{
    color-scheme:dark;
    --ui-accent:#4c72f2; --ui-accent-d:#6384f5; --ui-accent-soft:rgba(76,114,242,.16);
    --ui-ink:#e2e8f0; --ui-mute:#94a3b8; --ui-faint:#64748b; --ui-line:#26334a; --ui-line-2:#3a4a66;
    --ui-page:#0b1120; --ui-surface:#121b2e; --ui-bg:#0f1729; --ui-hover:#16213a; --ui-seg:#0f1729;
    --ui-bar-bg:rgba(11,17,32,.88); --ui-track:#475569;
    --ui-ok:#22c55e; --ui-ok-soft:rgba(34,197,94,.16);
    --ui-bad:#f87171; --ui-bad-soft:rgba(248,113,113,.1); --ui-bad-line:#7f1d1d; --ui-bad-text:#fca5a5;
    --ui-shadow:rgba(0,0,0,.4);
}
body{background:var(--ui-page)}
.toast{border:1px solid #26334a}
.confirm-box{border:1px solid var(--ui-line)}
}
.hidden{display:none!important}
.ui-page{max-width:1120px;margin:0 auto;padding:0 16px 96px;color:var(--ui-ink);font-size:14px}
.ui-page *{box-sizing:border-box}
 
/* bar atas (menempel saat scroll) */
.ui-bar{position:sticky;top:var(--bar-top,0);z-index:30;display:flex;flex-wrap:wrap;gap:12px;align-items:center;
    justify-content:space-between;padding:14px 16px;margin:0 -16px 18px;
    background:var(--ui-bar-bg);backdrop-filter:blur(10px);-webkit-backdrop-filter:blur(10px);border-bottom:1px solid var(--ui-line)}
.ui-bar h1{font-size:1.2rem;font-weight:700;letter-spacing:-.01em;margin:0;line-height:1.3}
.ui-bar p{margin:3px 0 0;font-size:.82rem;color:var(--ui-mute);max-width:64ch;line-height:1.5}
.ui-act{display:flex;align-items:center;gap:12px}
.ui-status{font-size:.82rem;color:var(--ui-mute);white-space:nowrap}
 
/* tombol */
.btn{display:inline-flex;align-items:center;justify-content:center;gap:8px;padding:9px 16px;border-radius:10px;
    font-size:.875rem;font-weight:600;line-height:1.2;cursor:pointer;border:1px solid transparent;transition:background .15s,border-color .15s,box-shadow .15s}
.btn:focus-visible,.btn-icon:focus-visible,.inp:focus-visible,.sel:focus-visible,.tab-btn:focus-visible{outline:2px solid var(--ui-accent);outline-offset:2px}
.btn:disabled{opacity:.6;cursor:not-allowed}
.btn-primary{background:var(--ui-accent);color:#fff;box-shadow:0 1px 2px var(--ui-shadow)}
.btn-primary:hover:not(:disabled){background:var(--ui-accent-d)}
.btn-ghost{background:var(--ui-surface);border-color:var(--ui-line);color:var(--ui-ink)}
.btn-ghost:hover{background:var(--ui-hover);border-color:var(--ui-line-2)}
.btn-ok{background:var(--ui-ok);color:#fff}
.btn-ok:hover{filter:brightness(1.07)}
.btn kbd{display:none;padding:1px 6px;border-radius:5px;font:600 10px ui-monospace,monospace;background:rgba(255,255,255,.2);border:1px solid rgba(255,255,255,.3)}
.btn-icon{display:inline-flex;align-items:center;justify-content:center;width:30px;height:30px;border-radius:8px;
    border:1px solid var(--ui-line);background:var(--ui-surface);color:var(--ui-mute);font-size:11px;cursor:pointer;transition:all .15s}
.btn-icon:hover{background:var(--ui-bg);color:var(--ui-ink)}
.btn-del{color:var(--ui-bad);border-color:var(--ui-bad-line)}
.btn-del:hover{background:var(--ui-bad-soft);color:var(--ui-bad)}
 
/* input */
.inp,.sel{width:100%;min-width:0;padding:8px 10px;border:1px solid var(--ui-line);border-radius:9px;background:var(--ui-surface);color:var(--ui-ink);font-size:.875rem;transition:border-color .15s,box-shadow .15s}
.inp:focus,.sel:focus{border-color:var(--ui-accent);box-shadow:0 0 0 3px var(--ui-accent-soft);outline:none}
.inp[readonly]{background:var(--ui-bg);color:var(--ui-mute)}
.inp.num{text-align:right;font-variant-numeric:tabular-nums}
.inp.mono{font-family:ui-monospace,SFMono-Regular,Menlo,monospace;font-size:.78rem}
.lbl{display:none;font-size:.75rem;color:var(--ui-mute);margin-bottom:4px}
.lbl2{font-size:.78rem;color:var(--ui-mute);margin-bottom:4px}
 
/* saklar & centang */
.sw{display:inline-flex;align-items:center;gap:8px;cursor:pointer;user-select:none}
.sw input{position:absolute;opacity:0;width:0;height:0}
.sw i{position:relative;width:36px;height:20px;border-radius:999px;background:var(--ui-track);transition:background .15s;flex-shrink:0}
.sw i::after{content:"";position:absolute;top:2px;left:2px;width:16px;height:16px;border-radius:50%;background:#fff;box-shadow:0 1px 2px rgba(0,0,0,.25);transition:transform .15s}
.sw input:checked+i{background:var(--ui-accent)}
.sw input:checked+i::after{transform:translateX(16px)}
.sw input:focus-visible+i{outline:2px solid var(--ui-accent);outline-offset:2px}
.chk{display:inline-flex;align-items:center;gap:6px;cursor:pointer}
.chk input{width:16px;height:16px;accent-color:var(--ui-accent)}
 
/* kartu & baris berbasis grid (jadi kartu bertumpuk di layar kecil) */
.card{background:var(--ui-surface);border:1px solid var(--ui-line);border-radius:14px;box-shadow:0 1px 2px var(--ui-shadow);overflow:hidden}
.grid-head{display:grid;gap:10px;align-items:center;padding:10px 14px;background:var(--ui-bg);border-bottom:1px solid var(--ui-line);
    font-size:.78rem;font-weight:600;color:var(--ui-mute)}
.row{display:grid;gap:10px;align-items:center;padding:10px 14px;border-bottom:1px solid var(--ui-line);transition:background .15s,opacity .15s}
.row:last-child{border-bottom:0}
.row:hover{background:var(--ui-hover)}
.row.off{opacity:.55}
.row.invalid{background:var(--ui-bad-soft)}
.mv{display:inline-flex;gap:4px}
.err-txt{grid-column:1/-1;font-size:.75rem;color:var(--ui-bad)}
.err-txt:empty{display:none}
.ui-empty{padding:28px 16px;text-align:center;color:var(--ui-mute)}
.total-bar{display:flex;justify-content:space-between;align-items:center;padding:14px;background:var(--ui-accent-soft);font-weight:700}
.total-bar span:last-child{font-size:1.05rem;font-variant-numeric:tabular-nums}
.ui-alert{padding:12px 14px;border-radius:10px;border:1px solid var(--ui-bad-line);background:var(--ui-bad-soft);color:var(--ui-bad-text);font-size:.85rem;margin-bottom:14px}
.ui-note{font-size:.78rem;color:var(--ui-mute);line-height:1.5}
.tbl-wrap{overflow-x:auto;border:1px solid var(--ui-line);border-radius:12px;background:var(--ui-surface)}
.sim-tbl{width:100%;border-collapse:collapse;font-size:.85rem;min-width:560px}
.sim-tbl th{background:var(--ui-bg);text-align:left;font-weight:600;color:var(--ui-mute)}
.sim-tbl th,.sim-tbl td{padding:8px 10px;border-bottom:1px solid var(--ui-line)}
.sim-tbl td.r,.sim-tbl th.r{text-align:right;font-variant-numeric:tabular-nums}
.g3{display:grid;grid-template-columns:repeat(3,1fr);gap:12px}
 
/* toast */
#toastWrap{position:fixed;bottom:20px;right:20px;left:20px;z-index:99999;display:flex;flex-direction:column;align-items:flex-end;gap:10px;pointer-events:none}
.toast{display:flex;align-items:center;gap:10px;padding:12px 16px 12px 12px;min-width:240px;max-width:min(380px,100%);background:#0f172a;color:#f8fafc;
    font-size:.85rem;border-radius:12px;box-shadow:0 12px 36px rgba(0,0,0,.25);opacity:0;transform:translateY(12px);
    transition:opacity .25s,transform .25s;pointer-events:auto;position:relative;overflow:hidden}
.toast.show{opacity:1;transform:none}
.toast-icon{width:22px;height:22px;border-radius:50%;display:flex;align-items:center;justify-content:center;font-size:12px;font-weight:700;flex-shrink:0}
.toast.success .toast-icon{background:#22c55e}.toast.error .toast-icon{background:#ef4444}.toast.info .toast-icon{background:#3b82f6}
.toast-bar{position:absolute;left:0;bottom:0;height:2px;background:rgba(255,255,255,.35);animation:toastBar linear forwards}
@keyframes toastBar{from{width:100%}to{width:0}}
 
/* modal konfirmasi */
#confirmBackdrop{position:fixed;inset:0;z-index:99998;background:rgba(15,23,42,.5);backdrop-filter:blur(4px);display:flex;align-items:center;justify-content:center;padding:20px;opacity:0;pointer-events:none;transition:opacity .2s}
#confirmBackdrop.open{opacity:1;pointer-events:all}
.confirm-box{background:var(--ui-surface);color:var(--ui-ink);border-radius:16px;box-shadow:0 20px 60px rgba(0,0,0,.3);width:100%;max-width:440px;padding:22px;transform:translateY(12px) scale(.97);transition:transform .2s}
#confirmBackdrop.open .confirm-box{transform:none}
.confirm-head{display:flex;gap:12px;margin-bottom:18px}
.confirm-icon{width:38px;height:38px;border-radius:10px;background:var(--ui-accent-soft);color:var(--ui-accent);display:flex;align-items:center;justify-content:center;font-size:18px;flex-shrink:0}
.confirm-icon.danger{background:var(--ui-bad-soft);color:var(--ui-bad)}
.confirm-title{font-size:1rem;font-weight:700;margin:0 0 4px}
.confirm-msg{font-size:.85rem;color:var(--ui-mute);margin:0;line-height:1.55;white-space:pre-line;max-height:40vh;overflow:auto}
.confirm-actions{display:flex;justify-content:flex-end;gap:10px}
.cf-ok.danger{background:var(--ui-bad)}
 
@media (min-width:768px){
    .btn kbd{display:inline-block}
    #toastWrap{left:auto}
    .ui-page{padding-bottom:40px}
}
@media (max-width:767px){
    .ui-bar{position:static;margin-bottom:12px}
    .ui-act{width:100%}
    .ui-act .btn-primary{flex:1}
    .lbl{display:block}
    .g3{grid-template-columns:1fr}
    /* tombol simpan melayang di bawah pada layar kecil */
    .ui-act.float{position:fixed;left:0;right:0;bottom:0;z-index:40;padding:10px 16px calc(10px + env(safe-area-inset-bottom));
        background:var(--ui-bar-bg);backdrop-filter:blur(10px);border-top:1px solid var(--ui-line);width:auto}
    #toastWrap{bottom:84px}
}
@media (prefers-reduced-motion:reduce){*{transition:none!important;animation:none!important}}
</style>
 
<div id="toastWrap" aria-live="polite"></div>
 
<div id="confirmBackdrop" role="dialog" aria-modal="true">
    <div class="confirm-box">
        <div class="confirm-head">
            <div class="confirm-icon" id="cfIcon">?</div>
            <div>
                <h3 class="confirm-title" id="cfTitle">Konfirmasi</h3>
                <p class="confirm-msg" id="cfMsg"></p>
            </div>
        </div>
        <div class="confirm-actions">
            <button type="button" class="btn btn-ghost" id="cfCancel">Batal</button>
            <button type="button" class="btn btn-primary cf-ok" id="cfOk">Ya, lanjutkan</button>
        </div>
    </div>
</div>
 
<script>
/* ---------- helper bersama ---------- */
const q        = (el, s) => el.querySelector(s);
const parseID  = v => Number(String(v ?? '').replace(/[^\d\-]/g, '')) || 0;
const formatID = v => Math.floor(v || 0).toString().replace(/\B(?=(\d{3})+(?!\d))/g, '.');
const esc      = s => String(s ?? '').replace(/&/g,'&amp;').replace(/"/g,'&quot;').replace(/</g,'&lt;');
const norm     = s => String(s).toUpperCase().replace(/[^A-Z0-9]+/g, '_').replace(/^_+|_+$/g, '');
const slug     = s => String(s).toLowerCase().replace(/[^a-z0-9]+/g, '_').replace(/^_+|_+$/g, '');
 
/* geser baris/kartu naik-turun; elemen yang digeser bertanda data-row */
function geser(btn, arah) {
    const el = btn.closest('[data-row]');
    const t  = arah < 0 ? el.previousElementSibling : el.nextElementSibling;
    if (!t || !t.hasAttribute('data-row')) return;
    arah < 0 ? t.before(el) : t.after(el);
}
 
function showToast(message, type = 'success', duration = 3000) {
    const el = document.createElement('div');
    el.className = 'toast ' + type;
    el.innerHTML = `<div class="toast-icon">${type === 'success' ? '✓' : type === 'error' ? '✕' : 'i'}</div>
        <div>${esc(message)}</div><div class="toast-bar" style="animation-duration:${duration}ms"></div>`;
    document.getElementById('toastWrap').appendChild(el);
    requestAnimationFrame(() => el.classList.add('show'));
    const remove = () => { el.classList.remove('show'); setTimeout(() => el.remove(), 300); };
    const timer = setTimeout(remove, duration);
    el.addEventListener('click', () => { clearTimeout(timer); remove(); });
}
 
function showConfirm({ title = 'Konfirmasi', message = '', okText = 'Ya, lanjutkan', cancelText = 'Batal', danger = false } = {}) {
    return new Promise(resolve => {
        const bd = document.getElementById('confirmBackdrop'), ok = document.getElementById('cfOk'), no = document.getElementById('cfCancel');
        document.getElementById('cfTitle').textContent = title;
        document.getElementById('cfMsg').textContent   = message;
        ok.textContent = okText; no.textContent = cancelText;
        const icon = document.getElementById('cfIcon');
        icon.textContent = danger ? '!' : '?';
        icon.className   = 'confirm-icon' + (danger ? ' danger' : '');
        ok.className     = 'btn btn-primary cf-ok' + (danger ? ' danger' : '');
        bd.classList.add('open');
 
        const done = v => {
            bd.classList.remove('open');
            ok.onclick = no.onclick = bd.onclick = null;
            document.removeEventListener('keydown', onKey);
            resolve(v);
        };
        const onKey = e => { if (e.key === 'Escape') done(false); if (e.key === 'Enter') done(true); };
        ok.onclick = () => done(true);
        no.onclick = () => done(false);
        bd.onclick = e => { if (e.target === bd) done(false); };
        document.addEventListener('keydown', onKey);
        setTimeout(() => ok.focus(), 100);
    });
}
 
/* Ctrl+S / Ctrl+Enter untuk menyimpan */
function bindSave(fn, boleh = true) {
    document.addEventListener('keydown', e => {
        if (!((e.ctrlKey || e.metaKey) && ['s', 'S', 'Enter'].includes(e.key))) return;
        e.preventDefault();
        if (document.getElementById('confirmBackdrop').classList.contains('open')) return;
        if (!boleh) { showToast('Anda tidak memiliki izin untuk mengubah pengaturan ini.', 'info'); return; }
        fn();
    });
}
 
/* pindahkan modal ke <body> agar tidak terpotong container layout */
document.addEventListener('DOMContentLoaded', () => {
    const el = document.getElementById('confirmBackdrop');
    if (el) document.body.appendChild(el);
});
</script>