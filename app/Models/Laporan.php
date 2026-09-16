<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Laporan extends Model
{

    protected $table = 'laporan';

    protected $fillable = [
        'pengajuan_id',
        'nama_kegiatan',
        'tujuan',
        'tanggal',
        'jam',
        'tempat',
        'anggaran_aktual_json'
    ];

    public function pengajuan()
    {
        return $this->belongsTo(Pengajuan::class);
    }

    public function laporan()
    {
        return $this->hasOne(Laporan::class);
    }
}