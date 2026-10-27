{{-- ===== MODAL: ASISTEN REDAKSI AI =====
     Di-include DI DALAM <form x-data="beritaEditor(...)"> supaya memakai scope Alpine yang sama.
     Semua input di sini TIDAK punya atribut name, jadi tidak ikut terkirim saat form disimpan
     maupun saat auto-save. --}}
<div x-show="aiOpen"
     x-cloak
     x-transition
     @click.self="if (!aiLoading) aiOpen = false"
     @keydown.escape.window="if (!aiLoading) aiOpen = false"
     class="lp-modal">
    <div class="lp-modal__inner lp-modal__inner--violet lp-modal__inner--ai">
        <div class="lp-modal__header">
            <span class="lp-modal__title">✨ Asisten Redaksi AI — Buat Berita dari Catatan</span>
            <button type="button" class="lp-modal__close" @click="aiOpen = false" :disabled="aiLoading" aria-label="Tutup">✕</button>
        </div>

        <div class="lp-modal__body">
            <p>
                Isi catatan kegiatan dengan bahasa sehari-hari. Cukup <strong>Nama kegiatan</strong> dan
                <strong>Apa yang terjadi</strong> yang wajib; makin lengkap isiannya, makin akurat beritanya.
                AI tidak akan mengarang fakta, dan hasilnya <strong>hanya mengisi form sebagai draf</strong> —
                periksa dulu sebelum disimpan.
            </p>

            <div class="lp-ai-alert lp-ai-alert--error" x-show="aiError" x-cloak x-text="aiError"></div>

            <div class="lp-ai-alert lp-ai-alert--warn" x-show="aiQuestions.length" x-cloak>
                <strong>Informasi masih kurang. Mohon lengkapi:</strong>
                <ul>
                    <template x-for="(q, i) in aiQuestions" :key="i">
                        <li x-text="q"></li>
                    </template>
                </ul>
            </div>

            <div class="lp-ai-grid">
                <template x-for="f in aiFields" :key="f.key">
                    <div class="lp-form-group" :class="{ 'lp-ai-field--full': f.full }">
                        <label class="lp-form-label" :for="'ai-' + f.key">
                            <span x-text="f.label"></span>
                            <span x-show="f.required" class="lp-ai-required">*</span>
                        </label>
                        <template x-if="f.type === 'textarea'">
                            <textarea :id="'ai-' + f.key"
                                      rows="2"
                                      class="lp-form-textarea"
                                      x-model="aiForm[f.key]"
                                      :placeholder="f.placeholder"
                                      :disabled="aiLoading"></textarea>
                        </template>
                        <template x-if="f.type !== 'textarea'">
                            <input type="text"
                                   :id="'ai-' + f.key"
                                   class="lp-form-input"
                                   x-model="aiForm[f.key]"
                                   :placeholder="f.placeholder"
                                   :disabled="aiLoading"
                                   @keydown.enter.prevent>
                        </template>
                    </div>
                </template>
            </div>

            {{-- Hasil & catatan redaksi setelah berhasil --}}
            <div class="lp-ai-result" x-show="aiResult" x-cloak>
                <div class="lp-ai-alert lp-ai-alert--ok">
                    ✅ Form berita sudah terisi. <strong>Belum tersimpan</strong> — periksa lalu klik
                    “Simpan Draft”. AI belum memverifikasi fakta apa pun.
                </div>

                <template x-if="aiResult">
                    <div class="lp-ai-result__grid">
                        <div class="lp-ai-result__box">
                            <h4>Pemeriksaan Redaksi</h4>
                            <ul>
                                <template x-for="(p, i) in aiResult.pemeriksaan" :key="i">
                                    <li x-text="p"></li>
                                </template>
                            </ul>
                        </div>
                        <div class="lp-ai-result__box">
                            <h4>Saran Foto Utama</h4>
                            <p><strong>Objek:</strong> <span x-text="aiResult.saran_foto.objek"></span></p>
                            <p><strong>Momen:</strong> <span x-text="aiResult.saran_foto.momen"></span></p>
                            <p><strong>Crop:</strong> horizontal 16:9</p>
                            <p><strong>Caption:</strong> <span x-text="aiResult.saran_foto.caption"></span></p>
                            <p><strong>Alt text:</strong> <span x-text="aiResult.saran_foto.alt_text"></span></p>
                        </div>
                        <div class="lp-ai-result__box">
                            <h4>Catatan Lain</h4>
                            <p><strong>Alasan kategori:</strong> <span x-text="aiResult.alasan_kategori"></span></p>
                            <p><strong>Focus keyword:</strong> <span x-text="aiResult.focus_keyword"></span></p>
                            <p><strong>Saran slug:</strong> <span x-text="aiResult.slug"></span></p>
                            <p class="lp-form-hint">Slug dibuat otomatis oleh sistem dari judul saat disimpan.</p>
                        </div>
                    </div>
                </template>
            </div>

            <div class="lp-modal__actions">
                <button type="button" class="lp-btn lp-btn--ghost" @click="aiOpen = false" :disabled="aiLoading">
                    <span x-text="aiResult ? 'Tutup & periksa form' : 'Batal'"></span>
                </button>
                <button type="button" class="lp-btn lp-btn--ai" @click="generateAI()" :disabled="aiLoading">
                    <span x-show="!aiLoading" x-text="aiResult ? '🔄 Buat Ulang' : '✨ Buat Berita'"></span>
                    <span x-show="aiLoading" x-cloak>⏳ Menyusun berita… (bisa 10–40 detik)</span>
                </button>
            </div>
        </div>
    </div>
