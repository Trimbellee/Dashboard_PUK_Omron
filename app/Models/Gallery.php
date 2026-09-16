<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Gallery extends Model
{
    protected $table = 'gallery';

    protected $fillable = [
        'judul', 'kegiatan', 'kategori', 'type', 'source', 'external_id',
        'thumbnail_url', 'embed_url', 'status', 'catatan_revisi',
        'created_by', 'reviewed_by_bidang5', 'approved_by_sekretaris', 'published_at',
    ];

    protected $casts = [
        'published_at' => 'datetime',
    ];

    // Daftar kategori tetap, dipakai di form admin & filter publik
    public const KATEGORI_LIST = [
        'rapat'      => 'Rapat',
        'pelatihan'  => 'Pelatihan',
        'sosial'     => 'Kegiatan Sosial',
        'perayaan'   => 'Perayaan',
	'aksi'	     => 'Aksi',
        'olahraga'   => 'Olahraga',
        'pendidikan' => 'Pendidikan', 
        'seminar'    => 'Seminar',
        'konsolidasi'=> 'Konsolidasi',
        'lainnya'    => 'Lainnya',
    ];

    public function scopePublished($query)
    {
        return $query->where('status', 'published');
    }

    // Aksesor untuk embed HTML (jika diperlukan di blade)
    public function getEmbedHtmlAttribute(): string
    {
        if ($this->source === 'youtube') {
            return '<iframe src="' . $this->embed_url . '" frameborder="0" allow="autoplay; encrypted-media" allowfullscreen></iframe>';
        }
        // Google Drive
        return '<iframe src="' . $this->embed_url . '" frameborder="0" allowfullscreen></iframe>';
    }

    // Tentukan apakah ini video
    public function isVideo(): bool
    {
        return $this->type === 'video';
    }

    // Tentukan apakah ini gambar
    public function isImage(): bool
    {
        return $this->type === 'image';
    }
}