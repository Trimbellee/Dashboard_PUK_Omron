<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class TotalKaryawan extends Model
{
    protected $table = 'total_karyawan';

    protected $fillable = [
        'bulan',
        'tahun',
        'total_karyawan',
    ];
}