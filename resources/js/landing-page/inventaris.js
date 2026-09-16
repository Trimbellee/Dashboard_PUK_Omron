document.addEventListener('DOMContentLoaded', () => {
    const cfg = window.LP_INVENTARIS;
    if (!cfg) return;

    const el = (id) => document.getElementById(id);
    const escapeHtml = (s) => { const d = document.createElement('div'); d.textContent = s ?? ''; return d.innerHTML; };

    let currentPage = 1;
    let searchKeyword = '';
    let searchDebounce = null;
    let currentRows = []; // ★ cache data yang sedang ditampilkan, untuk modal

    const KONDISI_LABEL = {
        baik: 'Baik',
        rusak_ringan: 'Rusak Ringan',
        rusak_berat: 'Rusak Berat',
    };

    const STATUS_LABEL = {
        tersedia: 'Tersedia',
        dipinjam: 'Dipinjam',
    };

    function kondisiBadge(kondisi) {
        const key = (kondisi || '').toLowerCase().replace(/\s+/g, '_');
        const cls = ['baik', 'rusak_ringan', 'rusak_berat'].includes(key) ? key : 'default';
        const label = KONDISI_LABEL[key] || (kondisi || '-');
        return `<span class="lp-inv-badge lp-inv-badge--${cls}">${escapeHtml(label)}</span>`;
    }

    // ★ badge status untuk dipakai di modal
    function statusBadge(status) {
        const key = (status || '').toLowerCase().replace(/\s+/g, '_');
        const cls = ['tersedia', 'dipinjam'].includes(key) ? key : 'default';
        const label = STATUS_LABEL[key] || (status || '-');
        return `<span class="lp-inv-status-badge lp-inv-status-badge--${cls}">${escapeHtml(label)}</span>`;
    }

    // ★ format Rupiah
    function formatRupiah(value) {
        if (value === null || value === undefined || value === '') return '-';
        const num = Number(value);
        if (Number.isNaN(num)) return escapeHtml(value);
        return 'Rp ' + num.toLocaleString('id-ID', { minimumFractionDigits: 0, maximumFractionDigits: 0 });
    }

    function loadData() {
        el('lpInvTbody').innerHTML = `<tr><td colspan="6" class="lp-inv-loading">Memuat data...</td></tr>`;
        el('lpInvPager').style.display = 'none';

        const params = new URLSearchParams({ page: currentPage });
        if (searchKeyword) params.set('search', searchKeyword);

        fetch(`${cfg.dataUrl}?${params.toString()}`)
            .then(res => res.json())
            .then(json => {
                currentRows = json.data; // ★ simpan untuk dipakai modal
                renderTable(json.data, json.meta);
                renderPager(json.meta);
                renderCount(json.meta);
            })
            .catch(() => {
                el('lpInvTbody').innerHTML = `<tr><td colspan="6" class="lp-inv-empty">⚠️ Gagal memuat data inventaris.</td></tr>`;
            });
    }

    function renderTable(rows, meta) {
        if (!rows.length) {
            el('lpInvTbody').innerHTML = `<tr><td colspan="6" class="lp-inv-empty">📭 Tidak ada data inventaris ditemukan.</td></tr>`;
            return;
        }
        const startNo = meta.from || 1;
        el('lpInvTbody').innerHTML = rows.map((item, idx) => `
            <tr data-id="${item.id}">
                <td class="lp-inv-no">${startNo + idx}</td>
                <td class="lp-inv-kode">${escapeHtml(item.kode_barang)}</td>
                <td class="lp-inv-nama">${escapeHtml(item.nama_barang)}</td>
                <td>${escapeHtml(item.type || '-')}</td>
                <td>${escapeHtml(item.kategori || '-')}</td>
                <td>${kondisiBadge(item.kondisi)}</td>
            </tr>
        `).join('');
    }

    function renderCount(meta) {
        el('lpInvCount').textContent = meta.total > 0
            ? `${meta.total} barang tercatat`
            : '';
    }

    function renderPager(meta) {
        const pager = el('lpInvPager');
        if (meta.last_page <= 1) { pager.style.display = 'none'; return; }
        pager.style.display = 'flex';

        let html = `<div class="lp-inv-pager__info">Menampilkan ${meta.from}–${meta.to} dari ${meta.total} barang</div>`;
        html += `<button type="button" data-page="prev" ${meta.current_page === 1 ? 'disabled' : ''}>‹</button>`;

        const maxButtons = 5;
        let startPage = Math.max(1, meta.current_page - Math.floor(maxButtons / 2));
        let endPage = Math.min(meta.last_page, startPage + maxButtons - 1);
        if (endPage - startPage + 1 < maxButtons) startPage = Math.max(1, endPage - maxButtons + 1);

        if (startPage > 1) {
            html += `<button type="button" data-page="1">1</button>`;
            if (startPage > 2) html += `<span style="padding:0 4px;color:var(--text-muted);">…</span>`;
        }
        for (let p = startPage; p <= endPage; p++) {
            html += `<button type="button" data-page="${p}" class="${p === meta.current_page ? 'active' : ''}">${p}</button>`;
        }
        if (endPage < meta.last_page) {
            if (endPage < meta.last_page - 1) html += `<span style="padding:0 4px;color:var(--text-muted);">…</span>`;
            html += `<button type="button" data-page="${meta.last_page}">${meta.last_page}</button>`;
        }

        html += `<button type="button" data-page="next" ${meta.current_page === meta.last_page ? 'disabled' : ''}>›</button>`;
        pager.innerHTML = html;

        pager.querySelectorAll('button[data-page]').forEach(btn => {
            btn.addEventListener('click', () => {
                const val = btn.dataset.page;
                if (val === 'prev') currentPage--;
                else if (val === 'next') currentPage++;
                else currentPage = parseInt(val);
                loadData();
                document.getElementById('inventaris').scrollIntoView({ behavior: 'smooth', block: 'start' });
            });
        });
    }

    // ================= ★ MODAL DETAIL ★ =================
    const modalOverlay = el('lpInvModalOverlay');
    const modalBody = el('lpInvModalBody');

    function detailRow(label, valueHtml, extraClass = '') {
        return `
            <div class="lp-inv-detail-row">
                <span class="lp-inv-detail-row__label">${label}</span>
                <span class="lp-inv-detail-row__value ${extraClass}">${valueHtml}</span>
            </div>
        `;
    }

    function openModal(item) {
        modalBody.innerHTML =
            detailRow('Kode Barang', escapeHtml(item.kode_barang), 'lp-inv-detail-row__value--kode') +
            detailRow('Nama Barang', escapeHtml(item.nama_barang)) +
            detailRow('Type', escapeHtml(item.type || '-')) +
            detailRow('Kategori', escapeHtml(item.kategori || '-')) +
            detailRow('Kondisi', kondisiBadge(item.kondisi)) +
            detailRow('Status', statusBadge(item.status)) +
            detailRow('Tanggal Pembelian', escapeHtml(item.tanggal_pembelian || '-')) +
            detailRow('Harga Perolehan', formatRupiah(item.harga_perolehan)) +
            detailRow('Keterangan', escapeHtml(item.keterangan || '-'));

        modalOverlay.classList.add('is-open');
        document.body.style.overflow = 'hidden';
    }

    function closeModal() {
        modalOverlay.classList.remove('is-open');
        document.body.style.overflow = '';
    }

    el('lpInvTbody').addEventListener('click', (e) => {
        const row = e.target.closest('tr[data-id]');
        if (!row) return;
        const item = currentRows.find(r => String(r.id) === row.dataset.id);
        if (item) openModal(item);
    });

    el('lpInvModalClose').addEventListener('click', closeModal);
    modalOverlay.addEventListener('click', (e) => {
        if (e.target === modalOverlay) closeModal();
    });
    document.addEventListener('keydown', (e) => {
        if (e.key === 'Escape' && modalOverlay.classList.contains('is-open')) closeModal();
    });
    // ================= END MODAL =================

    el('lpInvSearch').addEventListener('input', function() {
        clearTimeout(searchDebounce);
        const value = this.value.trim();
        searchDebounce = setTimeout(() => {
            searchKeyword = value;
            currentPage = 1;
            loadData();
        }, 350);
    });

    loadData();
});