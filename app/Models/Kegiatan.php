<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Kegiatan extends Model
{
    use HasFactory;

    protected $table = 'kegiatan';

    protected $fillable = [
        'judul',
        'slug',
        'jenis',
        'tingkat_kategori',
        'tingkat_keterangan',
        'deskripsi',
        'tanggal_mulai',
        'tanggal_selesai',
        'batas_pendaftaran',
        'lokasi',
        'kuota',
        'status',
        'thumbnail',
        'created_by',
    ];

    protected $casts = [
        'tanggal_mulai' => 'date',
        'tanggal_selesai' => 'date',
        'batas_pendaftaran' => 'date',
        'kuota' => 'integer',
    ];

    public function getRouteKeyName(): string
    {
        return 'slug';
    }

    public function customFields(): HasMany
    {
        return $this->hasMany(KegiatanCustomField::class)->orderBy('urutan');
    }

    public function pendaftar(): HasMany
    {
        return $this->hasMany(KegiatanPendaftaran::class);
    }

    /**
     * Jumlah pendaftar yang dihitung memenuhi kuota (diterima atau sudah hadir).
     */
    public function getKuotaTerisiAttribute(): int
    {
        return $this->pendaftar()->whereIn('status', ['diterima', 'hadir'])->count();
    }

    public function getKuotaPenuhAttribute(): bool
    {
        return $this->kuota !== null && $this->kuota_terisi >= $this->kuota;
    }

    public function getSisaKuotaAttribute(): ?int
    {
        return $this->kuota === null ? null : max(0, $this->kuota - $this->kuota_terisi);
    }
}