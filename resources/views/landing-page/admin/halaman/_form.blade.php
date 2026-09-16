@csrf
@if(isset($page))
    @method('PUT')
@endif

<div class="hlm-form-layout">
    <div class="hlm-form-main">

        <div class="hlm-form-card">
            <div class="hlm-form-card__header">
                <div class="hlm-form-card__icon">
                    <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M4 6h16M4 12h16M4 18h7"/></svg>
                </div>
                <div>
                    <h3 class="hlm-form-section__title">Informasi Menu</h3>
                    <p class="hlm-form-section__desc">Nama dan identitas yang tampil di sidebar admin.</p>
                </div>
            </div>

            <div class="lp-field">
                <label for="label">Label Menu</label>
                <input type="text" id="label" name="label" x-model="label"
                       @input="if (!slugTouched) slug = slugify(label)"
                       value="{{ old('label', $page->label ?? '') }}"
                       placeholder="Contoh: Laporan Tahunan" required maxlength="100">
                @error('label') <span class="lp-field__error">{{ $message }}</span> @enderror
            </div>

            <div class="lp-field">
                <label for="slug">Slug</label>
                <div class="hlm-slug-input">
                    <span class="hlm-slug-input__prefix">/p/</span>
                    <input type="text" id="slug" name="slug" x-model="slug"
                           @input="slugTouched = true"
                           value="{{ old('slug', $page->slug ?? '') }}"
                           placeholder="laporan-tahunan" required maxlength="100" pattern="[a-z0-9\-]+">
                </div>
                <small>Huruf kecil, angka, dan tanda hubung saja. Dipakai di URL placeholder dan sebagai key di matriks Peran &amp; Izin.</small>
                @error('slug') <span class="lp-field__error">{{ $message }}</span> @enderror
            </div>

            <div class="lp-field">
                <label for="icon_svg">Ikon (kode SVG, opsional)</label>
                <div class="hlm-icon-row">
                    <textarea id="icon_svg" name="icon_svg" rows="4" x-model="iconSvg"
                              placeholder="<svg width=&quot;18&quot; height=&quot;18&quot; ...>...</svg>">{{ old('icon_svg', $page->icon_svg ?? '') }}</textarea>
                    <div class="hlm-icon-preview-box">
                        <span x-show="!iconSvg" class="hlm-icon-preview-box__placeholder">Belum ada ikon</span>
                        <span x-show="iconSvg" x-html="iconSvg"></span>
                    </div>
                </div>
                <small>Tempel kode SVG dari <a href="https://heroicons.com" target="_blank" rel="noopener">heroicons.com</a> atau ikon sejenis (ukuran ~18–24px, stroke currentColor).</small>
                @error('icon_svg') <span class="lp-field__error">{{ $message }}</span> @enderror
            </div>

            <div class="lp-field lp-field--switch">
                <label class="hlm-switch">
                    <input type="checkbox" name="is_active" value="1" @checked(old('is_active', $page->is_active ?? true))>
                    <span class="hlm-switch__track"><span class="hlm-switch__thumb"></span></span>
                    <span class="hlm-switch__label">Tampilkan di menu sidebar</span>
                </label>
            </div>
        </div>

        <div class="hlm-form-card">
            <div class="hlm-form-card__header">
                <div class="hlm-form-card__icon hlm-form-card__icon--accent">
                    <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M13 2L3 14h7l-1 8 10-12h-7l1-8z"/></svg>
                </div>
                <div>
                    <h3 class="hlm-form-section__title">Routing Lanjutan</h3>
                    <p class="hlm-form-section__desc">Opsional — hanya diisi setelah controller &amp; route sungguhan dibuat.</p>
                </div>
            </div>

            <div class="lp-field">
                <label for="route_name">Route Kustom</label>
                <input type="text" id="route_name" name="route_name" placeholder="admin.laporan-saya.index"
                       value="{{ old('route_name', $page->route_name ?? '') }}" maxlength="150">
                <small>
                    Kosongkan dulu — halaman langsung aktif dengan tampilan placeholder "Halaman masih Kosong".
                    Isi nanti dengan nama route yang sudah dibuat di <code>routes/web.php</code> untuk menggantikan placeholder-nya.
                </small>
                @error('route_name') <span class="lp-field__error">{{ $message }}</span> @enderror
            </div>
        </div>

    </div>

    <div class="hlm-form-side">
        <div class="hlm-preview-card">
            <span class="hlm-preview-card__label">Preview di Sidebar</span>
            <div class="hlm-preview-item">
                <span class="hlm-preview-item__icon" x-html="iconSvg || '<svg width=&quot;18&quot; height=&quot;18&quot; viewBox=&quot;0 0 24 24&quot; fill=&quot;none&quot; stroke=&quot;currentColor&quot; stroke-width=&quot;2&quot;><rect x=&quot;4&quot; y=&quot;4&quot; width=&quot;16&quot; height=&quot;16&quot; rx=&quot;3&quot;/></svg>'"></span>
                <span x-text="label || 'Label Menu'"></span>
            </div>
            <p class="hlm-preview-card__hint">Tampilan ini menyesuaikan otomatis saat Anda mengetik.</p>
        </div>
    </div>
</div>

<div class="hlm-form-actions">
    <a href="{{ route('admin.halaman.index') }}" class="hlm-btn hlm-btn--ghost">Batal</a>
    <button type="submit" class="hlm-btn hlm-btn--primary">Simpan Halaman</button>
</div>