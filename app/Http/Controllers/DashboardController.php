<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Pengajuan;
use App\Models\LaporanBendahara;
use App\Models\BelanjaKoperasi;
use App\Services\LaporanKeuanganService;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class DashboardController extends Controller
{
    public function __construct(private LaporanKeuanganService $laporanKeuangan)
    {
    }

    public function index(Request $request)
    {
        $bulan = $request->query('bulan');
        $tahun = $request->query('tahun');

        if (!$request->has('bulan')) { $bulan = date('n'); }
        if (!$request->has('tahun')) { $tahun = date('Y'); }

        if ($bulan !== 'semua' && !is_numeric($bulan)) { $bulan = date('n'); }
        if ($tahun !== 'semua' && !is_numeric($tahun)) { $tahun = date('Y'); }
        if ($bulan === '') { $bulan = 'semua'; }

        $baseQuery = Pengajuan::query();
        if ($bulan !== 'semua') { $baseQuery->whereMonth('tanggal', $bulan); }
        if ($tahun !== 'semua') { $baseQuery->whereYear('tanggal', $tahun); }

        $totalPengajuan = (clone $baseQuery)->count();
        $menunggu       = (clone $baseQuery)->where('status', 'Menunggu')->count();
        $disetujui      = (clone $baseQuery)->where('status', 'Disetujui')->count();
        $ditolak        = (clone $baseQuery)->where('status', 'Ditolak')->count();

        $belomLapor = (clone $baseQuery)
            ->where('status', 'Disetujui')->whereNull('lampiran_laporan')->count();
        $aktualBiaya = (clone $baseQuery)
            ->where('status', 'Disetujui')->whereNotNull('aktual_biaya')->count();

        $totalRealisasi       = (clone $baseQuery)->where('status','Disetujui')->sum('aktual_biaya');
        $totalBelumDilaporkan = (clone $baseQuery)->where('status','Disetujui')
            ->selectRaw('SUM(biaya - COALESCE(aktual_biaya,0) - COALESCE(dikembalikan,0)) as sisa')->value('sisa');
        $totalBiaya      = (clone $baseQuery)->sum('biaya');
        $biayaMenunggu   = (clone $baseQuery)->where('status','Menunggu')->sum('biaya');
        $biayaDisetujui  = (clone $baseQuery)->where('status','Disetujui')->sum('biaya');
        $biayaDitolak    = (clone $baseQuery)->where('status','Ditolak')->sum('biaya');
        $persenRealisasi = $biayaDisetujui > 0 ? round(($totalRealisasi / $biayaDisetujui) * 100, 1) : 0;

        $prevQueryBase = Pengajuan::query();
        if ($bulan !== 'semua') {
            $prevMonth = $bulan - 1;
            $prevYear  = $tahun;
            if ($prevMonth <= 0) { $prevMonth = 12; $prevYear = $tahun - 1; }
            $prevQueryBase->whereMonth('tanggal', $prevMonth)->whereYear('tanggal', $prevYear);
        }

        $prevTotalNominal      = (clone $prevQueryBase)->sum('biaya');
        $prevApprovedNominal   = (clone $prevQueryBase)->where('status','Disetujui')->sum('biaya');
        $prevRejectedNominal   = (clone $prevQueryBase)->where('status','Ditolak')->sum('biaya');

        $growthTotalNominal    = $prevTotalNominal    > 0 ? round((($totalBiaya    - $prevTotalNominal)    / $prevTotalNominal)    * 100, 1) : 0;
        $growthApprovedNominal = $prevApprovedNominal > 0 ? round((($biayaDisetujui - $prevApprovedNominal) / $prevApprovedNominal) * 100, 1) : 0;
        $growthRejectedNominal = $prevRejectedNominal > 0 ? round((($biayaDitolak   - $prevRejectedNominal) / $prevRejectedNominal) * 100, 1) : 0;

        $rincian = (clone $baseQuery)
            ->where('status', 'Disetujui')->select(
                'bidang',
                DB::raw('SUM(biaya) as total_pengajuan'),
                DB::raw('SUM(CASE WHEN status="Disetujui" THEN biaya ELSE 0 END) as total_disetujui'),
                DB::raw('COALESCE(SUM(aktual_biaya),0) as total_aktual'),
                DB::raw('COALESCE(SUM(dikembalikan),0) as total_dikembalikan'),
                DB::raw('COUNT(CASE WHEN status="Disetujui" AND (aktual_biaya IS NULL OR aktual_biaya=0) AND (dikembalikan IS NULL OR dikembalikan=0) THEN 1 END) as kegiatan_belum_lapor')
            )
            ->groupBy('bidang')->get();

        foreach ($rincian as $row) {
            $row->belum_dilaporkan = ($row->total_disetujui ?? 0) - ($row->total_aktual ?? 0) - ($row->total_dikembalikan ?? 0);
        }

        $totalRow = [
            'total_pengajuan'    => $rincian->sum('total_pengajuan'),
            'total_aktual'       => $rincian->sum('total_aktual'),
            'total_dikembalikan' => $rincian->sum('total_dikembalikan'),
            'belum_dilaporkan'   => $rincian->sum('belum_dilaporkan'),
        ];

        // Ambil semua periode laporan bendahara yang ada
        $laporanPeriode = LaporanBendahara::selectRaw("CONCAT(tahun,'-',LPAD(bulan,2,'0')) as periode")
            ->pluck('periode')
            ->flip(); // jadi key untuk isset() check

        $detailBelumLapor = (clone $baseQuery)
            ->where('status', 'Disetujui')
            ->whereNull('aktual_biaya')
            ->whereNull('dikembalikan')
            ->get(['id', 'bidang', 'nama', 'keperluan', 'biaya', 'tanggal']); // ← pakai tanggal, bukan kolom yg tidak ada

        $belumLaporGrouped = $detailBelumLapor
            ->groupBy('bidang')
            ->map(function ($items) use ($laporanPeriode) {
                return $items->map(function ($item) use ($laporanPeriode) {
                    // Sama persis dengan PengajuanController — cek via periode
                    $periode       = substr($item->tanggal, 0, 7); // format: "2026-06"
                    $laporanLocked = isset($laporanPeriode[$periode]);

                    return [
                        'id'             => $item->id,
                        'nama'           => $item->nama,
                        'keperluan'      => $item->keperluan,
                        'biaya'          => $item->biaya,
                        'laporan_locked' => $laporanLocked,
                    ];
                })->values();
            });
        $tahunTersedia  = $this->tahunTersediaKeuangan();
        $tahunAktifTren = (int) ($tahun !== 'semua' ? $tahun : date('Y'));
        if (!in_array($tahunAktifTren, $tahunTersedia) && count($tahunTersedia)) {
            $tahunAktifTren = $tahunTersedia[0];
        }

        if ($request->ajax()) {
            return response()->json([
                'summary' => [
                    'totalPengajuan' => $totalPengajuan, 'menunggu' => $menunggu,
                    'disetujui' => $disetujui, 'ditolak' => $ditolak,
                    'belomLapor' => $belomLapor, 'aktualBiaya' => $aktualBiaya,
                    'totalBiaya' => $totalBiaya, 'biayaMenunggu' => $biayaMenunggu,
                    'biayaDisetujui' => $biayaDisetujui, 'biayaDitolak' => $biayaDitolak,
                    'totalRealisasi' => $totalRealisasi, 'totalBelumDilaporkan' => $totalBelumDilaporkan,
                ],
                'growth'           => ['total' => $growthTotalNominal, 'approved' => $growthApprovedNominal, 'rejected' => $growthRejectedNominal],
                'rincian'          => $rincian,
                'belumLaporGrouped'=> $belumLaporGrouped,
                'totalRow'         => $totalRow,
                'persenRealisasi'  => $persenRealisasi,
            ]);
        }

        return view('dashboard', compact(
            'bulan','tahun',
            'totalPengajuan','menunggu','disetujui','ditolak',
            'totalBiaya','biayaMenunggu','biayaDisetujui','biayaDitolak',
            'rincian','belumLaporGrouped','totalRealisasi',
            'totalBelumDilaporkan','totalRow','persenRealisasi','belomLapor','aktualBiaya',
            'tahunTersedia','tahunAktifTren',
        ));
    }

    private function defaultInsentif(): array
    {
        return [
            ['tujuan'=>'Sekretariat','keperluan'=>'Insentif Ketua','nominal'=>1150000],
            ['tujuan'=>'Sekretariat','keperluan'=>'Insentif Sekretaris','nominal'=>950000],
            ['tujuan'=>'Sekretariat','keperluan'=>'Insentif Bendahara','nominal'=>750000],
            ['tujuan'=>'Sekretariat','keperluan'=>'Insentif Wakil Ketua Bidang 1','nominal'=>650000],
            ['tujuan'=>'Sekretariat','keperluan'=>'Insentif Wakil Ketua Bidang 2','nominal'=>650000],
            ['tujuan'=>'Sekretariat','keperluan'=>'Insentif Wakil Ketua Bidang 3','nominal'=>650000],
            ['tujuan'=>'Sekretariat','keperluan'=>'Insentif Wakil Ketua Bidang 4','nominal'=>650000],
            ['tujuan'=>'Sekretariat','keperluan'=>'Insentif Wakil Ketua Bidang 5','nominal'=>650000],
            ['tujuan'=>'Sekretariat','keperluan'=>'Insentif Wakil Ketua Bidang 6','nominal'=>650000],
            ['tujuan'=>'Sekretariat','keperluan'=>'Insentif Wakil Sekretaris Bidang 1','nominal'=>650000],
            ['tujuan'=>'Sekretariat','keperluan'=>'Insentif Wakil Sekretaris Bidang 2','nominal'=>650000],
            ['tujuan'=>'Sekretariat','keperluan'=>'Insentif Wakil Sekretaris Bidang 3','nominal'=>650000],
            ['tujuan'=>'Sekretariat','keperluan'=>'Insentif Wakil Sekretaris Bidang 4','nominal'=>650000],
            ['tujuan'=>'Sekretariat','keperluan'=>'Insentif Wakil Sekretaris Bidang 5','nominal'=>650000],
            ['tujuan'=>'Sekretariat','keperluan'=>'Insentif Wakil Sekretaris Bidang 6','nominal'=>650000],
            ['tujuan'=>'Sekretariat','keperluan'=>'Insentif PANGKORMI','nominal'=>400000],
            ['tujuan'=>'Sekretariat','keperluan'=>'Insentif PANGKORLAP','nominal'=>400000],
            ['tujuan'=>'Sekretariat','keperluan'=>'Insentif Pengurus Struktural Organisasi di atas PUK','nominal'=>500000],
        ];
    }

    public function trenKeuanganApi(Request $request)
    {
        $tahun = (int) $request->get('tahun', now()->year);
        abort_if($tahun < 2000 || $tahun > 2100, 422, 'Tahun tidak valid');
        return response()->json($this->getTrenKeuanganBulanan($tahun));
    }

    private function getTrenKeuanganBulanan(int $tahun): array
    {
        $bulanLabel  = ['Jan','Feb','Mar','Apr','Mei','Jun','Jul','Agu','Sep','Okt','Nov','Des'];
        $bidangTetap = ['SEKRETARIS','BIDANG 1','BIDANG 2','BIDANG 3','BIDANG 4','BIDANG 5','BIDANG 6','GM','KORLAP'];

        $disetujuiRaw = Pengajuan::selectRaw('MONTH(tanggal) as bulan, SUM(biaya) as total')
            ->where('status','Disetujui')->whereYear('tanggal',$tahun)->groupBy('bulan')->pluck('total','bulan');
        $aktualRaw = Pengajuan::selectRaw('MONTH(tanggal) as bulan, SUM(aktual_biaya) as total')
            ->where('status','Disetujui')->whereYear('tanggal',$tahun)->whereNotNull('aktual_biaya')->groupBy('bulan')->pluck('total','bulan');

        $pengajuanBidangRaw = Pengajuan::selectRaw('MONTH(tanggal) as bulan, UPPER(TRIM(bidang)) as bidang_norm, SUM(COALESCE(aktual_biaya,0)) as total')
            ->where('status','Disetujui')->whereYear('tanggal',$tahun)
            ->whereIn(DB::raw('UPPER(TRIM(bidang))'),$bidangTetap)->groupBy('bulan','bidang_norm')->get()->groupBy('bidang_norm');

        $koperasiBidangRaw = BelanjaKoperasi::selectRaw('MONTH(tanggal) as bulan, UPPER(TRIM(bidang)) as bidang_norm, SUM(biaya) as total')
            ->whereYear('tanggal',$tahun)->whereIn(DB::raw('UPPER(TRIM(bidang))'),$bidangTetap)
            ->groupBy(DB::raw('MONTH(tanggal)'),DB::raw('UPPER(TRIM(bidang))'))->get()->groupBy('bidang_norm');

        $laporanPerBulan = LaporanBendahara::where('tahun',$tahun)->get()->keyBy('bulan');

        // ★ Khusus Dana Operasional & rasio: hanya laporan berstatus "Selesai" yang dipakai
        $laporanSelesaiPerBulan = LaporanBendahara::where('tahun',$tahun)
            ->where('status','Selesai')->get()->keyBy('bulan');

        $danaOperPerBulan = []; $insentifPerBulan = [];
        $currentYear = now()->year; $currentMonth = now()->month;
        $maxMonth = ($tahun === $currentYear) ? $currentMonth : 12;

        for ($m = 1; $m <= 12; $m++) {
            $laporan        = $laporanPerBulan->get($m);
            $laporanSelesai = $laporanSelesaiPerBulan->get($m);
            $danaOperPerBulan[$m] = (int) ($laporanSelesai?->debit_json['Dana Operasional'] ?? 0);
            if ($m > $maxMonth) { $insentifPerBulan[$m] = 0; continue; }
            $insentifItems = $laporan?->insentif_json;
            if (empty($insentifItems)) $insentifItems = $this->defaultInsentif();
            $insentifPerBulan[$m] = collect($insentifItems)->sum(fn($i) => (int)($i['nominal'] ?? 0));
        }

        // ★ Bulan terakhir laporan "Selesai" — dipakai untuk truncate/mask grafik Rasio
        $bulanTerakhirLaporan = $laporanSelesaiPerBulan->keys()->max() ?? 0;

        $labels = []; $disetujui = []; $aktual = [];
        for ($m = 1; $m <= 12; $m++) {
            $labels[] = $bulanLabel[$m-1]; $disetujui[] = (int)($disetujuiRaw[$m] ?? 0); $aktual[] = (int)($aktualRaw[$m] ?? 0);
        }

        $bidangPerBulan = []; $rasioPerBulan = [];
        foreach ($bidangTetap as $bidang) {
            $pengajuanRows = $pengajuanBidangRaw->get($bidang,collect())->pluck('total','bulan');
            $koperasiRows  = $koperasiBidangRaw->get($bidang,collect())->pluck('total','bulan');
            $nominalBulanan = []; $rasioBulanan = [];
            for ($m = 1; $m <= 12; $m++) {
                $nominal = (int)($pengajuanRows[$m] ?? 0) + (int)($koperasiRows[$m] ?? 0);
                if ($bidang === 'SEKRETARIS') $nominal += (int)($insentifPerBulan[$m] ?? 0);
                $nominalBulanan[] = $nominal;
                $danaOper = (int)($danaOperPerBulan[$m] ?? 0);
                $rasioBulanan[] = $danaOper > 0 ? round(($nominal/$danaOper)*100,1) : 0;
            }
            $bidangPerBulan[$bidang] = $nominalBulanan;
            $rasioPerBulan[$bidang]  = $rasioBulanan;
        }

        $totalPerBidang = [];
        foreach ($bidangTetap as $bidang) $totalPerBidang[$bidang] = array_sum($bidangPerBulan[$bidang]);

        return [
            'labels' => $labels, 'disetujui' => $disetujui, 'aktual' => $aktual,
            'bidangTetap' => $bidangTetap, 'bidangPerBulan' => $bidangPerBulan,
            'rasioPerBulan' => $rasioPerBulan, 'danaOperPerBulan' => array_values($danaOperPerBulan),
            'totalPerBidang' => $totalPerBidang,'bulanTerakhirLaporan' => (int) $bulanTerakhirLaporan, 'tahun' => $tahun,
        ];
    }

    /*
    |--------------------------------------------------------------------------
    | API — DEBIT / KREDIT PER JENIS DANA (Grafik #2)
    | ★ Menyertakan jenisPerBulan + totalPerBulan dari kolom total_json
    | ★ Hanya laporan bendahara berstatus "Selesai" yang dihitung
    |--------------------------------------------------------------------------
    */
    public function debitKreditApi(Request $request)
    {
        $tahun = (int) $request->get('tahun', now()->year);
        abort_if($tahun < 2000 || $tahun > 2100, 422, 'Tahun tidak valid');
        return response()->json($this->laporanKeuangan->getDebitKreditBulanan($tahun));
    }

    private function tahunTersediaKeuangan(): array
    {
        return $this->laporanKeuangan->tahunTersedia();
    }

    public function detailBidangApi(Request $request)
    {
        $bidang = strtoupper(trim($request->get('bidang', '')));
        $tahun  = (int) $request->get('tahun', now()->year);
        $bulan  = $request->get('bulan');
        abort_if(!$bidang || $tahun < 2000 || $tahun > 2100, 422, 'Parameter tidak valid');

        $rows = collect();

        $qPengajuan = Pengajuan::where('status','Disetujui')->whereYear('tanggal',$tahun)
            ->whereRaw('UPPER(TRIM(bidang)) = ?', [$bidang])
            ->select('nama','tujuan','keperluan',DB::raw('COALESCE(aktual_biaya,0) as realisasi'),'tanggal',DB::raw("'Pengajuan' as sumber"));
        if ($bulan && $bulan !== 'semua') $qPengajuan->whereMonth('tanggal',(int)$bulan);
        $rows = $rows->merge($qPengajuan->get());

        $qKoperasi = BelanjaKoperasi::whereYear('tanggal',$tahun)->whereRaw('UPPER(TRIM(bidang)) = ?',[$bidang])
            ->select(DB::raw("'-' as nama"),DB::raw("'-' as tujuan"),'keperluan',DB::raw('biaya as realisasi'),'tanggal',DB::raw("'Belanja Koperasi' as sumber"));
        if ($bulan && $bulan !== 'semua') $qKoperasi->whereMonth('tanggal',(int)$bulan);
        $rows = $rows->merge($qKoperasi->get());

        if ($bidang === 'SEKRETARIS') {
            $laporanQuery = LaporanBendahara::where('tahun',$tahun);
            if ($bulan && $bulan !== 'semua') $laporanQuery->where('bulan',(int)$bulan);
            $laporans = $laporanQuery->get();
            if ($laporans->isNotEmpty()) {
                foreach ($laporans as $laporan) {
                    $insentifItems = $laporan->insentif_json ?? $this->defaultInsentif();
                    $tgl = Carbon::create($laporan->tahun,$laporan->bulan,1)->format('Y-m-d');
                    foreach ($insentifItems as $item) {
                        $rows->push((object)['nama'=>$item['keperluan']??'-','tujuan'=>'-','keperluan'=>$item['keperluan']??'-','realisasi'=>(int)($item['nominal']??0),'tanggal'=>$tgl,'sumber'=>'Insentif']);
                    }
                }
            } else {
                $tglDefault = ($bulan && $bulan !== 'semua') ? Carbon::create($tahun,(int)$bulan,1)->format('Y-m-d') : Carbon::create($tahun,1,1)->format('Y-m-d');
                foreach ($this->defaultInsentif() as $item) {
                    $rows->push((object)['nama'=>$item['keperluan']??'-','tujuan'=>'-','keperluan'=>$item['keperluan']??'-','realisasi'=>(int)($item['nominal']??0),'tanggal'=>$tglDefault,'sumber'=>'Insentif']);
                }
            }
        }

        /*
        |--------------------------------------------------------------------------
        | Handler untuk jenis dana dari LaporanBendahara
        | ★ Hanya laporan berstatus "Selesai" — konsisten dengan breakdown per jenis
        |--------------------------------------------------------------------------
        */
        $danaJenisKeys = [
            'DANA PERJUANGAN', 'DANA SOSIAL', 'DANA PERMUSYAWARATAN',
            'DANA SOSIAL POLITIK', 'BUNGA GIRO', 'BUNGA DEPOSITO',
        ];
        
        $humanKeyMap = [
            'DANA PERJUANGAN'      => 'Dana Perjuangan',
            'DANA SOSIAL'          => 'Dana Sosial',
            'DANA PERMUSYAWARATAN' => 'Dana Permusyawaratan',
            'DANA SOSIAL POLITIK'  => 'Dana Sosial Politik',
            'BUNGA GIRO'           => 'Bunga Giro',
            'BUNGA DEPOSITO'       => 'Bunga Deposito',
        ];
        
        if (in_array($bidang, $danaJenisKeys)) {
            $humanKey = $humanKeyMap[$bidang] ?? $bidang;
        
            $laporanQuery = LaporanBendahara::where('tahun', $tahun)
                ->where('status', 'Selesai');
            if ($bulan && $bulan !== 'semua') $laporanQuery->where('bulan', (int)$bulan);
            $laporans = $laporanQuery->orderBy('bulan')->get();
        
            foreach ($laporans as $laporan) {
                $tgl = Carbon::create($laporan->tahun, $laporan->bulan, 1)->format('Y-m-d');
                $bulanLabel = Carbon::create($laporan->tahun, $laporan->bulan, 1)->translatedFormat('F Y');
    
        
                /* Pajak bank — khusus Bunga Giro */
                if ($bidang === 'BUNGA GIRO') {
                    $pajakBank = (int)($laporan->pajak_bank ?? 0);
                    if ($pajakBank > 0) {
                        $rows->push((object)[
                            'nama'      => 'Pajak Bank',
                            'tujuan'    => 'Bank',
                            'keperluan' => "Pajak Bank {$bulanLabel}",
                            'realisasi' => $pajakBank,
                            'tanggal'   => $tgl,
                            'sumber'    => 'Pajak Bank',
                        ]);
                    }
                }
            }
        
        }

        $rows = $rows->sortBy('tanggal')->values();
        return response()->json(['data' => $rows, 'total' => $rows->count()]);
    }
    
}