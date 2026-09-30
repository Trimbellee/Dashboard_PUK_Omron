@extends('layouts.app')
@section('title', 'Pengajuan | PUK OMRON')
@section('content')

{{-- ── SUCCESS ALERT ────────────────────────────────────────── --}}
@if(session('success'))
<div id="pq-success-alert" style="background:#F0FDF4;border:1px solid #BBF7D0;color:#166534;padding:12px 16px;border-radius:10px;font-size:13.5px;margin-bottom:16px;text-align:center;">
    {{ session('success') }}
</div>
<script>setTimeout(()=>{const el=document.getElementById('pq-success-alert');if(el){el.style.transition='opacity 0.5s';el.style.opacity='0';setTimeout(()=>el.remove(),500);}},2000);</script>
@endif

{{-- ── PAGE HEADER ──────────────────────────────────────────── --}}
<div style="display:flex;flex-wrap:wrap;align-items:center;justify-content:space-between;gap:12px;margin-bottom:20px;">
    <h1 style="font-size:20px;font-weight:600;color:var(--text-primary);margin:0;">Data Pengajuan Dana</h1>
    <button id="btnAdd" class="btn btn-primary" type="button">＋ Tambah Pengajuan</button>
</div>

{{-- ── FILTER BAR ───────────────────────────────────────────── --}}
<div style="background:var(--bg-surface);border:1px solid var(--border);border-radius:14px;padding:16px 20px;margin-bottom:16px;box-shadow:var(--shadow-sm);">
    <div style="display:flex;flex-wrap:wrap;align-items:center;justify-content:space-between;gap:12px;">
        <div style="display:flex;flex-wrap:wrap;gap:10px;align-items:center;">

            {{-- Search --}}
            <div style="position:relative;">
                <input type="text" id="searchInput" placeholder="Cari nama / tujuan..." autocomplete="off"
                    class="form-input" style="width:220px;padding-left:34px;">
                <span style="position:absolute;left:10px;top:50%;transform:translateY(-50%);color:var(--text-muted);font-size:14px;pointer-events:none;">🔍</span>
            </div>

            {{-- Status --}}
            <select id="statusFilter" class="form-input" style='width:auto;cursor:pointer;
                    background-image:url("data:image/svg+xml,%3Csvg xmlns=%27http://www.w3.org/2000/svg%27 width=%2716%27 height=%2716%27 viewBox=%270 0 24 24%27 fill=%27none%27 stroke=%27%239CA3AF%27 stroke-width=%272%27%3E%3Cpolyline points=%276 9 12 15 18 9%27/%3E%3C/svg%3E");
                    background-repeat:no-repeat;
                    background-position:right 8px center;
                    padding-right:30px;'>
                <option value="">Semua Status</option>
                <option value="Menunggu">Menunggu</option>
                <option value="Disetujui">Disetujui</option>
                <option value="Ditolak">Ditolak</option>
            </select>

            {{-- Bidang --}}
            <select id="bidangFilter" class="form-input" style='width:auto;cursor:pointer;
                    background-image:url("data:image/svg+xml,%3Csvg xmlns=%27http://www.w3.org/2000/svg%27 width=%2716%27 height=%2716%27 viewBox=%270 0 24 24%27 fill=%27none%27 stroke=%27%239CA3AF%27 stroke-width=%272%27%3E%3Cpolyline points=%276 9 12 15 18 9%27/%3E%3C/svg%3E");
                    background-repeat:no-repeat;
                    background-position:right 8px center;
                    padding-right:30px;'>
                <option value="" disabled selected>Alokasi Dana</option>
                <option value="">Semua Bidang</option>
                @foreach($bidangList ?? [] as $bidang)
                    <option value="{{ $bidang }}">{{ $bidang }}</option>
                @endforeach
            </select>

        </div>
        <div style="font-size:12px;color:var(--text-muted);">
            Total: <strong id="totalData" style="color:var(--text-primary);">0</strong>
        </div>
    </div>
</div>

{{-- ── EXPORT BAR ───────────────────────────────────────────── --}}
@if(in_array(session('user')['role'], ['Ketua', 'Sekretaris', 'Bendahara']))
<form action="{{ route('pengajuan.export') }}" method="GET"
    style="background:var(--bg-surface);border:1px solid var(--border);border-radius:14px;padding:16px 20px;margin-bottom:20px;box-shadow:var(--shadow-sm);">
    <div style="display:flex;flex-wrap:wrap;align-items:flex-end;gap:14px;">

        <div style="flex:1;min-width:140px;">
            <label class="form-label">Bulan</label>
            <select name="bulan" class="form-input"
                    style='cursor:pointer;
                    background-image:url("data:image/svg+xml,%3Csvg xmlns=%27http://www.w3.org/2000/svg%27 width=%2716%27 height=%2716%27 viewBox=%270 0 24 24%27 fill=%27none%27 stroke=%27%239CA3AF%27 stroke-width=%272%27%3E%3Cpolyline points=%276 9 12 15 18 9%27/%3E%3C/svg%3E");
                    background-repeat:no-repeat;
                    background-position:right 10px center;
                    padding-right:32px;'>
                <option value="">Semua Bulan</option>
                @for($i = 1; $i <= 12; $i++)
                    <option value="{{ $i }}">{{ DateTime::createFromFormat('!m', $i)->format('F') }}</option>
                @endfor
            </select>
        </div>

        <div style="flex:1;min-width:140px;">
            <label class="form-label">Tahun</label>
            <input list="tahunList" name="tahun" id="tahunInput" maxlength="4"
                placeholder="{{ date('Y') }}" autocomplete="off" class="form-input">
            <datalist id="tahunList">
                @php $thisYear = (int) date('Y'); @endphp
                @for($t = $thisYear - 1; $t <= $thisYear + 4; $t++)
                    <option value="{{ $t }}">
                @endfor
            </datalist>
        </div>

        <button type="submit" class="btn btn-primary" style="background:#16A34A;border-color:#16A34A;gap:6px;">
            <svg xmlns="http://www.w3.org/2000/svg" style="width:14px;height:14px;" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                <path stroke-linecap="round" stroke-linejoin="round" d="M12 4v12m0 0l-3-3m3 3l3-3M4 20h16"/>
            </svg>
            Export XLSX
        </button>
    </div>
</form>
@endif

