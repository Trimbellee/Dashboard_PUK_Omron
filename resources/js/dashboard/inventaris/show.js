/**
 * Inventaris — Detail Barang
 */
import { showToast, flashSessionMessages } from './_shared.js';

document.addEventListener('DOMContentLoaded', function () {
    flashSessionMessages();

    document.querySelectorAll('[data-copy]').forEach((el) => {
        el.style.cursor = 'pointer';
        el.title = 'Klik untuk menyalin';
        el.addEventListener('click', async function () {
            const value = this.dataset.copy;
            if (!value) return;
            try {
                await navigator.clipboard.writeText(value);
                showToast(`Tersalin: ${value}`, 'success', 2000);
            } catch {
                showToast('Gagal menyalin ke clipboard', 'error');
            }
        });
    });
});
