/* ==========================================================================
   Landing Page — Grafik Keuangan (#keuangan)
   Data dari window.LP_KEUANGAN (lihat partials/keuangan-section.blade.php)
   ========================================================================== */

import { Chart, registerables } from 'chart.js';
import ChartDataLabels from 'chartjs-plugin-datalabels';
Chart.register(...registerables, ChartDataLabels);

const LK = window.LP_KEUANGAN || {};

const BULAN_LABEL = ['Jan','Feb','Mar','Apr','Mei','Jun','Jul','Agu','Sep','Okt','Nov','Des'];
const BULAN_FULL  = ['Januari','Februari','Maret','April','Mei','Juni','Juli','Agustus','September','Oktober','November','Desember'];

function fmtRp(n) { return 'Rp ' + Number(n || 0).toLocaleString('id-ID'); }
function shortNominal(v) {
    if (!v) return '';
    function fmtDec(n, s) { let r = Math.round(n * 100) / 100; return (Number.isInteger(r) ? r : r.toString().replace(/\.?0+$/, '')) + s; }
    if (v >= 1000000000) return fmtDec(v / 1000000000, 'M');
    if (v >= 1000000)    return fmtDec(v / 1000000, 'jt');
    if (v >= 1000)       return Math.round(v / 1000) + 'rb';
    return v;
}

/* ── Dark mode: baca dari <html data-theme>, reaktif ke toggle Alpine ── */
function isDark() { return document.documentElement.getAttribute('data-theme') === 'dark'; }
function gridColor() { return isDark() ? 'rgba(255,255,255,0.08)' : 'rgba(0,0,0,0.07)'; }
function txtColor()  { return isDark() ? '#94a3b8' : '#6b7f94'; }

let trendChart = null;
let breakdownChart = null;
let currentPayload = null;
let jenisPilih = 'Dana Operasional';
let isMobile = window.innerWidth <= 640;

function setLoading(on) {
    const el = document.getElementById('lpKeuLoading');
    if (el) el.style.display = on ? 'block' : 'none';
}

function updateBadge(payload) {
    const el = document.getElementById('lpKeuBadge');
    if (!el) return;
    const bl = payload.bulanTerakhirLaporan || 0;
    el.textContent = bl > 0
        ? `📋 Laporan s/d ${BULAN_FULL[bl - 1]} ${payload.tahun}`
        : `📋 Belum ada laporan selesai di ${payload.tahun}`;
}

/* ── GRAFIK TREN DEBIT/KREDIT ── */
function buildTrendChart(payload) {
    if (trendChart) trendChart.destroy();
    const ctx = document.getElementById('lpKeuTrendChart');
    if (!ctx) return;

    trendChart = new Chart(ctx, {
        type: 'bar',
        data: {
            labels: payload.labels,
            datasets: [
                { type: 'bar', label: 'Masuk', data: payload.debit, backgroundColor: 'rgba(61,154,94,0.8)', borderRadius: 4, yAxisID: 'yLeft' },
                { type: 'bar', label: 'Keluar', data: payload.kredit, backgroundColor: 'rgba(226,75,74,0.8)', borderRadius: 4, yAxisID: 'yLeft' },
                
                {type:'line',label:'Saldo',data: payload.totalPerBulan, borderColor:'#7f77dd',backgroundColor:'rgba(127,119,221,0.1)',borderWidth:2.5,pointRadius:isMobile?2:4,pointHoverRadius:6,pointBackgroundColor:'#7f77dd',pointBorderColor:isDark()?'#fcfcfc':'#000000',pointBorderWidth:2,tension:0.35,fill:false,yAxisID:'yRight',order:1,
                  datalabels:{display:!isMobile,anchor:'end',align:'top',offset:4,color:'#7f77dd',font:{size:9,weight:'600'},formatter:v=>shortNominal(v)}},
            ],
        },
        options: {
            responsive: true, maintainAspectRatio: false,
            layout: { padding: { top: isMobile ? 20 : 35 } },
            interaction: { mode: 'index', intersect: false },
            onClick(e) {
                const pts = trendChart.getElementsAtEventForMode(e, 'index', { intersect: false }, true);
                if (!pts.length) return;
                const idx = pts[0].index;
                showKasModal(payload.labels[idx], payload, idx);
            },
            plugins: {
                legend: { display: false },
                datalabels: {
                    display: !isMobile && ((ctx) => ctx.dataset.type === 'bar'),
                    anchor: 'end', align: 'end', rotation: -90, offset: 2,
                    color: isDark() ? '#e5e7eb' : '#374151',
                    font: { size: 9, weight: '600' },
                    formatter: (v) => shortNominal(v),
                },
                tooltip: { callbacks: { label: (c) => ` ${c.dataset.label}: ${c.dataset.label === 'Saldo' ? fmtRp(c.parsed.y) : fmtRp(c.parsed.y)}` } },
            },
            scales: {
                x: { grid: { display: false }, ticks: { color: txtColor(), font: { size: isMobile ? 9 : 11 } } },
                yLeft: { type: 'linear', position: 'left', beginAtZero: true, grid: { color: gridColor() }, ticks: { color: txtColor(), callback: (v) => shortNominal(v) } },
                yRight: { type: 'linear', position: 'right', beginAtZero: true, grid: { drawOnChartArea: false }, ticks: { color: '#7f77dd', callback: (v) => shortNominal(v) } },
            },
        },
    });
}

