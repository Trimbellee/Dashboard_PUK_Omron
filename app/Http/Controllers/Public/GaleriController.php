<?php

namespace App\Http\Controllers\Public;

use App\Http\Controllers\Controller;
use App\Models\Gallery;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class GaleriController extends Controller
{
    public function index(Request $request): View
    {
        $search   = $request->string('q')->trim()->toString();
        $type     = $request->string('type')->toString();
        $kategori = $request->string('kategori')->toString();
        $kegiatan = $request->string('kegiatan')->trim()->toString();
        $sort     = $request->string('sort')->toString() === 'terlama' ? 'terlama' : 'terbaru';

        $query = Gallery::published();

        if ($search !== '') {
            $query->where(function ($q) use ($search) {
                $q->where('judul', 'like', "%{$search}%")
                  ->orWhere('kegiatan', 'like', "%{$search}%");
            });
        }

        if (in_array($type, ['image', 'video'], true)) {
            $query->where('type', $type);
        } else {
            $type = '';
        }

        if (array_key_exists($kategori, Gallery::KATEGORI_LIST)) {
            $query->where('kategori', $kategori);
        } else {
            $kategori = '';
        }

        if ($kegiatan !== '') {
            $query->where('kegiatan', $kegiatan);
        }

        if ($sort === 'terlama') {
            $query->oldest('published_at')->orderBy('id');
        } else {
            $query->latest('published_at')->orderByDesc('id');
        }

        // "media" tidak ikut di link pagination supaya lightbox tidak terbuka ulang
        $galeriList = $query->paginate(12)->appends($request->except(['page', 'media']));

        // Album = kegiatan yang sama dikelompokkan (15 terbaru)
        $albums = Gallery::published()
            ->whereNotNull('kegiatan')
            ->where('kegiatan', '!=', '')
            ->select('kegiatan', DB::raw('COUNT(*) as total'), DB::raw('MAX(published_at) as terakhir'))
            ->groupBy('kegiatan')
            ->orderByDesc('terakhir')
            ->limit(15)
            ->get();

        // Deep-link ?media=ID (media boleh berada di halaman lain)
        $focusId    = $request->integer('media');
        $focusMedia = $focusId ? Gallery::published()->find($focusId) : null;

        return view('landing-page.galeri.index', [
            'galeriList' => $galeriList,
            'albums'     => $albums,
            'focusMedia' => $focusMedia,
            'filters'    => [
                'q'        => $search,
                'type'     => $type,
                'kategori' => $kategori,
                'kegiatan' => $kegiatan,
                'sort'     => $sort,
            ],
        ]);
    }
}