<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class KegiatanCustomField extends Model
{
    protected $table = 'kegiatan_custom_field';

    protected $fillable = [
        'kegiatan_id',
        'label',
        'tipe',
        'opsi',
        'wajib',
        'urutan',
    ];

    protected $casts = [
        'opsi' => 'array',
        'wajib' => 'boolean',
        'urutan' => 'integer',
    ];

    public function kegiatan(): BelongsTo
    {
        return $this->belongsTo(Kegiatan::class);
    }
}