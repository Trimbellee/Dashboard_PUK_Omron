<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class InsentifMaster extends Model
{
    protected $table = 'insentif_master';

    protected $fillable = ['tujuan', 'keperluan', 'nominal', 'urutan', 'aktif'];

    protected $casts = [
        'nominal' => 'integer',
        'urutan'  => 'integer',
        'aktif'   => 'boolean',
    ];
}