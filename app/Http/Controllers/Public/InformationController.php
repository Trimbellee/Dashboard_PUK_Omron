<?php

namespace App\Http\Controllers\Public;

use App\Http\Controllers\Controller;
use App\Models\Information;
use App\Models\News;
use App\Models\Tag;
use Illuminate\View\View;

class InformationController extends Controller
{
    public function show(string $slug): View
    {
        $informasi = Information::published()->where('slug', $slug)->firstOrFail();

        // ===== SIDEBAR: reuse widget dari Berita =====
        $recentNews = News::published()
            ->latest('published_at')
            ->take(5)
            ->get();

        $popularNews = News::published()
            ->orderBy('views', 'desc')
            ->take(5)
            ->get();

        $categories = News::published()
            ->select('kategori')
            ->distinct()
            ->pluck('kategori');

        $popularTags = Tag::withCount('news')
            ->having('news_count', '>', 0)
            ->orderBy('news_count', 'desc')
            ->take(10)
            ->get();

        return view('landing-page.informasi.show', compact(
            'informasi',
            'recentNews',
            'popularNews',
            'categories',
            'popularTags'
        ));
    }
}