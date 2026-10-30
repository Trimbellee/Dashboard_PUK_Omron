<div
    x-data="{

        /* ─ STATE DOMISILI ─ */
        listKotaD : [],
        listKecD  : [],
        listKelD  : [],
        busyKotaD : false,
        busyKecD  : false,
        busyKelD  : false,
        provD : '{{ old('provinsi_domisili', $anggota->provinsi_domisili ?? '') }}',
        kotD  : '{{ old('kota_domisili',     $anggota->kota_domisili     ?? '') }}',
        kecD  : '{{ old('kecamatan_domisili',$anggota->kecamatan_domisili ?? '') }}',
        kelD  : '{{ old('kelurahan_domisili',$anggota->kelurahan_domisili ?? '') }}',

        /* ─ STATE KTP ─ */
        listKotaK : [],
        listKecK  : [],
        listKelK  : [],
        busyKotaK : false,
        busyKecK  : false,
        busyKelK  : false,
        provK : '{{ old('provinsi_ktp', $anggota->provinsi_ktp ?? '') }}',
        kotK  : '{{ old('kota_ktp',     $anggota->kota_ktp     ?? '') }}',
        kecK  : '{{ old('kecamatan_ktp',$anggota->kecamatan_ktp ?? '') }}',
        kelK  : '{{ old('kelurahan_ktp',$anggota->kelurahan_ktp ?? '') }}',

        async ajx(url) {
            try {
                const r = await window.fetch(url);
                return r.ok ? await r.json() : [];
            } catch { return []; }
        },

        async init() {
            if (this.provD) {
                this.busyKotaD = true;
                this.listKotaD = await this.ajx('/api/wilayah/kota/' + this.provD);
                this.busyKotaD = false;
                if (this.kotD) {
                    this.busyKecD = true;
                    this.listKecD = await this.ajx('/api/wilayah/kecamatan/' + this.kotD);
                    this.busyKecD = false;
                    if (this.kecD) {
                        this.busyKelD = true;
                        this.listKelD = await this.ajx('/api/wilayah/kelurahan/' + this.kecD);
                        this.busyKelD = false;
                    }
                }
            }
            if (this.provK) {
                this.busyKotaK = true;
                this.listKotaK = await this.ajx('/api/wilayah/kota/' + this.provK);
                this.busyKotaK = false;
                if (this.kotK) {
                    this.busyKecK = true;
                    this.listKecK = await this.ajx('/api/wilayah/kecamatan/' + this.kotK);
                    this.busyKecK = false;
                    if (this.kecK) {
                        this.busyKelK = true;
                        this.listKelK = await this.ajx('/api/wilayah/kelurahan/' + this.kecK);
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
            if (t === 'D') { this.busyKotaD=true; this.listKotaD = await this.ajx('/api/wilayah/kota/'+code); this.busyKotaD=false; }
            else           { this.busyKotaK=true; this.listKotaK = await this.ajx('/api/wilayah/kota/'+code); this.busyKotaK=false; }
        },

        async onKot(t) {
            const code = t === 'D' ? this.kotD : this.kotK;
            if (t === 'D') { this.listKecD=[]; this.kecD=''; this.listKelD=[]; this.kelD=''; }
            else           { this.listKecK=[]; this.kecK=''; this.listKelK=[]; this.kelK=''; }
            if (!code) return;
            if (t === 'D') { this.busyKecD=true; this.listKecD = await this.ajx('/api/wilayah/kecamatan/'+code); this.busyKecD=false; }
            else           { this.busyKecK=true; this.listKecK = await this.ajx('/api/wilayah/kecamatan/'+code); this.busyKecK=false; }
        },

        async onKec(t) {
            const code = t === 'D' ? this.kecD : this.kecK;
            if (t === 'D') { this.listKelD=[]; this.kelD=''; }
            else           { this.listKelK=[]; this.kelK=''; }
            if (!code) return;
            if (t === 'D') { this.busyKelD=true; this.listKelD = await this.ajx('/api/wilayah/kelurahan/'+code); this.busyKelD=false; }
            else           { this.busyKelK=true; this.listKelK = await this.ajx('/api/wilayah/kelurahan/'+code); this.busyKelK=false; }
        },
    }"
    class="af-root"
>

    {{-- ── HERO ────────────────────────────────────────────────── --}}
    <div class="af-hero">
        <div class="af-hero-left">
            <span class="af-eyebrow">Serikat Pekerja · Database Anggota</span>
            <h1 class="af-hero-title">Form Data Anggota</h1>
            <p class="af-hero-sub">Kelola identitas, status keanggotaan, dan data personal anggota serikat.</p>
        </div>
        <div class="af-hero-badges">
            <div class="af-badge">
                <span class="af-badge-label">Status</span>
                <span class="af-badge-value">{{ old('status_karyawan', $anggota->status_karyawan ?? '—') }}</span>
            </div>
            <div class="af-badge">
                <span class="af-badge-label">NIK</span>
                <span class="af-badge-value">{{ old('nik_karyawan', $anggota->nik_karyawan ?? '—') }}</span>
            </div>
        </div>
</div>

    @if ($errors->any())
        <div class="af-alert-error">
            <strong>Periksa kembali data yang diisi:</strong>
            <ul>
                @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    @csrf

    {{-- ══ 01 DATA UTAMA ══════════════════════════════════════════ --}}
    <section class="af-card">
        <div class="af-card-header">
            <div class="af-step-num">01</div>
            <div>
                <h2 class="af-card-title">Data Utama</h2>
                <p class="af-card-sub">Informasi dasar anggota dan status pekerjaan.</p>
            </div>
        </div>
        <div class="af-grid-2">

            <div class="af-field af-span-2">
                <label class="af-label">Nama Lengkap <span class="af-req">*</span></label>
                <input type="text" name="nama_lengkap"
                       value="{{ old('nama_lengkap', $anggota->nama_lengkap ?? '') }}"
                       class="af-input" placeholder="Masukkan nama lengkap">
                @error('nama_lengkap')<p class="af-error">{{ $message }}</p>@enderror
            </div>

            <div class="af-field">
                <label class="af-label">NIK Karyawan <span class="af-req">*</span></label>
                <input type="text" name="nik_karyawan" maxlength="8" inputmode="numeric"
                       pattern="[0-9]*" oninput="this.value=this.value.replace(/[^0-9]/g,'')"
                       value="{{ old('nik_karyawan', $anggota->nik_karyawan ?? '') }}"
                       class="af-input" placeholder="Contoh: 12345678">
                @error('nik_karyawan')<p class="af-error">{{ $message }}</p>@enderror
            </div>

            <div class="af-field">
                <label class="af-label">Jenis Kelamin <span class="af-req">*</span></label>
                <select name="jenis_kelamin" class="af-input">
                    <option value="">Pilih Jenis Kelamin</option>
                    <option value="Pria"   {{ old('jenis_kelamin', $anggota->jenis_kelamin ?? '') == 'Pria'   ? 'selected' : '' }}>Pria</option>
                    <option value="Wanita" {{ old('jenis_kelamin', $anggota->jenis_kelamin ?? '') == 'Wanita' ? 'selected' : '' }}>Wanita</option>
                </select>
                @error('jenis_kelamin')<p class="af-error">{{ $message }}</p>@enderror
            </div>

            <div class="af-field">
                <label class="af-label">Departemen <span class="af-req">*</span></label>
                <input type="text" name="departemen"
                       value="{{ old('departemen', $anggota->departemen ?? '') }}"
                       class="af-input" placeholder="Contoh: Produksi">
                @error('departemen')<p class="af-error">{{ $message }}</p>@enderror
            </div>

            <div class="af-field">
                <label class="af-label">Posisi / Jabatan <span class="af-req">*</span></label>
                <input type="text" name="jabatan"
                       value="{{ old('jabatan', $anggota->jabatan ?? '') }}"
                       class="af-input" placeholder="Contoh: Operator">
                @error('jabatan')<p class="af-error">{{ $message }}</p>@enderror
            </div>

            <div class="af-field">
                <label class="af-label">Status Karyawan <span class="af-req">*</span></label>
                <select name="status_karyawan" class="af-input">
                    <option value="">Pilih Status</option>
                    <option value="Permanen" {{ old('status_karyawan', $anggota->status_karyawan ?? '') == 'Permanen' ? 'selected' : '' }}>Permanen</option>
                    <option value="Kontrak"  {{ old('status_karyawan', $anggota->status_karyawan ?? '') == 'Kontrak'  ? 'selected' : '' }}>Kontrak</option>
                </select>
                @error('status_karyawan')<p class="af-error">{{ $message }}</p>@enderror
            </div>

            <div class="af-field">
                <label class="af-label">Tanggal Mulai Bekerja</label>
                <input type="date" name="tanggal_mulai_bekerja"
                       value="{{ old('tanggal_mulai_bekerja', isset($anggota->tanggal_mulai_bekerja) ? \Carbon\Carbon::parse($anggota->tanggal_mulai_bekerja)->format('Y-m-d') : '') }}"
                       class="af-input">
            </div>

        </div>
    </section>

    {{-- ══ 02 IDENTITAS ══════════════════════════════════════════ --}}
    <section class="af-card">
        <div class="af-card-header">
            <div class="af-step-num">02</div>
            <div>
                <h2 class="af-card-title">Data Identitas</h2>
                <p class="af-card-sub">Informasi identitas resmi anggota.</p>
            </div>
        </div>
        <div class="af-grid-2">

            <div class="af-field">
                <label class="af-label">Jenis Identitas</label>
                <select name="jenis_identitas" class="af-input">
                    <option value="">Pilih</option>
                    <option value="KTP"    {{ old('jenis_identitas', $anggota->jenis_identitas ?? '') == 'KTP'    ? 'selected' : '' }}>KTP</option>
                    <option value="SIM"    {{ old('jenis_identitas', $anggota->jenis_identitas ?? '') == 'SIM'    ? 'selected' : '' }}>SIM</option>
                    <option value="PASPOR" {{ old('jenis_identitas', $anggota->jenis_identitas ?? '') == 'PASPOR' ? 'selected' : '' }}>PASPOR</option>
                </select>
            </div>

            <div class="af-field">
                <label class="af-label">Nomor Identitas</label>
                <input type="text" name="nomor_identitas" maxlength="16" inputmode="numeric"
                       oninput="this.value=this.value.replace(/[^0-9]/g,'')"
                       value="{{ old('nomor_identitas', $anggota->nomor_identitas ?? '') }}"
                       class="af-input" placeholder="Masukkan nomor identitas">
                @error('nomor_identitas')<p class="af-error">{{ $message }}</p>@enderror
            </div>

            <div class="af-field af-span-2"
                x-data="rfidField('{{ addslashes(old('rfid_code', $anggota->rfid_code ?? '')) }}')">
                <label class="af-label">Kode RFID / NFC</label>

                <div style="display:flex;gap:8px;align-items:center;flex-wrap:wrap;">
                    <input type="text" name="rfid_code" x-model="value"
                        @change="confirmIfNeeded()"
                        class="af-input" style="flex:1;min-width:200px;"
                        placeholder="Tap kartu di reader USB, atau ketik manual"
                        autocomplete="off">

                    <button type="button" class="af-file-btn" @click="tapNfc()" x-show="nfcSupported" x-cloak>
                        <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <path d="M12 2a10 10 0 1 0 0 20 10 10 0 0 0 0-20z"/>
                            <path d="M8 12a4 4 0 0 1 8 0"/>
                        </svg>
                        <span x-text="nfcButtonLabel"></span>
                    </button>
                </div>

                <template x-if="originalValue">
                    <p style="font-size:11px;color:#16A34A;margin-top:6px;">
                        ✓ Kartu sudah terdaftar: <code x-text="originalValue"></code>
                    </p>
                </template>

                <p style="font-size:11px;color:var(--af-text-faint);margin-top:4px;">
                    Klik field lalu tap kartu di reader USB, atau tekan tombol "Tap NFC" untuk scan pakai HP (Chrome Android saja).
                </p>

                @error('rfid_code')<p class="af-error">{{ $message }}</p>@enderror
            </div>

            <script>
            function rfidField(initial) {
                return {
                    value: initial,
                    originalValue: initial,
                    nfcSupported: 'NDEFReader' in window,
                    nfcButtonLabel: 'Tap NFC (HP)',

                    confirmIfNeeded() {
                        if (!this.originalValue || this.value === this.originalValue) return;

                        const proceed = (ok) => { if (!ok) this.value = this.originalValue; };

                        if (typeof Swal !== 'undefined') {
                            Swal.fire({
                                icon: 'warning',
                                title: 'Ganti Kode RFID?',
                                html: `Anggota ini sudah punya kartu terdaftar (<b>${this.originalValue}</b>).<br>Kartu lama akan digantikan kode baru saat data disimpan.`,
                                showCancelButton: true,
                                confirmButtonText: 'Ya, Ganti',
                                cancelButtonText: 'Batal',
                                reverseButtons: true,
                            }).then(res => proceed(res.isConfirmed));
                        } else {
                            proceed(confirm(`Anggota ini sudah punya kartu terdaftar (${this.originalValue}). Ganti dengan kode baru?`));
                        }
                    },

                    async tapNfc() {
                        if (!this.nfcSupported) {
                            alert('HP/browser ini tidak mendukung Web NFC. Gunakan Chrome di Android.');
                            return;
                        }
                        try {
                            this.nfcButtonLabel = 'Tempelkan kartu...';
                            const reader = new NDEFReader();
                            await reader.scan();

                            reader.onreading = (event) => {
                                const normalized = (event.serialNumber || '')
                                    .toUpperCase()
                                    .replace(/[^A-Z0-9]/g, '');

                                this.nfcButtonLabel = 'Tap NFC (HP)';
                                this.value = normalized;
                                this.confirmIfNeeded();
                            };

                            reader.onreadingerror = () => {
                                this.nfcButtonLabel = 'Tap NFC (HP)';
                                alert('Gagal membaca kartu, coba tap ulang.');
                            };
                        } catch (e) {
                            this.nfcButtonLabel = 'Tap NFC (HP)';
                            alert('Tidak bisa akses NFC — pastikan izin diberikan dan NFC aktif di pengaturan HP.');
                        }
                    },
                };
            }
            </script>

            <div class="af-field">
                <label class="af-label">Nomor WhatsApp <span class="af-req">*</span></label>
                <input type="text" name="nomor_whatsapp" maxlength="13" inputmode="tel"
                       value="{{ old('nomor_whatsapp', $anggota->nomor_whatsapp ?? '') }}"
                       class="af-input" placeholder="Contoh: +6281234567890"
                       oninput="
                           let v=this.value;
                           v=v.replace(/[^0-9+]/g,'');
                           if(v.indexOf('+')>0){ v=v.replace(/\+/g,''); }
                           if(v.startsWith('+')){ v='+'+v.substring(1).replace(/\+/g,''); }
                           this.value=v;" required>
                @error('nomor_whatsapp')<p class="af-error">{{ $message }}</p>@enderror
            </div>

            <div class="af-field">
                <label class="af-label">Alamat Email <span class="af-req">*</span></label>
                <input type="email" name="email"
                       value="{{ old('email', $anggota->email ?? '') }}"
                       class="af-input" placeholder="contoh@email.com" required>
                @error('email')<p class="af-error">{{ $message }}</p>@enderror
            </div>

        </div>
    </section>

    {{-- ══ 03 KELAHIRAN ══════════════════════════════════════════ --}}
    <section class="af-card">
        <div class="af-card-header">
            <div class="af-step-num">03</div>
            <div>
                <h2 class="af-card-title">Data Kelahiran</h2>
                <p class="af-card-sub">Informasi personal anggota.</p>
            </div>
        </div>
        <div class="af-grid-3">
            <div class="af-field">
                <label class="af-label">Tempat Lahir</label>
                <input type="text" name="tempat_lahir"
                       value="{{ old('tempat_lahir', $anggota->tempat_lahir ?? '') }}"
                       class="af-input" placeholder="Kota kelahiran">
            </div>
            <div class="af-field">
                <label class="af-label">Tanggal Lahir</label>
                <input type="date" name="tanggal_lahir"
                       value="{{ old('tanggal_lahir', isset($anggota->tanggal_lahir) ? $anggota->tanggal_lahir->format('Y-m-d') : '') }}"
                       class="af-input">
            </div>
            <div class="af-field">
                <label class="af-label">Pendidikan Terakhir</label>
                <select name="pendidikan_terakhir" class="af-input">
                    <option value="">Pilih</option>
                    @foreach(['SD','SMP','SMA','SMK','D1','D2','D3','D4','S1','S2','S3'] as $edu)
                        <option value="{{ $edu }}" {{ old('pendidikan_terakhir', $anggota->pendidikan_terakhir ?? '') == $edu ? 'selected' : '' }}>{{ $edu }}</option>
                    @endforeach
                </select>
            </div>
        </div>
    </section>

    {{-- ══ 04 DOMISILI ════════════════════════════════════════════ --}}
    <section class="af-card">
        <div class="af-card-header">
            <div class="af-step-num">04</div>
            <div>
                <h2 class="af-card-title">Alamat Domisili</h2>
                <p class="af-card-sub">Alamat tempat tinggal saat ini.</p>
            </div>
        </div>
        <div class="af-grid-2">

            <div class="af-field">
                <label class="af-label">Provinsi</label>
                <select name="provinsi_domisili" class="af-input"
                        x-model="provD" @change="onProv('D')">
                    <option value="">— Pilih Provinsi —</option>
                    @foreach($provinsiList as $prov)
                        <option value="{{ $prov->code }}">{{ $prov->name }}</option>
                    @endforeach
                </select>
            </div>

            <div class="af-field">
                <label class="af-label">Kota / Kabupaten</label>
                <div class="af-sel-wrap">
                    <select name="kota_domisili" class="af-input"
                            x-model="kotD" :disabled="!listKotaD.length || busyKotaD"
                            @change="onKot('D')">
                        <option value="" x-text="busyKotaD ? 'Memuat...' : (provD ? '— Pilih Kota —' : '— Pilih Provinsi dulu —')"></option>
                        <template x-for="item in listKotaD" :key="item.code">
                            <option :value="item.code" :selected="item.code == kotD" x-text="item.name"></option>
                        </template>
                    </select>
                    <span class="af-spin" x-show="busyKotaD"></span>
                </div>
            </div>

            <div class="af-field">
                <label class="af-label">Kecamatan</label>
                <div class="af-sel-wrap">
                    <select name="kecamatan_domisili" class="af-input"
                            x-model="kecD" :disabled="!listKecD.length || busyKecD"
                            @change="onKec('D')">
                        <option value="" x-text="busyKecD ? 'Memuat...' : (kotD ? '— Pilih Kecamatan —' : '— Pilih Kota dulu —')"></option>
                        <template x-for="item in listKecD" :key="item.code">
                            <option :value="item.code" :selected="item.code == kecD" x-text="item.name"></option>
                        </template>
                    </select>
                    <span class="af-spin" x-show="busyKecD"></span>
                </div>
            </div>

            <div class="af-field">
                <label class="af-label">Kelurahan / Desa</label>
                <div class="af-sel-wrap">
                    <select name="kelurahan_domisili" class="af-input"
                            x-model="kelD" :disabled="!listKelD.length || busyKelD">
                        <option value="" x-text="busyKelD ? 'Memuat...' : (kecD ? '— Pilih Kelurahan —' : '— Pilih Kecamatan dulu —')"></option>
                        <template x-for="item in listKelD" :key="item.code">
                            <option :value="item.code" :selected="item.code == kelD" x-text="item.name"></option>
                        </template>
                    </select>
                    <span class="af-spin" x-show="busyKelD"></span>
                </div>
            </div>

            <div class="af-field af-span-2">
                <label class="af-label">Alamat Lengkap</label>
                <textarea name="alamat_domisili" rows="3" class="af-textarea"
                          placeholder="Jalan, RT/RW, Nomor...">{{ old('alamat_domisili', $anggota->alamat_domisili ?? '') }}</textarea>
            </div>

        </div>
    </section>

    {{-- ══ 05 KTP ══════════════════════════════════════════════════ --}}
    <section class="af-card">
        <div class="af-card-header">
            <div class="af-step-num">05</div>
            <div>
                <h2 class="af-card-title">Alamat Sesuai KTP</h2>
                <p class="af-card-sub">Alamat berdasarkan identitas resmi.</p>
            </div>
        </div>
        <div class="af-grid-2">

            <div class="af-field">
                <label class="af-label">Provinsi</label>
                <select name="provinsi_ktp" class="af-input"
                        x-model="provK" @change="onProv('K')">
                    <option value="">— Pilih Provinsi —</option>
                    @foreach($provinsiList as $prov)
                        <option value="{{ $prov->code }}">{{ $prov->name }}</option>
                    @endforeach
                </select>
            </div>

            <div class="af-field">
                <label class="af-label">Kota / Kabupaten</label>
                <div class="af-sel-wrap">
                    <select name="kota_ktp" class="af-input"
                            x-model="kotK" :disabled="!listKotaK.length || busyKotaK"
                            @change="onKot('K')">
                        <option value="" x-text="busyKotaK ? 'Memuat...' : (provK ? '— Pilih Kota —' : '— Pilih Provinsi dulu —')"></option>
                        <template x-for="item in listKotaK" :key="item.code">
                            <option :value="item.code" :selected="item.code == kotK" x-text="item.name"></option>
                        </template>
                    </select>
                    <span class="af-spin" x-show="busyKotaK"></span>
                </div>
            </div>

            <div class="af-field">
                <label class="af-label">Kecamatan</label>
                <div class="af-sel-wrap">
                    <select name="kecamatan_ktp" class="af-input"
                            x-model="kecK" :disabled="!listKecK.length || busyKecK"
                            @change="onKec('K')">
                        <option value="" x-text="busyKecK ? 'Memuat...' : (kotK ? '— Pilih Kecamatan —' : '— Pilih Kota dulu —')"></option>
                        <template x-for="item in listKecK" :key="item.code">
                            <option :value="item.code" :selected="item.code == kecK" x-text="item.name"></option>
                        </template>
                    </select>
                    <span class="af-spin" x-show="busyKecK"></span>
                </div>
            </div>

            <div class="af-field">
                <label class="af-label">Kelurahan / Desa</label>
                <div class="af-sel-wrap">
                    <select name="kelurahan_ktp" class="af-input"
                            x-model="kelK" :disabled="!listKelK.length || busyKelK">
                        <option value="" x-text="busyKelK ? 'Memuat...' : (kecK ? '— Pilih Kelurahan —' : '— Pilih Kecamatan dulu —')"></option>
                        <template x-for="item in listKelK" :key="item.code">
                            <option :value="item.code" :selected="item.code == kelK" x-text="item.name"></option>
                        </template>
                    </select>
                    <span class="af-spin" x-show="busyKelK"></span>
                </div>
            </div>

            <div class="af-field af-span-2">
                <label class="af-label">Alamat Lengkap</label>
                <textarea name="alamat_ktp" rows="3" class="af-textarea"
                          placeholder="Jalan, RT/RW, Nomor...">{{ old('alamat_ktp', $anggota->alamat_ktp ?? '') }}</textarea>
            </div>

        </div>
    </section>

    {{-- ══ 06 FOTO ════════════════════════════════════════════════ --}}
    <section class="af-card">
        <div class="af-card-header">
            <div class="af-step-num">06</div>
            <div>
                <h2 class="af-card-title">Foto Anggota</h2>
                <p class="af-card-sub">Upload foto profil anggota.</p>
            </div>
        </div>
        <div x-data="{ preview: '{{ $anggota->foto_url ?? asset('assets/no-photo.png') }}' }" class="af-photo-wrap">
            <div class="af-photo-preview-wrap">
                <img :src="preview" class="af-photo-img" alt="Foto anggota">
                <div class="af-photo-overlay">Ganti Foto</div>
            </div>
            <div class="af-photo-right">
                <p class="af-photo-hint">Foto yang jelas akan mempermudah identifikasi anggota.</p>
                <label class="af-file-btn">
                    <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"/>
                        <polyline points="17 8 12 3 7 8"/>
                        <line x1="12" y1="3" x2="12" y2="15"/>
                    </svg>
                    Pilih Foto
                    <input type="file" name="foto" accept="image/*" class="af-file-hidden"
                           @change="const f=$event.target.files[0]; if(f){ preview=URL.createObjectURL(f); }">
                </label>
                <p class="af-file-meta">JPG, PNG, WEBP · Maks. 2 MB</p>
                @error('foto')<p class="af-error">{{ $message }}</p>@enderror
            </div>
        </div>
    </section>

    {{-- ══ 07 SERAGAM ═════════════════════════════════════════════ --}}
    <section class="af-card">
        <div class="af-card-header">
            <div class="af-step-num">07</div>
            <div>
                <h2 class="af-card-title">Data Seragam</h2>
                <p class="af-card-sub">Ukuran dan jumlah kaos &amp; kemeja organisasi yang dimiliki.</p>
            </div>
        </div>
        <div class="af-grid-2">

            <div class="af-field">
                <label class="af-label">Ukuran Kaos</label>
                <select name="ukuran_kaos" class="af-input">
                    <option value="">Pilih ukuran</option>
                    @foreach(\App\Helpers\SeragamHelper::UKURAN as $uk)
                        <option value="{{ $uk }}" {{ old('ukuran_kaos', $anggota->ukuran_kaos ?? '') === $uk ? 'selected' : '' }}>{{ $uk }}</option>
                    @endforeach
                </select>
                @error('ukuran_kaos')<p class="af-error">{{ $message }}</p>@enderror
            </div>

            <div class="af-field">
                <label class="af-label">Jumlah Kaos yang Dimiliki</label>
                <input type="number" name="jumlah_kaos" class="af-input"
                       min="0" max="{{ \App\Helpers\SeragamHelper::JUMLAH_MAKS }}" step="1"
                       value="{{ old('jumlah_kaos', $anggota->jumlah_kaos ?? 0) }}"
                       placeholder="0 = belum punya">
                @error('jumlah_kaos')<p class="af-error">{{ $message }}</p>@enderror
            </div>

            <div class="af-field">
                <label class="af-label">Ukuran Kemeja</label>
                <select name="ukuran_kemeja" class="af-input">
                    <option value="">Pilih ukuran</option>
                    @foreach(\App\Helpers\SeragamHelper::UKURAN as $uk)
                        <option value="{{ $uk }}" {{ old('ukuran_kemeja', $anggota->ukuran_kemeja ?? '') === $uk ? 'selected' : '' }}>{{ $uk }}</option>
                    @endforeach
                </select>
                @error('ukuran_kemeja')<p class="af-error">{{ $message }}</p>@enderror
            </div>

            <div class="af-field">
                <label class="af-label">Jumlah Kemeja yang Dimiliki</label>
                <input type="number" name="jumlah_kemeja" class="af-input"
                       min="0" max="{{ \App\Helpers\SeragamHelper::JUMLAH_MAKS }}" step="1"
                       value="{{ old('jumlah_kemeja', $anggota->jumlah_kemeja ?? 0) }}"
                       placeholder="0 = belum punya">
                @error('jumlah_kemeja')<p class="af-error">{{ $message }}</p>@enderror
            </div>

        </div>
    </section>

    {{-- ── ACTION BAR ──────────────────────────────────────────── --}}
    <div class="af-actions">
        <a href="{{ route('anggota.index') }}" class="btn btn-ghost" style="text-decoration:none;">
            <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                <polyline points="15 18 9 12 15 6"/>
            </svg>
            Kembali
        </a>
        <button type="submit" class="btn btn-primary">
            Simpan Data
            <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                <polyline points="20 6 9 17 4 12"/>
            </svg>
        </button>
    </div>

</div>

<style>
/*
 * CSS VARIABLES: af-* yang berkaitan dengan warna sekarang
 * menggunakan CSS var() dari layouts/app.blade sebagai value,
 * sehingga dark mode (.dark-mode class pada <body>) otomatis berlaku.
 *
 * Font: disamakan dengan layout ('DM Sans')
 */

:root {
    /* Override af-* pakai var dari layout */
    --af-surface:     var(--bg-surface);
    --af-surface2:    var(--bg-base);
    --af-border:      var(--border);
    --af-border-h:    var(--accent);
    --af-accent:      var(--accent);
    --af-accent-glow: rgba(37,99,235,.12);
    --af-text:        var(--text-primary);
    --af-text-muted:  var(--text-secondary);
    --af-text-faint:  var(--text-muted);
    --af-step-bg:     var(--accent-muted);
    --af-step-color:  var(--accent-text);
    --af-req:         #ef4444;
    --af-error:       #ef4444;
    --af-radius:      var(--nav-radius, 10px);
    --af-radius-lg:   14px;
    --af-radius-xl:   18px;
    /* Pakai font yang sama dengan layout */
    --af-font:        'DM Sans', system-ui, sans-serif;
    --af-mono:        'DM Mono', monospace;
}

.af-root *,
.af-root *::before,
.af-root *::after { box-sizing:border-box; }

.af-root {
    font-family: var(--af-font);
    color: var(--af-text);
    display: flex;
    flex-direction: column;
    gap: 1.25rem;
    padding-bottom: 3rem;
}

/* Hero */
.af-hero {
    background: linear-gradient(135deg,#1e3a8a,#1e40af 50%,#1d4ed8);
    border-radius: var(--af-radius-xl);
    padding: 2.25rem 2.5rem;
    display: flex; flex-direction: column; gap: 1.5rem;
    position: relative; overflow: hidden;
}
.af-hero::after {
    content:''; position:absolute; top:-100px; right:-80px;
    width:320px; height:320px;
    background:rgba(255,255,255,.05); border-radius:50%; pointer-events:none;
}
@media(min-width:768px){
    .af-hero { flex-direction:row; align-items:center; justify-content:space-between; }
}
.af-eyebrow {
    font-family:var(--af-mono); font-size:.68rem; letter-spacing:.1em;
    text-transform:uppercase; color:#93c5fd;
    background:rgba(255,255,255,.08); border:1px solid rgba(255,255,255,.12);
    padding:.3rem .8rem; border-radius:999px; display:inline-block; margin-bottom:.7rem;
}
.af-hero-title {
    font-size:clamp(1.6rem,3vw,2.1rem); font-weight:700; letter-spacing:-.025em;
    color:#fff; margin-bottom:.35rem; line-height:1.15;
}
.af-hero-sub { font-size:.85rem; color:rgba(255,255,255,.55); line-height:1.6; max-width:40ch; }
.af-hero-badges { display:flex; gap:.65rem; flex-shrink:0; }
.af-badge {
    background:rgba(255,255,255,.1); border:1px solid rgba(255,255,255,.15);
    border-radius:var(--af-radius); padding:.9rem 1.2rem; min-width:115px;
}
.af-badge-label {
    display:block; font-family:var(--af-mono); font-size:.62rem;
    letter-spacing:.1em; text-transform:uppercase;
    color:rgba(255,255,255,.45); margin-bottom:.3rem;
}
.af-badge-value { display:block; font-size:.95rem; font-weight:600; color:#fff; }

/* Cards — pakai var dari layout */
.af-card {
    background:var(--af-surface); border:1px solid var(--af-border);
    border-radius:var(--af-radius-xl); padding:1.75rem 2rem 2rem;
    box-shadow:var(--shadow-sm); transition:border-color .2s,box-shadow .2s;
}
.af-card:focus-within {
    border-color:var(--af-border-h);
    box-shadow:0 0 0 3px var(--af-accent-glow);
}
.af-card-header {
    display:flex; align-items:flex-start; gap:1rem;
    margin-bottom:1.5rem; padding-bottom:1.25rem;
    border-bottom:1px solid var(--af-border);
}
.af-step-num {
    font-family:var(--af-mono); font-size:.7rem; font-weight:500;
    color:var(--af-step-color); background:var(--af-step-bg);
    border:1px solid rgba(37,99,235,.2); border-radius:8px;
    padding:.3rem .55rem; flex-shrink:0; margin-top:.1rem;
}
.af-card-title { font-size:1rem; font-weight:700; color:var(--af-text); margin-bottom:.15rem; }
.af-card-sub { font-size:.8rem; color:var(--af-text-muted); }

/* Grids */
.af-grid-2 { display:grid; grid-template-columns:1fr; gap:1.1rem; }
.af-grid-3 { display:grid; grid-template-columns:1fr; gap:1.1rem; }
@media(min-width:600px){
    .af-grid-2 { grid-template-columns:1fr 1fr; }
    .af-grid-3 { grid-template-columns:1fr 1fr 1fr; }
}
.af-span-2 { grid-column:1/-1; }

/* Fields */
.af-field { display:flex; flex-direction:column; gap:.45rem; }
.af-label {
    font-size:.75rem; font-weight:600; letter-spacing:.03em;
    text-transform:uppercase; color:var(--af-text-muted);
}
.af-req { color:var(--af-req); }

/* Inputs — selaraskan dengan .form-input dari layout */
.af-input, .af-textarea {
    width:100%;
    background:var(--af-surface2);
    border:1px solid var(--border-strong);   /* pakai var layout */
    border-radius:9px;
    color:var(--af-text);
    font-family:var(--af-font);
    font-size:.875rem;
    padding:.72rem 1rem;
    outline:none;
    transition:border-color .15s,box-shadow .15s,background .15s;
    appearance:none; -webkit-appearance:none;
}
.af-input::placeholder, .af-textarea::placeholder { color:var(--af-text-faint); }
.af-input:hover, .af-textarea:hover { border-color:var(--border-strong); background:var(--af-surface); }
.af-input:focus, .af-textarea:focus {
    border-color:var(--accent);
    box-shadow:0 0 0 3px var(--af-accent-glow);
    background:var(--af-surface);
}
.af-textarea { resize:vertical; line-height:1.6; }

select.af-input {
    background-image:url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='16' height='16' viewBox='0 0 24 24' fill='none' stroke='%2394a3b8' stroke-width='2'%3E%3Cpolyline points='6 9 12 15 18 9'/%3E%3C/svg%3E");
    background-repeat:no-repeat; background-position:right .9rem center;
    padding-right:2.75rem; cursor:pointer;
}
select.af-input:disabled { opacity:.5; cursor:not-allowed; background-color:var(--bg-elevated); }

/* Select spinner */
.af-sel-wrap { position:relative; display:flex; align-items:center; }
.af-sel-wrap .af-input { flex:1; }
.af-spin {
    position:absolute; right:2.8rem;
    width:13px; height:13px;
    border:2px solid var(--border-strong); border-top-color:var(--accent);
    border-radius:50%; animation:afSpin .55s linear infinite; pointer-events:none;
}
@keyframes afSpin { to { transform:rotate(360deg); } }

/* Error */
.af-error { font-size:.775rem; color:var(--af-error); margin:0; }
.af-error::before { content:'⚠ '; }

/* Date picker */
.af-input::-webkit-calendar-picker-indicator { opacity:.4; cursor:pointer; }

/* Photo */
.af-photo-wrap { display:flex; flex-direction:column; align-items:flex-start; gap:1.5rem; }
@media(min-width:600px){ .af-photo-wrap { flex-direction:row; align-items:center; } }
.af-photo-preview-wrap {
    position:relative; flex-shrink:0;
    border-radius:var(--af-radius-lg); overflow:hidden; cursor:pointer;
}
.af-photo-img {
    width:110px; height:110px; object-fit:cover; display:block;
    border:2px solid var(--af-border); border-radius:var(--af-radius-lg);
}
.af-photo-overlay {
    position:absolute; inset:0;
    background:rgba(37,99,235,.72);
    display:flex; align-items:center; justify-content:center;
    font-size:.75rem; font-weight:600; color:#fff;
    opacity:0; transition:opacity .2s;
}
.af-photo-preview-wrap:hover .af-photo-overlay { opacity:1; }
.af-photo-right { flex:1; }
.af-photo-hint { font-size:.82rem; color:var(--af-text-muted); margin-bottom:.9rem; line-height:1.5; }
.af-file-btn {
    display:inline-flex; align-items:center; gap:.5rem; cursor:pointer;
    background:var(--af-surface2); border:1px solid var(--af-border);
    color:var(--af-text); font-family:var(--af-font); font-size:.85rem;
    font-weight:600; padding:.65rem 1.2rem; border-radius:9px;
    transition:border-color .15s,background .15s;
}
.af-file-btn:hover { border-color:var(--border-strong); background:var(--af-surface); }
.af-file-hidden { display:none; }
.af-file-meta { font-size:.72rem; color:var(--af-text-faint); margin-top:.5rem; font-family:var(--af-mono); }

/* Actions — pakai class .btn .btn-primary .btn-ghost dari layout */
.af-actions {
    display:flex; align-items:center;
    justify-content:flex-end; gap:.65rem; padding-top:.25rem;
}

/* Alert */
.af-alert-error {
    background:#fef2f2; border:1px solid #fecaca;
    color:#991b1b; padding:16px; border-radius:12px; margin-bottom:4px;
}
.af-alert-error ul { margin-top:8px; padding-left:18px; }

</style>