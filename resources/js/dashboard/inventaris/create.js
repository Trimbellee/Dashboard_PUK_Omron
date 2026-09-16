/**
 * Inventaris — Tambah Barang
 */
import { flashSessionMessages, confirmAction, attachThousandsSeparator } from './_shared.js';

document.addEventListener('DOMContentLoaded', function () {
    flashSessionMessages();

    const form = document.querySelector('.inv-form-card form');
    const submitBtn = form?.querySelector('button[type="submit"]');
    const originalBtnHTML = submitBtn?.innerHTML;

    const namaInput = document.getElementById('nama_barang');
    const kodeInput = document.getElementById('kode_barang');
    const jumlahUnitInput = document.getElementById('jumlah_unit');
    const kodeBarangNote = document.getElementById('kodeBarangNote');
    const kodeBarangHint = document.getElementById('kodeBarangHint');

    // ── Kode barang hanya berlaku kalau Jumlah Unit = 1 ──
    function syncKodeBarangState() {
        if (!jumlahUnitInput || !kodeInput) return;
        const isBulk = parseInt(jumlahUnitInput.value, 10) > 1;

        kodeInput.disabled = isBulk;
        kodeInput.placeholder = isBulk ? 'Dibuat otomatis per unit' : 'Otomatis dari nama barang';
        if (isBulk) kodeInput.value = '';

        if (kodeBarangNote) {
            kodeBarangNote.textContent = isBulk
                ? `Kode akan dibuat otomatis untuk ${jumlahUnitInput.value || 0} unit — semuanya unik.`
                : 'Hanya berlaku kalau Jumlah Unit = 1. Untuk lebih dari 1 unit, kode dibuat otomatis per unit supaya tetap unik.';
        }
        if (kodeBarangHint) {
            kodeBarangHint.textContent = isBulk ? '(otomatis, tidak bisa diisi manual)' : '(opsional, otomatis kalau kosong)';
        }
    }

    jumlahUnitInput?.addEventListener('input', syncKodeBarangState);
    syncKodeBarangState();

    // ── Auto-generate Kode Barang saat blur (hanya mode 1 unit) ──
    if (namaInput && kodeInput) {
        namaInput.addEventListener('blur', function () {
            if (kodeInput.disabled || kodeInput.value.trim()) return;
            const name = this.value.trim();
            if (!name) return;
            const prefix = name.substring(0, 3).toUpperCase();
            const random = Math.floor(Math.random() * 10000).toString().padStart(4, '0');
            kodeInput.value = `${prefix}-${random}`;
        });
    }

    // ── Inline validation feedback ──
    document.querySelectorAll('.form-input[required]').forEach((input) => {
        input.addEventListener('blur', function () {
            this.classList.toggle('is-invalid', !this.value.trim());
        });
        input.addEventListener('input', function () {
            if (this.value.trim()) this.classList.remove('is-invalid');
        });
    });

        // ── Harga: pemisah ribuan otomatis ──
    attachThousandsSeparator(document.getElementById('harga_perolehan'));

    // ── Submit loading state ──
    if (form && submitBtn) {
        form.addEventListener('submit', function () {
            submitBtn.disabled = true;
            submitBtn.innerHTML = '<span class="spinner-border"></span> Menyimpan...';
        });
    }

    // ── Confirm before leaving with unsaved data ──
    document.querySelector('.js-cancel-link')?.addEventListener('click', async function (e) {
        const inputs = form?.querySelectorAll('.form-input');
        const hasValue = Array.from(inputs || []).some((i) => i.value.trim() && i.name !== 'jumlah_unit');

        if (hasValue) {
            e.preventDefault();
            const ok = await confirmAction(
                'Ada data yang belum disimpan. Yakin ingin keluar tanpa menyimpan?',
                { title: 'Data Belum Disimpan', confirmText: 'Ya, Keluar' }
            );
            if (ok) window.location.href = this.href;
        }
    });

    if (document.querySelector('.is-invalid') && submitBtn) {
        submitBtn.disabled = false;
        submitBtn.innerHTML = originalBtnHTML;
    }
});
