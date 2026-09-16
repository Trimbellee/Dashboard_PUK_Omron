{{-- ═══════════════════════════════════════
     RIWAYAT VERSI SEBELUMNYA
═══════════════════════════════════════ --}}
@if(!empty($meta['arsip_versi']) && $meta['arsip_versi']->count())
<div class="mt-6" style="max-width:900px;margin:32px auto 0;">
    <div style="display:flex;align-items:center;gap:8px;margin-bottom:16px;">
        <i class="ti ti-versions" style="font-size:16px;color:#6b7280;"></i>
        <h3 class="font-bold uppercase text-sm" style="color:#374151;letter-spacing:.02em;margin:0;">
            Riwayat Versi Sebelumnya
        </h3>
    </div>

    <div style="display:flex;flex-direction:column;gap:10px;">
        @foreach($meta['arsip_versi'] as $v)
        <div style="display:flex;align-items:flex-start;justify-content:space-between;gap:12px;
                    padding:14px 16px;border-radius:12px;
                    background:#fffbeb;border:1px solid #fde68a;">
            <div style="display:flex;gap:12px;min-width:0;flex:1;">
                <div style="flex-shrink:0;width:32px;height:32px;border-radius:8px;
                            background:#fef3c7;color:#b45309;
                            display:flex;align-items:center;justify-content:center;">
                    <i class="ti ti-lock-open" style="font-size:15px;"></i>
                </div>

                <div style="min-width:0;">
                    <div style="display:flex;flex-wrap:wrap;align-items:baseline;gap:6px;">
                        <span style="font-weight:700;font-size:12px;color:#92400e;
                                    background:#fde68a;padding:1px 8px;border-radius:20px;">
                            Versi {{ $v->versi_ke }}
                        </span>
                        <span style="font-size:11px;color:#92400e;">
                            dibuka oleh <strong>{{ $v->dibuka_oleh }}</strong>
                            &middot; {{ \Carbon\Carbon::parse($v->dibuka_at)->translatedFormat('d M Y, H:i') }}
                        </span>
                    </div>
                    <div style="font-size:12px;color:#78350f;font-style:italic;margin-top:6px;line-height:1.5;">
                        "{{ $v->alasan_buka_kembali }}"
                    </div>
                </div>
            </div>

            @if($v->pdf_url)
            <a href="{{ $v->pdf_url }}" target="_blank"
               title="Lihat PDF Versi {{ $v->versi_ke }}"
               style="flex-shrink:0;width:32px;height:32px;border-radius:8px;
                      background:#d97706;color:#fff;display:inline-flex;
                      align-items:center;justify-content:center;text-decoration:none;">
                <i class="ti ti-file-type-pdf" style="font-size:16px;"></i>
            </a>
            @endif
        </div>
        @endforeach
    </div>
</div>
@endif