{{-- ── TABLE ────────────────────────────────────────────────── --}}
<div style="background:var(--bg-surface);border:1px solid var(--border);border-radius:14px;overflow:hidden;box-shadow:var(--shadow-sm);max-height:75vh;overflow-y:auto;overflow-x:auto;">
    <table style="width:100%;border-collapse:collapse;font-size:13px;min-width:900px;">
        <thead style="position:sticky;top:0;z-index:10;background:var(--bg-elevated);">
            <tr style="border-bottom:1px solid var(--border);">
                @foreach(['No','Nama','Alokasi Dana','Tujuan','Keperluan','Biaya','Proposal','Aktual Biaya','Sisa/Kurang','Laporan','Status','Catatan','Approver','Tanggal','Aksi'] as $h)
                <th style="padding:10px 12px;font-size:11px;font-weight:600;letter-spacing:0.05em;text-transform:uppercase;color:var(--text-muted);white-space:nowrap;text-align:center;">{{ $h }}</th>
                @endforeach
            </tr>
        </thead>
        <tbody id="dataBody"></tbody>
    </table>
</div>

{{-- ── PAGINATION ───────────────────────────────────────────── --}}
<div style="display:flex;flex-wrap:wrap;align-items:center;justify-content:space-between;gap:12px;margin-top:16px;">
    <span id="statusInfo" style="font-size:12.5px;color:var(--text-muted);">Memuat data...</span>

    <div style="display:flex;align-items:center;gap:10px;">
        <div style="display:flex;align-items:center;gap:4px;">
            <button id="prevPage" class="btn btn-ghost" style="padding:6px 12px;min-width:auto;" type="button">← Prev</button>
            <div id="pageButtons" style="display:flex;gap:4px;"></div>
            <button id="nextPage" class="btn btn-ghost" style="padding:6px 12px;min-width:auto;" type="button">Next →</button>
        </div>
    </div>
    <div style="display:flex;align-items:center;gap:6px;font-size:12.5px;color:var(--text-secondary);">
            Per halaman:
            <select id="perPage" class="form-input" style='width:auto;
                    padding:5px 18px 5px 8px;
                    font-size:12.5px;
                    cursor:pointer;
                    background-image:url("data:image/svg+xml,%3Csvg xmlns=%27http://www.w3.org/2000/svg%27 width=%2714%27 height=%2714%27 viewBox=%270 0 24 24%27 fill=%27none%27 stroke=%27%239CA3AF%27 stroke-width=%272%27%3E%3Cpolyline points=%276 9 12 15 18 9%27/%3E%3C/svg%3E");
                    background-repeat:no-repeat;
                    background-position:right 6px center;'>
                <option value="5">5</option>
                <option value="10" selected>10</option>
                <option value="20">20</option>
                <option value="50">50</option>
            </select>
        </div>
</div>

{{-- ═══════════════════════════════════════════════════════════
     MODALS
════════════════════════════════════════════════════════════ --}}

{{-- Modal Tambah --}}
<div id="modalAdd" class="modal-backdrop">
    <div class="modal-box" style="max-width:480px;max-height:85vh;display:flex;flex-direction:column;" onclick="event.stopPropagation()">
        <div class="modal-header">
            <h2 class="modal-title">Tambah Pengajuan</h2>
            <button class="modal-close" id="closeAdd">✕</button>
        </div>
        <div style="overflow-y:auto;flex:1;padding-bottom:4px;">
            <form id="formAdd" enctype="multipart/form-data" novalidate>
                @csrf
                <input type="hidden" id="addNama" name="Nama" value="{{ session('user.username') }}">

                <div class="form-group">
                    <label class="form-label">Alokasi Dana</label>
                    <select id="addBidang" name="Bidang" class="form-input" style="cursor:pointer;" required>
                        <option value="">-- Pilih --</option>
                        @foreach($bidangList as $opt)
                            <option value="{{ $opt }}">{{ $opt }}</option>
                        @endforeach
                    </select>
                </div>

                <div class="form-group">
                    <label class="form-label">Tujuan</label>
                    <input type="text" id="addTujuan" name="Tujuan" class="form-input" required autocomplete="off">
                </div>

                <div class="form-group">
                    <label class="form-label">Keperluan</label>
                    <textarea id="addKeperluan" name="Keperluan" rows="3" class="form-input" style="resize:vertical;line-height:1.6;" required></textarea>
                </div>

                <div class="form-group">
                    <label class="form-label">Biaya</label>
                    <input type="text" id="addBiaya" name="Biaya" inputmode="numeric" class="form-input" required autocomplete="off">
                </div>

                <div class="form-group">
                    <label class="form-label">Lampiran Proposal <span style="color:var(--text-muted);font-weight:400;">(PDF, maks. 5MB)</span></label>
                    <input type="file" id="addProposalFile" name="LampiranProposal" accept="application/pdf"
                        style="width:100%;font-size:13px;color:var(--text-secondary);">
                </div>

                <div class="form-actions">
                    <button type="button" id="cancelAdd" class="btn btn-ghost">Batal</button>
                    <button type="submit" id="submitAdd" class="btn btn-primary">
                        <span id="submitAddText">Simpan</span>
                        <div id="submitAddSpinner" class="spinner" style="display:none;"></div>
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

{{-- Modal Upload Laporan --}}
<div id="modalUploadLaporan" class="modal-backdrop">
    <div class="modal-box" style="max-width:440px;" onclick="event.stopPropagation()">
        <div class="modal-header">
            <h2 class="modal-title">Upload Laporan Kegiatan</h2>
            <button class="modal-close" id="closeUploadLaporan">✕</button>
        </div>
        <input type="hidden" id="laporanTargetId">
        <div class="form-group">
            <label class="form-label">Dana Pengajuan</label>
            <input type="text" id="laporanBiayaPengajuan" class="form-input" readonly>
        </div>
        <div class="form-group">
            <label class="form-label">Aktual Biaya</label>
            <input type="text" id="laporanAktualBiaya" inputmode="numeric" class="form-input input-aktual" placeholder="Masukkan aktual biaya" autocomplete="off">
            <div id="laporanSelisihBox" style="margin-top:8px;font-size:13px;display:none;"></div>
        </div>
        <div class="form-group">
            <label class="form-label">Upload Laporan <span style="color:var(--text-muted);font-weight:400;">(PDF, maks. 5MB)</span></label>
            <input type="file" id="laporanFileInput" accept="application/pdf" style="font-size:13px;color:var(--text-secondary);">
        </div>
        <div class="form-actions">
            <button id="cancelUploadLaporan" type="button" class="btn btn-ghost">Batal</button>
            <button id="confirmUploadLaporan" type="button" class="btn btn-primary">
                <span id="uploadLaporanText">Upload</span>
                <div id="uploadLaporanSpinner" class="spinner" style="display:none;"></div>
            </button>
        </div>
    </div>
</div>

