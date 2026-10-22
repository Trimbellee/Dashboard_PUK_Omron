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
        $kegiatan = $request->string('kegiatan')->trim()->toString();   // "__none" = media tanpa album
        $sort     = $request->string('sort')->toString() === 'terlama' ? 'terlama' : 'terbaru';
        $tampilan = $request->string('tampilan')->toString() === 'semua' ? 'semua' : '';

        if (!in_array($type, ['image', 'video'], true)) {
            $type = '';
        }
        if (!array_key_exists($kategori, Gallery::KATEGORI_LIST)) {
            $kategori = '';
        }

        // Deep-link ?media=ID (media boleh berada di halaman lain)
        $focusId    = $request->integer('media');
        $focusMedia = $focusId ? Gallery::published()->find($focusId) : null;

        // Mode tampilan:
        //  - album : daftar kartu album (default, tanpa filter apa pun)
        //  - foto  : grid media (saat membuka album, mencari/menyaring, atau memilih "Semua Media")
        $adaFilter = $search !== '' || $type !== '' || $kategori !== '' || $kegiatan !== '';
        $mode      = ($adaFilter || $tampilan === 'semua') ? 'foto' : 'album';

        // Tautan langsung ke satu media: buka album tempat media itu berada di belakang lightbox
        if ($mode === 'album' && $focusMedia) {
            $namaAlbum = trim((string) $focusMedia->kegiatan);
            $kegiatan  = $namaAlbum !== '' ? $namaAlbum : '__none';
            $mode      = 'foto';
        }

        // Statistik hero (tidak terpengaruh filter)
        $typeCounts = Gallery::published()
            ->select('type', DB::raw('COUNT(*) as total'))
            ->groupBy('type')
            ->pluck('total', 'type');

        $stats = [
            'total' => (int) $typeCounts->sum(),
            'image' => (int) ($typeCounts['image'] ?? 0),
            'video' => (int) ($typeCounts['video'] ?? 0),
            'album' => (int) Gallery::published()
                ->whereNotNull('kegiatan')
                ->where('kegiatan', '!=', '')
                ->distinct()
                ->count('kegiatan'),
        ];

        $data = [
            'mode'        => $mode,
            'stats'       => $stats,
            'tabCounts'   => [
                'total' => $stats['total'],
                'image' => $stats['image'],
                'video' => $stats['video'],
            ],
            'focusMedia'  => $focusMedia,
            'albums'      => null,
            'covers'      => [],
            'tanpaAlbum'  => null,
            'albumHeader' => null,
            'galeriList'  => null,
            'filters'     => [
                'q'        => $search,
                'type'     => $type,
                'kategori' => $kategori,
                'kegiatan' => $kegiatan,
                'sort'     => $sort,
                'tampilan' => $tampilan,
            ],
        ];

        /* ------------------------------------------------------------
         |  MODE ALBUM: daftar album (kegiatan yang sama dikelompokkan)
         ------------------------------------------------------------ */
        if ($mode === 'album') {
            $albumQuery = Gallery::published()
                ->whereNotNull('kegiatan')
                ->where('kegiatan', '!=', '')
                ->select(
                    'kegiatan',
                    DB::raw('COUNT(*) as total'),
                    DB::raw("SUM(type = 'image') as foto"),
                    DB::raw("SUM(type = 'video') as video"),
                    DB::raw('MAX(published_at) as terakhir'),
                    DB::raw('MIN(id) as cover_id')
                )
                ->groupBy('kegiatan');

            $sort === 'terlama'
                ? $albumQuery->orderBy('terakhir')
                : $albumQuery->orderByDesc('terakhir');

            $albums = $albumQuery->paginate(12)
                ->appends($request->except(['page', 'media']))
                ->fragment('hasil');

            $data['albums'] = $albums;
            $data['covers'] = Gallery::whereIn('id', $albums->pluck('cover_id'))
                ->pluck('thumbnail_url', 'id');

            // Media yang belum punya album tetap bisa dijangkau lewat kartu "Lainnya"
            $tanpa      = $this->scopeKegiatan(Gallery::published(), '__none');
            $totalTanpa = (clone $tanpa)->count();

            if ($totalTanpa > 0) {
                $data['tanpaAlbum'] = [
                    'total' => $totalTanpa,
                    'cover' => (clone $tanpa)->orderBy('id')->value('thumbnail_url'),
                ];
            }

            return view('landing-page.galeri.index', $data);
        }

        /* ------------------------------------------------------------
         |  MODE FOTO: grid media (dengan atau tanpa filter)
         ------------------------------------------------------------ */
        $query = Gallery::published();

        if ($search !== '') {
            $query->where(function ($q) use ($search) {
                $q->where('judul', 'like', "%{$search}%")
                  ->orWhere('kegiatan', 'like', "%{$search}%");
            });
        }

        if ($type !== '') {
            $query->where('type', $type);
        }

        if ($kategori !== '') {
            $query->where('kategori', $kategori);
        }

        if ($kegiatan !== '') {
            $this->scopeKegiatan($query, $kegiatan);

            // Kepala album + jumlah di tab tipe mengikuti isi album ini
            $agg = $this->scopeKegiatan(Gallery::published(), $kegiatan)
                ->selectRaw("COUNT(*) as total, SUM(type = 'image') as foto, SUM(type = 'video') as video, MAX(published_at) as terakhir")
                ->first();

            $data['albumHeader'] = [
                'foto'     => (int) ($agg->foto ?? 0),
                'video'    => (int) ($agg->video ?? 0),
                'terakhir' => $agg->terakhir ?? null,
            ];
            $data['tabCounts'] = [
                'total' => (int) ($agg->total ?? 0),
                'image' => (int) ($agg->foto ?? 0),
                'video' => (int) ($agg->video ?? 0),
            ];
        }

        if ($sort === 'terlama') {
            $query->oldest('published_at')->orderBy('id');
        } else {
            $query->latest('published_at')->orderByDesc('id');
        }

        // Di dalam album tampilkan lebih banyak per halaman agar navigasi lightbox (← →) lebih leluasa.
        // "media" tidak ikut di link pagination; fragment "#hasil" menjaga posisi scroll di area galeri.
        $data['galeriList'] = $query->paginate($kegiatan !== '' ? 24 : 12)
            ->appends($request->except(['page', 'media']))
            ->fragment('hasil');

        return view('landing-page.galeri.index', $data);
    }

    /** Batasi query ke satu album. "__none" = media yang belum punya kegiatan. */
    private function scopeKegiatan($query, string $kegiatan)
    {
        return $kegiatan === '__none'
            ? $query->where(fn ($w) => $w->whereNull('kegiatan')->orWhere('kegiatan', ''))
            : $query->where('kegiatan', $kegiatan);
    }
}