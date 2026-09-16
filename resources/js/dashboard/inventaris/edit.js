/**
 * Inventaris — Edit Barang
 */
import { flashSessionMessages, confirmAction, attachThousandsSeparator } from './_shared.js';

document.addEventListener('DOMContentLoaded', function () {
    flashSessionMessages();

    const form = document.querySelector('.inv-form-card form');
    const submitBtn = form?.querySelector('button[type="submit"]');
    const originalBtnHTML = submitBtn?.innerHTML;

    // Format harga dulu sebelum snapshot diambil.
    attachThousandsSeparator(document.getElementById('harga_perolehan'));

    // snapshot original values to detect real changes
    const initialValues = new Map();
    form?.querySelectorAll('.form-input').forEach((input) => initialValues.set(input, input.value));

    document.querySelectorAll('.form-input[required]').forEach((input) => {
        input.addEventListener('blur', function () {
            this.classList.toggle('is-invalid', !this.value.trim());
        });
        input.addEventListener('input', function () {
            if (this.value.trim()) this.classList.remove('is-invalid');
        });
    });

    // (blok "Harga: numeric only" lama dihapus — sudah digantikan attachThousandsSeparator di atas)

    if (form && submitBtn) {
        form.addEventListener('submit', function () {
            submitBtn.disabled = true;
            submitBtn.innerHTML = '<span class="spinner-border"></span> Menyimpan...';
        });
    }

    document.querySelector('.js-cancel-link')?.addEventListener('click', async function (e) {
        const changed = Array.from(initialValues.entries()).some(([input, original]) => input.value !== original);

        if (changed) {
            e.preventDefault();
            const ok = await confirmAction(
                'Ada perubahan yang belum disimpan. Yakin ingin keluar tanpa menyimpan?',
                { title: 'Perubahan Belum Disimpan', confirmText: 'Ya, Keluar' }
            );
            if (ok) window.location.href = this.href;
        }
    });

    if (document.querySelector('.is-invalid') && submitBtn) {
        submitBtn.disabled = false;
        submitBtn.innerHTML = originalBtnHTML;
    }
});