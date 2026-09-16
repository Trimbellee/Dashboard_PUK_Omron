<?php

namespace App\Http\Controllers\Public;

use App\Http\Controllers\Controller;
use App\Models\Gallery;
use Illuminate\Http\Request;
use Illuminate\View\View;

class GaleriController extends Controller
{
    public function index(Request $request): View
    {
        $search = $request->string('q')->trim()->toString();
        $type = $request->string('type')->toString();
        $kategori = $request->string('kategori')->toString();

        $query = Gallery::published()->latest('published_at');

        if ($search !== '') {
            $query->where(function ($q) use ($search) {
                $q->where('judul', 'like', "%{$search}%")
                  ->orWhere('kegiatan', 'like', "%{$search}%");
            });
        }

        if (in_array($type, ['image', 'video'], true)) {
            $query->where('type', $type);
        }

        if (array_key_exists($kategori, Gallery::KATEGORI_LIST)) {
            $query->where('kategori', $kategori);
        }

        $galeriList = $query->paginate(12)->withQueryString();

        return view('landing-page.galeri.index', [
            'galeriList' => $galeriList,
            'filters'    => [
                'q'        => $search,
                'type'     => $type,
                'kategori' => $kategori,
            ],
        ]);
    }
}