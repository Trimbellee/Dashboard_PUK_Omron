<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Concerns\HasUuids;

class Pengajuan extends Model
{
    use HasUuids;

    protected $table = 'pengajuan';
    protected $primaryKey = 'id';
    public $incrementing = false;
    protected $keyType = 'string';

    // App\Models\Pengajuan.php
    protected $fillable = [
        'id',
        'nama',
        'bidang',
        'tujuan',
        'keperluan',
        'biaya',
	'status_biaya',
        'lampiran_proposal',
        'lampiran_laporan',
        'status',
        'tanggal',
        'aktual_biaya',
        'dikembalikan',
        'approver',
        'catatan',
        'created_by'
    ];


    protected $casts = [
        'tanggal' => 'datetime'
    ];

    public function getKodeAttribute()
	{
	    return 'PGJ-' . $this->tanggal->format('Ymd') . '-' . substr($this->id, 0, 3);
	}
}

