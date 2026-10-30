<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PengambilanSeragam extends Model
{
    protected $table = 'pengambilan_seragam';

    protected $fillable = [
        'anggota_id',
        'jenis',
        'ukuran',
        'walau_sudah_punya',
        'metode',
        'dicatat_oleh',
        'dibatalkan_at',
        'dibatalkan_oleh',
    ];

    protected $casts = [
        'walau_sudah_punya' => 'boolean',
        'dibatalkan_at'     => 'datetime',
    ];

    public function anggota(): BelongsTo
    {
        return $this->belongsTo(Anggota::class);
    }

    /** Hanya pengambilan yang masih berlaku (belum dibatalkan). */
    public function scopeAktif($query)
    {
        return $query->whereNull('dibatalkan_at');
    }
}