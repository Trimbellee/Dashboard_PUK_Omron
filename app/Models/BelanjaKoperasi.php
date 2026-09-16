<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class BelanjaKoperasi extends Model
{
    use HasFactory;

    protected $table = 'belanja_koperasi';

    protected $fillable = [
        'bulan', 'tahun', 'tanggal', 'bidang', 'tujuan', 'keperluan', 'biaya', 'timestamp'
    ];

    public $timestamps = false; // kalau kolom timestamp sudah manual
}
