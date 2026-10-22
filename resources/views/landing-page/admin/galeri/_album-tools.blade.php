{{-- resources/views/landing-page/admin/galeri/_album-tools.blade.php
     Dipakai di index.blade: toggle tampilan, daftar album, dan bar aksi massal per album.
     Variabel dari controller: $mode, $albums, $covers, $tanpaKegiatan, $kegiatanAktif, $albumInfo, $activeFilter --}}
@php
    $can = fn (string $p) => \App\Helpers\PermissionHelper::can($p);
    $filterParam = $activeFilter !== 'semua' ? ['filter' => $activeFilter] : [];
@endphp

{{-- Toggle tampilan --}}
<div class="lp-view-toggle">
    <a href="{{ route('admin.galeri.index', $filterParam) }}"
       class="{{ $mode === 'album' ? 'active' : '' }}">▦ Album</a>
    <a href="{{ route('admin.galeri.index', ['tampilan' => 'foto'] + $filterParam) }}"
       class="{{ $mode === 'foto' && $kegiatanAktif === null ? 'active' : '' }}">☰ Semua Foto</a>
</div>

@if ($mode === 'album')
    {{-- ========== DAFTAR ALBUM ========== --}}
    @php $adaTanpa = $tanpaKegiatan && $albums->currentPage() === 1; @endphp

    <div class="lp-album-grid">
        @if ($adaTanpa)
            <a class="lp-album-card" href="{{ route('admin.galeri.index', ['kegiatan' => '__none'] + $filterParam) }}">
                <div class="lp-album-card__cover">
                    @if ($tanpaKegiatan['cover'])
                        <img src="{{ $tanpaKegiatan['cover'] }}" alt="" loading="lazy">
                    @else
                        <div class="lp-thumb-placeholder">🖼️</div>
                    @endif
                    <span class="lp-album-card__count">{{ $tanpaKegiatan['total'] }} foto</span>
                </div>
                <div class="lp-album-card__body">
                    <p class="lp-album-card__title">Tanpa kegiatan</p>
                </div>
            </a>
        @endif

        @foreach ($albums as $album)
            @php $cover = $covers[$album->cover_id] ?? null; @endphp
            <a class="lp-album-card" href="{{ route('admin.galeri.index', ['kegiatan' => $album->kegiatan] + $filterParam) }}">
                <div class="lp-album-card__cover">
                    @if ($cover)
                        <img src="{{ $cover }}" alt="{{ $album->kegiatan }}" loading="lazy">
                    @else
                        <div class="lp-thumb-placeholder">🖼️</div>
                    @endif
                    <span class="lp-album-card__count">{{ $album->total }} foto</span>
                </div>
                <div class="lp-album-card__body">
                    <p class="lp-album-card__title">{{ $album->kegiatan }}</p>
                    <div class="lp-album-card__badges">
                        @if ($album->draft > 0)
                            <span class="lp-admin-badge lp-admin-badge--draft">{{ $album->draft }} draft</span>
                        @endif
                        @if ($album->menunggu > 0)
                            <span class="lp-admin-badge lp-admin-badge--submitted_bidang5">{{ $album->menunggu }} menunggu</span>
                        @endif
                        @if ($album->published > 0)
                            <span class="lp-admin-badge lp-admin-badge--published">{{ $album->published }} published</span>
                        @endif
                    </div>
                </div>
            </a>
        @endforeach

        @if (!$adaTanpa && $albums->isEmpty())
            <div class="lp-admin-empty">
                <p>Belum ada album yang cocok.</p>
            </div>
        @endif
    </div>

