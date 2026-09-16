
{{-- ═══════════════════════════════════════
     RIWAYAT LAPORAN (Timeline)
═══════════════════════════════════════ --}}
@if(!empty($meta['riwayat']))
@php
    $aksiConfig = [
        'simpan_draft'          => ['icon' => 'device-floppy', 'color' => '#64748b', 'bg' => '#f1f5f9', 'label' => 'Menyimpan Draft'],
        'ajukan_ke_sekretaris'  => ['icon' => 'send',           'color' => '#0891b2', 'bg' => '#ecfeff', 'label' => 'Mengajukan ke Sekretaris'],
        'setujui_sekretaris'    => ['icon' => 'circle-check',   'color' => '#7c3aed', 'bg' => '#f5f3ff', 'label' => 'Menyetujui (Sekretaris)'],
        'setujui_ketua'         => ['icon' => 'circle-check',   'color' => '#7c3aed', 'bg' => '#f5f3ff', 'label' => 'Menyetujui (Ketua)'],
        'tolak'                 => ['icon' => 'x',               'color' => '#dc2626', 'bg' => '#fef2f2', 'label' => 'Menolak & Meminta Revisi'],
        'buka_kembali'          => ['icon' => 'lock-open',       'color' => '#d97706', 'bg' => '#fffbeb', 'label' => 'Membuka Kembali Laporan'],
    ];
    $riwayatList  = array_reverse($meta['riwayat']);
    $riwayatLimit = 5;
    $riwayatSisa  = count($riwayatList) - $riwayatLimit;
@endphp
<div class="mt-10" style="max-width:900px;margin:40px auto 0;">
    <div style="display:flex;align-items:center;gap:8px;margin-bottom:16px;">
        <i class="ti ti-history" style="font-size:16px;color:#6b7280;"></i>
        <h3 class="font-bold uppercase text-sm" style="color:#374151;letter-spacing:.02em;margin:0;">
            Riwayat Laporan
        </h3>
    </div>

    <div id="riwayatTimelineWrap" style="position:relative;padding-left:6px;">
        <div style="position:absolute;left:17px;top:6px;bottom:6px;width:2px;background:#e5e7eb;"></div>

        @foreach($riwayatList as $idx => $r)
        @php
            $aksi = $r['aksi'] ?? '';
            $cfg  = $aksiConfig[$aksi] ?? ['icon' => 'point', 'color' => '#6b7280', 'bg' => '#f3f4f6', 'label' => ucfirst(str_replace('_',' ', $aksi))];
            $isHidden = $idx >= $riwayatLimit;
        @endphp
        <div class="riwayat-item {{ $isHidden ? 'riwayat-hidden' : '' }}"
             style="position:relative;display:{{ $isHidden ? 'none' : 'flex' }};gap:14px;padding-bottom:18px;">
            <div style="flex-shrink:0;width:34px;height:34px;border-radius:50%;
                        background:{{ $cfg['bg'] }};color:{{ $cfg['color'] }};
                        display:flex;align-items:center;justify-content:center;
                        border:2px solid #fff;box-shadow:0 0 0 1px #e5e7eb;
                        position:relative;z-index:1;">
                <i class="ti ti-{{ $cfg['icon'] }}" style="font-size:16px;"></i>
            </div>

            <div style="flex:1;padding-top:4px;min-width:0;">
                <div style="display:flex;flex-wrap:wrap;align-items:baseline;gap:6px;">
                    <span style="font-weight:600;font-size:13px;color:#111827;">
                        {{ $cfg['label'] }}
                    </span>
                    <span style="font-size:12px;color:#9ca3af;">
                        &middot; {{ \Carbon\Carbon::parse($r['at'])->translatedFormat('d M Y, H:i') }}
                    </span>
                </div>
                <div style="font-size:12px;color:#6b7280;margin-top:2px;">
                    oleh <span style="font-weight:500;color:#374151;">{{ $r['nama'] ?? '-' }}</span>
                    <span style="color:#9ca3af;">({{ $r['role'] ?? '-' }})</span>
                </div>

                @if(!empty($r['catatan']))
                <div style="margin-top:8px;padding:8px 12px;border-radius:8px;
                            background:{{ $cfg['bg'] }};border-left:3px solid {{ $cfg['color'] }};
                            font-size:12px;color:#374151;font-style:italic;line-height:1.5;">
                    "{{ $r['catatan'] }}"
                </div>
                @endif
            </div>
        </div>
        @endforeach
    </div>

    @if($riwayatSisa > 0)
    <button type="button" id="btnToggleRiwayat" onclick="toggleRiwayatTimeline()"
        style="display:flex;align-items:center;gap:6px;margin-top:4px;margin-left:6px;
               background:none;border:none;cursor:pointer;
               font-size:12px;font-weight:600;color:#2563eb;padding:6px 0;">
        <i class="ti ti-chevron-down" id="iconToggleRiwayat" style="font-size:14px;"></i>
        <span id="textToggleRiwayat">Tampilkan {{ $riwayatSisa }} riwayat lainnya</span>
    </button>
    @endif
</div>

<script>
let riwayatExpanded = false;

function toggleRiwayatTimeline(){
    riwayatExpanded = !riwayatExpanded;

    document.querySelectorAll('.riwayat-hidden').forEach(el => {
        el.style.display = riwayatExpanded ? 'flex' : 'none';
    });

    const icon = document.getElementById('iconToggleRiwayat');
    const text = document.getElementById('textToggleRiwayat');
    const totalSisa = document.querySelectorAll('.riwayat-hidden').length;

    if (riwayatExpanded) {
        icon.classList.remove('ti-chevron-down');
        icon.classList.add('ti-chevron-up');
        text.innerText = 'Sembunyikan riwayat';
    } else {
        icon.classList.remove('ti-chevron-up');
        icon.classList.add('ti-chevron-down');
        text.innerText = `Tampilkan ${totalSisa} riwayat lainnya`;
    }
}
</script>
@endif
