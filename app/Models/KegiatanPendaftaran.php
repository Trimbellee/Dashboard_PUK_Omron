<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class KegiatanPendaftaran extends Model
{
    protected $table = 'kegiatan_pendaftaran';

    protected $fillable = [
        'kegiatan_id',
        'anggota_id',
        'status',
        'custom_values',
    ];

    protected $casts = [
        'custom_values' => 'array',
    ];

    public function kegiatan(): BelongsTo
    {
        return $this->belongsTo(Kegiatan::class);
    }

    // Sesuaikan dengan model Anggota yang sudah ada di project.
    public function anggota(): BelongsTo
    {
        return $this->belongsTo(Anggota::class);
    }
}