/* ── GRAFIK BREAKDOWN PER JENIS ── */
function buildJenisFilter(jenisLabels) {
    const sel = document.getElementById('lpKeuJenisFilter');
    if (!sel) return;
    if (!jenisLabels.includes(jenisPilih)) jenisPilih = jenisLabels[0] ?? '';
    sel.innerHTML = jenisLabels.map((j) => `<option value="${j}" ${j === jenisPilih ? 'selected' : ''}>${j}</option>`).join('');
}

function buildBreakdownStats(dj) {
    const el = document.getElementById('lpKeuJenisStats');
    if (!el) return;
    const td = (dj.debit || []).reduce((a, b) => a + (b ?? 0), 0);
    const tk = (dj.kredit || []).reduce((a, b) => a + (b ?? 0), 0);
    const saldo = dj.total?.length ? dj.total[dj.total.length - 1] : 0;
    el.innerHTML = `
        <div><div class="lp-keu-stats__label">Total Dana Masuk</div><div class="lp-keu-stats__value" style="color:#3d9a5e;">${fmtRp(td)}</div></div>
        <div><div class="lp-keu-stats__label">Total Dana Keluar</div><div class="lp-keu-stats__value" style="color:#e24b4a;">${fmtRp(tk)}</div></div>
        <div><div class="lp-keu-stats__label">Saldo Akhir</div><div class="lp-keu-stats__value" style="color:#7f77dd;">${fmtRp(saldo)}</div></div>`;
}

