@extends('layouts.app')
@section('title', 'Import Anggota')

@section('content')

<div style="max-width:680px;margin:0 auto;display:flex;flex-direction:column;gap:24px;">

    {{-- ── PIPELINE STEPS ───────────────────────────────────────── --}}
    <div style="display:flex;align-items:center;gap:0;position:relative;">

        {{-- Line behind steps --}}
        <div style="position:absolute;top:50%;left:0;right:0;height:2px;background:var(--border);z-index:0;transform:translateY(-50%);"></div>

        {{-- Step 1: Upload (aktif) --}}
        <div style="position:relative;z-index:1;display:flex;align-items:center;gap:8px;">
            <span style="width:28px;height:28px;border-radius:50%;background:var(--accent);color:#fff;display:flex;align-items:center;justify-content:center;font-size:12px;font-weight:700;flex-shrink:0;box-shadow:0 0 0 4px var(--accent-muted);">1</span>
            <span style="font-size:13px;font-weight:600;color:var(--accent-text);white-space:nowrap;">Upload File</span>
        </div>

        <div style="flex:1;height:2px;background:var(--border);margin:0 12px;position:relative;z-index:1;"></div>

        {{-- Step 2: Preview --}}
        <div style="position:relative;z-index:1;display:flex;align-items:center;gap:8px;">
            <span style="width:28px;height:28px;border-radius:50%;background:var(--bg-elevated);border:2px solid var(--border-strong);color:var(--text-muted);display:flex;align-items:center;justify-content:center;font-size:12px;font-weight:700;flex-shrink:0;">2</span>
            <span style="font-size:13px;font-weight:500;color:var(--text-muted);white-space:nowrap;">Preview & Validasi</span>
        </div>

        <div style="flex:1;height:2px;background:var(--border);margin:0 12px;position:relative;z-index:1;"></div>

        {{-- Step 3: Selesai --}}
        <div style="position:relative;z-index:1;display:flex;align-items:center;gap:8px;">
            <span style="width:28px;height:28px;border-radius:50%;background:var(--bg-elevated);border:2px solid var(--border);color:var(--text-muted);display:flex;align-items:center;justify-content:center;font-size:12px;font-weight:700;flex-shrink:0;">3</span>
            <span style="font-size:13px;font-weight:500;color:var(--text-muted);white-space:nowrap;">Selesai</span>
        </div>

    </div>

    {{-- ── PAGE HEADER ──────────────────────────────────────────── --}}
    <div>
        <h1 style="font-size:20px;font-weight:600;color:var(--text-primary);margin:0 0 4px;">Import Data Anggota</h1>
        <p style="font-size:13px;color:var(--text-muted);margin:0;">Upload file Excel untuk sinkronisasi data anggota ke database.</p>
    </div>

    {{-- NOTIF IMPORT TERAKHIR --}}
    @if($lastImport)
    <div style="
        margin-top:12px;
        padding:14px 16px;
        border-radius:12px;
        background:#ECFDF5;
        border:1px solid #A7F3D0;
        color:#065F46;
        font-size:13px;
    ">
        <div style="font-weight:600;margin-bottom:4px;">
            📊 Data karyawan terakhir diupload
        </div>

        <div>
            Bulan
            <strong>
                {{ \Carbon\Carbon::create()->month($lastImport->bulan)->translatedFormat('F') }}
            </strong>
            Tahun
            <strong>{{ $lastImport->tahun }}</strong>
        </div>

        <div style="font-size:12px;color:#047857;margin-top:4px;">
            Diupdate {{ $lastImport->updated_at?->translatedFormat('d F Y H:i') }}
        </div>
    </div>
    @endif

    {{-- ── CARD ─────────────────────────────────────────────────── --}}
    <div style="background:var(--bg-surface);border:1px solid var(--border);border-radius:18px;box-shadow:var(--shadow-sm);padding:28px 32px;">

        @if($errors->has('periode'))
        <div style="
            padding:12px;
            margin-bottom:16px;
            border-radius:10px;
            background:#FEF2F2;
            border:1px solid #FCA5A5;
            color:#991B1B;
            font-size:13px;
        ">
            ⚠ {{ $errors->first('periode') }}
        </div>
        @endif

        <form action="{{ route('anggota.import.preview') }}" method="POST" enctype="multipart/form-data">
            @csrf

            {{-- BULAN & TAHUN --}}
            <div style="display:grid;grid-template-columns:1fr 1fr;gap:16px;margin-bottom:4px;">

                <div class="form-group" style="margin-bottom:0;">
                    <label class="form-label">Bulan Import</label>
                    <select name="bulan_import" required class="form-input" style="cursor:pointer;
                        background-image:url(\"data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='16' height='16' viewBox='0 0 24 24' fill='none' stroke='%239CA3AF' stroke-width='2'%3E%3Cpolyline points='6 9 12 15 18 9'/%3E%3C/svg%3E\");
                        background-repeat:no-repeat;background-position:right 10px center;padding-right:32px;">
                        @if($lastImport)

                            @foreach($bulanOptions as $item)
                                <option
                                    value="{{ $item['bulan'] }}"
                                    data-tahun="{{ $item['tahun'] }}"
                                >
                                    {{ \Carbon\Carbon::create()->month($item['bulan'])->translatedFormat('F') }}
                                    
                                </option>
                            @endforeach

                        @else

                            @foreach(range(1,12) as $bulan)
                                <option value="{{ $bulan }}">
                                    {{ \Carbon\Carbon::create()->month($bulan)->translatedFormat('F') }}
                                </option>
                            @endforeach

                        @endif
                    </select>
                </div>

                <div class="form-group" style="margin-bottom:0;">
                    <label class="form-label">Tahun Import</label>
                    <select name="tahun_import" required class="form-input" style="cursor:pointer;
                        background-image:url(\"data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='16' height='16' viewBox='0 0 24 24' fill='none' stroke='%239CA3AF' stroke-width='2'%3E%3Cpolyline points='6 9 12 15 18 9'/%3E%3C/svg%3E\");
                        background-repeat:no-repeat;background-position:right 10px center;padding-right:32px;">
                        @if($lastImport)

                            @foreach($tahunOptions as $tahun)
                                <option value="{{ $tahun }}">
                                    {{ $tahun }}
                                </option>
                            @endforeach

                        @else

                            @for($tahun = now()->year + 1; $tahun >= 2021; $tahun--)
                                <option value="{{ $tahun }}">
                                    {{ $tahun }}
                                </option>
                            @endfor

                        @endif
                    </select>
                </div>

            </div>

            {{-- FILE INPUT --}}
            <div class="form-group" style="margin-top:16px;">
                <label class="form-label">File Excel</label>
                <input type="file" name="file"
                    accept=".xlsx,.xls,.csv"
                    required
                    class="form-input"
                    style="padding:8px 12px;cursor:pointer;">
                @error('file')
                    <p style="font-size:12px;color:#EF4444;margin-top:5px;">⚠ {{ $message }}</p>
                @enderror
                <div id="fileNameError"
                    style="display:none;margin-top:6px;font-size:12px;color:#DC2626;">
                </div>
            </div>
            

            {{-- INFO BOX --}}
            <div style="border-radius:12px;background:var(--accent-muted);border:1px solid rgba(37,99,235,.18);padding:16px 18px;margin-bottom:24px;">
                <div style="font-size:12.5px;font-weight:600;color:var(--accent-text);margin-bottom:10px;display:flex;align-items:center;gap:6px;">
                    <svg style="width:14px;height:14px;flex-shrink:0;" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                    </svg>
                    Kolom wajib dalam file Excel
                </div>
                <div style="display:grid;grid-template-columns:1fr 1fr;gap:4px 16px;">
                    @foreach(['nik_karyawan','nama_lengkap','departemen','jabatan','jenis_kelamin','status_karyawan'] as $col)
                    <div style="display:flex;align-items:center;gap:6px;font-size:12.5px;color:var(--accent-text);">
                        <span style="width:5px;height:5px;border-radius:50%;background:var(--accent);flex-shrink:0;"></span>
                        <code style="font-family:'DM Mono',monospace;font-size:11.5px;">{{ $col }}</code>
                    </div>
                    @endforeach
                </div>
            </div>

            {{-- ACTIONS --}}
            <div class="form-actions">
                <a href="{{ route('anggota.index') }}" class="btn btn-ghost" style="text-decoration:none;">
                    ← Kembali
                </a>
                <button type="submit" class="btn btn-primary">
                    Preview Import →
                </button>
            </div>

        </form>
    </div>

</div>

<script>
const LAST_BULAN = {{ $lastImport?->bulan ?? 'null' }};
const LAST_TAHUN = {{ $lastImport?->tahun ?? 'null' }};

let bypassConfirm = false;

document.querySelector('form').addEventListener('submit', async function(e){

    if (!validateFileName()) {
        e.preventDefault();

        Swal.fire({
            icon: 'error',
            title: 'Nama File Tidak Sesuai',
            text: 'Nama file Excel harus mengandung bulan dan tahun yang dipilih.'
        });

        return;
    }

    if (bypassConfirm) {
        return;
    }

    const bulan = parseInt(document.querySelector('[name=bulan_import]').value);
    const tahun = parseInt(document.querySelector('[name=tahun_import]').value);

    if (
        LAST_BULAN &&
        LAST_TAHUN &&
        bulan === LAST_BULAN &&
        tahun === LAST_TAHUN
    ) {
        e.preventDefault();

        const result = await Swal.fire({
            icon: 'warning',
            title: 'Periode Sudah Pernah Diupload',
            html: `
                Data periode ini sudah pernah diupload.<br><br>
                <b>Melanjutkan akan memperbarui data periode tersebut.</b>
            `,
            showCancelButton: true,
            confirmButtonText: 'Ya, Lanjutkan',
            cancelButtonText: 'Batal',
            reverseButtons: true
        });

        if (result.isConfirmed) {
            bypassConfirm = true;
            this.submit();
        }
    }
});

const bulanIndonesia = {
    1: ['januari','january','jan'],
    2: ['februari','february','feb'],
    3: ['maret','march','mar'],
    4: ['april','apr'],
    5: ['mei','may'],
    6: ['juni','june','jun'],
    7: ['juli','july','jul'],
    8: ['agustus','august','agu','agt'],
    9: ['september','sep'],
    10:['oktober','october','oct','okt'],
    11:['november','nov'],
    12:['desember','december','dec','des']
};

function validateFileName() {

    const fileInput = document.getElementById('excelFile');
    const errorBox  = document.getElementById('fileNameError');

    if (!fileInput.files.length) {
        errorBox.style.display = 'none';
        return true;
    }

    const bulan = parseInt(
        document.querySelector('[name="bulan_import"]').value
    );

    const tahun = document.querySelector('[name="tahun_import"]').value;

    const fileName = fileInput.files[0].name.toLowerCase();

    const monthMatched =
        bulanIndonesia[bulan].some(keyword =>
            fileName.includes(keyword)
        );

    const yearMatched =
        fileName.includes(tahun);

    if (!monthMatched || !yearMatched) {

        const namaBulan =
            bulanIndonesia[bulan][0].charAt(0).toUpperCase() +
            bulanIndonesia[bulan][0].slice(1);

        errorBox.innerHTML =
            `Nama file harus mengandung <b>${namaBulan} ${tahun}</b>.`;

        errorBox.style.display = 'block';

        return false;
    }

    errorBox.style.display = 'none';
    return true;
}

document.getElementById('excelFile')
    ?.addEventListener('change', validateFileName);

document.querySelector('[name="bulan_import"]')
    ?.addEventListener('change', validateFileName);

document.querySelector('[name="tahun_import"]')
    ?.addEventListener('change', validateFileName);

</script>

<script>
@if($lastImport)

const bulanSelect = document.querySelector('[name="bulan_import"]');
const tahunSelect = document.querySelector('[name="tahun_import"]');

function syncTahun() {
    const selected = bulanSelect.options[bulanSelect.selectedIndex];
    tahunSelect.value = selected.dataset.tahun;
}

bulanSelect.addEventListener('change', syncTahun);
syncTahun();

@endif
</script>
@endsection