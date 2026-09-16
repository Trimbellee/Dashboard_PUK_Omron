/**
 * Inventaris — Form Peminjaman (1 unit spesifik)
 */
import { flashSessionMessages } from './_shared.js';

document.addEventListener('DOMContentLoaded', function () {
    flashSessionMessages();

    const form = document.querySelector('.inv-form-card form');
    const submitBtn = form?.querySelector('button[type="submit"]');
    const tglPinjam = document.getElementById('tanggal_pinjam');
    const tglKembali = document.getElementById('tanggal_kembali');

    // keep tanggal_kembali from ever landing before tanggal_pinjam
    tglPinjam?.addEventListener('change', function () {
        if (tglKembali) tglKembali.min = this.value;
        if (tglKembali && tglKembali.value && tglKembali.value < this.value) {
            tglKembali.value = this.value;
        }
    });

    document.querySelectorAll('.form-input[required]').forEach((input) => {
        input.addEventListener('blur', function () {
            this.classList.toggle('is-invalid', !this.value.trim());
        });
        input.addEventListener('input', function () {
            if (this.value.trim()) this.classList.remove('is-invalid');
        });
    });

    if (form && submitBtn) {
        form.addEventListener('submit', function () {
            submitBtn.disabled = true;
            submitBtn.innerHTML = '<span class="spinner-border"></span> Memproses...';
        });
    }
});