{{-- Modal Catatan Approve/Reject --}}
<div id="modalCatatan" class="modal-backdrop">
    <div class="modal-box" style="max-width:420px;" onclick="event.stopPropagation()">
        <div class="modal-header">
            <h2 class="modal-title" id="modalCatatanTitle">Catatan</h2>
            <button class="modal-close" id="closeCatatan">✕</button>
        </div>
        <div class="form-group">
            <label class="form-label">Catatan</label>
            <textarea id="inputCatatan" rows="4" class="form-input" style="resize:vertical;line-height:1.6;" placeholder="Tulis alasan..."></textarea>
        </div>
        <div class="form-actions">
            <button id="cancelCatatan" type="button" class="btn btn-ghost">Batal</button>
            <button id="confirmCatatan" type="button" class="btn btn-primary">
                <span id="catatanText">Kirim</span>
                <div id="catatanSpinner" class="spinner" style="display:none;"></div>
            </button>
        </div>
    </div>
</div>

{{-- Modal Edit Pengajuan --}}
<div id="modalEditPengajuan" class="modal-backdrop">
    <div class="modal-box" style="max-width:480px;max-height:85vh;display:flex;flex-direction:column;" onclick="event.stopPropagation()">
        <div class="modal-header">
            <h2 class="modal-title">Edit Pengajuan</h2>
            <button class="modal-close" id="closeEditPengajuan">✕</button>
        </div>
        <div style="overflow-y:auto;flex:1;">
            <input type="hidden" id="editID">
            <div class="form-group">
                <label class="form-label">Nama</label>
                <input id="editNama" class="form-input" disabled style="opacity:0.6;cursor:not-allowed;">
            </div>
            <div class="form-group">
                <label class="form-label">Alokasi Dana</label>
                <select id="editBidang" class="form-input" style="cursor:pointer;" required>
                    @foreach($bidangList as $opt)
                        <option value="{{ $opt }}">{{ $opt }}</option>
                    @endforeach
                </select>
            </div>
            <div class="form-group">
                <label class="form-label">Tujuan</label>
                <input type="text" id="editTujuan" class="form-input" required autocomplete="off">
            </div>
            <div class="form-group">
                <label class="form-label">Keperluan</label>
                <textarea id="editKeperluan" rows="3" class="form-input" style="resize:vertical;line-height:1.6;" required></textarea>
            </div>
            <div class="form-group">
                <label class="form-label">Biaya</label>
                <input type="text" id="editBiaya" inputmode="numeric" class="form-input" required autocomplete="off">
            </div>
            <div class="form-group">
                <label class="form-label">Ganti Proposal <span style="color:var(--text-muted);font-weight:400;">(opsional)</span></label>
                <input type="file" id="editProposal" accept="application/pdf" style="font-size:13px;color:var(--text-secondary);">
            </div>
            <div class="form-actions">
                <button id="cancelEditPengajuan" type="button" class="btn btn-ghost">Batal</button>
                <button id="saveEditPengajuan" type="button" class="btn btn-primary">
                    <span id="editSaveText">Simpan</span>
                    <div id="editSaveSpinner" class="spinner" style="display:none;"></div>
                </button>
            </div>
        </div>
    </div>
</div>

{{-- Modal Keperluan Detail --}}
<div id="modalKeperluan" class="modal-backdrop">
    <div class="modal-box" style="max-width:460px;" onclick="event.stopPropagation()">
        <div class="modal-header">
            <h2 class="modal-title">Detail Keperluan</h2>
            <button class="modal-close" id="closeModalKeperluan">✕</button>
        </div>
        <div id="modalKeperluanContent" style="font-size:13.5px;color:var(--text-secondary);white-space:pre-line;word-break:break-word;max-height:55vh;overflow-y:auto;line-height:1.7;"></div>
        <div class="form-actions">
            <button id="closeModalKeperluanBtn" class="btn btn-primary">Tutup</button>
        </div>
    </div>
</div>

{{-- Modal Konfirmasi Hapus --}}
<div id="modalConfirmDelete" class="modal-backdrop">
    <div class="modal-box" style="max-width:380px;text-align:center;" onclick="event.stopPropagation()">
        <div style="width:48px;height:48px;border-radius:12px;background:#FEF2F2;display:flex;align-items:center;justify-content:center;font-size:22px;margin:0 auto 12px;">⚠️</div>
        <h2 style="font-size:16px;font-weight:600;color:var(--text-primary);margin:0 0 6px;">Hapus Pengajuan?</h2>
        <p style="font-size:13px;color:var(--text-secondary);margin:0 0 20px;">Pengajuan akan dihapus secara permanen dan tidak dapat dikembalikan.</p>
        <div class="form-actions" style="justify-content:center;">
            <button id="cancelDeleteBtn" type="button" class="btn btn-ghost">Batal</button>
            <button id="confirmDeleteBtn" type="button" class="btn btn-primary" style="background:#DC2626;border-color:#DC2626;">
                <span id="deleteText">Ya, Hapus</span>
                <div id="deleteSpinner" class="spinner" style="display:none;"></div>
            </button>
        </div>
    </div>
</div>

{{-- Modal Pilih Jenis Laporan --}}
<div id="modalPilihLaporan" class="modal-backdrop">
    <div class="modal-box" style="max-width:320px;text-align:center;" onclick="event.stopPropagation()">
        <div class="modal-header">
            <h2 class="modal-title">Pilih Jenis Laporan</h2>
            <button class="modal-close" id="closePilihLaporan">✕</button>
        </div>
        <div style="display:flex;flex-direction:column;gap:10px;">
            <button id="btnInputManual" class="btn btn-primary" style="justify-content:center;">📝 Input Manual</button>
            <button id="btnUploadPDF" class="btn btn-ghost" style="justify-content:center;border-color:var(--accent);color:var(--accent);">📄 Upload PDF</button>
        </div>
    </div>
</div>

{{-- Toast --}}
<div id="pengajuan-toast" style="position:fixed;bottom:70px;right:24px;z-index:99999;padding:11px 16px;border-radius:11px;font-size:13px;color:#fff;background:#111827;box-shadow:var(--shadow-lg);opacity:0;transform:translateY(8px);pointer-events:none;transition:opacity 0.25s,transform 0.25s;max-width:300px;display:flex;align-items:center;gap:8px;"></div>

<style>
/* Table rows */
#dataBody tr { border-bottom:1px solid var(--border);transition:background 0.1s; }
#dataBody tr:hover td { background:var(--bg-elevated); }
#dataBody td { padding:10px 12px;font-size:13px;color:var(--text-secondary);vertical-align:top; }