function buildBreakdownChart(payload) {
    currentPayload = payload;
    buildJenisFilter(payload.jenisLabels);
    const dj = payload.jenisPerBulan[jenisPilih] || { debit: [], kredit: [], total: [] };
    buildBreakdownStats(dj);

    if (breakdownChart) breakdownChart.destroy();
    const ctx = document.getElementById('lpKeuBreakdownChart');
    if (!ctx) return;

    breakdownChart = new Chart(ctx, {
        type: 'bar',
        data: {
            labels: payload.labels,
            datasets: [
                { type: 'bar', label: 'Masuk', data: dj.debit, backgroundColor: 'rgba(61,154,94,0.8)', borderRadius: 4, yAxisID: 'yLeft' },
                { type: 'bar', label: 'Keluar', data: dj.kredit, backgroundColor: 'rgba(226,75,74,0.8)', borderRadius: 4, yAxisID: 'yLeft' },
                {type:'line',label:'Saldo',data:dj.total||[],borderColor:'#7f77dd',backgroundColor:'rgba(127,119,221,0.08)',borderWidth:2.5,pointRadius:isMobile?2:4,pointHoverRadius:6,pointBackgroundColor:'#7f77dd',pointBorderColor:isDark()?'#f7f7f7':'#000000',pointBorderWidth:2,tension:0.35,fill:false,yAxisID:'yRight',order:1,
                  datalabels:{display:!isMobile,anchor:'end',align:'top',offset:4,color:'#7f77dd',font:{size:9,weight:'600'},formatter:v=>shortNominal(v)}},
            ],
        },
        options: {
            responsive: true, maintainAspectRatio: false,
            layout: { padding: { top: isMobile ? 20 : 35 } },
            interaction: { mode: 'index', intersect: false },
            onClick(e) {
                const pts = breakdownChart.getElementsAtEventForMode(e, 'nearest', { intersect: true }, true);
                if (!pts.length) return;
                const pt = pts[0];
                const dsLabel = breakdownChart.data.datasets[pt.datasetIndex]?.label ?? '';
                if (dsLabel !== 'Keluar') return; // hanya bar Kredit yang bisa diklik untuk detail
                if (jenisPilih === 'Dana Operasional') {
                    showBidangOperasionalModal(payload.labels[pt.index], pt.index);
                } else {
                    showJenisDetailModal(jenisPilih, payload.labels[pt.index], pt.index);
                }
            },
            plugins: {
                legend: { display: true, position: 'bottom', labels: { color: txtColor(), boxWidth: 10, font: { size: 11 } } },
                datalabels: {
                    display: !isMobile && ((ctx) => ctx.dataset.type === 'bar'),
                    anchor: 'end', align: 'end', rotation: -90, offset: 2,
                    color: isDark() ? '#e5e7eb' : '#374151',
                    font: { size: 9, weight: '600' },
                    formatter: (v) => shortNominal(v),
                },
                tooltip: { callbacks: { label: (c) => ` ${c.dataset.label}: ${fmtRp(c.parsed.y)}` } },
            },
            scales: {
                x: { grid: { display: false }, ticks: { color: txtColor(), font: { size: isMobile ? 9 : 11 } } },
                yLeft: { type: 'linear', position: 'left', beginAtZero: true, grid: { color: gridColor() }, ticks: { color: txtColor(), callback: (v) => shortNominal(v) } },
                yRight: { type: 'linear', position: 'right', beginAtZero: true, grid: { drawOnChartArea: false }, ticks: { color: '#7f77dd', callback: (v) => shortNominal(v) } },
            },
        },
    });
}

window.lpChangeJenis = function () {
    jenisPilih = document.getElementById('lpKeuJenisFilter').value;
    if (currentPayload) buildBreakdownChart(currentPayload);
};

/* ── LOAD DATA (awal & ganti tahun) ── */
async function loadTahun(tahun) {
    setLoading(true);
    try {
        const res = await fetch(`${LK.debitKreditUrl}?tahun=${tahun}`, { headers: { Accept: 'application/json' } });
        const payload = await res.json();
        renderAll(payload);
    } catch (e) {
        console.error(e);
    } finally {
        setLoading(false);
    }
}

function renderAll(payload) {
    updateBadge(payload);
    buildTrendChart(payload);
    buildBreakdownChart(payload);
    document.dispatchEvent(new Event('lp:section-rendered'));
}

/* ── MODAL: RINGKASAN KAS (klik titik grafik tren) ── */
window.lpCloseKasModal = function () {
    document.getElementById('lpKasModal').style.display = 'none';
    document.body.style.overflow = '';
};

