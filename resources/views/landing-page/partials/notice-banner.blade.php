@if (!session('anggota'))
    <div class="lp-notice-banner"
         x-data="{ show: !sessionStorage.getItem('puk-notice-dismissed') }"
         x-show="show"
         x-transition
         x-cloak>
        <div class="lp-container lp-notice-banner__inner">
            <div class="lp-notice-banner__text">
                <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                    <circle cx="12" cy="12" r="10"/>
                    <line x1="12" y1="16" x2="12" y2="12"/>
                    <line x1="12" y1="8" x2="12.01" y2="8"/>
                </svg>
                <span>Beberapa informasi seperti <strong>laporan keuangan</strong> dan <strong>inventaris</strong> hanya dapat dilihat setelah login.</span>
            </div>
            <div class="lp-notice-banner__actions">
                <a href="{{ route('anggota.login') }}" class="lp-notice-banner__login">Login</a>
                <button type="button"
                        class="lp-notice-banner__close"
                        @click="show = false; sessionStorage.setItem('puk-notice-dismissed', '1')"
                        aria-label="Tutup notice">
                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4" stroke-linecap="round"><path d="M6 6l12 12M18 6 6 18"/></svg>
                </button>
            </div>
        </div>
    </div>
@endif