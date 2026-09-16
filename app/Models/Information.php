<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class Information extends Model
{
    protected $table = 'informations';

    protected $fillable = [
        'judul', 'slug', 'kategori', 'konten', 'status', 'urutan',
    ];

    // Daftar kategori tetap — dipakai di form admin & badge
    public const KATEGORI_LIST = [
        'kebijakan'      => 'Kebijakan',
        'pedoman-siber'  => 'Pedoman Siber',
        'iklan'          => 'Info Iklan',
        'legal'          => 'Legal & Privasi',
        'disclaimer'     => 'Disclaimer',
        'kode etik'      => 'Kode Etik',
        'lainnya'        => 'Lainnya',
    ];

    public const STATUS_LIST = [
        'draft'     => 'Draft',
        'published' => 'Published',
    ];

    public function scopePublished($query)
    {
        return $query->where('status', 'published');
    }
}