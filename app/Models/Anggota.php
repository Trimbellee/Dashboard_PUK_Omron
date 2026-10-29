<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;
use Laravolt\Indonesia\Models\Province;
use Laravolt\Indonesia\Models\City;
use Laravolt\Indonesia\Models\District;
use Laravolt\Indonesia\Models\Village;

class Anggota extends Model
{
    use SoftDeletes;

    protected $table = 'anggota';

    protected $fillable = [
        'nama_lengkap',
        'nik_karyawan',
        'departemen',
        'jabatan',
        'jabatan_puk',
        'jenis_kelamin',
        'jenis_identitas',
        'nomor_identitas',
        'tempat_lahir',
        'tanggal_lahir',
        'provinsi_domisili',
        'kota_domisili',
        'alamat_domisili',
        'provinsi_ktp',
        'kota_ktp',
        'alamat_ktp',
        'pendidikan_terakhir',
        'tanggal_mulai_bekerja',
        'status_karyawan',
        'tanggal_masuk_anggota',
        'tanggal_keluar_anggota',
        'is_active',
        'foto',
        'password',
        'kecamatan_domisili',
        'kelurahan_domisili',
        'kecamatan_ktp',
        'kelurahan_ktp',
        'nomor_whatsapp',
        'email',
        'rfid_code',
        'ukuran_kaos',
        'ukuran_kemeja',
        'sudah_punya_kaos',
        'sudah_punya_kemeja',
    ];

    protected $casts = [
        'tanggal_lahir' => 'date',
        'tanggal_mulai_bekerja' => 'date',
        'tanggal_masuk_anggota' => 'date',
        'sudah_punya_kaos' => 'boolean',
        'sudah_punya_kemeja' => 'boolean',
    ];

    /*
    |--------------------------------------------------------------------------
    | ACCESSOR FOTO
    |--------------------------------------------------------------------------
    */

    public function getFotoUrlAttribute()
    {
        if (!$this->foto) {
            return asset('assets/no-photo.png');
        }
        return asset('storage/' . $this->foto);
    }

    /*
    |--------------------------------------------------------------------------
    | RELATIONS
    |--------------------------------------------------------------------------
    */

    public function jabatanStruktur()
    {
        return $this->hasMany(StrukturOrganisasi::class, 'anggota_id');
    }

    /*
    |--------------------------------------------------------------------------
    | SCOPE
    |--------------------------------------------------------------------------
    */

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    /*
    |--------------------------------------------------------------------------
    | BOOTED
    |--------------------------------------------------------------------------
    */

    protected static function booted()
    {
        static::creating(function ($anggota) {
            if (!$anggota->ulid) {
                $anggota->ulid = (string) Str::ulid();
            }
        });

        static::saved(function ($anggota) {
            // Hanya sinkron jika anggota aktif
            if ($anggota->is_active) {
                $anggota->syncJabatanPuk();
            }
        });
    }

    public function getRouteKeyName()
    {
        return 'ulid';
    }

    /*
    |--------------------------------------------------------------------------
    | SYNC JABATAN PUK
    |--------------------------------------------------------------------------
    */

    public function syncJabatanPuk()
    {
        // Hanya untuk anggota aktif
        if (!$this->is_active) {
            return;
        }

        $jabatan = $this->jabatanStruktur()->with('parent')->get();

        if ($jabatan->isEmpty()) {
            $this->jabatan_puk = 'anggota';
        } else {
            $selected = $this->getHighestJabatan($jabatan);
            $this->jabatan_puk = $selected ? $selected->jabatan : 'anggota';
        }

        if ($this->isDirty('jabatan_puk')) {
            $this->saveQuietly();
        }
    }

    private function getHighestJabatan($jabatanCollection)
    {
        $minDepth = PHP_INT_MAX;
        $selected = null;

        foreach ($jabatanCollection as $jab) {
            $depth = $this->getDepth($jab);
            if ($depth < $minDepth) {
                $minDepth = $depth;
                $selected = $jab;
            }
        }

        return $selected;
    }

    private function getDepth($node, $depth = 0)
    {
        if ($node->parent_id === null) {
            return $depth;
        }
        $parent = $node->parent;
        if ($parent) {
            return $this->getDepth($parent, $depth + 1);
        }
        return $depth;
    }

    /*
    |--------------------------------------------------------------------------
    | ACCESSOR NAMA WILAYAH DOMISILI
    |--------------------------------------------------------------------------
    */

    public function getProvinsiDomisiliNamaAttribute(): ?string
    {
        return $this->provinsi_domisili
            ? Province::find($this->provinsi_domisili)?->name
            : null;
    }

    public function getKotaDomisiliNamaAttribute(): ?string
    {
        return $this->kota_domisili
            ? City::find($this->kota_domisili)?->name
            : null;
    }

    public function getKecamatanDomisiliNamaAttribute(): ?string
    {
        return $this->kecamatan_domisili
            ? District::find($this->kecamatan_domisili)?->name
            : null;
    }

    public function getKelurahanDomisiliNamaAttribute(): ?string
    {
        return $this->kelurahan_domisili
            ? Village::find($this->kelurahan_domisili)?->name
            : null;
    }

    /*
    |--------------------------------------------------------------------------
    | ACCESSOR NAMA WILAYAH KTP
    |--------------------------------------------------------------------------
    */

    public function getProvinsiKtpNamaAttribute(): ?string
    {
        return $this->provinsi_ktp
            ? Province::find($this->provinsi_ktp)?->name
            : null;
    }

    public function getKotaKtpNamaAttribute(): ?string
    {
        return $this->kota_ktp
            ? City::find($this->kota_ktp)?->name
            : null;
    }

    public function getKecamatanKtpNamaAttribute(): ?string
    {
        return $this->kecamatan_ktp
            ? District::find($this->kecamatan_ktp)?->name
            : null;
    }

    public function getKelurahanKtpNamaAttribute(): ?string
    {
        return $this->kelurahan_ktp
            ? Village::find($this->kelurahan_ktp)?->name
            : null;
    }
}