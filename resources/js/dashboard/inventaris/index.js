import { showToast, flashSessionMessages, confirmAction, createPaginator } from './_shared.js';

document.addEventListener('DOMContentLoaded', function () {
    flashSessionMessages();

    const searchInput = document.getElementById('searchInput');
    const filterKategori = document.getElementById('filterKategori');
    const filterStatus = document.getElementById('filterStatus');
    const tableRows = Array.from(document.querySelectorAll('.inv-table tbody tr[data-row]'));
    const emptyState = document.querySelector('.empty-state');

    const paginator = createPaginator({
        container: document.getElementById('inv-pagination'),
        pageSize: 10,
    });

    function filterTable(resetPage = false) {
        if (!tableRows.length) return;

        const searchTerm = (searchInput?.value || '').toLowerCase().trim();
        const kategori = (filterKategori?.value || '').toLowerCase();
        const status = (filterStatus?.value || '').toLowerCase();

        const matched = tableRows.filter((row) => {
            const nama = row.dataset.nama || '';
            const type = row.dataset.type || '';
            const kode = row.dataset.kode || '';
            const rowKategori = row.dataset.kategori || '';
            const rowStatus = row.dataset.status || '';
            const rowDipinjam = row.dataset.dipinjam === '1';

            const matchSearch = nama.includes(searchTerm) || type.includes(searchTerm) || kode.includes(searchTerm);
            const matchKategori = !kategori || rowKategori === kategori;

            let matchStatus = true;
            if (status === 'dipinjam') matchStatus = rowDipinjam;
            else if (status) matchStatus = rowStatus === status;

            return matchSearch && matchKategori && matchStatus;
        });

        tableRows.forEach((row) => { if (!matched.includes(row)) row.style.display = 'none'; });

        if (resetPage) paginator.reset();
        paginator.update(matched);

        if (emptyState) emptyState.style.display = matched.length === 0 ? '' : 'none';
    }

    searchInput?.addEventListener('input', () => filterTable(true));
    filterKategori?.addEventListener('change', () => filterTable(true));
    filterStatus?.addEventListener('change', () => filterTable(true));
    filterTable(true);

    // ── Delete confirmation ──
    document.querySelectorAll('.js-confirm-delete').forEach((form) => {
        form.addEventListener('submit', async function (e) {
            if (form.dataset.confirmed === 'true') return;
            e.preventDefault();

            const namaBarang = form.dataset.namaBarang || 'barang ini';
            const ok = await confirmAction(
                `Yakin ingin menghapus "${namaBarang}"? Tindakan ini tidak dapat dibatalkan.`,
                { title: 'Hapus Barang', confirmText: 'Hapus' }
            );

            if (ok) {
                form.dataset.confirmed = 'true';
                const btn = form.querySelector('button[type="submit"]');
                if (btn) btn.innerHTML = '<span class="spinner-border"></span>';
                form.requestSubmit ? form.requestSubmit() : form.submit();
            }
        });
    });

    // ── Keyboard shortcuts ──
    document.addEventListener('keydown', function (e) {
        if ((e.ctrlKey || e.metaKey) && e.key === 'f' && searchInput) {
            e.preventDefault();
            searchInput.focus();
        }
        if (e.key === 'Escape' && document.activeElement === searchInput) {
            searchInput.value = '';
            filterTable(true);
            searchInput.blur();
        }
    });
});