</div>

@push('scripts')
<script>
    // Mixin Alpine untuk beritaEditor — dipakai lewat `...aiAssistant()`.
    function aiAssistant() {
        return {
            aiOpen: false,
            aiLoading: false,
            aiError: '',
            aiQuestions: [],
            aiResult: null,

            aiForm: {
                nama_kegiatan: '', apa: '', siapa: '', kapan: '', dimana: '', mengapa: '',
                bagaimana: '', hasil: '', poin_penting: '', narasumber: '', kutipan: '',
                info_tambahan: '', dokumentasi: '', peliput: '',
            },

            aiFields: [
                { key: 'nama_kegiatan', label: 'Nama kegiatan', type: 'input', required: true, full: true, placeholder: 'Contoh: Pertemuan Anggota PUK Omron' },
                { key: 'apa', label: 'Apa yang terjadi?', type: 'textarea', required: true, full: true, placeholder: 'Ceritakan singkat apa yang dilakukan' },
                { key: 'siapa', label: 'Siapa yang terlibat?', type: 'textarea', placeholder: 'Pengurus, anggota, pihak lain' },
                { key: 'kapan', label: 'Kapan?', type: 'input', placeholder: 'Contoh: Sabtu, 25 Oktober 2026, pukul 09.00' },
                { key: 'dimana', label: 'Di mana?', type: 'input', placeholder: 'Lokasi kegiatan' },
                { key: 'mengapa', label: 'Mengapa dilakukan?', type: 'textarea', placeholder: 'Tujuan / latar belakang' },
                { key: 'bagaimana', label: 'Bagaimana berlangsung?', type: 'textarea', full: true, placeholder: 'Urutan acara: diskusi, penyampaian informasi, tanya jawab…' },
                { key: 'hasil', label: 'Hasil / tindak lanjut', type: 'textarea', placeholder: 'Isi hanya jika sudah pasti. Kosongkan jika belum ada.' },
                { key: 'poin_penting', label: 'Pesan / poin penting', type: 'textarea', placeholder: 'Hal yang ingin ditonjolkan' },
                { key: 'narasumber', label: 'Narasumber & jabatan', type: 'input', placeholder: 'Nama, jabatan' },
                { key: 'kutipan', label: 'Kutipan yang benar-benar diucapkan', type: 'textarea', placeholder: 'Kosongkan jika tidak ada. AI tidak akan membuat kutipan sendiri.' },
                { key: 'info_tambahan', label: 'Informasi tambahan', type: 'textarea', placeholder: 'Opsional' },
                { key: 'dokumentasi', label: 'Foto / dokumentasi yang ada', type: 'input', placeholder: 'Contoh: 5 foto kegiatan' },
                { key: 'peliput', label: 'Nama penulis / peliput', type: 'input', placeholder: 'Opsional' },
            ],

            async generateAI() {
                if (this.aiLoading) return;

                this.aiError = '';
                this.aiQuestions = [];

                if (!this.aiForm.nama_kegiatan.trim() || !this.aiForm.apa.trim()) {
                    this.aiError = 'Isi minimal "Nama kegiatan" dan "Apa yang terjadi".';
                    return;
                }

                const hasContent = (this.judul && this.judul.trim())
                    || (this.quill && this.quill.getText().trim().length > 0);

                if (hasContent && !this.aiResult && !confirm(
                    'Form berita sudah berisi tulisan. Hasil AI akan MENIMPA kategori, judul, tags, ringkasan, konten, dan SEO. Lanjutkan?'
                )) {
                    return;
                }

                this.aiLoading = true;

                try {
                    const res = await fetch('{{ route("admin.berita.generate-ai") }}', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                            'Accept': 'application/json',
                            'X-CSRF-TOKEN': '{{ csrf_token() }}',
                            'X-Requested-With': 'XMLHttpRequest',
                        },
                        body: JSON.stringify(this.aiForm),
                    });

                    const data = await res.json().catch(() => ({}));

                    if (!res.ok || !data.success) {
                        if (res.status === 422 && data.errors) {
                            this.aiError = Object.values(data.errors).flat().join(' ');
                        } else if (res.status === 419) {
                            this.aiError = 'Sesi habis. Muat ulang halaman (simpan dulu pekerjaan Anda).';
                        } else if (res.status === 403) {
                            this.aiError = 'Anda tidak punya izin menggunakan fitur ini.';
                        } else {
                            this.aiError = data.message || 'Gagal menyusun berita. Silakan coba lagi.';
                        }
                        return;
                    }

                    if (data.needs_more_info) {
                        this.aiQuestions = data.questions || [];
                        return;
                    }

                    this.applyAiResult(data.data);
                } catch (e) {
                    console.error('AI error:', e);
                    this.aiError = 'Terjadi kesalahan di halaman: ' + (e && e.message ? e.message : 'tidak diketahui');
                } finally {
                    this.aiLoading = false;
                }
            },

            applyAiResult(d) {
                this.kategori = d.kategori;
                this.judul = d.judul;
                this.ringkasan = d.ringkasan;
                this.tags = (d.tags || []).join(', ');
                this.isFeatured = !!d.featured;
                this.isBreaking = !!d.breaking;
                this.isHeadline = !!d.headline;
                this.metaTitle = d.meta_title;
                this.metaDescription = d.meta_description;

                // Pakai objek Quill asli (bukan proxy Alpine) dan setContents(),
                // yang tidak menyentuh kursor/selection.
                const q = (window.Alpine && Alpine.raw) ? Alpine.raw(this.quill) : this.quill;

                if (q) {
                    try {
                        const delta = q.clipboard.convert({ html: d.konten_html || '' });
                        q.setContents(delta, 'user');   // 'user' agar bisa di-undo (Ctrl+Z)
                    } catch (err) {
                        console.warn('Fallback isi editor:', err);
                        q.root.innerHTML = d.konten_html || '';
                    }
                    this.konten = q.root.innerHTML;
                } else {
                    this.konten = d.konten_html || '';
                }

                this.aiResult = d;

                this.$nextTick(() => {
                    // Counter meta diperbarui lewat event DOM di halaman ini, jadi picu manual.
                    const t = document.getElementById('meta-title-counter');
                    const m = document.getElementById('meta-desc-counter');
                    if (t) t.textContent = (this.metaTitle || '').length + '/70';
                    if (m) m.textContent = (this.metaDescription || '').length + '/250';
                    this.updateSeoScore();
                });
            },
        };
    }
</script>
@endpush