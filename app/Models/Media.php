<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Media extends Model
{
    protected $fillable = [
        'name', 'url', 'thumbnail', 'size', 'mime_type', 'user_id'
    ];

    public function user()
    {
        return $this->belongsTo(Anggota::class, 'user_id');
    }

    public function getThumbnailAttribute($value)
    {
        return $value ?? $this->url;
    }
}