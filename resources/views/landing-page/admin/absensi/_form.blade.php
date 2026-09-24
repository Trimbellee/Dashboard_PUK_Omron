@php
    $kolomOptions = \App\Http\Controllers\Public\Admin\SesiAbsensiController::kolomOptions();
    $selectedKolom = old('kolom_ditampilkan', $sesi->kolom_ditampilkan ?? ['nama_lengkap', 'nik_karyawan', 'departemen']);
@endphp

<form method="POST"
      action="{{ isset($sesi) ? route('admin.sesi-absensi.update', $sesi) : route('admin.sesi-absensi.store') }}"
      class="abs-card">
    @csrf
    @if(isset($sesi))
        @method('PUT')
    @endif

    @if ($errors->any())
    <div class="abs-alert abs-alert--error">
        <ul>
            @foreach ($errors->all() as $error)
                <li>{{ $error }}</li>
            @endforeach
        </ul>
    </div>
    @endif

    <div class="abs-field">
        <label>Judul Kegiatan</label>
        <input type="text" name="judul" value="{{ old('judul', $sesi->judul ?? '') }}" class="lp-form__input"
               placeholder="Contoh: Konsolidasi September 2026" required>
        @error('judul')<span class="lp-form__error">{{ $message }}</span>@enderror
    </div>

    <div class="abs-field">
        <label>Lokasi / Tempat</label>
        <input type="text" name="lokasi" value="{{ old('lokasi', $sesi->lokasi ?? '') }}" class="lp-form__input"
               placeholder="Contoh: Aula Serbaguna PUK OMRON">
        @error('lokasi')<span class="lp-form__error">{{ $message }}</span>@enderror
    </div>

    <div class="abs-field">
        <label>Kolom yang Ditampilkan</label>
        <div class="abs-kolom-grid">
            @foreach($kolomOptions as $key => $label)
            <label class="abs-kolom-item">
                <input type="checkbox" name="kolom_ditampilkan[]" value="{{ $key }}"
                       {{ in_array($key, $selectedKolom) ? 'checked' : '' }}>
                {{ $label }}
            </label>
            @endforeach
        </div>
        @error('kolom_ditampilkan')<span class="lp-form__error">{{ $message }}</span>@enderror
    </div>

    <div class="abs-actions">
        <a href="{{ isset($sesi) ? route('admin.sesi-absensi.show', $sesi) : route('admin.sesi-absensi.index') }}" class="lp-btn lp-btn--ghost">← Kembali</a>
        <button type="submit" class="lp-btn lp-btn--primary">{{ isset($sesi) ? 'Simpan Perubahan' : 'Buat Sesi →' }}</button>
    </div>
</form>