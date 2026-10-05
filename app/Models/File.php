<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class File extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'name',
        'type',
        'path',
        'parent_id',
        'thumbnail',
        'size',
    ];

    // relasi parent
    public function parent()
    {
        return $this->belongsTo(File::class, 'parent_id');
    }

    // relasi child (isi folder)
    public function children()
    {
        return $this->hasMany(File::class, 'parent_id');
    }
}