function showKasModal(periodLabel, payload, idx) {
    document.getElementById('lpKasModalTitle').textContent = 'Ringkasan Kas';
    document.getElementById('lpKasModalSubtitle').textContent = `${periodLabel} ${payload.tahun}`;
    document.getElementById('lpKasModalThead').innerHTML = `
        <th>Jenis Dana</th><th style="text-align:right;">Saldo Awal</th><th style="text-align:right;">Dana Masuk</th><th style="text-align:right;">Dana Keluar</th><th style="text-align:right;">Saldo akhir</th>`;

    const tbody = document.getElementById('lpKasModalTbody');
    const tfoot = document.getElementById('lpKasModalTfoot');
    const emptyEl = document.getElementById('lpKasModalEmpty');
    tbody.innerHTML = ''; tfoot.innerHTML = '';

    if (!payload.jenisLabels?.length) {
        emptyEl.style.display = 'block';
    } else {
        emptyEl.style.display = 'none';
        let gsa = 0, gd = 0, gk = 0, gt = 0;
        payload.jenisLabels.forEach((j) => {
            const dj = payload.jenisPerBulan[j];
            if (!dj) return;
            const sa = dj.saldoAwal?.[idx] ?? 0, d = dj.debit?.[idx] ?? 0, k = dj.kredit?.[idx] ?? 0, t = dj.total?.[idx] ?? 0;
            gsa += sa; gd += d; gk += k; gt += t;
            const tr = document.createElement('tr');
            tr.innerHTML = `<td>${j}</td><td style="text-align:right;color:var(--text-secondary);">${fmtRp(sa)}</td><td style="text-align:right;color:#3d9a5e;">${fmtRp(d)}</td><td style="text-align:right;color:#e24b4a;">${fmtRp(k)}</td><td style="text-align:right;color:#7f77dd;">${fmtRp(t)}</td>`;
            tbody.appendChild(tr);
        });
        tfoot.innerHTML = `<tr><td>Total</td><td style="text-align:right;">${fmtRp(gsa)}</td><td style="text-align:right;">${fmtRp(gd)}</td><td style="text-align:right;">${fmtRp(gk)}</td><td style="text-align:right;">${fmtRp(gt)}</td></tr>`;
    }

    document.getElementById('lpKasModal').style.display = 'flex';
    document.body.style.overflow = 'hidden';
}

/* ── MODAL: DANA OPERASIONAL PER BIDANG (klik bar Kredit khusus jenis "Dana Operasional") ──
   Reuse lpKasModal yang sama, header diganti jadi 2 kolom (Bidang / Realisasi) */
async function showBidangOperasionalModal(periodLabel, idx) {
    const bulan = idx + 1; // labels breakdown selalu mulai dari bulan 1 (Januari)

    document.getElementById('lpKasModalTitle').textContent = 'Dana Operasional — Per Bidang';
    document.getElementById('lpKasModalSubtitle').textContent = `${periodLabel} ${currentPayload?.tahun ?? ''}`;
    document.getElementById('lpKasModalThead').innerHTML = `<th>Bidang</th><th style="text-align:right;">Realisasi</th>`;

    const tbody = document.getElementById('lpKasModalTbody');
    const tfoot = document.getElementById('lpKasModalTfoot');
    const emptyEl = document.getElementById('lpKasModalEmpty');
    tbody.innerHTML = ''; tfoot.innerHTML = ''; emptyEl.style.display = 'none';

    document.getElementById('lpKasModal').style.display = 'flex';
    document.body.style.overflow = 'hidden';

    try {
        const qs = new URLSearchParams({ tahun: currentPayload?.tahun ?? '', bulan });
        const res = await fetch(`${LK.realisasiBidangUrl}?${qs}`, { headers: { Accept: 'application/json' } });
        const json = await res.json();
        const rows = json.data ?? [];

        if (!rows.length) {
            emptyEl.style.display = 'block';
            return;
        }
        let total = 0;
        rows.forEach((r) => {
            total += Number(r.total || 0);
            const tr = document.createElement('tr');
            tr.innerHTML = `<td>${r.bidang}</td><td style="text-align:right;font-weight:600;">${fmtRp(r.total)}</td>`;
            tbody.appendChild(tr);
        });
        tfoot.innerHTML = `<tr><td>Total</td><td style="text-align:right;">${fmtRp(total)}</td></tr>`;
    } catch (e) {
        console.error(e);
        emptyEl.style.display = 'block';
    }
}

/* ── MODAL: DETAIL JENIS DANA (klik bar Kredit breakdown) ── */
const JENIS_PER_PAGE = 15;
let jenisAllRows = [];
let jenisFilteredRows = [];
let jenisDetailLabel = '';
let jenisPeriodLabel = '';
let jenisPage = 1;

window.lpCloseJenisModal = function () {
    document.getElementById('lpJenisModal').style.display = 'none';
    document.body.style.overflow = '';
};

