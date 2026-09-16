<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

// app/Models/KodeSurat.php
class KodeSurat extends Model
{
    protected $table = 'kode_surat';

    protected $fillable = [
        'nomor_urut',
        'no_surat',
        'jenis',
        'bidang',
        'bulan',
        'bulan_romawi',
        'tahun',
        'keterangan'
    ];
}

