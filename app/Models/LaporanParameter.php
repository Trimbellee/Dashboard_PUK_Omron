<?php
// app/Models/LaporanParameter.php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class LaporanParameter extends Model
{
    protected $table = 'laporan_parameter';
    protected $fillable = ['kode', 'nama', 'nilai'];
    protected $casts = ['nilai' => 'float'];
}