function fmtTgl(str) {
    if (!str) return '—';
    const d = new Date(str);
    if (isNaN(d)) return str;
    return d.toLocaleDateString('id-ID', { day: '2-digit', month: 'short', year: 'numeric' });
}

async function showJenisDetailModal(jenis, periodLabel, idx) {
    jenisDetailLabel = jenis;
    jenisPeriodLabel = `${periodLabel} ${currentPayload?.tahun ?? ''}`;
    jenisPage = 1;

    document.getElementById('lpJenisModalTitle').textContent = `Detail — ${jenis}`;
    document.getElementById('lpJenisModalSubtitle').textContent = jenisPeriodLabel;

    const searchEl  = document.getElementById('lpJenisSearch');
    const loadingEl = document.getElementById('lpJenisModalLoading');
    const emptyEl   = document.getElementById('lpJenisModalEmpty');
    const tableEl   = document.getElementById('lpJenisModalTable');

    if (searchEl) searchEl.value = '';
    document.getElementById('lpJenisModalTbody').innerHTML = '';
    document.getElementById('lpJenisModalTfoot').innerHTML = '';
    document.getElementById('lpJenisPager').innerHTML = '';
    document.getElementById('lpJenisPagerInfo').textContent = '';
    document.getElementById('lpJenisCount').textContent = '';

    loadingEl.style.display = 'block'; emptyEl.style.display = 'none'; tableEl.style.display = 'none';
    document.getElementById('lpJenisModal').style.display = 'flex';
    document.body.style.overflow = 'hidden';

    try {
        const bulan = idx + 1; // index bulan pada tahun aktif (jenisPerBulan mengikuti urutan bulanTerakhirLaporan)
        const qs = new URLSearchParams({ jenis, tahun: currentPayload?.tahun ?? '', bulan });
        const res = await fetch(`${LK.detailJenisUrl}?${qs}`, { headers: { Accept: 'application/json' } });
        const json = await res.json();
        jenisAllRows = json.data ?? [];
        jenisFilteredRows = [...jenisAllRows];

        loadingEl.style.display = 'none';
        if (!jenisAllRows.length) {
            emptyEl.style.display = 'block';
            return;
        }
        tableEl.style.display = '';
        renderJenisPage();
    } catch (e) {
        console.error(e);
        loadingEl.style.display = 'none';
        emptyEl.style.display = 'block';
    }
}

function renderJenisPage() {
    const tbody = document.getElementById('lpJenisModalTbody');
    const tfoot = document.getElementById('lpJenisModalTfoot');
    const emptyEl = document.getElementById('lpJenisModalEmpty');
    const tableEl = document.getElementById('lpJenisModalTable');

    tbody.innerHTML = ''; tfoot.innerHTML = '';

    if (!jenisFilteredRows.length) {
        emptyEl.style.display = 'block';
        tableEl.style.display = 'none';
        document.getElementById('lpJenisCount').textContent = '';
        document.getElementById('lpJenisPagerInfo').textContent = '';
        document.getElementById('lpJenisPager').innerHTML = '';
        return;
    }
    emptyEl.style.display = 'none';
    tableEl.style.display = '';

    const start = (jenisPage - 1) * JENIS_PER_PAGE;
    const end = Math.min(start + JENIS_PER_PAGE, jenisFilteredRows.length);
    const page = jenisFilteredRows.slice(start, end);

    page.forEach((row, i) => {
        const tr = document.createElement('tr');
        tr.innerHTML = `
            <td style="text-align:center;color:var(--text-muted);font-size:11px;">${start + i + 1}</td>
            <td>${row.nama ?? '—'}</td>
            <td>${row.keperluan ?? '—'}</td>
            <td style="text-align:right;font-weight:600;">${fmtRp(row.realisasi)}</td>
            <td style="text-align:center;">${fmtTgl(row.tanggal)}</td>
            <td style="text-align:center;">${row.sumber ?? '—'}</td>`;
        tbody.appendChild(tr);
    });

    const total = jenisFilteredRows.reduce((s, r) => s + Number(r.realisasi || 0), 0);
    tfoot.innerHTML = `<tr><td colspan="3">Total (${jenisFilteredRows.length} baris)</td><td style="text-align:right;">${fmtRp(total)}</td><td colspan="2"></td></tr>`;

    document.getElementById('lpJenisCount').textContent = `${jenisFilteredRows.length} data`;
    document.getElementById('lpJenisPagerInfo').textContent = `${start + 1}–${end} dari ${jenisFilteredRows.length}`;
    buildJenisPager();
}

