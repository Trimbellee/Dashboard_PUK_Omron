<?php

namespace App\Http\Controllers\Public;

use App\Http\Controllers\Controller;
use App\Models\News;
use App\Models\Tag;
use Illuminate\Http\Request;

class BeritaController extends Controller
{
    public function index(Request $request)
    {
        $query = News::published()->latest('published_at');

        // Filter by kategori
        if ($request->filled('kategori')) {
            $query->where('kategori', $request->kategori);
        }

        // Filter by tag
        if ($request->filled('tag')) {
            $query->whereHas('tags', function ($q) use ($request) {
                $q->where('name', $request->tag);
            });
        }

        // Filter by author
        if ($request->filled('author')) {
            $query->where('created_by', $request->author);
        }

        $berita = $query->paginate(9)->withQueryString();

        // Data untuk sidebar
        $categories = News::published()->distinct()->pluck('kategori')->filter()->values();
        $popularTags = Tag::withCount('news')
            ->having('news_count', '>', 0)
            ->orderBy('news_count', 'desc')
            ->take(10)
            ->get();
        $popularNews = News::published()
            ->orderBy('views', 'desc')
            ->take(5)
            ->get();
        $featuredNews = News::published()
            ->where('is_featured', true)
            ->latest('published_at')
            ->take(3)
            ->get();

        // ===== TRENDING WIDGET (7 hari terakhir, urut berdasarkan views) =====
        $trendingNews = News::published()
            ->where('published_at', '>=', now()->subDays(7))
            ->orderBy('views', 'desc')
            ->take(5)
            ->get();

        // Untuk Breaking News Ticker (dipakai juga di layout global,
        // tapi tetap disiapkan di sini jika suatu saat dibutuhkan inline)
        $breakingNews = News::published()
            ->where('is_breaking', true)
            ->latest('published_at')
            ->take(5)
            ->get();

        // Untuk Hero Featured Story
        $headline = News::published()
            ->where('is_headline', true)
            ->first();
        $heroFeatured = News::published()
            ->where('is_featured', true)
            ->where('id', '!=', $headline?->id)
            ->latest('published_at')
            ->take(4)
            ->get();

        return view('landing-page.berita.index', compact(
            'berita',
            'categories',
            'popularTags',
            'popularNews',
            'featuredNews',
            'trendingNews',
            'breakingNews',
            'headline',
            'heroFeatured'
        ));
    }

    public function show(string $slug)
    {
        $berita = News::published()
            ->where('slug', $slug)
            ->firstOrFail();

        $berita->incrementViews();

        $beritaLain = News::published()
            ->where('id', '!=', $berita->id)
            ->latest('published_at')
            ->take(4)
            ->get();

        return view('landing-page.berita.show', compact('berita', 'beritaLain'));
    }
}