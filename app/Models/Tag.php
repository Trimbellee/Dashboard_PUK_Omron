<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Tag extends Model
{
    protected $fillable = ['name', 'slug'];

    public function news()
    {
        return $this->belongsToMany(News::class, 'news_tag');
    }

    public static function getPopularTags($limit = 10)
    {
        return self::withCount('news')
                   ->orderBy('news_count', 'desc')
                   ->limit($limit)
                   ->get();
    }
}