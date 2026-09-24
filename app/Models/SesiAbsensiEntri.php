<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SesiAbsensiEntri extends Model
{
    protected $table = 'sesi_absensi_entri';

    protected $fillable = [
        'sesi_absensi_id', 'anggota_id', 'data', 'metode',
    ];

    protected $casts = [
        'data' => 'array',
    ];

    public function sesiAbsensi(): BelongsTo
    {
        return $this->belongsTo(SesiAbsensi::class);
    }

    public function anggota(): BelongsTo
    {
        return $this->belongsTo(Anggota::class);
    }
}