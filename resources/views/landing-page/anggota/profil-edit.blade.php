@extends('landing-page.layouts.app')

@section('title', 'Edit Profil - PUK OMRON')
@section('meta_description', 'Edit profil anggota PUK OMRON.')

@section('content')
<section class="lp-section lp-profile-section">
     
    <div class="lp-container lp-profile-edit-container">
       <a href="{{ route('anggota.profil') }}" class="lp-auth__back">&larr; Kembali</a>

        @if ($errors->any())
            <div class="lp-profile-alert lp-profile-alert--error">
                <strong>Periksa kembali data yang diisi:</strong>
                <ul>
                    @foreach ($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        <div
            x-data="{
                listKotaD: [], listKecD: [], listKelD: [],
                busyKotaD: false, busyKecD: false, busyKelD: false,
                provD: '{{ old('provinsi_domisili', $anggota->provinsi_domisili ?? '') }}',
                kotD:  '{{ old('kota_domisili', $anggota->kota_domisili ?? '') }}',
                kecD:  '{{ old('kecamatan_domisili', $anggota->kecamatan_domisili ?? '') }}',
                kelD:  '{{ old('kelurahan_domisili', $anggota->kelurahan_domisili ?? '') }}',

                listKotaK: [], listKecK: [], listKelK: [],
                busyKotaK: false, busyKecK: false, busyKelK: false,
                provK: '{{ old('provinsi_ktp', $anggota->provinsi_ktp ?? '') }}',
                kotK:  '{{ old('kota_ktp', $anggota->kota_ktp ?? '') }}',
                kecK:  '{{ old('kecamatan_ktp', $anggota->kecamatan_ktp ?? '') }}',
                kelK:  '{{ old('kelurahan_ktp', $anggota->kelurahan_ktp ?? '') }}',

                preview: '{{ $anggota->foto_url }}',
                showPassOld: false,
                showPassNew: false,
                showPassConfirm: false,

                async ajx(url) {
                    try {
                        const r = await window.fetch(url);
                        return r.ok ? await r.json() : [];
                    } catch { return []; }
                },

                async init() {
                if (this.provD) {
                    this.busyKotaD = true;
                    this.listKotaD = await this.ajx('/anggota/api/wilayah/kota/' + this.provD);
                    this.busyKotaD = false;
                    if (this.kotD) {
                        this.busyKecD = true;
                        this.listKecD = await this.ajx('/anggota/api/wilayah/kecamatan/' + this.kotD);
                        this.busyKecD = false;
                        if (this.kecD) {
                            this.busyKelD = true;
                            this.listKelD = await this.ajx('/anggota/api/wilayah/kelurahan/' + this.kecD);
                            this.busyKelD = false;
                        }
                    }
                }
                if (this.provK) {
                    this.busyKotaK = true;
                    this.listKotaK = await this.ajx('/anggota/api/wilayah/kota/' + this.provK);
                    this.busyKotaK = false;
                    if (this.kotK) {
                        this.busyKecK = true;
                        this.listKecK = await this.ajx('/anggota/api/wilayah/kecamatan/' + this.kotK);
                        this.busyKecK = false;
                        if (this.kecK) {
                            this.busyKelK = true;
                            this.listKelK = await this.ajx('/anggota/api/wilayah/kelurahan/' + this.kecK);
                            this.busyKelK = false;
                        }
                    }
                }
            },

            async onProv(t) {
                const code = t === 'D' ? this.provD : this.provK;
                if (t === 'D') { this.listKotaD=[]; this.kotD=''; this.listKecD=[]; this.kecD=''; this.listKelD=[]; this.kelD=''; }
                else           { this.listKotaK=[]; this.kotK=''; this.listKecK=[]; this.kecK=''; this.listKelK=[]; this.kelK=''; }
                if (!code) return;
                if (t === 'D') { this.busyKotaD=true; this.listKotaD = await this.ajx('/anggota/api/wilayah/kota/'+code); this.busyKotaD=false; }
                else           { this.busyKotaK=true; this.listKotaK = await this.ajx('/anggota/api/wilayah/kota/'+code); this.busyKotaK=false; }
            },

            async onKot(t) {
                const code = t === 'D' ? this.kotD : this.kotK;
                if (t === 'D') { this.listKecD=[]; this.kecD=''; this.listKelD=[]; this.kelD=''; }
                else           { this.listKecK=[]; this.kecK=''; this.listKelK=[]; this.kelK=''; }
                if (!code) return;
                if (t === 'D') { this.busyKecD=true; this.listKecD = await this.ajx('/anggota/api/wilayah/kecamatan/'+code); this.busyKecD=false; }
                else           { this.busyKecK=true; this.listKecK = await this.ajx('/anggota/api/wilayah/kecamatan/'+code); this.busyKecK=false; }
            },

            async onKec(t) {
                const code = t === 'D' ? this.kecD : this.kecK;
                if (t === 'D') { this.listKelD=[]; this.kelD=''; }
                else           { this.listKelK=[]; this.kelK=''; }
                if (!code) return;
                if (t === 'D') { this.busyKelD=true; this.listKelD = await this.ajx('/anggota/api/wilayah/kelurahan/'+code); this.busyKelD=false; }
                else           { this.busyKelK=true; this.listKelK = await this.ajx('/anggota/api/wilayah/kelurahan/'+code); this.busyKelK=false; }
            },
            }"
        >
            <form action="{{ route('anggota.profil.update') }}" method="POST" enctype="multipart/form-data" class="lp-edit-form">
                @csrf
                @method('PUT')

                {{-- ===== HEADER: read-only info + foto ===== --}}
                <div class="lp-edit-header">
                    <div class="lp-edit-photo-wrap" @click="$refs.fotoInput.click()">
                        <img :src="preview" class="lp-edit-photo-img" alt="Foto anggota">
                        <div class="lp-edit-photo-overlay">Ganti Foto</div>
                    </div>
                    <div class="lp-edit-header-info">
                        <h1 class="lp-edit-header-name">{{ $anggota->nama_lengkap }}</h1>
                        <div class="lp-edit-header-meta">
                            <span>NIK {{ $anggota->nik_karyawan }}</span>
                            <span>&middot;</span>
                            <span>{{ $anggota->jabatan }} - {{ $anggota->departemen }}</span>
                        </div>
                        <p class="lp-edit-hint">
                            Klik foto untuk menggantinya. Gunakan foto setengah badan (dari dada ke atas) dengan wajah terlihat jelas.
                            Format JPG, PNG, WEBP · Maks. 8 MB.
                        </p>
                        <input x-ref="fotoInput" type="file" name="foto" accept="image/*" class="lp-edit-file-hidden"
                               @change="const f=$event.target.files[0]; if(f){ preview = URL.createObjectURL(f); }">
                        @error('foto')<p class="lp-edit-error">{{ $message }}</p>@enderror
                    </div>
                </div>

                <p class="lp-edit-note">
                    Nama, NIK, departemen, jabatan, dan status karyawan hanya dapat diubah oleh admin.
                    Hubungi Bidang organisasi jika ada data tersebut yang keliru.
                </p>

                {{-- ===== IDENTITAS ===== --}}
                <div class="lp-edit-card">
                    <h3 class="lp-edit-card__title">Data Identitas</h3>
                    <div class="lp-edit-grid-2">
                        <div class="lp-edit-field">
                            <label class="lp-edit-label">Jenis Identitas</label>
                            <select name="jenis_identitas" class="lp-edit-input">
                                <option value="">Pilih</option>
                                @foreach(['KTP','SIM','PASPOR'] as $opt)
                                    <option value="{{ $opt }}" {{ old('jenis_identitas', $anggota->jenis_identitas) == $opt ? 'selected' : '' }}>{{ $opt }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="lp-edit-field">
                            <label class="lp-edit-label">Nomor Identitas</label>
                            <input type="text" name="nomor_identitas" maxlength="20" inputmode="numeric"
                                   oninput="this.value=this.value.replace(/[^0-9]/g,'')"
                                   value="{{ old('nomor_identitas', $anggota->nomor_identitas) }}"
                                   class="lp-edit-input" placeholder="Masukkan nomor identitas">
                            @error('nomor_identitas')<p class="lp-edit-error">{{ $message }}</p>@enderror
                        </div>
                    </div>
                </div>

                {{-- ===== KONTAK ===== --}}
                <div class="lp-edit-card">
                    <h3 class="lp-edit-card__title">Kontak</h3>
                    <div class="lp-edit-grid-2">
                        <div class="lp-edit-field">
                            <label class="lp-edit-label">Alamat Email <span class="lp-edit-req">*</span></label>
                            <input type="email" name="email" value="{{ old('email', $anggota->email) }}"
                                   class="lp-edit-input" placeholder="contoh@email.com" required>
                            @error('email')<p class="lp-edit-error">{{ $message }}</p>@enderror
                        </div>
                        <div class="lp-edit-field">
                            <label class="lp-edit-label">Nomor WhatsApp <span class="lp-edit-req">*</span></label>
                            <input type="text" name="nomor_whatsapp" maxlength="13" inputmode="tel"
                                   value="{{ old('nomor_whatsapp', $anggota->nomor_whatsapp) }}"
                                   class="lp-edit-input" placeholder="Contoh: +6281234567890"
                                   oninput="
                                       let v=this.value; v=v.replace(/[^0-9+]/g,'');
                                       if(v.indexOf('+')>0){ v=v.replace(/\+/g,''); }
                                       if(v.startsWith('+')){ v='+'+v.substring(1).replace(/\+/g,''); }
                                       this.value=v;" required>
                            @error('nomor_whatsapp')<p class="lp-edit-error">{{ $message }}</p>@enderror
                        </div>
                    </div>
                </div>

                {{-- ===== KELAHIRAN & KEPEGAWAIAN ===== --}}
                <div class="lp-edit-card">
                    <h3 class="lp-edit-card__title">Data Kelahiran & Mulai Bekerja</h3>
                    <div class="lp-edit-grid-2">
                        <div class="lp-edit-field">
                            <label class="lp-edit-label">Tempat Lahir</label>
                            <input type="text" name="tempat_lahir" value="{{ old('tempat_lahir', $anggota->tempat_lahir) }}"
                                class="lp-edit-input" placeholder="Kota kelahiran"  autocomplete="off">
                        </div>
                        <div class="lp-edit-field">
                            <label class="lp-edit-label">Tanggal Lahir</label>
                            <input type="date" name="tanggal_lahir"
                                value="{{ old('tanggal_lahir', $anggota->tanggal_lahir?->format('Y-m-d')) }}"
                                class="lp-edit-input">
                        </div>
                        <div class="lp-edit-field">
                            <label class="lp-edit-label">Pendidikan Terakhir</label>
                            <select name="pendidikan_terakhir" class="lp-edit-input">
                                <option value="">Pilih</option>
                                @foreach(['SD','SMP','SMA','SMK','D1','D2','D3','D4','S1','S2','S3'] as $edu)
                                    <option value="{{ $edu }}" {{ old('pendidikan_terakhir', $anggota->pendidikan_terakhir) == $edu ? 'selected' : '' }}>{{ $edu }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="lp-edit-field">
                            <label class="lp-edit-label">Tanggal Mulai Bekerja di PT OMRON</label>
                            <input type="date" name="tanggal_mulai_bekerja"
                                value="{{ old('tanggal_mulai_bekerja', $anggota->tanggal_mulai_bekerja?->format('Y-m-d')) }}"
                                class="lp-edit-input">
                            @error('tanggal_mulai_bekerja')<p class="lp-edit-error">{{ $message }}</p>@enderror
                        </div>
                    </div>
                </div>

                {{-- ===== DOMISILI ===== --}}
                <div class="lp-edit-card">
                    <h3 class="lp-edit-card__title">Alamat Domisili</h3>
                    <div class="lp-edit-grid-2">
                        <div class="lp-edit-field">
                            <label class="lp-edit-label">Provinsi</label>
                            <select name="provinsi_domisili" class="lp-edit-input" x-model="provD" @change="onProv('D')">
                                <option value="">— Pilih Provinsi —</option>
                                @foreach($provinsiList as $prov)
                                    <option value="{{ $prov->code }}">{{ $prov->name }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="lp-edit-field">
                            <label class="lp-edit-label">Kota / Kabupaten</label>
                            <div class="lp-edit-sel-wrap">
                                <select name="kota_domisili" class="lp-edit-input"
                                        x-model="kotD" :disabled="!listKotaD.length || busyKotaD" @change="onKot('D')">
                                    <option value="" x-text="busyKotaD ? 'Memuat...' : (provD ? '— Pilih Kota —' : '— Pilih Provinsi dulu —')"></option>
                                    <template x-for="item in listKotaD" :key="item.code">
                                        <option :value="item.code" :selected="item.code == kotD" x-text="item.name"></option>
                                    </template>
                                </select>
                            </div>
                        </div>
                        <div class="lp-edit-field">
                            <label class="lp-edit-label">Kecamatan</label>
                            <div class="lp-edit-sel-wrap">
                                <select name="kecamatan_domisili" class="lp-edit-input"
                                        x-model="kecD" :disabled="!listKecD.length || busyKecD" @change="onKec('D')">
                                    <option value="" x-text="busyKecD ? 'Memuat...' : (kotD ? '— Pilih Kecamatan —' : '— Pilih Kota dulu —')"></option>
                                    <template x-for="item in listKecD" :key="item.code">
                                        <option :value="item.code" :selected="item.code == kecD" x-text="item.name"></option>
                                    </template>
                                </select>
                            </div>
                        </div>
                        <div class="lp-edit-field">
                            <label class="lp-edit-label">Kelurahan / Desa</label>
                            <div class="lp-edit-sel-wrap">
                                <select name="kelurahan_domisili" class="lp-edit-input"
                                        x-model="kelD" :disabled="!listKelD.length || busyKelD">
                                    <option value="" x-text="busyKelD ? 'Memuat...' : (kecD ? '— Pilih Kelurahan —' : '— Pilih Kecamatan dulu —')"></option>
                                    <template x-for="item in listKelD" :key="item.code">
                                        <option :value="item.code" :selected="item.code == kelD" x-text="item.name"></option>
                                    </template>
                                </select>
                            </div>
                        </div>
                        <div class="lp-edit-field lp-edit-span-2">
                            <label class="lp-edit-label">Alamat Lengkap</label>
                            <textarea name="alamat_domisili" rows="3" class="lp-edit-textarea"
                                      placeholder="Jalan, RT/RW, Nomor...">{{ old('alamat_domisili', $anggota->alamat_domisili) }}</textarea>
                        </div>
                    </div>
                </div>

                {{-- ===== KTP ===== --}}
                <div class="lp-edit-card">
                    <h3 class="lp-edit-card__title">Alamat Sesuai KTP</h3>
                    <div class="lp-edit-grid-2">
                        <div class="lp-edit-field">
                            <label class="lp-edit-label">Provinsi</label>
                            <select name="provinsi_ktp" class="lp-edit-input" x-model="provK" @change="onProv('K')">
                                <option value="">— Pilih Provinsi —</option>
                                @foreach($provinsiList as $prov)
                                    <option value="{{ $prov->code }}">{{ $prov->name }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="lp-edit-field">
                            <label class="lp-edit-label">Kota / Kabupaten</label>
                            <div class="lp-edit-sel-wrap">
                                <select name="kota_ktp" class="lp-edit-input"
                                        x-model="kotK" :disabled="!listKotaK.length || busyKotaK" @change="onKot('K')">
                                    <option value="" x-text="busyKotaK ? 'Memuat...' : (provK ? '— Pilih Kota —' : '— Pilih Provinsi dulu —')"></option>
                                    <template x-for="item in listKotaK" :key="item.code">
                                        <option :value="item.code" :selected="item.code == kotK" x-text="item.name"></option>
                                    </template>
                                </select>
                            </div>
                        </div>
                        <div class="lp-edit-field">
                            <label class="lp-edit-label">Kecamatan</label>
                            <div class="lp-edit-sel-wrap">
                                <select name="kecamatan_ktp" class="lp-edit-input"
                                        x-model="kecK" :disabled="!listKecK.length || busyKecK" @change="onKec('K')">
                                    <option value="" x-text="busyKecK ? 'Memuat...' : (kotK ? '— Pilih Kecamatan —' : '— Pilih Kota dulu —')"></option>
                                    <template x-for="item in listKecK" :key="item.code">
                                        <option :value="item.code" :selected="item.code == kecK" x-text="item.name"></option>
                                    </template>
                                </select>
                            </div>
                        </div>
                        <div class="lp-edit-field">
                            <label class="lp-edit-label">Kelurahan / Desa</label>
                            <div class="lp-edit-sel-wrap">
                                <select name="kelurahan_ktp" class="lp-edit-input"
                                        x-model="kelK" :disabled="!listKelK.length || busyKelK">
                                    <option value="" x-text="busyKelK ? 'Memuat...' : (kecK ? '— Pilih Kelurahan —' : '— Pilih Kecamatan dulu —')"></option>
                                    <template x-for="item in listKelK" :key="item.code">
                                        <option :value="item.code" :selected="item.code == kelK" x-text="item.name"></option>
                                    </template>
                                </select>
                            </div>
                        </div>
                        <div class="lp-edit-field lp-edit-span-2">
                            <label class="lp-edit-label">Alamat Lengkap</label>
                            <textarea name="alamat_ktp" rows="3" class="lp-edit-textarea"
                                      placeholder="Jalan, RT/RW, Nomor...">{{ old('alamat_ktp', $anggota->alamat_ktp) }}</textarea>
                        </div>
                    </div>
                </div>

                {{-- ===== GANTI PASSWORD (opsional) ===== --}}
                <div class="lp-edit-card">
                    <h3 class="lp-edit-card__title">Ganti Password</h3>
                    <p class="lp-edit-hint" style="margin-bottom:14px;">Kosongkan semua kolom jika tidak ingin mengganti password.</p>
                    <div class="lp-edit-grid-2">
                        <div class="lp-edit-field lp-edit-span-2">
                            <label class="lp-edit-label">Password Lama</label>
                            <div class="lp-edit-password-wrap">
                                <input :type="showPassOld ? 'text' : 'password'" name="password_lama"
                                    class="lp-edit-input" placeholder="Masukkan password lama Anda" autocomplete="current-password">
                                <button type="button" class="lp-edit-password-toggle" @click="showPassOld = !showPassOld"
                                        :aria-label="showPassOld ? 'Sembunyikan password' : 'Tampilkan password'">
                                    <svg x-show="!showPassOld" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8Z"/><circle cx="12" cy="12" r="3"/></svg>
                                    <svg x-show="showPassOld" x-cloak width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M9.88 9.88a3 3 0 1 0 4.24 4.24"/><path d="M10.73 5.08A10.43 10.43 0 0 1 12 5c7 0 11 7 11 7a13.16 13.16 0 0 1-1.67 2.68"/><path d="M6.61 6.61A13.526 13.526 0 0 0 1 12s4 7 11 7a9.74 9.74 0 0 0 5.39-1.61"/><line x1="2" y1="2" x2="22" y2="22"/></svg>
                                </button>
                            </div>
                            @error('password_lama')<p class="lp-edit-error">{{ $message }}</p>@enderror
                        </div>
                        <div class="lp-edit-field">
                            <label class="lp-edit-label">Password Baru</label>
                            <div class="lp-edit-password-wrap">
                                <input :type="showPassNew ? 'text' : 'password'" name="password"
                                    class="lp-edit-input" placeholder="Minimal 8 karakter" autocomplete="new-password">
                                <button type="button" class="lp-edit-password-toggle" @click="showPassNew = !showPassNew"
                                        :aria-label="showPassNew ? 'Sembunyikan password' : 'Tampilkan password'">
                                    <svg x-show="!showPassNew" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8Z"/><circle cx="12" cy="12" r="3"/></svg>
                                    <svg x-show="showPassNew" x-cloak width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M9.88 9.88a3 3 0 1 0 4.24 4.24"/><path d="M10.73 5.08A10.43 10.43 0 0 1 12 5c7 0 11 7 11 7a13.16 13.16 0 0 1-1.67 2.68"/><path d="M6.61 6.61A13.526 13.526 0 0 0 1 12s4 7 11 7a9.74 9.74 0 0 0 5.39-1.61"/><line x1="2" y1="2" x2="22" y2="22"/></svg>
                                </button>
                            </div>
                            @error('password')<p class="lp-edit-error">{{ $message }}</p>@enderror
                        </div>
                        <div class="lp-edit-field">
                            <label class="lp-edit-label">Konfirmasi Password Baru</label>
                            <div class="lp-edit-password-wrap">
                                <input :type="showPassConfirm ? 'text' : 'password'" name="password_confirmation"
                                    class="lp-edit-input" placeholder="Ulangi password baru" autocomplete="new-password">
                                <button type="button" class="lp-edit-password-toggle" @click="showPassConfirm = !showPassConfirm"
                                        :aria-label="showPassConfirm ? 'Sembunyikan password' : 'Tampilkan password'">
                                    <svg x-show="!showPassConfirm" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8Z"/><circle cx="12" cy="12" r="3"/></svg>
                                    <svg x-show="showPassConfirm" x-cloak width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M9.88 9.88a3 3 0 1 0 4.24 4.24"/><path d="M10.73 5.08A10.43 10.43 0 0 1 12 5c7 0 11 7 11 7a13.16 13.16 0 0 1-1.67 2.68"/><path d="M6.61 6.61A13.526 13.526 0 0 0 1 12s4 7 11 7a9.74 9.74 0 0 0 5.39-1.61"/><line x1="2" y1="2" x2="22" y2="22"/></svg>
                                </button>
                            </div>
                        </div>
                    </div>
                </div>

                {{-- ===== ACTIONS ===== --}}
                <div class="lp-edit-actions">
                    <a href="{{ route('anggota.profil') }}" class="lp-btn lp-btn--ghost">Batal</a>
                    <button type="submit" class="lp-btn lp-btn--primary">Simpan Perubahan</button>
                </div>

            </form>
        </div>
    </div>
</section>
@push('styles')
    @vite(['resources/css/landing-page/anggota/profile.css'])
@endpush
@endsection