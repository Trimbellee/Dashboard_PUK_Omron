<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class SesiAbsensi extends Model
{
    use HasFactory;

    protected $table = 'sesi_absensi';

    protected $fillable = [
        'judul',
        'lokasi',
        'kolom_ditampilkan',
        'status',
        'dibuat_oleh',
        'ditutup_at',
    ];

    protected $casts = [
        'kolom_ditampilkan' => 'array',
        'ditutup_at' => 'datetime',
    ];

    public function entri(): HasMany
    {
        return $this->hasMany(SesiAbsensiEntri::class);
    }

    public function isBuka(): bool
    {
        return $this->status === 'buka';
    }
}