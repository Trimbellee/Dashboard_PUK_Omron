<?php

namespace App\Services;

use App\Models\Pengajuan;
use App\Models\LaporanBendahara;
use App\Models\BelanjaKoperasi;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

/**
 * Sumber tunggal untuk logic laporan keuangan (Debit/Kredit, Breakdown per Jenis
 * Dana, daftar tahun tersedia). Diekstrak dari DashboardController supaya bisa
 * dipakai ulang oleh LandingKeuanganController (grafik keuangan di landing page,
 * khusus anggota login) tanpa duplikasi query.
 *
 * PRINSIP: hanya laporan bendahara berstatus "Selesai" yang dihitung di sini —
 * lihat catatan ★ di masing-masing method.
 */
class LaporanKeuanganService
{
    /**
     * Daftar tahun yang punya data (dari Pengajuan atau LaporanBendahara),
     * terbaru duluan.
     */
    public function tahunTersedia(): array
    {
        $tahun = Pengajuan::whereNotNull('tanggal')
            ->selectRaw('YEAR(tanggal) as tahun')->distinct()->pluck('tahun')
            ->merge(LaporanBendahara::distinct()->pluck('tahun'))
            ->unique()->sortDesc()->values()->map(fn($v) => (int)$v)->toArray();
        return count($tahun) ? $tahun : [now()->year];
    }

    /*
    |--------------------------------------------------------------------------
    | DEBIT / KREDIT PER JENIS DANA (Grafik #2 dashboard + grafik keuangan landing)
    | ★ Hanya laporan bendahara berstatus "Selesai" yang dihitung.
    |--------------------------------------------------------------------------
    */
    private const JENIS_KODE = [
        'Dana Operasional'     => 'dana_operasional',
        'Dana Perjuangan'      => 'dana_perjuangan',
        'Dana Sosial'          => 'dana_sosial',
        'Dana Permusyawaratan' => 'dana_permusyawaratan',
        'Dana Sosial Politik'  => 'dana_sosial_politik',
        'Bunga Giro'           => 'bunga_giro',
        'Bunga Deposito'       => 'bunga_deposito',
        'COS DPP'              => 'cos_dpp',
    ];

    /** Baca nilai dari json laporan: coba kode dulu, lalu nama lama (data lama belum termigrasi). */
    private function nilaiJenis(?array $json, string $namaLabel): int
    {
        if (!$json) return 0;
        $kode = self::JENIS_KODE[$namaLabel] ?? null;
        return (int) ($json[$kode] ?? $json[$namaLabel] ?? 0);
    }

