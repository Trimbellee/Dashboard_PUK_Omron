<?php

namespace App\Http\Controllers\Public;

use App\Http\Controllers\Controller;
use App\Models\Anggota;
use App\Models\AgendaEvent;
use App\Models\InventarisBarang;
use App\Models\Gallery;
use App\Models\News;
use App\Models\OrganizationProfile;
use App\Models\Program;
use App\Models\SocialMedia;
use App\Services\LaporanKeuanganService;
use Carbon\Carbon;
use Illuminate\Http\Request;

class LandingPageController extends Controller
{
    public function __construct(private LaporanKeuanganService $laporanKeuangan)
    {
    }

    protected array $urutanJabatan = [
        'Ketua', 'Sekretaris', 'Bendahara',
        'Bidang 1', 'Bidang 2', 'Bidang 3', 'Bidang 4', 'Bidang 5',
        'Tim Media',
    ];

     public function index()
    {
        $profile = OrganizationProfile::first();

        $pengurus = Anggota::whereIn('jabatan_puk', $this->urutanJabatan)
            ->get()
            ->sortBy(fn ($orang) => array_search($orang->jabatan_puk, $this->urutanJabatan))
            ->values();

        $programKerja = Program::published()
            ->latest('published_at')
            ->take(6)
            ->get();

        $totalProgram = Program::published()->count();
        $programSelesai = Program::published()->where('status_program', 'selesai')->count();
        $realisasiProgram = $totalProgram > 0
            ? round(($programSelesai / $totalProgram) * 100)
            : 0;

        $beritaTerbaru = News::published()
            ->latest('published_at')
            ->take(6)
            ->get();

        $galeriTerbaru = Gallery::published()
        ->latest('published_at')
        ->take(6)
        ->get();

        // Ambil semua media sosial dari database
        $socialMedias = SocialMedia::orderBy('platform')->get();

        // ★ Data grafik keuangan — HANYA dihitung kalau anggota sedang login,
        //   supaya query keuangan tidak ikut jalan untuk pengunjung biasa.
        $tahunTersediaKeuangan = [];
        $tahunAktifKeuangan    = null;
        $debitKreditAwal       = null;

        if (session()->has('anggota')) {
            $tahunTersediaKeuangan = $this->laporanKeuangan->tahunTersedia();
            $tahunAktifKeuangan    = $tahunTersediaKeuangan[0] ?? now()->year;
            $debitKreditAwal       = $this->laporanKeuangan->getDebitKreditBulanan($tahunAktifKeuangan);
        }

        return view('landing-page.index', [
            'totalAnggota' => Anggota::where('is_active', 1)->count(),
            'periodeKepengurusan' => $profile?->periode_kepengurusan,
            'sejarahSingkat' => $profile?->sejarah_singkat,
            'visi' => $profile?->visi,
            'misi' => $profile?->misi,
            'alamatSekretariat' => $profile?->alamat_sekretariat,
            'pengurus' => $pengurus,
            'programKerja' => $programKerja,
            'totalProgram' => $totalProgram,
            'realisasiProgram' => $realisasiProgram,
            'beritaTerbaru' => $beritaTerbaru,
            'galeriTerbaru' => $galeriTerbaru,
            'socialMedias' => $socialMedias,
            'tahunTersediaKeuangan' => $tahunTersediaKeuangan,
            'tahunAktifKeuangan' => $tahunAktifKeuangan,
            'debitKreditAwal' => $debitKreditAwal,
        ]);
    }

    /**
     * Data agenda untuk landing page — READ ONLY, khusus anggota login.
     * Tidak mengirim field can_manage / recipients / reminder_offset_days
     * karena halaman ini bukan tempat kelola agenda.
     */
    public function agendaData(Request $request)
    {
        if (! session()->has('anggota')) {
            abort(403);
        }

        $month = $request->query('month', now()->format('Y-m'));
        $start = Carbon::parse($month . '-01')->startOfMonth()->subDays(7);
        $end   = Carbon::parse($month . '-01')->endOfMonth()->addDays(7);

        $events = AgendaEvent::query()
            ->where(function ($q) use ($start, $end) {
                $q->whereBetween('start_date', [$start, $end])
                  ->orWhereBetween('end_date', [$start, $end]);
            })
            ->orderBy('start_date')
            ->get()
            ->map(function (AgendaEvent $e) {
                return [
                    'id'          => $e->id,
                    'title'       => $e->title,
                    'description' => $e->description,
                    'start_date'  => $e->start_date->format('Y-m-d'),
                    'end_date'    => $e->end_date->format('Y-m-d'),
                    'location'    => $e->location,
                    'start_time'  => $e->start_time ? $e->start_time->format('H:i') : null,
                    'end_time'    => $e->end_time ? $e->end_time->format('H:i') : null,
                    'category'    => $e->category,
                    'bidang'      => $e->bidang,
                ];
            });

        return response()->json($events);
    }

    public function inventarisData(Request $request)
    {
        if (! session()->has('anggota')) {
            abort(403);
        }

        $perPage = 10;
        $search  = trim((string) $request->query('search', ''));

        $query = InventarisBarang::query();

        if ($search !== '') {
            $query->where(function ($q) use ($search) {
                $q->where('kode_barang', 'like', "%{$search}%")
                ->orWhere('nama_barang', 'like', "%{$search}%")
                ->orWhere('type', 'like', "%{$search}%")
                ->orWhere('kategori', 'like', "%{$search}%");
            });
        }

        $paginator = $query
            ->orderBy('tanggal_pembelian', 'asc')
            ->paginate($perPage)
            ->withQueryString();

        return response()->json([
            'data' => $paginator->getCollection()->map(function (InventarisBarang $item) {
                return [
                    'id'                => $item->id,
                    'kode_barang'       => $item->kode_barang,
                    'nama_barang'       => $item->nama_barang,
                    'type'              => $item->type,
                    'kategori'          => $item->kategori,
                    'kondisi'           => $item->kondisi,
                    'status'            => $item->status,
                    'tanggal_pembelian' => optional($item->tanggal_pembelian)->format('d F Y'),
                    'harga_perolehan'   => $item->harga_perolehan,
                    'keterangan'        => $item->keterangan,
                ];
            }),
            'meta' => [
                'current_page' => $paginator->currentPage(),
                'last_page'    => $paginator->lastPage(),
                'per_page'     => $paginator->perPage(),
                'total'        => $paginator->total(),
                'from'         => $paginator->firstItem(),
                'to'           => $paginator->lastItem(),
            ],
        ]);
    }
}