@elseif ($kegiatanAktif !== null)
    {{-- ========== BAR AKSI MASSAL UNTUK SATU ALBUM ========== --}}
    @php
        $namaAlbum = $kegiatanAktif === '__none' ? 'Tanpa kegiatan' : $kegiatanAktif;
        $idHapus   = 'hapus-album-form';
    @endphp

    <div class="lp-album-bar">
        <div class="lp-album-bar__info">
            <a href="{{ route('admin.galeri.index', $filterParam) }}" class="lp-album-bar__back">← Semua Album</a>
            <h2 class="lp-album-bar__title">{{ $namaAlbum }}</h2>
            <span class="lp-album-bar__meta">{{ $albumInfo['total'] }} foto</span>
        </div>

        <div class="lp-album-bar__actions">
            @if ($can('create_gallery') && $albumInfo['bisa_kirim'] > 0)
                @php
                    $nKirim = $albumInfo['bisa_kirim'];
                    if ($can('approve_gallery')) {
                        $labelKirim = "Terbitkan semua ({$nKirim})";
                        $pesanKirim = "Terbitkan {$nKirim} foto di album \"{$namaAlbum}\" langsung ke galeri publik?";
                    } elseif ($can('review_gallery')) {
                        $labelKirim = "Kirim semua ke Sekretaris ({$nKirim})";
                        $pesanKirim = "Kirim {$nKirim} foto di album \"{$namaAlbum}\" ke Sekretaris untuk approval?";
                    } else {
                        $labelKirim = "Kirim semua ke Reviewer ({$nKirim})";
                        $pesanKirim = "Kirim {$nKirim} foto di album \"{$namaAlbum}\" ke Reviewer?";
                    }
                @endphp
                <form id="submit-album-form" action="{{ route('admin.galeri.album-aksi') }}" method="POST">
                    @csrf
                    <input type="hidden" name="kegiatan" value="{{ $kegiatanAktif }}">
                    <input type="hidden" name="aksi" value="submit">
                </form>
                <button type="button" class="lp-pill-btn lp-pill-btn--primary"
                        @click="$dispatch('open-confirm', { message: {{ \Illuminate\Support\Js::from($pesanKirim) }}, formId: 'submit-album-form' })">
                    {{ $labelKirim }}
                </button>
            @endif

            @if ($can('review_gallery') && $albumInfo['review'] > 0)
                @php $pesanReview = "Teruskan {$albumInfo['review']} foto di album \"{$namaAlbum}\" ke approval final?"; @endphp
                <form id="review-album-form" action="{{ route('admin.galeri.album-aksi') }}" method="POST">
                    @csrf
                    <input type="hidden" name="kegiatan" value="{{ $kegiatanAktif }}">
                    <input type="hidden" name="aksi" value="review">
                </form>
                <button type="button" class="lp-pill-btn lp-pill-btn--success"
                        @click="$dispatch('open-confirm', { message: {{ \Illuminate\Support\Js::from($pesanReview) }}, formId: 'review-album-form' })">
                    Teruskan semua ke approval ({{ $albumInfo['review'] }})
                </button>
            @endif

            @if ($can('approve_gallery') && $albumInfo['approval'] > 0)
                @php $pesanApprove = "Setujui dan publikasikan {$albumInfo['approval']} foto di album \"{$namaAlbum}\" ke galeri publik?"; @endphp
                <form id="approve-album-form" action="{{ route('admin.galeri.album-aksi') }}" method="POST">
                    @csrf
                    <input type="hidden" name="kegiatan" value="{{ $kegiatanAktif }}">
                    <input type="hidden" name="aksi" value="approve">
                </form>
                <button type="button" class="lp-pill-btn lp-pill-btn--success"
                        @click="$dispatch('open-confirm', { message: {{ \Illuminate\Support\Js::from($pesanApprove) }}, formId: 'approve-album-form' })">
                    Setujui semua ({{ $albumInfo['approval'] }})
                </button>
            @endif

            @if ($albumInfo['bisa_hapus'] > 0)
                <form id="{{ $idHapus }}" action="{{ route('admin.galeri.album-aksi') }}" method="POST">
                    @csrf
                    <input type="hidden" name="kegiatan" value="{{ $kegiatanAktif }}">
                    <input type="hidden" name="aksi" value="hapus">
                </form>
                <button type="button" class="lp-pill-btn lp-pill-btn--danger"
                        @click="$dispatch('open-confirm', { message: 'Hapus {{ $albumInfo['bisa_hapus'] }} foto di album ini secara permanen? Foto yang tidak boleh Anda hapus akan dilewati.', formId: '{{ $idHapus }}' })">
                    Hapus album ({{ $albumInfo['bisa_hapus'] }})
                </button>
            @endif
        </div>
    </div>
@endif