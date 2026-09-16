@extends('layouts.app')
@section('title', 'Hasil Import')

@section('content')

<div style="max-width:1000px;margin:0 auto;display:flex;flex-direction:column;gap:24px;animation:fadeUp 0.35s ease both;">

    {{-- ── PIPELINE STEPS ───────────────────────────────────────── --}}
    <div style="display:flex;align-items:center;position:relative;">
        <div style="position:absolute;top:50%;left:0;right:0;height:2px;background:var(--border);z-index:0;transform:translateY(-50%);"></div>

        {{-- Step 1: Done --}}
        <div style="position:relative;z-index:1;display:flex;align-items:center;gap:8px;">
            <span style="width:28px;height:28px;border-radius:50%;background:var(--accent);color:#fff;display:flex;align-items:center;justify-content:center;font-size:11px;font-weight:700;flex-shrink:0;">
                <svg style="width:13px;height:13px;" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="3"><path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"/></svg>
            </span>
            <span style="font-size:13px;font-weight:500;color:var(--accent-text);white-space:nowrap;">Upload File</span>
        </div>

        <div style="flex:1;height:2px;background:var(--accent);margin:0 12px;position:relative;z-index:1;"></div>

        {{-- Step 2: Done --}}
        <div style="position:relative;z-index:1;display:flex;align-items:center;gap:8px;">
            <span style="width:28px;height:28px;border-radius:50%;background:var(--accent);color:#fff;display:flex;align-items:center;justify-content:center;font-size:11px;font-weight:700;flex-shrink:0;">
                <svg style="width:13px;height:13px;" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="3"><path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"/></svg>
            </span>
            <span style="font-size:13px;font-weight:500;color:var(--accent-text);white-space:nowrap;">Preview & Validasi</span>
        </div>

        <div style="flex:1;height:2px;background:var(--accent);margin:0 12px;position:relative;z-index:1;"></div>

        {{-- Step 3: Active --}}
        <div style="position:relative;z-index:1;display:flex;align-items:center;gap:8px;">
            <span style="width:28px;height:28px;border-radius:50%;background:var(--accent);color:#fff;display:flex;align-items:center;justify-content:center;font-size:12px;font-weight:700;flex-shrink:0;box-shadow:0 0 0 4px var(--accent-muted);">3</span>
            <span style="font-size:13px;font-weight:600;color:var(--accent-text);white-space:nowrap;">Selesai</span>
        </div>
    </div>

    {{-- ── PAGE HEADER ──────────────────────────────────────────── --}}
    <div style="display:flex;align-items:center;justify-content:space-between;flex-wrap:wrap;gap:12px;">
        <div>
            <h1 style="font-size:20px;font-weight:600;color:var(--text-primary);margin:0 0 4px;">Hasil Import Anggota</h1>
            <p style="font-size:13px;color:var(--text-muted);margin:0;">Import selesai diproses. Berikut ringkasan perubahan pada database.</p>
        </div>
        <a href="{{ route('anggota.index') }}" class="btn btn-ghost" style="text-decoration:none;gap:6px;">
            <svg style="width:14px;height:14px;" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M15 19l-7-7 7-7"/></svg>
            Kembali ke Daftar
        </a>
    </div>

    {{-- ── STAT CARDS ───────────────────────────────────────────── --}}
    <div style="display:grid;grid-template-columns:repeat(4,1fr);gap:14px;">

        {{-- Anggota Baru --}}
        <div style="background:var(--bg-surface);border:1px solid rgba(22,163,74,0.3);border-radius:16px;padding:20px 22px;box-shadow:var(--shadow-sm);position:relative;overflow:hidden;">
            <div style="position:absolute;top:-16px;right:-16px;width:72px;height:72px;border-radius:50%;background:rgba(22,163,74,0.07);"></div>
            <div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:12px;">
                <span style="font-size:10.5px;font-weight:600;letter-spacing:0.07em;text-transform:uppercase;color:var(--text-muted);">Anggota Baru</span>
                <span style="width:30px;height:30px;border-radius:8px;background:rgba(22,163,74,0.1);display:flex;align-items:center;justify-content:center;font-size:15px;">🆕</span>
            </div>
            <div style="font-size:32px;font-weight:800;color:#16A34A;letter-spacing:-0.02em;font-variant-numeric:tabular-nums;">
                {{ count($newMembers) }}
            </div>
            <div style="margin-top:6px;font-size:11.5px;color:#16A34A;font-weight:500;">
                {{ count($newMembers) > 0 ? 'ditambahkan ke database' : 'tidak ada penambahan' }}
            </div>
        </div>

        {{-- Updated --}}
        <div style="background:var(--bg-surface);border:1px solid rgba(37,99,235,0.25);border-radius:16px;padding:20px 22px;box-shadow:var(--shadow-sm);position:relative;overflow:hidden;">
            <div style="position:absolute;top:-16px;right:-16px;width:72px;height:72px;border-radius:50%;background:rgba(37,99,235,0.06);"></div>
            <div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:12px;">
                <span style="font-size:10.5px;font-weight:600;letter-spacing:0.07em;text-transform:uppercase;color:var(--text-muted);">Updated</span>
                <span style="width:30px;height:30px;border-radius:8px;background:var(--accent-muted);display:flex;align-items:center;justify-content:center;font-size:15px;">✏️</span>
            </div>
            <div style="font-size:32px;font-weight:800;color:var(--accent);letter-spacing:-0.02em;font-variant-numeric:tabular-nums;">
                {{ count($updatedMembers) }}
            </div>
            <div style="margin-top:6px;font-size:11.5px;color:var(--accent-text);font-weight:500;">
                {{ count($updatedMembers) > 0 ? 'data diperbarui' : 'tidak ada perubahan' }}
            </div>
        </div>

        {{-- Resign --}}
        <div style="background:var(--bg-surface);border:1px solid rgba(220,38,38,0.25);border-radius:16px;padding:20px 22px;box-shadow:var(--shadow-sm);position:relative;overflow:hidden;">
            <div style="position:absolute;top:-16px;right:-16px;width:72px;height:72px;border-radius:50%;background:rgba(220,38,38,0.06);"></div>
            <div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:12px;">
                <span style="font-size:10.5px;font-weight:600;letter-spacing:0.07em;text-transform:uppercase;color:var(--text-muted);">Resign / Habis Kontrak</span>
                <span style="width:30px;height:30px;border-radius:8px;background:rgba(220,38,38,0.08);display:flex;align-items:center;justify-content:center;font-size:15px;">🚪</span>
            </div>
            <div style="font-size:32px;font-weight:800;color:#DC2626;letter-spacing:-0.02em;font-variant-numeric:tabular-nums;">
                {{ $resignedMembers->count() }}
            </div>
            <div style="margin-top:6px;font-size:11.5px;color:#DC2626;font-weight:500;">
                {{ $resignedMembers->count() > 0 ? 'dinonaktifkan' : 'tidak ada' }}
            </div>
        </div>

        {{-- Failed --}}
        <div style="background:var(--bg-surface);border:1px solid rgba(217,119,6,0.25);border-radius:16px;padding:20px 22px;box-shadow:var(--shadow-sm);position:relative;overflow:hidden;">
            <div style="position:absolute;top:-16px;right:-16px;width:72px;height:72px;border-radius:50%;background:rgba(217,119,6,0.06);"></div>
            <div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:12px;">
                <span style="font-size:10.5px;font-weight:600;letter-spacing:0.07em;text-transform:uppercase;color:var(--text-muted);">Failed Rows</span>
                <span style="width:30px;height:30px;border-radius:8px;background:rgba(217,119,6,0.1);display:flex;align-items:center;justify-content:center;font-size:15px;">⚠️</span>
            </div>
            <div style="font-size:32px;font-weight:800;color:#D97706;letter-spacing:-0.02em;font-variant-numeric:tabular-nums;">
                {{ count($failedRows) }}
            </div>
            <div style="margin-top:6px;font-size:11.5px;color:#D97706;font-weight:500;">
                {{ count($failedRows) > 0 ? 'baris gagal diproses' : 'semua baris berhasil' }}
            </div>
        </div>

    </div>

    {{-- ── ANGGOTA BARU ─────────────────────────────────────────── --}}
    <div style="background:var(--bg-surface);border:1px solid var(--border);border-radius:16px;overflow:hidden;box-shadow:var(--shadow-sm);">

        {{-- Header --}}
        <div style="display:flex;align-items:center;justify-content:space-between;padding:16px 20px;border-bottom:1px solid var(--border);">
            <div style="display:flex;align-items:center;gap:10px;">
                <span style="width:8px;height:8px;border-radius:50%;background:#16A34A;flex-shrink:0;"></span>
                <span style="font-size:14px;font-weight:600;color:var(--text-primary);">Anggota Baru</span>
                <span style="padding:2px 9px;border-radius:5px;font-size:11.5px;font-weight:500;background:rgba(22,163,74,0.1);color:#16A34A;border:1px solid rgba(22,163,74,0.2);">
                    {{ count($newMembers) }} orang
                </span>
            </div>
        </div>

        <div style="padding:20px;">
            @forelse($newMembers as $item)
                @php
                    // $item bisa berupa string "NIK - Nama" atau object/array
                    if (is_string($item)) {
                        $parts   = explode(' - ', $item, 2);
                        $nik     = trim($parts[0] ?? $item);
                        $nama    = trim($parts[1] ?? '');
                    } elseif (is_array($item)) {
                        $nik     = $item['nik_karyawan'] ?? $item['nik'] ?? '';
                        $nama    = $item['nama_lengkap'] ?? $item['nama'] ?? '';
                    } else {
                        $nik     = $item->nik_karyawan ?? '';
                        $nama    = $item->nama_lengkap ?? '';
                    }
                @endphp

                @if($loop->first)
                <div style="display:grid;grid-template-columns:repeat(3,1fr);gap:8px;" id="newGrid">
                @endif

                    <div style="display:flex;align-items:center;gap:10px;padding:10px 12px;border-radius:10px;border:1px solid var(--border);background:var(--bg-base);transition:border-color 0.15s;"
                        onmouseover="this.style.borderColor='rgba(22,163,74,0.4)';this.style.background='rgba(22,163,74,0.04)';"
                        onmouseout="this.style.borderColor='var(--border)';this.style.background='var(--bg-base)';">
                        <div style="width:34px;height:34px;border-radius:8px;background:rgba(22,163,74,0.1);display:flex;align-items:center;justify-content:center;font-size:13px;font-weight:700;color:#16A34A;flex-shrink:0;font-family:'DM Mono',monospace;">
                            {{ strtoupper(mb_substr($nama ?: $nik, 0, 2)) }}
                        </div>
                        <div style="min-width:0;">
                            <div style="font-size:13px;font-weight:500;color:var(--text-primary);overflow:hidden;text-overflow:ellipsis;white-space:nowrap;">
                                {{ $nama ?: '—' }}
                            </div>
                            <div style="font-size:11px;color:var(--text-muted);font-family:'DM Mono',monospace;margin-top:1px;">
                                {{ $nik }}
                            </div>
                        </div>
                    </div>

                @if($loop->last)
                </div>
                @endif

            @empty
                <div style="display:flex;align-items:center;justify-content:center;gap:8px;padding:32px;color:var(--text-muted);font-size:13.5px;">
                    <svg style="width:18px;height:18px;opacity:0.5;" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5"><path stroke-linecap="round" stroke-linejoin="round" d="M18 9v3m0 0v3m0-3h3m-3 0h-3m-2-5a4 4 0 11-8 0 4 4 0 018 0zM3 20a6 6 0 0112 0v1H3v-1z"/></svg>
                    Tidak ada anggota baru
                </div>
            @endforelse
        </div>

    </div>

    {{-- ── ANGGOTA RESIGN / HABIS KONTRAK ──────────────────────── --}}
    <div style="background:var(--bg-surface);border:1px solid var(--border);border-radius:16px;overflow:hidden;box-shadow:var(--shadow-sm);">

        {{-- Header --}}
        <div style="display:flex;align-items:center;justify-content:space-between;padding:16px 20px;border-bottom:1px solid var(--border);">
            <div style="display:flex;align-items:center;gap:10px;">
                <span style="width:8px;height:8px;border-radius:50%;background:#DC2626;flex-shrink:0;"></span>
                <span style="font-size:14px;font-weight:600;color:var(--text-primary);">Habis Kontrak / Resign</span>
                <span style="padding:2px 9px;border-radius:5px;font-size:11.5px;font-weight:500;background:rgba(220,38,38,0.08);color:#DC2626;border:1px solid rgba(220,38,38,0.2);">
                    {{ $resignedMembers->count() }} orang
                </span>
            </div>
        </div>

        <div style="padding:20px;">
            @forelse($resignedMembers as $item)

                @if($loop->first)
                <div style="display:grid;grid-template-columns:repeat(3,1fr);gap:8px;">
                @endif

                    <div style="display:flex;align-items:center;gap:10px;padding:10px 12px;border-radius:10px;border:1px solid var(--border);background:var(--bg-base);transition:border-color 0.15s;"
                        onmouseover="this.style.borderColor='rgba(220,38,38,0.35)';this.style.background='rgba(220,38,38,0.03)';"
                        onmouseout="this.style.borderColor='var(--border)';this.style.background='var(--bg-base)';">
                        <div style="width:34px;height:34px;border-radius:8px;background:rgba(220,38,38,0.08);display:flex;align-items:center;justify-content:center;font-size:13px;font-weight:700;color:#DC2626;flex-shrink:0;font-family:'DM Mono',monospace;">
                            {{ strtoupper(mb_substr($item->nama_lengkap ?? '', 0, 2)) }}
                        </div>
                        <div style="min-width:0;">
                            <div style="font-size:13px;font-weight:500;color:var(--text-primary);overflow:hidden;text-overflow:ellipsis;white-space:nowrap;">
                                {{ $item->nama_lengkap ?? '—' }}
                            </div>
                            <div style="font-size:11px;color:var(--text-muted);font-family:'DM Mono',monospace;margin-top:1px;">
                                {{ $item->nik_karyawan ?? '' }}
                            </div>
                        </div>
                    </div>

                @if($loop->last)
                </div>
                @endif

            @empty
                <div style="display:flex;align-items:center;justify-content:center;gap:8px;padding:32px;color:var(--text-muted);font-size:13.5px;">
                    <svg style="width:18px;height:18px;opacity:0.5;" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5"><path stroke-linecap="round" stroke-linejoin="round" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1"/></svg>
                    Tidak ada anggota yang resign / habis kontrak
                </div>
            @endforelse
        </div>

    </div>

    {{-- ── FAILED ROWS ──────────────────────────────────────────── --}}
    @if(count($failedRows) > 0)
    <div style="background:var(--bg-surface);border:1px solid rgba(220,38,38,0.25);border-radius:16px;overflow:hidden;box-shadow:var(--shadow-sm);">

        <div style="display:flex;align-items:center;gap:10px;padding:16px 20px;border-bottom:1px solid rgba(220,38,38,0.15);">
            <span style="width:8px;height:8px;border-radius:50%;background:#D97706;flex-shrink:0;"></span>
            <span style="font-size:14px;font-weight:600;color:var(--text-primary);">Failed Rows</span>
            <span style="padding:2px 9px;border-radius:5px;font-size:11.5px;font-weight:500;background:rgba(217,119,6,0.1);color:#D97706;border:1px solid rgba(217,119,6,0.2);">
                {{ count($failedRows) }} baris
            </span>
        </div>

        <div style="padding:16px 20px;display:flex;flex-direction:column;gap:6px;">
            @foreach($failedRows as $row)
                <div style="display:flex;align-items:flex-start;gap:10px;padding:10px 14px;border-radius:9px;background:rgba(220,38,38,0.04);border:1px solid rgba(220,38,38,0.15);">
                    <span style="display:inline-flex;align-items:center;justify-content:center;padding:2px 8px;border-radius:5px;background:rgba(220,38,38,0.1);color:#DC2626;font-size:11px;font-weight:700;white-space:nowrap;font-family:'DM Mono',monospace;flex-shrink:0;margin-top:1px;">
                        Row {{ $row['row'] }}
                    </span>
                    <span style="font-size:13px;color:#B91C1C;line-height:1.5;">{{ $row['error'] }}</span>
                </div>
            @endforeach
        </div>

    </div>
    @endif

    {{-- ── BOTTOM ACTION ────────────────────────────────────────── --}}
    <div style="display:flex;justify-content:flex-end;padding-bottom:16px;">
        <a href="{{ route('anggota.index') }}" class="btn btn-primary" style="text-decoration:none;gap:8px;">
            Lihat Database Anggota
            <svg style="width:14px;height:14px;" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M9 5l7 7-7 7"/></svg>
        </a>
    </div>

</div>

@endsection