/* Status badges */
.pq-badge { display:inline-flex;align-items:center;padding:3px 9px;border-radius:6px;font-size:11.5px;font-weight:500;white-space:nowrap; }
.pq-badge-menunggu  { background:#FFFBEB;color:#92400E; }
.pq-badge-disetujui { background:#F0FDF4;color:#166534; }
.pq-badge-ditolak   { background:#FEF2F2;color:#991B1B; }

/* Aksi buttons */
.pq-btn { display:inline-flex;align-items:center;justify-content:center;padding:4px 10px;border-radius:7px;font-size:12px;font-weight:500;cursor:pointer;border:none;transition:all 0.15s;font-family:inherit;white-space:nowrap; }
.pq-btn-edit    { background:var(--accent-muted);color:var(--accent-text); }
.pq-btn-approve { background:#F0FDF4;color:#166534; }
.pq-btn-reject  { background:#FEF2F2;color:#991B1B; }
.pq-btn-delete  { background:var(--bg-elevated);color:var(--text-muted);border:1px solid var(--border); }
.pq-btn:hover   { filter:brightness(0.95); }
.pq-btn:disabled { opacity:0.55;cursor:not-allowed; }

/* Pagination */
#pageButtons button { display:inline-flex;align-items:center;justify-content:center;width:32px;height:32px;border-radius:8px;border:1px solid var(--border);background:transparent;color:var(--text-secondary);font-size:13px;cursor:pointer;transition:all 0.15s;font-family:inherit; }
#pageButtons button:hover { background:var(--bg-elevated);color:var(--text-primary); }
#pageButtons button.active { background:var(--accent);color:#fff;border-color:var(--accent); }
#prevPage:disabled, #nextPage:disabled { opacity:0.4;cursor:not-allowed; }
</style>

<script>
(function () {
'use strict';

const USER_ROLE = @json(session('user.role'));
const USERNAME  = @json(session('user.username'));
const DATA_URL  = "{{ url('pengajuan/data') }}";

let currentPage  = 1;
let totalPages   = 1;
let perPage      = 10;
let tableData    = [];
let deleteTargetId      = null;
let laporanModeTargetId = null;
let currentAction = '';
let currentId     = '';
let abortCtrl     = null;

/* ── HELPERS ─────────────────────────────── */
function esc(str) {
    if (str === null || str === undefined) return '';
    return String(str).replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;').replace(/"/g,'&quot;').replace(/'/g,'&#039;');
}
function formatRibuan(val) {
    if (!val && val !== 0) return '';
    return String(val).replace(/\D/g,'').replace(/\B(?=(\d{3})+(?!\d))/g,'.');
}
function parseRibuan(val) { return String(val||'').replace(/\./g,''); }
function applyRibuan(input) {
    const from = input.value.length - input.selectionStart;
    input.value = formatRibuan(input.value);
    const pos = input.value.length - from;
    input.setSelectionRange(pos, pos);
}
function truncate(text, limit=40) {
    if (!text) return '';
    const s = String(text);
    return s.length > limit ? s.slice(0, limit)+'…' : s;
}
function formatTanggal(dateStr) {
    if (!dateStr) return '-';
    const d = new Date(dateStr);
    if (isNaN(d)) return esc(dateStr);
    const bln = ['Jan','Feb','Mar','Apr','Mei','Jun','Jul','Agu','Sep','Okt','Nov','Des'];
    return `<div style="text-align:center;line-height:1.4;">
        <div>${String(d.getDate()).padStart(2,'0')} ${bln[d.getMonth()]} ${d.getFullYear()}</div>
        <div style="font-size:11px;color:var(--text-muted);">${d.toLocaleTimeString('id-ID',{hour12:false})}</div>
    </div>`;
}
function formatTglSimple(dateStr) {
    if (!dateStr) return '';
    const d = new Date(dateStr);
    return isNaN(d) ? '' : d.toLocaleDateString('id-ID',{day:'2-digit',month:'short',year:'numeric'});
}

/* ── TOAST ───────────────────────────────── */
const pqToast = document.getElementById('pengajuan-toast');
function showPqToast(msg, ok = true) {
    pqToast.textContent = msg;
    pqToast.style.background = ok ? '#111827' : '#7F1D1D';
    pqToast.style.opacity = '1';
    pqToast.style.transform = 'translateY(0)';
    clearTimeout(pqToast._t);
    pqToast._t = setTimeout(() => {
        pqToast.style.opacity = '0';
        pqToast.style.transform = 'translateY(8px)';
    }, 3000);
}

/* ── MODAL HELPERS ───────────────────────── */
function openModal(id) {
    const m = document.getElementById(id);
    if (!m) return;
    m.classList.add('open');
    document.body.style.overflow = 'hidden';
    setTimeout(() => m.querySelector('input:not([disabled]),select,textarea,button')?.focus(), 60);
}
function closeModal(id) {
    const m = document.getElementById(id);
    if (!m) return;
    m.classList.remove('open');
    document.body.style.overflow = '';
}

/* backdrop close */
['modalAdd','modalUploadLaporan','modalCatatan','modalEditPengajuan','modalKeperluan','modalConfirmDelete','modalPilihLaporan'].forEach(id => {
    document.getElementById(id)?.addEventListener('click', e => { if (e.target === document.getElementById(id)) closeModal(id); });
});
document.addEventListener('keydown', e => {
    if (e.key !== 'Escape') return;
    ['modalAdd','modalUploadLaporan','modalCatatan','modalEditPengajuan','modalKeperluan','modalConfirmDelete','modalPilihLaporan'].forEach(closeModal);
});

/* ── TABLE CELLS ─────────────────────────── */
function buildBiayaCell(row) {
    const isSetuju = String(row.Status).trim() === 'Disetujui';
    const tgl = formatTglSimple(row.TanggalTransfer);
    let html = `<div style="font-weight:500;text-align:right;color:var(--text-primary);">Rp${parseInt(row.Biaya||0).toLocaleString('id-ID')}</div>`;
    if (isSetuju) {
        const sudah = row.StatusBiaya === 'Transfer';
        html += `<div style="font-size:11.5px;margin-top:4px;color:${sudah ? '#16A34A' : '#DC2626'};">
            ${sudah ? `🟢 Sudah ditransfer<br><span style="color:var(--text-muted);">${tgl}</span>` : '🟡 Belum ditransfer'}
        </div>`;
        if (USER_ROLE === 'Bendahara' && !sudah) {
            html += `<button class="btn-transfer-dana pq-btn pq-btn-approve" style="margin-top:6px;font-size:11px;" data-id="${row.ID}">Transfer</button>`;
        }
    }
    return html;
}

function buildSisaCell(row) {
    const val = row.Dikembalikan !== null && row.Dikembalikan !== undefined ? Number(row.Dikembalikan) : null;
    if (val === null) return `<span style="color:var(--text-muted);">-</span>`;
    if (val === 0)    return `<span class="pq-badge pq-badge-menunggu">Dana Pas</span>`;
    const isPlus = val > 0;
    const abs    = Math.abs(val);
    const sudah  = row.StatusPengembalian === 'Sudah';
    const tgl    = formatTglSimple(row.TanggalPengembalian);
    const label  = isPlus
        ? `<span class="pq-badge pq-badge-disetujui">Sisa +Rp${formatRibuan(abs)}</span>`
        : `<span class="pq-badge pq-badge-ditolak">Kurang -Rp${formatRibuan(abs)}</span>`;
    const statusTxt = sudah
        ? `🟢 ${isPlus ? 'Sudah dikembalikan' : 'Sudah ditransfer'}<br><span style="color:var(--text-muted);">${tgl}</span>`
        : `🟡 ${isPlus ? 'Belum dikembalikan' : 'Belum ditransfer'}`;
    let btn = '';
    if (USER_ROLE === 'Bendahara' && !sudah) {
        const cls = isPlus ? 'btn-konfirmasi-sisa pq-btn-approve' : 'btn-konfirmasi-kurang';
        btn = `<button class="${cls} pq-btn" style="margin-top:6px;font-size:11px;" data-id="${row.ID}">${isPlus ? 'Konfirmasi Pengembalian' : 'Konfirmasi Transfer'}</button>`;
    }
    return `<div style="display:flex;flex-direction:column;align-items:center;gap:4px;">${label}<div style="font-size:11.5px;color:${sudah?'#16A34A':'#D97706'};">${statusTxt}</div>${btn}</div>`;
}

/* ── RENDER ──────────────────────────────── */
function renderTable(data, page) {
    tableData = data;
    const tbody = document.getElementById('dataBody');
    tbody.innerHTML = '';
    if (!data || !data.length) {
        tbody.innerHTML = `<tr><td colspan="15" style="text-align:center;padding:40px;color:var(--text-muted);">Belum ada data</td></tr>`;
        return;
    }
    const frag = document.createDocumentFragment();
    data.forEach((row, i) => {
        const isPembuat  = String(row.Nama||'').trim().toLowerCase() === String(USERNAME||'').trim().toLowerCase();
        const canApprove = ['Ketua','Sekretaris'].includes(USER_ROLE);
        const statusStr  = String(row.Status).trim();
        const isSetuju   = statusStr === 'Disetujui';
        const isMenunggu = statusStr === 'Menunggu';
        const laporanLocked = Boolean(row.LaporanBendaharaAda);

        const proposalHtml = row.LampiranProposal
            ? `<button class="btn-preview-proposal pq-btn pq-btn-edit" data-id="${row.ID}" style="font-size:11px;">🖇️ Lampiran</button>`
            : `<span style="color:var(--text-muted);font-size:12px;">-</span>`;

        let laporanHtml = `<span style="color:var(--text-muted);font-size:12px;">-</span>`;
        if (row.LampiranLaporan) {
            const gantiBtn = (isSetuju && isPembuat && !laporanLocked)
                ? `<button class="btn-buat-laporan pq-btn" style="font-size:10.5px;color:var(--accent);background:var(--accent-muted);margin-top:4px;" data-id="${row.ID}">Ganti</button>` : '';
            laporanHtml = `<div style="display:flex;flex-direction:column;align-items:center;gap:2px;">
                <button class="btn-preview-laporan pq-btn pq-btn-edit" data-id="${row.ID}" style="font-size:11px;">🖇️ Laporan</button>${gantiBtn}</div>`;
        } else if (isSetuju && isPembuat && !laporanLocked) {
            laporanHtml = `<button class="btn-buat-laporan pq-btn pq-btn-approve" data-id="${row.ID}" style="font-size:11px;">Buat Laporan</button>`;
        }

        const badgeMap = { Disetujui:'pq-badge-disetujui', Ditolak:'pq-badge-ditolak', Menunggu:'pq-badge-menunggu' };
        const badgeClass = badgeMap[statusStr] || 'pq-badge-menunggu';

        const aksiParts = [];
        if (isMenunggu && isPembuat)  aksiParts.push(`<button class="btn-edit pq-btn pq-btn-edit" data-id="${row.ID}">Edit</button>`);
        if (canApprove && isMenunggu) {
            aksiParts.push(`<button class="btn-approve pq-btn pq-btn-approve" data-id="${row.ID}">✓ Setuju</button>`);
            aksiParts.push(`<button class="btn-reject pq-btn pq-btn-reject" data-id="${row.ID}">✕ Tolak</button>`);
        }
        if (isMenunggu && isPembuat) aksiParts.push(`<button class="btn-delete pq-btn pq-btn-delete" data-id="${row.ID}">Hapus</button>`);

        const aktualBiaya = (row.AktualBiaya !== null && row.AktualBiaya !== undefined && row.AktualBiaya !== '')
            ? `Rp${Number(row.AktualBiaya).toLocaleString('id-ID')}` : '-';

        const tr = document.createElement('tr');
        tr.innerHTML = `
            <td style="text-align:center;">${(page-1)*perPage+(i+1)}</td>
            <td style="color:var(--text-primary);font-weight:500;">${esc(row.Nama)}</td>
            <td>${esc(row.Bidang)}</td>
            <td>${esc(row.Tujuan)}</td>
            <td style="max-width:180px;">
                <div style="word-break:break-word;">${esc(truncate(row.Keperluan||''))}</div>
                ${(row.Keperluan||'').length > 40 ? `<button class="btn-keperluan pq-btn" style="font-size:11px;color:var(--accent);margin-top:4px;background:var(--accent-muted);" data-text="${esc(row.Keperluan)}">Baca selengkapnya</button>` : ''}
            </td>
            <td>${buildBiayaCell(row)}</td>
            <td style="text-align:center;">${proposalHtml}</td>
            <td style="text-align:center;">${aktualBiaya}</td>
            <td style="text-align:center;">${buildSisaCell(row)}</td>
            <td style="text-align:center;">${laporanHtml}</td>
            <td style="text-align:center;"><span class="pq-badge ${badgeClass}">${esc(statusStr)}</span></td>
            <td style="text-align:center;font-size:12px;">${esc(row.Catatan||'-')}</td>
            <td style="text-align:center;font-size:12px;">${esc(row.Approver||'-')}</td>
            <td>${formatTanggal(row.Tanggal)}</td>
            <td style="text-align:center;"><div style="display:flex;flex-direction:column;gap:4px;align-items:center;">${aksiParts.join('')}</div></td>`;
        frag.appendChild(tr);
    });
    tbody.appendChild(frag);
}

function renderPagination() {
    const c = document.getElementById('pageButtons');
    c.innerHTML = '';
    const MAX = window.innerWidth < 640 ? 3 : 7;
    let start = Math.max(1, currentPage - Math.floor(MAX/2));
    let end   = Math.min(totalPages, start + MAX - 1);
    if (end - start < MAX - 1) start = Math.max(1, end - MAX + 1);
    const make = (label, page, active=false) => {
        const b = document.createElement('button');
        b.textContent = label;
        b.type = 'button';
        if (active) b.classList.add('active');
        if (page) b.onclick = () => { currentPage = page; loadPengajuan(); };
        return b;
    };
    const dots = () => { const s = document.createElement('span'); s.textContent = '…'; s.style.cssText = 'padding:0 4px;color:var(--text-muted);font-size:13px;'; return s; };
    if (start > 1) { c.appendChild(make(1, 1)); if (start > 2) c.appendChild(dots()); }
    for (let i = start; i <= end; i++) c.appendChild(make(i, i, i === currentPage));
    if (end < totalPages) { if (end < totalPages - 1) c.appendChild(dots()); c.appendChild(make(totalPages, totalPages)); }
    document.getElementById('prevPage').disabled = currentPage === 1;
    document.getElementById('nextPage').disabled = currentPage >= totalPages || totalPages === 0;
}

/* ── DATA FETCH ──────────────────────────── */
async function loadPengajuan() {
    if (abortCtrl) abortCtrl.abort();
    abortCtrl = new AbortController();
    const tbody = document.getElementById('dataBody');
    tbody.innerHTML = `<tr><td colspan="15" style="text-align:center;padding:40px;color:var(--text-muted);">Memuat data...</td></tr>`;
    try {
        perPage = Number(document.getElementById('perPage').value) || 10;
        const params = new URLSearchParams({
            page: currentPage, limit: perPage,
            search: document.getElementById('searchInput')?.value?.trim()||'',
            status: document.getElementById('statusFilter')?.value||'',
            bidang: document.getElementById('bidangFilter')?.value||'',
        });
        const res  = await fetch(`${DATA_URL}?${params}`, {cache:'no-store', signal:abortCtrl.signal});
        if (!res.ok) throw new Error(`HTTP ${res.status}`);
        const json = await res.json();
        if (!json.success) throw new Error(json.message||'Invalid');
        totalPages = json.totalPages || Math.max(1, Math.ceil((json.total||0)/perPage));
        renderTable(json.data||[], json.page||currentPage);
        renderPagination();
        document.getElementById('statusInfo').textContent = `Halaman ${currentPage} dari ${totalPages} • Total: ${json.total??0}`;
        document.getElementById('totalData').textContent = json.total??0;
    } catch(err) {
        if (err.name === 'AbortError') return;
        tbody.innerHTML = `<tr><td colspan="15" style="text-align:center;padding:40px;color:#DC2626;">Gagal memuat data. <button style="text-decoration:underline;cursor:pointer;background:none;border:none;color:inherit;" onclick="loadPengajuan()">Coba lagi</button></td></tr>`;
    }
}

/* ── FILTERS ─────────────────────────────── */
let searchDebounce = null;
document.getElementById('searchInput')?.addEventListener('input', () => {
    clearTimeout(searchDebounce);
    searchDebounce = setTimeout(() => { currentPage=1; loadPengajuan(); }, 300);
});
['statusFilter','bidangFilter','perPage'].forEach(id => {
    document.getElementById(id)?.addEventListener('change', () => { currentPage=1; loadPengajuan(); });
});
document.getElementById('prevPage').addEventListener('click', () => { if (currentPage>1) { currentPage--; loadPengajuan(); } });
document.getElementById('nextPage').addEventListener('click', () => { if (currentPage<totalPages) { currentPage++; loadPengajuan(); } });
document.getElementById('tahunInput')?.addEventListener('input', function() { this.value=this.value.replace(/\D/g,'').slice(0,4); });

/* ── MODAL: TAMBAH ───────────────────────── */
document.getElementById('btnAdd').onclick   = () => openModal('modalAdd');
document.getElementById('closeAdd').onclick = () => closeModal('modalAdd');
document.getElementById('cancelAdd').onclick= () => closeModal('modalAdd');

document.getElementById('formAdd').addEventListener('submit', async function(e) {
    e.preventDefault();
    const file = document.getElementById('addProposalFile').files[0];
    if (file) {
        if (file.type !== 'application/pdf') return showPqToast('File harus berformat PDF', false);
        if (file.size > 5*1024*1024)         return showPqToast('PDF maksimal 5MB', false);
    }
    const fd = new FormData(this);
    fd.set('Biaya', parseRibuan(document.getElementById('addBiaya').value));
    const btn = document.getElementById('submitAdd');
    const txt = document.getElementById('submitAddText');
    const spn = document.getElementById('submitAddSpinner');
    btn.disabled=true; txt.textContent='Menyimpan…'; spn.style.display='block';
    try {
        const res  = await fetch("{{ route('pengajuan.store') }}", {method:'POST',headers:{'X-CSRF-TOKEN':'{{ csrf_token() }}','Accept':'application/json'},body:fd});
        const json = await res.json();
        if (!json.success) return showPqToast(json.message||'Gagal menyimpan', false);
        showPqToast('Pengajuan berhasil dikirim ✅');
        closeModal('modalAdd'); this.reset(); loadPengajuan();
    } catch { showPqToast('Kesalahan jaringan', false); }
    finally  { btn.disabled=false; txt.textContent='Simpan'; spn.style.display='none'; }
});

/* ── MODAL: CATATAN ──────────────────────── */
function openCatatanModal(id, action) {
    currentAction=action; currentId=id;
    document.getElementById('modalCatatanTitle').textContent = action==='approve' ? 'Catatan Persetujuan' : 'Catatan Penolakan';
    document.getElementById('inputCatatan').value='';
    openModal('modalCatatan');
}
document.getElementById('closeCatatan').onclick  = () => closeModal('modalCatatan');
document.getElementById('cancelCatatan').onclick = () => closeModal('modalCatatan');
document.getElementById('confirmCatatan').onclick = async () => {
    const catatan = document.getElementById('inputCatatan').value.trim();
    if (!catatan) return showPqToast('Isi catatan dulu!', false);
    const btn = document.getElementById('confirmCatatan');
    const txt = document.getElementById('catatanText');
    const spn = document.getElementById('catatanSpinner');
    btn.disabled=true; txt.textContent='Mengirim…'; spn.style.display='block';
    try {
        const url = currentAction==='approve' ? "{{ route('pengajuan.approve') }}" : "{{ route('pengajuan.reject') }}";
        const res  = await fetch(url, {method:'POST',headers:{'X-CSRF-TOKEN':'{{ csrf_token() }}','Accept':'application/json','Content-Type':'application/json'},body:JSON.stringify({id:currentId,catatan})});
        const json = await res.json();
        if (json.success) { closeModal('modalCatatan'); showPqToast(currentAction==='approve'?'Disetujui ✅':'Ditolak ❌'); loadPengajuan(); }
        else showPqToast(json.message||'Gagal', false);
    } catch { showPqToast('Kesalahan jaringan', false); }
    finally  { btn.disabled=false; txt.textContent='Kirim'; spn.style.display='none'; }
};

/* ── MODAL: DELETE ───────────────────────── */
document.getElementById('cancelDeleteBtn').onclick = () => closeModal('modalConfirmDelete');
document.getElementById('confirmDeleteBtn').onclick = async () => {
    if (!deleteTargetId) return;
    const btn = document.getElementById('confirmDeleteBtn');
    const txt = document.getElementById('deleteText');
    const spn = document.getElementById('deleteSpinner');
    btn.disabled=true; txt.textContent='Menghapus…'; spn.style.display='block';
    try {
        const res  = await fetch("{{ route('pengajuan.delete') }}", {method:'POST',headers:{'X-CSRF-TOKEN':'{{ csrf_token() }}','Accept':'application/json','Content-Type':'application/json'},body:JSON.stringify({id:deleteTargetId})});
        const json = await res.json();
        if (json.success) { showPqToast('Pengajuan dihapus'); closeModal('modalConfirmDelete'); loadPengajuan(); }
        else showPqToast(json.message||'Gagal', false);
    } catch { showPqToast('Kesalahan jaringan', false); }
    finally  { btn.disabled=false; txt.textContent='Ya, Hapus'; spn.style.display='none'; deleteTargetId=null; }
};

/* ── MODAL: EDIT ─────────────────────────── */
document.getElementById('closeEditPengajuan').onclick  = () => closeModal('modalEditPengajuan');
document.getElementById('cancelEditPengajuan').onclick = () => closeModal('modalEditPengajuan');
document.getElementById('saveEditPengajuan').onclick = async () => {
    const id = document.getElementById('editID').value;
    if (!id) return showPqToast('ID tidak ditemukan', false);
    const fd = new FormData();
    fd.append('ID', id); fd.append('Nama', document.getElementById('editNama').value);
    fd.append('Bidang', document.getElementById('editBidang').value); fd.append('Tujuan', document.getElementById('editTujuan').value);
    fd.append('Keperluan', document.getElementById('editKeperluan').value); fd.append('Biaya', parseRibuan(document.getElementById('editBiaya').value));
    const f = document.getElementById('editProposal');
    if (f?.files.length) fd.append('LampiranProposal', f.files[0]);
    const btn = document.getElementById('saveEditPengajuan');
    const txt = document.getElementById('editSaveText');
    const spn = document.getElementById('editSaveSpinner');
    btn.disabled=true; txt.textContent='Menyimpan…'; spn.style.display='block';
    try {
        const res  = await fetch("{{ route('pengajuan.update') }}", {method:'POST',headers:{'X-CSRF-TOKEN':'{{ csrf_token() }}','Accept':'application/json'},body:fd});
        const json = await res.json();
        if (json.success) { showPqToast('Berhasil diperbarui ✅'); closeModal('modalEditPengajuan'); loadPengajuan(); }
        else showPqToast(json.message||'Gagal', false);
    } catch { showPqToast('Kesalahan jaringan', false); }
    finally  { btn.disabled=false; txt.textContent='Simpan'; spn.style.display='none'; }
};

/* ── MODAL: KEPERLUAN ────────────────────── */
document.getElementById('closeModalKeperluan').onclick    = () => closeModal('modalKeperluan');
document.getElementById('closeModalKeperluanBtn').onclick = () => closeModal('modalKeperluan');

/* ── MODAL: PILIH LAPORAN ────────────────── */
document.getElementById('closePilihLaporan').onclick = () => closeModal('modalPilihLaporan');
document.getElementById('btnInputManual').onclick = () => { if (!laporanModeTargetId) return; window.location.href=`/laporan/manual/${laporanModeTargetId}`; };
document.getElementById('btnUploadPDF').onclick = () => {
    if (!laporanModeTargetId) return;
    const row = tableData.find(r => String(r.ID)===String(laporanModeTargetId));
    const biaya = row ? (row.Biaya||0) : 0;
    document.getElementById('laporanTargetId').value = laporanModeTargetId;
    document.getElementById('laporanBiayaPengajuan').value = formatRibuan(String(biaya));
    document.getElementById('laporanAktualBiaya').value = '';
    document.getElementById('laporanAktualBiaya').dataset.max = biaya;
    document.getElementById('laporanSelisihBox').style.display = 'none';
    closeModal('modalPilihLaporan'); openModal('modalUploadLaporan');
};

/* ── MODAL: UPLOAD LAPORAN ───────────────── */
document.getElementById('closeUploadLaporan').onclick  = () => closeModal('modalUploadLaporan');
document.getElementById('cancelUploadLaporan').onclick = () => closeModal('modalUploadLaporan');
document.getElementById('laporanAktualBiaya').addEventListener('input', function() {
    const max=Number(this.dataset.max||0), aktual=Number(parseRibuan(this.value)||0);
    const box=document.getElementById('laporanSelisihBox');
    if (!max || !aktual) { box.style.display='none'; return; }
    box.style.display='block';
    const diff=max-aktual;
    box.innerHTML=diff>0 ? `<span style="color:#16A34A;font-weight:500;">Sisa Dana: Rp ${formatRibuan(diff)}</span>`
        : diff<0 ? `<span style="color:#DC2626;font-weight:500;">Kekurangan: Rp ${formatRibuan(Math.abs(diff))}</span>`
        : `<span style="color:var(--accent);font-weight:500;">Dana Pas ✓</span>`;
});
document.getElementById('confirmUploadLaporan').onclick = async () => {
    const file=document.getElementById('laporanFileInput').files[0];
    const aktual=parseRibuan(document.getElementById('laporanAktualBiaya').value);
    const id=document.getElementById('laporanTargetId').value;
    if (!file)   return showPqToast('Pilih file laporan', false);
    if (!aktual) return showPqToast('Isi aktual biaya', false);
    if (!id)     return showPqToast('Target ID tidak ditemukan', false);
    if (file.type!=='application/pdf') return showPqToast('Hanya PDF yang diperbolehkan', false);
    if (file.size>5*1024*1024) return showPqToast('Maksimal 5MB', false);
    const btn=document.getElementById('confirmUploadLaporan');
    const txt=document.getElementById('uploadLaporanText');
    const spn=document.getElementById('uploadLaporanSpinner');
    btn.disabled=true; txt.textContent='Mengunggah…'; spn.style.display='block';
    try {
        const fd=new FormData();
        fd.append('ID',id); fd.append('LampiranLaporan',file); fd.append('AktualBiaya',aktual);
        const res=await fetch("{{ route('pengajuan.uploadLaporan') }}", {method:'POST',headers:{'X-CSRF-TOKEN':'{{ csrf_token() }}','Accept':'application/json'},body:fd});
        const json=await res.json();
        if (json.success) { showPqToast('Laporan berhasil diupload ✅'); closeModal('modalUploadLaporan'); document.getElementById('laporanFileInput').value=''; loadPengajuan(); }
        else showPqToast(json.message||'Gagal', false);
    } catch { showPqToast('Kesalahan jaringan', false); }
    finally  { btn.disabled=false; txt.textContent='Upload'; spn.style.display='none'; }
};

/* ── FORMAT RIBUAN INPUT ─────────────────── */
document.addEventListener('input', e => {
    if (['addBiaya','editBiaya'].includes(e.target.id) || e.target.classList.contains('input-aktual')) applyRibuan(e.target);
});

/* ── DELEGATED TABLE ACTIONS ─────────────── */
document.addEventListener('click', function(e) {
    const btnEdit = e.target.closest('.btn-edit');
    if (btnEdit) {
        const row = tableData.find(r => String(r.ID)===btnEdit.dataset.id);
        if (!row) return;
        document.getElementById('editID').value       = row.ID;
        document.getElementById('editNama').value     = row.Nama||'';
        document.getElementById('editBidang').value   = row.Bidang||'';
        document.getElementById('editTujuan').value   = row.Tujuan||'';
        document.getElementById('editKeperluan').value= row.Keperluan||'';
        document.getElementById('editBiaya').value    = formatRibuan(String(row.Biaya||''));
        document.getElementById('editProposal').value = '';
        openModal('modalEditPengajuan'); return;
    }
    const btnDelete = e.target.closest('.btn-delete');
    if (btnDelete) { deleteTargetId=btnDelete.dataset.id; openModal('modalConfirmDelete'); return; }
    const btnProposal = e.target.closest('.btn-preview-proposal');
    if (btnProposal) { window.open(`/pengajuan/file/proposal/${btnProposal.dataset.id}`,'_blank'); return; }
    const btnLaporan = e.target.closest('.btn-preview-laporan');
    if (btnLaporan)  { window.open(`/pengajuan/file/laporan/${btnLaporan.dataset.id}?v=${Date.now()}`,'_blank'); return; }
    const btnBuat = e.target.closest('.btn-buat-laporan');
    if (btnBuat) { laporanModeTargetId=btnBuat.dataset.id; openModal('modalPilihLaporan'); return; }
    const btnKep = e.target.closest('.btn-keperluan');
    if (btnKep)  { document.getElementById('modalKeperluanContent').textContent=btnKep.dataset.text||''; openModal('modalKeperluan'); return; }
    const btnApprove = e.target.closest('.btn-approve');
    if (btnApprove) { openCatatanModal(btnApprove.dataset.id,'approve'); return; }
    const btnReject  = e.target.closest('.btn-reject');
    if (btnReject)   { openCatatanModal(btnReject.dataset.id,'reject'); return; }

    const btnTransfer = e.target.closest('.btn-transfer-dana');
    if (btnTransfer) {
        btnTransfer.disabled=true; btnTransfer.textContent='Memproses…';
        fetch("{{ route('pengajuan.updateStatusBiaya') }}", {method:'POST',headers:{'X-CSRF-TOKEN':'{{ csrf_token() }}','Accept':'application/json','Content-Type':'application/json'},body:JSON.stringify({id:btnTransfer.dataset.id,status_biaya:'Transfer'})})
        .then(r=>r.json()).then(j=>j.success?(showPqToast('Dana sudah ditransfer 💰'),loadPengajuan()):showPqToast(j.message||'Gagal',false))
        .catch(()=>showPqToast('Kesalahan jaringan',false))
        .finally(()=>{btnTransfer.disabled=false;btnTransfer.textContent='Transfer';}); return;
    }
    const btnSisa = e.target.closest('.btn-konfirmasi-sisa');
    if (btnSisa) {
        btnSisa.disabled=true; btnSisa.textContent='Memproses…';
        fetch("{{ route('pengajuan.konfirmasiPengembalian') }}", {method:'POST',headers:{'X-CSRF-TOKEN':'{{ csrf_token() }}','Accept':'application/json','Content-Type':'application/json'},body:JSON.stringify({id:btnSisa.dataset.id})})
        .then(r=>r.json()).then(j=>j.success?(showPqToast('Dikonfirmasi ✅'),loadPengajuan()):showPqToast(j.message||'Gagal',false))
        .catch(()=>showPqToast('Kesalahan jaringan',false))
        .finally(()=>{btnSisa.disabled=false;btnSisa.textContent='Konfirmasi Pengembalian';}); return;
    }
    const btnKurang = e.target.closest('.btn-konfirmasi-kurang');
    if (btnKurang) {
        btnKurang.disabled=true; btnKurang.textContent='Memproses…';
        fetch("{{ route('pengajuan.konfirmasiTransferKurang') }}", {method:'POST',headers:{'X-CSRF-TOKEN':'{{ csrf_token() }}','Accept':'application/json','Content-Type':'application/json'},body:JSON.stringify({id:btnKurang.dataset.id})})
        .then(r=>r.json()).then(j=>j.success?(showPqToast('Dikonfirmasi ✅'),loadPengajuan()):showPqToast(j.message||'Gagal',false))
        .catch(()=>showPqToast('Kesalahan jaringan',false))
        .finally(()=>{btnKurang.disabled=false;btnKurang.textContent='Konfirmasi Transfer';}); return;
    }
});
document.addEventListener('DOMContentLoaded', () => {
    ['modalAdd', 'modalUploadLaporan', 'modalCatatan','modalEditPengajuan','modalConfirmDelete','modalPilihLaporan'].forEach(id => {
        const el = document.getElementById(id);
        if (el) document.body.appendChild(el);
    });
});
loadPengajuan();
})();


</script>

@endsection