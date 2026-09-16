/**
 * Inventaris — Manajemen Peminjaman
 */
import { showToast, flashSessionMessages, confirmAction, createPaginator } from './_shared.js';

document.addEventListener('DOMContentLoaded', function () {
    flashSessionMessages();

    const tabs = document.querySelectorAll('.tab-btn');
    const rows = Array.from(document.querySelectorAll('#peminjamanBody tr[data-status]'));
    const searchInput = document.getElementById('searchPeminjaman');
    const modalBackdrop = document.getElementById('detailModal');
    const modalContent = document.getElementById('detailContent');
    const emptyRow = document.getElementById('peminjamanEmptyRow');

    const paginator = createPaginator({
        container: document.getElementById('peminjaman-pagination'),
        pageSize: 10,
    });

    // ── Filter ──
    function filterTable(resetPage = false) {
        const activeTab = document.querySelector('.tab-btn.active');
        const status = activeTab ? activeTab.dataset.status : 'semua';
        const searchTerm = (searchInput?.value || '').toLowerCase().trim();

        const matched = rows.filter((row) => {
            const rowStatus = row.dataset.status || '';
            const text = row.textContent.toLowerCase();

            const matchStatus = status === 'semua' || rowStatus === status;
            const matchSearch = text.includes(searchTerm);

            return matchStatus && matchSearch;
        });

        rows.forEach((row) => { if (!matched.includes(row)) row.style.display = 'none'; });

        if (resetPage) paginator.reset();
        paginator.update(matched);

        if (emptyRow) emptyRow.style.display = matched.length === 0 ? '' : 'none';
    }

    tabs.forEach((tab) => {
        tab.addEventListener('click', function () {
            tabs.forEach((t) => t.classList.remove('active'));
            this.classList.add('active');

            const status = this.dataset.status;
            window.location.hash = status !== 'semua' ? status : '';

            filterTable(true);
        });
    });

    searchInput?.addEventListener('input', () => filterTable(true));

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

    (function restoreTabFromHash() {
        const hash = window.location.hash.replace('#', '');
        if (!hash) return;
        const targetTab = document.querySelector(`.tab-btn[data-status="${hash}"]`);
        if (targetTab) {
            tabs.forEach((t) => t.classList.remove('active'));
            targetTab.classList.add('active');
        }
    })();

    filterTable(true);

    // ── Detail modal (AJAX) ──
    window.showDetail = function (id) {
        if (!modalBackdrop) return;

        modalBackdrop.classList.add('open');
        document.body.style.overflow = 'hidden';

        modalContent.innerHTML = `
            <div class="text-center" style="padding:32px 0;">
                <span class="spinner-border" style="width:26px;height:26px;border-width:3px;border-color:var(--border);border-top-color:var(--inv-blue);"></span>
                <p class="text-muted" style="margin-top:10px;">Memuat detail...</p>
            </div>
        `;

        fetch(`/inventaris/peminjaman/${id}`)
            .then((response) => {
                if (!response.ok) throw new Error('Network response was not ok');
                return response.text();
            })
            .then((html) => { modalContent.innerHTML = html; })
            .catch(() => {
                modalContent.innerHTML = `
                    <div class="text-center" style="padding:32px 0;">
                        <span style="font-size:30px;">⚠️</span>
                        <p class="text-muted" style="margin-top:8px;">Gagal memuat detail peminjaman</p>
                        <button type="button" class="btn btn-secondary btn-sm" style="margin-top:10px;" onclick="showDetail(${id})">Coba Lagi</button>
                    </div>
                `;
                showToast('Gagal memuat detail peminjaman', 'error');
            });
    };

    window.closeDetail = function () {
        if (!modalBackdrop) return;
        modalBackdrop.classList.remove('open');
        document.body.style.overflow = '';
    };

    modalBackdrop?.addEventListener('click', function (e) {
        if (e.target === this) window.closeDetail();
    });

    // ── Tambahkan fungsi untuk membuka modal pengembalian ──
    window.showKembaliForm = function(id, namaBarang, peminjamNama) {
        const modal = document.getElementById('kembaliModal');
        const form = document.getElementById('formKembali');
        const barangNama = document.getElementById('kembaliBarangNama');
        const peminjamNamaEl = document.getElementById('kembaliPeminjamNama');
        
        // Set action form
        form.action = `/inventaris/kembali/${id}`;
        barangNama.textContent = namaBarang;
        peminjamNamaEl.textContent = peminjamNama;
        
        // Reset form
        form.reset();
        document.getElementById('kondisi_kembali').value = 'baik';
        
        modal.classList.add('open');
        document.body.style.overflow = 'hidden';
    };

    window.closeKembaliModal = function() {
        const modal = document.getElementById('kembaliModal');
        modal.classList.remove('open');
        document.body.style.overflow = '';
    };

    window.showHilangForm = function(id, namaBarang, peminjamNama) {
        const modal = document.getElementById('hilangModal');
        const form = document.getElementById('formHilang');
        const barangNama = document.getElementById('hilangBarangNama');
        const peminjamNamaEl = document.getElementById('hilangPeminjamNama');

        form.action = `/hilang/${id}`;
        barangNama.textContent = namaBarang;
        peminjamNamaEl.textContent = peminjamNama;

        form.reset();

        modal.classList.add('open');
        document.body.style.overflow = 'hidden';
    };

    window.closeHilangModal = function() {
        const modal = document.getElementById('hilangModal');
        modal.classList.remove('open');
        document.body.style.overflow = '';
    };

    document.getElementById('hilangModal')?.addEventListener('click', function(e) {
        if (e.target === this) window.closeHilangModal();
    });

    document.getElementById('formHilang')?.addEventListener('submit', function(e) {
        const submitBtn = this.querySelector('button[type="submit"]');
        submitBtn.disabled = true;
        submitBtn.innerHTML = '<span class="spinner-border"></span> Memproses...';
    });

    // Event listener untuk modal klik backdrop
    document.getElementById('kembaliModal')?.addEventListener('click', function(e) {
        if (e.target === this) window.closeKembaliModal();
    });

    // Escape key untuk tutup modal
    document.addEventListener('keydown', function(e) {
        if (e.key === 'Escape') {
            window.closeKembaliModal();
            window.closeHilangModal();
            window.closeDetail();
        }
    });

    // Handle submit form pengembalian
    document.getElementById('formKembali')?.addEventListener('submit', function(e) {
        const submitBtn = this.querySelector('button[type="submit"]');
        submitBtn.disabled = true;
        submitBtn.innerHTML = '<span class="spinner-border"></span> Memproses...';
    });

    // ── Tooltip untuk catatan kembali ──
    // Menggunakan tooltip native bawaan browser (title attribute)
    // Sudah otomatis bekerja karena kita menggunakan title="..."
    
    // Atau jika ingin custom tooltip yang lebih bagus:
    document.querySelectorAll('.catatan-tooltip').forEach(el => {
        el.addEventListener('mouseenter', function(e) {
            // Buat tooltip custom jika diperlukan
            const tooltipText = this.dataset.tooltip || this.title;
            // Native tooltip sudah cukup, tapi kita bisa tambahkan styling
        });
    });

    // ── Refresh otomatis setelah pengembalian ──
    // Cek flash data untuk reload jika perlu
    const flashData = document.getElementById('inv-flash-data');
    if (flashData) {
        const success = flashData.dataset.success;
        const error = flashData.dataset.error;
        if (success || error) {
            // Jika ada pesan sukses/error, refresh tabel
            setTimeout(() => {
                location.reload();
            }, 2000);
        }
    }
});