function buildJenisPager() {
    const totalPages = Math.ceil(jenisFilteredRows.length / JENIS_PER_PAGE);
    const pager = document.getElementById('lpJenisPager');
    pager.innerHTML = '';
    if (totalPages <= 1) return;

    const make = (label, page, active = false, disabled = false) => {
        const b = document.createElement('button');
        b.type = 'button';
        b.textContent = label;
        if (active) b.classList.add('active');
        if (disabled) b.disabled = true;
        b.onclick = () => { jenisPage = page; renderJenisPage(); };
        pager.appendChild(b);
    };

    make('‹', jenisPage - 1, false, jenisPage === 1);
    for (let p = 1; p <= totalPages; p++) make(p, p, p === jenisPage);
    make('›', jenisPage + 1, false, jenisPage === totalPages);
}

window.lpFilterJenis = function () {
    const q = (document.getElementById('lpJenisSearch')?.value ?? '').toLowerCase().trim();
    jenisFilteredRows = q
        ? jenisAllRows.filter((r) =>
            (r.nama ?? '').toLowerCase().includes(q) ||
            (r.keperluan ?? '').toLowerCase().includes(q) ||
            (r.sumber ?? '').toLowerCase().includes(q))
        : [...jenisAllRows];
    jenisPage = 1;
    renderJenisPage();
};

window.lpExportJenisCSV = function () {
    if (!jenisFilteredRows.length) return;
    const header = ['No', 'Nama', 'Keperluan', 'Realisasi', 'Tanggal', 'Sumber'];
    const rows = jenisFilteredRows.map((r, i) => [i + 1, r.nama ?? '', r.keperluan ?? '', r.realisasi ?? 0, r.tanggal ?? '', r.sumber ?? ''].map((v) => `"${String(v).replace(/"/g, '""')}"`).join(','));
    const csv = [header.join(','), ...rows].join('\n');
    const blob = new Blob(['\uFEFF' + csv], { type: 'text/csv;charset=utf-8;' });
    const url = URL.createObjectURL(blob);
    const a = document.createElement('a');
    a.href = url; a.download = `Detail_${jenisDetailLabel.replace(/\s+/g, '_')}.csv`; a.click();
    URL.revokeObjectURL(url);
};

/* ── Tutup modal via backdrop / ESC ── */
['lpKasModal', 'lpJenisModal'].forEach((id) => {
    document.addEventListener('DOMContentLoaded', () => {
        const el = document.getElementById(id);
        el?.addEventListener('click', (e) => { if (e.target === el) el.style.display = 'none'; document.body.style.overflow = ''; });
    });
});
document.addEventListener('keydown', (e) => {
    if (e.key !== 'Escape') return;
    window.lpCloseKasModal?.();
    window.lpCloseJenisModal?.();
});

/* ── Redraw chart saat dark-mode di-toggle (tombol Alpine di navbar) ── */
const themeObserver = new MutationObserver(() => {
    if (currentPayload) renderAll(currentPayload);
});
themeObserver.observe(document.documentElement, { attributes: true, attributeFilter: ['data-theme'] });

/* ── Ganti tahun ── */
document.addEventListener('DOMContentLoaded', () => {
    const yearSel = document.getElementById('lpKeuTahun');
    yearSel?.addEventListener('change', () => loadTahun(yearSel.value));

    const jenisSel = document.getElementById('lpKeuJenisFilter');
    jenisSel?.addEventListener('change', () => window.lpChangeJenis());

    const jenisSearch = document.getElementById('lpJenisSearch');
    jenisSearch?.addEventListener('input', () => window.lpFilterJenis());

    if (LK.dataAwal) {
        renderAll(LK.dataAwal);
    } else if (LK.tahunAktif) {
        loadTahun(LK.tahunAktif);
    }
});