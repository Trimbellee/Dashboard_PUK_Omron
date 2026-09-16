<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Program extends Model
{
    protected $table = 'programs';

    protected $fillable = [
        'judul', 'deskripsi', 'status_program', 'status', 'catatan_revisi',
        'created_by', 'reviewed_by_bidang5', 'approved_by_sekretaris', 'published_at',
    ];

    protected $casts = [
        'published_at' => 'datetime',
    ];

    public function scopePublished($query)
    {
        return $query->where('status', 'published');
    }

    public function creator()
    {
        return $this->belongsTo(Anggota::class, 'created_by');
    }
}