    public function getDebitKreditBulanan(int $tahun): array
    {
        $bulanLabel = ['Jan','Feb','Mar','Apr','Mei','Jun','Jul','Agu','Sep','Okt','Nov','Des'];

        $jenisKas = [
            'Dana Operasional','Dana Perjuangan','Dana Sosial',
            'Dana Permusyawaratan','Dana Sosial Politik',
            'Bunga Giro','Bunga Deposito','COS DPP',
        ];

        $laporanPerBulan = LaporanBendahara::where('tahun', $tahun)
            ->where('status', 'Selesai')
            ->get()->keyBy('bulan');

        $bulanTerakhirLaporan = $laporanPerBulan->keys()->max() ?? 0;

        $labels = []; $debit = []; $kredit = [];
        $totalDebitPerJenis  = array_fill_keys($jenisKas, 0);
        $totalKreditPerJenis = array_fill_keys($jenisKas, 0);
        $totalSaldoPerJenis  = array_fill_keys($jenisKas, 0);

        $jenisPerBulan = [];
        foreach ($jenisKas as $j) {
            $jenisPerBulan[$j] = ['saldoAwal' => [], 'debit' => [], 'kredit' => [], 'total' => []];
        }

        $totalPerBulan = [];
        $batasLoop = $bulanTerakhirLaporan > 0 ? $bulanTerakhirLaporan : 12;

        for ($m = 1; $m <= $batasLoop; $m++) {
            $labels[] = $bulanLabel[$m - 1];
            $laporan = $laporanPerBulan->get($m);

            $debitBulan = 0; $kreditBulan = 0; $totalBulan = 0;

            foreach ($jenisKas as $j) {
                $sa = $this->nilaiJenis($laporan?->saldo_awal_json, $j);
                $d  = $this->nilaiJenis($laporan?->debit_json, $j);
                $k  = $this->nilaiJenis($laporan?->kredit_json, $j);
                $t  = $this->nilaiJenis($laporan?->total_json, $j);

                $debitBulan  += $d;
                $kreditBulan += $k;
                $totalBulan  += $t;

                $totalDebitPerJenis[$j]  += $d;
                $totalKreditPerJenis[$j] += $k;
                $totalSaldoPerJenis[$j]  += $t;

                $jenisPerBulan[$j]['saldoAwal'][] = $sa;
                $jenisPerBulan[$j]['debit'][]     = $d;
                $jenisPerBulan[$j]['kredit'][]    = $k;
                $jenisPerBulan[$j]['total'][]     = $t;
            }

            $debit[]         = $debitBulan;
            $kredit[]        = $kreditBulan;
            $totalPerBulan[] = $totalBulan;
        }

        return [
            'labels'               => $labels,
            'debit'                => $debit,
            'kredit'               => $kredit,
            'totalPerBulan'        => $totalPerBulan,
            'bulanTerakhirLaporan' => (int) $bulanTerakhirLaporan,
            'jenisLabels'          => $jenisKas,
            'jenisDebit'           => array_values($totalDebitPerJenis),
            'jenisKredit'          => array_values($totalKreditPerJenis),
            'jenisSaldo'           => array_values($totalSaldoPerJenis),
            'jenisPerBulan'        => $jenisPerBulan,
            'tahun'                => $tahun,
        ];
    }
    /*
    |--------------------------------------------------------------------------
    | DETAIL PER JENIS DANA (dipakai modal drill-down breakdown)
    | Sama persis dengan blok "$danaJenisKeys" di DashboardController::detailBidangApi
    | — query Pengajuan & BelanjaKoperasi jalan untuk $jenisKey APA PUN (bukan cuma
    | unit organisasi), ditambah baris Pajak Bank khusus utk Bunga Giro.
    | ★ Bagian LaporanBendahara (Pajak Bank) hanya menghitung status "Selesai".
    |--------------------------------------------------------------------------
    */
    public function getDetailJenisDana(string $jenisKey, int $tahun, $bulan = 'semua'): array
    {
        $jenisKey = strtoupper(trim($jenisKey));

        $rows = collect();

        $qPengajuan = Pengajuan::where('status', 'Disetujui')->whereYear('tanggal', $tahun)
            ->whereRaw('UPPER(TRIM(bidang)) = ?', [$jenisKey])
            ->select('nama', 'tujuan', 'keperluan', DB::raw('COALESCE(aktual_biaya,0) as realisasi'), 'tanggal', DB::raw("'Pengajuan' as sumber"));
        if ($bulan && $bulan !== 'semua') {
            $qPengajuan->whereMonth('tanggal', (int) $bulan);
        }
        $rows = $rows->merge($qPengajuan->get());

        $qKoperasi = BelanjaKoperasi::whereYear('tanggal', $tahun)->whereRaw('UPPER(TRIM(bidang)) = ?', [$jenisKey])
            ->select(DB::raw("'-' as nama"), DB::raw("'-' as tujuan"), 'keperluan', DB::raw('biaya as realisasi'), 'tanggal', DB::raw("'Belanja Koperasi' as sumber"));
        if ($bulan && $bulan !== 'semua') {
            $qKoperasi->whereMonth('tanggal', (int) $bulan);
        }
        $rows = $rows->merge($qKoperasi->get());

        $danaJenisKeys = [
            'DANA PERJUANGAN', 'DANA SOSIAL', 'DANA PERMUSYAWARATAN',
            'DANA SOSIAL POLITIK', 'BUNGA GIRO', 'BUNGA DEPOSITO',
        ];

        if (in_array($jenisKey, $danaJenisKeys, true)) {
            $laporanQuery = LaporanBendahara::where('tahun', $tahun)
                ->where('status', 'Selesai');
            if ($bulan && $bulan !== 'semua') {
                $laporanQuery->where('bulan', (int) $bulan);
            }
            $laporans = $laporanQuery->orderBy('bulan')->get();

            foreach ($laporans as $laporan) {
                $tgl        = Carbon::create($laporan->tahun, $laporan->bulan, 1)->format('Y-m-d');
                $bulanLabel = Carbon::create($laporan->tahun, $laporan->bulan, 1)->translatedFormat('F Y');

                /* Pajak bank — khusus Bunga Giro */
                if ($jenisKey === 'BUNGA GIRO') {
                    $pajakBank = (int) ($laporan->pajak_json['bunga_giro'] ?? $laporan->pajak_bank ?? 0);
                    if ($pajakBank > 0) {
                        $rows->push((object) [
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

        return $rows->sortBy('tanggal')->values()->toArray();
    }

    /*
    |--------------------------------------------------------------------------
    | REALISASI PER BIDANG (untuk breakdown "Dana Operasional")
    | Sama persis dengan porsi bidangPerBulan di DashboardController::
    | getTrenKeuanganBulanan — Pengajuan (aktual_biaya) + BelanjaKoperasi (biaya)
    | per bidang, ditambah insentif khusus SEKRETARIS. TIDAK memfilter status
    | LaporanBendahara karena datanya dari Pengajuan/BelanjaKoperasi, kecuali
    | bagian insentif yang tetap ambil dari laporan bulan terkait (atau
    | default kalau laporan belum ada, sama seperti dashboard).
    |--------------------------------------------------------------------------
    */
    public function getRealisasiPerBidang(int $tahun, $bulan = 'semua'): array
    {
        $bidangTetap = ['SEKRETARIS','BIDANG 1','BIDANG 2','BIDANG 3','BIDANG 4','BIDANG 5','BIDANG 6','GM','KORLAP'];
        $bidangDisplay = [
            'SEKRETARIS' => 'Sekretaris', 'BIDANG 1' => 'Bidang 1', 'BIDANG 2' => 'Bidang 2',
            'BIDANG 3' => 'Bidang 3', 'BIDANG 4' => 'Bidang 4', 'BIDANG 5' => 'Bidang 5',
            'BIDANG 6' => 'Bidang 6', 'GM' => 'GM', 'KORLAP' => 'Korlap',
        ];

        if ($bulan && $bulan !== 'semua') {
            $bulanRange = [(int) $bulan];
        } else {
            $currentYear  = now()->year;
            $currentMonth = now()->month;
            $maxMonth     = ($tahun === $currentYear) ? $currentMonth : 12;
            $bulanRange   = range(1, $maxMonth);
        }

        // Insentif Sekretaris — per bulan, fallback ke defaultInsentif() kalau laporan bulan itu belum ada
        $laporanPerBulan = LaporanBendahara::where('tahun', $tahun)->whereIn('bulan', $bulanRange)->get()->keyBy('bulan');
        $insentifTotal = 0;
        foreach ($bulanRange as $m) {
            $laporan = $laporanPerBulan->get($m);
            $items = $laporan?->insentif_json;
            if (empty($items)) {
                $items = $this->defaultInsentif();
            }
            $insentifTotal += collect($items)->sum(fn($i) => (int) ($i['nominal'] ?? 0));
        }

        $rows = [];
        foreach ($bidangTetap as $bidang) {
            $totalPengajuan = (int) Pengajuan::where('status', 'Disetujui')->whereYear('tanggal', $tahun)
                ->whereRaw('UPPER(TRIM(bidang)) = ?', [$bidang])
                ->whereIn(DB::raw('MONTH(tanggal)'), $bulanRange)
                ->sum(DB::raw('COALESCE(aktual_biaya,0)'));

            $totalKoperasi = (int) BelanjaKoperasi::whereYear('tanggal', $tahun)
                ->whereRaw('UPPER(TRIM(bidang)) = ?', [$bidang])
                ->whereIn(DB::raw('MONTH(tanggal)'), $bulanRange)
                ->sum('biaya');

            $total = $totalPengajuan + $totalKoperasi + ($bidang === 'SEKRETARIS' ? $insentifTotal : 0);

            $rows[] = ['bidang' => $bidangDisplay[$bidang] ?? $bidang, 'total' => $total];
        }

        return $rows;
    }

    /**
     * Sama persis dengan DashboardController::defaultInsentif() — dipakai
     * sebagai fallback nominal insentif kalau LaporanBendahara bulan tsb
     * belum diisi. Duplikasi kecil ini disengaja supaya service tidak perlu
     * bergantung ke DashboardController.
     */
    private function defaultInsentif(): array
    {
        return [
            ['keperluan' => 'Insentif Ketua', 'nominal' => 1150000],
            ['keperluan' => 'Insentif Sekretaris', 'nominal' => 950000],
            ['keperluan' => 'Insentif Bendahara', 'nominal' => 750000],
            ['keperluan' => 'Insentif Wakil Ketua Bidang 1', 'nominal' => 650000],
            ['keperluan' => 'Insentif Wakil Ketua Bidang 2', 'nominal' => 650000],
            ['keperluan' => 'Insentif Wakil Ketua Bidang 3', 'nominal' => 650000],
            ['keperluan' => 'Insentif Wakil Ketua Bidang 4', 'nominal' => 650000],
            ['keperluan' => 'Insentif Wakil Ketua Bidang 5', 'nominal' => 650000],
            ['keperluan' => 'Insentif Wakil Ketua Bidang 6', 'nominal' => 650000],
            ['keperluan' => 'Insentif Wakil Sekretaris Bidang 1', 'nominal' => 650000],
            ['keperluan' => 'Insentif Wakil Sekretaris Bidang 2', 'nominal' => 650000],
            ['keperluan' => 'Insentif Wakil Sekretaris Bidang 3', 'nominal' => 650000],
            ['keperluan' => 'Insentif Wakil Sekretaris Bidang 4', 'nominal' => 650000],
            ['keperluan' => 'Insentif Wakil Sekretaris Bidang 5', 'nominal' => 650000],
            ['keperluan' => 'Insentif Wakil Sekretaris Bidang 6', 'nominal' => 650000],
            ['keperluan' => 'Insentif PANGKORMI', 'nominal' => 400000],
            ['keperluan' => 'Insentif PANGKORLAP', 'nominal' => 400000],
            ['keperluan' => 'Insentif Pengurus Struktural Organisasi di atas PUK', 'nominal' => 500000],
        ];
    }
}