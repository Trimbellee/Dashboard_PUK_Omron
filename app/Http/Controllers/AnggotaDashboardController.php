<?php

namespace App\Http\Controllers;

use App\Models\Anggota;
use App\Models\TotalKaryawan;
use Illuminate\Http\Request;
use Carbon\Carbon;
use Yajra\DataTables\Facades\DataTables;

class AnggotaDashboardController extends Controller
{
    /*
    |--------------------------------------------------------------------------
    | HALAMAN UTAMA
    |--------------------------------------------------------------------------
    */
    public function index()
    {
        $tahunTersedia = $this->tahunTersedia();
        $tahunAktif    = now()->year;

        if (!in_array($tahunAktif, $tahunTersedia) && count($tahunTersedia)) {
            $tahunAktif = $tahunTersedia[0];
        }

        $summary        = $this->getSummary();
        $tren           = $this->getTrenBulanan($tahunAktif);
        $distribusi     = $this->getDistribusi();
        $departemen     = $this->getDepartemen();
        $masaKerja      = $this->getMasaKerja();
        $distribusiUmur = $this->getDistribusiUmur();

        return view('anggota.dashboard', compact(
            'summary', 'tren', 'distribusi', 'departemen',
            'masaKerja', 'distribusiUmur', 'tahunTersedia', 'tahunAktif'
        ));
    }

    /*
    |--------------------------------------------------------------------------
    | API — tren bulanan (AJAX ganti tahun)
    |--------------------------------------------------------------------------
    */
    public function trenApi(Request $request)
    {
        $tahun = (int) $request->get('tahun', now()->year);
        abort_if($tahun < 2000 || $tahun > 2100, 422, 'Tahun tidak valid');
        return response()->json($this->getTrenBulanan($tahun));
    }

    
    /*
    |--------------------------------------------------------------------------
    | RINGKASAN
    |--------------------------------------------------------------------------
    */
    private function getSummary(): array
    {
        $bulanIni = now()->month;
        $tahunIni = now()->year;

        $totalAktif     = Anggota::where('is_active', 1)->count();
        $totalHistoris  = Anggota::withTrashed()->count();

        $masukBulanIni = Anggota::whereYear('tanggal_masuk_anggota', $tahunIni)
            ->whereMonth('tanggal_masuk_anggota', $bulanIni)->count();

        $keluarBulanIni = Anggota::whereNotNull('tanggal_keluar_anggota')
            ->whereYear('tanggal_keluar_anggota', $tahunIni)
            ->whereMonth('tanggal_keluar_anggota', $bulanIni)->count();

        $rasioAktif = $totalHistoris > 0
            ? round(($totalAktif / $totalHistoris) * 100) : 0;

        $netChange = $masukBulanIni - $keluarBulanIni;

        return compact(
            'totalAktif', 'masukBulanIni', 'keluarBulanIni',
            'rasioAktif', 'netChange', 'totalHistoris'
        );
    }

    /*
    |--------------------------------------------------------------------------
    | TREN BULANAN
    | Mengembalikan: labels, aktif, masuk, keluar, totalKaryawan, rasio, tahun
    |--------------------------------------------------------------------------
    */
    private function getTrenBulanan(int $tahun): array
    {

        $bulanLabel = ['Jan','Feb','Mar','Apr','Mei','Jun',
                       'Jul','Agu','Sep','Okt','Nov','Des'];

        /* ── Masuk & keluar per bulan ─────────────────────────── */
        $masukRaw = Anggota::selectRaw('MONTH(tanggal_masuk_anggota) as bulan, COUNT(*) as total')
            ->whereYear('tanggal_masuk_anggota', $tahun)
            ->whereNotNull('tanggal_masuk_anggota')
            ->groupBy('bulan')
            ->pluck('total', 'bulan');

        $keluarRaw = Anggota::selectRaw('MONTH(tanggal_keluar_anggota) as bulan, COUNT(*) as total')
            ->whereYear('tanggal_keluar_anggota', $tahun)
            ->whereNotNull('tanggal_keluar_anggota')
            ->groupBy('bulan')
            ->pluck('total', 'bulan');

        /* ── Total karyawan dari tabel total_karyawan ─────────── */
        $totalKaryawanRaw = TotalKaryawan::where('tahun', $tahun)
            ->pluck('total_karyawan', 'bulan');

        /* ── Snapshot aktif per akhir bulan ──────────────────── */
        $aktifPerBulan = [];
        for ($m = 1; $m <= 12; $m++) {
            if ($tahun == now()->year && $m > now()->month) {
                $aktifPerBulan[$m] = null;
                continue;
            }
            $akhirBulan = Carbon::create($tahun, $m, 1)->endOfMonth();
            $aktifPerBulan[$m] = Anggota::where(function ($q) use ($akhirBulan) {
                    $q->whereNull('tanggal_masuk_anggota')
                      ->orWhere('tanggal_masuk_anggota', '<=', $akhirBulan);
                })
                ->where(function ($q) use ($akhirBulan) {
                    $q->whereNull('tanggal_keluar_anggota')
                      ->orWhere('tanggal_keluar_anggota', '>', $akhirBulan);
                })
                ->count();
        }

        /* ── Susun array per bulan ────────────────────────────── */
        $labels        = [];
        $aktif         = [];
        $masuk         = [];
        $keluar        = [];
        $totalKaryawan = [];
        $rasio         = [];

        for ($m = 1; $m <= 12; $m++) {
            $labels[] = $bulanLabel[$m - 1];
            $aktif[]  = $aktifPerBulan[$m];
            $masuk[]  = (int) ($masukRaw[$m]  ?? 0);
            $keluar[] = (int) ($keluarRaw[$m] ?? 0);

            $tk = isset($totalKaryawanRaw[$m]) ? (int) $totalKaryawanRaw[$m] : null;
            $totalKaryawan[] = $tk;

            // Rasio hanya jika keduanya tersedia dan tidak-null
            if ($aktifPerBulan[$m] !== null && $tk !== null && $tk > 0) {
                $rasio[] = round(($aktifPerBulan[$m] / $tk) * 100, 1);
            } else {
                $rasio[] = null;
            }
        }

        return compact('labels', 'aktif', 'masuk', 'keluar',
                       'totalKaryawan', 'rasio', 'tahun');
    }

    private function getDistribusi(): array
    {
        $gender = Anggota::where('is_active', 1)
            ->selectRaw('jenis_kelamin, COUNT(*) as total')
            ->groupBy('jenis_kelamin')
            ->orderBy('jenis_kelamin')
            ->pluck('total', 'jenis_kelamin')
            ->toArray();

        $status = Anggota::where('is_active', 1)
            ->selectRaw('status_karyawan, COUNT(*) as total')
            ->groupBy('status_karyawan')
            ->orderByDesc('total')
            ->pluck('total', 'status_karyawan')
            ->toArray();

        $pendidikan = Anggota::where('is_active', 1)
            ->whereNotNull('pendidikan_terakhir')
            ->selectRaw('pendidikan_terakhir, COUNT(*) as total')
            ->groupBy('pendidikan_terakhir')
            ->orderByDesc('total')
            ->pluck('total', 'pendidikan_terakhir')
            ->toArray();

        // Gender -> Status
        $genderDetail = Anggota::where('is_active', 1)
            ->selectRaw('jenis_kelamin, status_karyawan, COUNT(*) as total')
            ->groupBy('jenis_kelamin', 'status_karyawan')
            ->get()
            ->groupBy('jenis_kelamin')
            ->map(function ($items) {
                return $items->pluck('total', 'status_karyawan')->toArray();
            })
            ->toArray();

        // Status -> Gender
        $statusDetail = Anggota::where('is_active', 1)
            ->selectRaw('status_karyawan, jenis_kelamin, COUNT(*) as total')
            ->groupBy('status_karyawan', 'jenis_kelamin')
            ->get()
            ->groupBy('status_karyawan')
            ->map(function ($items) {
                return $items->pluck('total', 'jenis_kelamin')->toArray();
            })
            ->toArray();

        return compact(
            'gender',
            'status',
            'pendidikan',
            'genderDetail',
            'statusDetail'
        );
    }

    /*
    |--------------------------------------------------------------------------
    | TOP DEPARTEMEN
    |--------------------------------------------------------------------------
    */
    private function getDepartemen(int $limit = 10): array
    {
        $query = Anggota::where('is_active', 1)
            ->selectRaw('departemen, COUNT(*) as total')
            ->groupBy('departemen')
            ->orderByDesc('total');
    
        if ($limit > 0) {
            $query->limit($limit);
        }
    
        $rows = $query->get();
    
        return [

            'labels' => $rows->pluck('departemen')->toArray(),
            'data'   => $rows->pluck('total')->map(fn ($v) => (int) $v)->toArray(),
        ];
    }

    public function departemenApi(Request $request)
    {
        $all  = filter_var($request->get('all', false), FILTER_VALIDATE_BOOLEAN);
        $data = $this->getDepartemen($all ? 0 : 10);
    
        return response()->json($data);
    }

    /*
    |--------------------------------------------------------------------------
    | DISTRIBUSI MASA KERJA — bucket s/d > 25 tahun
    |--------------------------------------------------------------------------
    */
    private function getMasaKerja(): array
    {
        $rows = Anggota::where('is_active', 1)
            ->whereNotNull('tanggal_mulai_bekerja')
            ->selectRaw("
                TIMESTAMPDIFF(YEAR, tanggal_mulai_bekerja, CURDATE()) as tahun_kerja,
                CASE
                    WHEN LOWER(status_karyawan) IN ('permanen','permanent') THEN 'Permanen'
                    ELSE 'Kontrak'
                END as status_grup
            ")
            ->get();

        // ── Bucket 5 tahun (existing) ──────────────────────────────
        $bucketKeys5 = [
            '< 1 tahun', '1–2 tahun', '3–5 tahun', '6–10 tahun',
            '11–15 tahun', '16–20 tahun', '21–25 tahun', '> 26 tahun',
        ];
        $permanen5 = array_fill_keys($bucketKeys5, 0);
        $kontrak5  = array_fill_keys($bucketKeys5, 0);

        // ── Bucket 3 tahun: <1, 1–3, 4–6, 7–9, ... ───────────────
        $bucketKeys3 = ['< 1 tahun'];
        for ($i = 1; $i <= 28; $i += 3) {
            $bucketKeys3[] = "{$i}–" . ($i + 2) . ' tahun';
        }
        $permanen3 = array_fill_keys($bucketKeys3, 0);
        $kontrak3  = array_fill_keys($bucketKeys3, 0);

        // ── Bucket 2 tahun: <1, 1–2, 3–4, 5–6, ... ───────────────
        $bucketKeys2 = ['< 1 tahun'];
        for ($i = 1; $i <= 29; $i += 2) {
            $bucketKeys2[] = "{$i}–" . ($i + 1) . ' tahun';
        }
        $permanen2 = array_fill_keys($bucketKeys2, 0);
        $kontrak2  = array_fill_keys($bucketKeys2, 0);

        foreach ($rows as $row) {
            $y      = (int) $row->tahun_kerja;
            $isPerm = $row->status_grup === 'Permanen';

            // 5 tahun
            if      ($y < 1)  $b5 = '< 1 tahun';
            elseif  ($y < 3)  $b5 = '1–2 tahun';
            elseif  ($y < 6)  $b5 = '3–5 tahun';
            elseif  ($y < 11) $b5 = '6–10 tahun';
            elseif  ($y < 16) $b5 = '11–15 tahun';
            elseif  ($y < 21) $b5 = '16–20 tahun';
            elseif  ($y < 26) $b5 = '21–25 tahun';
            else              $b5 = '> 26 tahun';
            if ($isPerm) $permanen5[$b5]++; else $kontrak5[$b5]++;

            // 3 tahun
            if ($y < 1) {
                $b3 = '< 1 tahun';
            } else {
                $base = intdiv($y - 1, 3) * 3 + 1;
                $b3   = "{$base}–" . ($base + 2) . ' tahun';
                if (!isset($permanen3[$b3])) { $permanen3[$b3] = 0; $kontrak3[$b3] = 0; }
            }
            if ($isPerm) $permanen3[$b3]++; else $kontrak3[$b3]++;

            // 2 tahun
            if ($y < 1) {
                $b2 = '< 1 tahun';
            } else {
                $base = intdiv($y - 1, 2) * 2 + 1;
                $b2   = "{$base}–" . ($base + 1) . ' tahun';
                if (!isset($permanen2[$b2])) { $permanen2[$b2] = 0; $kontrak2[$b2] = 0; }
            }
            if ($isPerm) $permanen2[$b2]++; else $kontrak2[$b2]++;
        }

        return [
            // 5 tahun
            'labels'   => $bucketKeys5,
            'permanen' => array_values($permanen5),
            'kontrak'  => array_values($kontrak5),

            // 3 tahun
            'labels3'   => array_keys($permanen3),
            'permanen3' => array_values($permanen3),
            'kontrak3'  => array_values($kontrak3),

            // 2 tahun
            'labels2'   => array_keys($permanen2),
            'permanen2' => array_values($permanen2),
            'kontrak2'  => array_values($kontrak2),
        ];
    }

    /*
    |--------------------------------------------------------------------------
    | DISTRIBUSI UMUR
    |--------------------------------------------------------------------------
    */
    private function getDistribusiUmur(): array
    {
        $rows = Anggota::where('is_active', 1)
            ->whereNotNull('tanggal_lahir')
            ->selectRaw("
                TIMESTAMPDIFF(YEAR, tanggal_lahir, CURDATE()) as umur,
                CASE
                    WHEN LOWER(status_karyawan) IN ('permanen','permanent') THEN 'Permanen'
                    ELSE 'Kontrak'
                END as status_grup
            ")
            ->get();

        $totalDenganUmur = $rows->count();
        $totalAktif      = Anggota::where('is_active', 1)->count();

        // ── Bucket 5 tahun (existing) ──────────────────────────────
        $bucketKeys5 = [
            '< 20 tahun', '20–24', '25–29', '30–34',
            '35–39', '40–44', '45–49', '≥ 50 tahun',
        ];
        $permanen5 = array_fill_keys($bucketKeys5, 0);
        $kontrak5  = array_fill_keys($bucketKeys5, 0);

        // ── Bucket 2 tahun (18–19, 20–21, ..., 52–53, 54) ────────
        $bucketKeys2 = [];
        for ($a = 18; $a <= 52; $a += 2) {
            $bucketKeys2[] = "{$a}–" . ($a + 1);
        }
        $bucketKeys2[] = '54';
        $permanen2 = array_fill_keys($bucketKeys2, 0);
        $kontrak2  = array_fill_keys($bucketKeys2, 0);

        // ── Bucket 1 tahun (18, 19, ..., 54) ──────────────────────
        $bucketKeys1 = [];
        for ($a = 18; $a <= 54; $a++) {
            $bucketKeys1[] = (string) $a;
        }
        $permanen1 = array_fill_keys($bucketKeys1, 0);
        $kontrak1  = array_fill_keys($bucketKeys1, 0);

        $allUmur = [];

        foreach ($rows as $row) {
            $umur    = (int) $row->umur;
            $allUmur[] = $umur;
            $isPerm  = $row->status_grup === 'Permanen';

            // 5 tahun
            if      ($umur < 20) $b5 = '< 20 tahun';
            elseif  ($umur < 25) $b5 = '20–24';
            elseif  ($umur < 30) $b5 = '25–29';
            elseif  ($umur < 35) $b5 = '30–34';
            elseif  ($umur < 40) $b5 = '35–39';
            elseif  ($umur < 45) $b5 = '40–44';
            elseif  ($umur < 50) $b5 = '45–49';
            else                  $b5 = '≥ 50 tahun';
            if ($isPerm) $permanen5[$b5]++; else $kontrak5[$b5]++;

            // 2 tahun (hanya 18–54)
            if ($umur >= 18 && $umur <= 54) {
                if ($umur === 54) {
                    $b2 = '54';
                } else {
                    $base = $umur - (($umur - 18) % 2);
                    $b2   = "{$base}–" . ($base + 1);
                }
                if ($isPerm) $permanen2[$b2]++; else $kontrak2[$b2]++;
            }

            // 1 tahun (hanya 18–54)
            if ($umur >= 18 && $umur <= 54) {
                $b1 = (string) $umur;
                if ($isPerm) $permanen1[$b1]++; else $kontrak1[$b1]++;
            }
        }

        $allUmurCol = collect($allUmur);
        $rataRata   = $totalDenganUmur > 0 ? round($allUmurCol->avg()) : null;
        $median     = null;
        if ($totalDenganUmur > 0) {
            $sorted = $allUmurCol->sort()->values();
            $mid    = intdiv($totalDenganUmur, 2);
            $median = $totalDenganUmur % 2 === 0
                ? round(($sorted[$mid - 1] + $sorted[$mid]) / 2, 1)
                : $sorted[$mid];
        }

        // Helper closure untuk data total per bucket
        $makeTotal = fn($keys, $p, $k) => array_map(
            fn($key) => $p[$key] + $k[$key], $keys
        );

        return [
            // Mode 5 tahun (default)
            'labels'   => $bucketKeys5,
            'permanen' => array_values($permanen5),
            'kontrak'  => array_values($kontrak5),
            'data'     => $makeTotal($bucketKeys5, $permanen5, $kontrak5),

            // Mode 2 tahun
            'labels2'   => $bucketKeys2,
            'permanen2' => array_values($permanen2),
            'kontrak2'  => array_values($kontrak2),

            // Mode 1 tahun
            'labels1'   => $bucketKeys1,
            'permanen1' => array_values($permanen1),
            'kontrak1'  => array_values($kontrak1),

            'rataRata'       => $rataRata,
            'median'         => $median,
            'termuda'        => $totalDenganUmur > 0 ? min($allUmur) : null,
            'tertua'         => $totalDenganUmur > 0 ? max($allUmur) : null,
            'totalDiketahui' => $totalDenganUmur,
            'tidakDiketahui' => $totalAktif - $totalDenganUmur,
        ];
    }

    /*
    |--------------------------------------------------------------------------
    | HELPER — tahun tersedia
    |--------------------------------------------------------------------------
    */
    private function tahunTersedia(): array
    {
        $masuk = Anggota::whereNotNull('tanggal_masuk_anggota')
            ->selectRaw('YEAR(tanggal_masuk_anggota) as tahun')
            ->distinct()
            ->pluck('tahun');

        $keluar = Anggota::whereNotNull('tanggal_keluar_anggota')
            ->selectRaw('YEAR(tanggal_keluar_anggota) as tahun')
            ->distinct()
            ->pluck('tahun');

        $tahun = $masuk->merge($keluar)
            ->unique()
            ->sortDesc()
            ->values()
            ->map(fn ($v) => (int) $v)
            ->toArray();

        return count($tahun)
            ? $tahun
            : [now()->year];
    }

    
    public function detailApi(Request $request)
    {
        $type  = $request->type;
        $value = $request->value;
        $tahun = $request->tahun;
        $bulan = $request->bulan;
    
        $query = Anggota::query();
    
        switch ($type) {
    
            /*
            |----------------------------------------------------------------------
            | MASUK — tampilkan anggota yang tanggal_masuk_anggota sesuai
            | Keterangan selalu "Masuk"
            | 
            | Jika bulan = 0 atau null, tampilkan data untuk seluruh tahun
            |----------------------------------------------------------------------
            */
            case 'masuk':
                $query->whereYear('tanggal_masuk_anggota', $tahun);
                
                // Jika bulan > 0, filter berdasarkan bulan
                if ($bulan && $bulan > 0) {
                    $query->whereMonth('tanggal_masuk_anggota', $bulan);
                }
                
                $query->selectRaw("
                    id,
                    nik_karyawan,
                    nama_lengkap,
                    departemen,
                    status_karyawan,
                    tanggal_masuk_anggota,
                    tanggal_keluar_anggota,
                    'Masuk' AS keterangan
                ");
                break;
    
            /*
            |----------------------------------------------------------------------
            | KELUAR — tampilkan anggota yang tanggal_keluar_anggota sesuai
            | Keterangan selalu "Keluar"
            | 
            | Jika bulan = 0 atau null, tampilkan data untuk seluruh tahun
            |----------------------------------------------------------------------
            */
            case 'keluar':
                $query->whereYear('tanggal_keluar_anggota', $tahun);
                
                // Jika bulan > 0, filter berdasarkan bulan
                if ($bulan && $bulan > 0) {
                    $query->whereMonth('tanggal_keluar_anggota', $bulan);
                }
                
                $query->selectRaw("
                    id,
                    nik_karyawan,
                    nama_lengkap,
                    departemen,
                    status_karyawan,
                    tanggal_masuk_anggota,
                    tanggal_keluar_anggota,
                    'Keluar' AS keterangan
                ");
                break;
    
            /*
            |----------------------------------------------------------------------
            | UMUR
            |----------------------------------------------------------------------
            */
            case 'umur':
                $query->where('is_active', 1)
                    ->whereNotNull('tanggal_lahir')
                    ->selectRaw('
                        id, nik_karyawan, nama_lengkap, departemen, status_karyawan,
                        tanggal_lahir,
                        tanggal_masuk_anggota,
                        tanggal_keluar_anggota,
                        TIMESTAMPDIFF(YEAR, tanggal_lahir, CURDATE()) AS umur,
                        NULL AS keterangan
                    ');

                // ── 5 tahun (existing) ─────────────────────────────────────
                $map5 = [
                    '< 20 tahun' => 'TIMESTAMPDIFF(YEAR,tanggal_lahir,CURDATE()) < 20',
                    '20–24'      => 'TIMESTAMPDIFF(YEAR,tanggal_lahir,CURDATE()) BETWEEN 20 AND 24',
                    '25–29'      => 'TIMESTAMPDIFF(YEAR,tanggal_lahir,CURDATE()) BETWEEN 25 AND 29',
                    '30–34'      => 'TIMESTAMPDIFF(YEAR,tanggal_lahir,CURDATE()) BETWEEN 30 AND 34',
                    '35–39'      => 'TIMESTAMPDIFF(YEAR,tanggal_lahir,CURDATE()) BETWEEN 35 AND 39',
                    '40–44'      => 'TIMESTAMPDIFF(YEAR,tanggal_lahir,CURDATE()) BETWEEN 40 AND 44',
                    '45–49'      => 'TIMESTAMPDIFF(YEAR,tanggal_lahir,CURDATE()) BETWEEN 45 AND 49',
                    '≥ 50 tahun' => 'TIMESTAMPDIFF(YEAR,tanggal_lahir,CURDATE()) >= 50',
                ];

                if (isset($map5[$value])) {
                    $query->whereRaw($map5[$value]);
                    break;
                }

                // ── 2 tahun: "XX–YY" ───────────────────────────────────────
                if (preg_match('/^(\d+)–(\d+)$/', $value, $m)) {
                    $query->whereRaw(
                        'TIMESTAMPDIFF(YEAR,tanggal_lahir,CURDATE()) BETWEEN ? AND ?',
                        [(int)$m[1], (int)$m[2]]
                    );
                    break;
                }

                // ── 1 tahun: "XX" (angka murni) ────────────────────────────
                if (preg_match('/^\d+$/', $value)) {
                    $query->whereRaw(
                        'TIMESTAMPDIFF(YEAR,tanggal_lahir,CURDATE()) = ?',
                        [(int)$value]
                    );
                    break;
                }

                break;
            
            /*
            |----------------------------------------------------------------------
            | MASA KERJA
            |----------------------------------------------------------------------
            */
            case 'masa_kerja':
                $query->where('is_active', 1)
                    ->whereNotNull('tanggal_mulai_bekerja')
                    ->selectRaw('
                        id, nik_karyawan, nama_lengkap, departemen, status_karyawan,
                        tanggal_mulai_bekerja, tanggal_masuk_anggota, tanggal_keluar_anggota,
                        TIMESTAMPDIFF(YEAR, tanggal_mulai_bekerja, CURDATE()) AS tahun_kerja,
                        NULL AS keterangan
                    ');

                // ── Bucket 5 tahun (label existing) ───────────────────────
                $map5_masa = [
                    '< 1 tahun'   => 'TIMESTAMPDIFF(YEAR,tanggal_mulai_bekerja,CURDATE()) < 1',
                    '1–2 tahun'   => 'TIMESTAMPDIFF(YEAR,tanggal_mulai_bekerja,CURDATE()) BETWEEN 1 AND 2',
                    '3–5 tahun'   => 'TIMESTAMPDIFF(YEAR,tanggal_mulai_bekerja,CURDATE()) BETWEEN 3 AND 5',
                    '6–10 tahun'  => 'TIMESTAMPDIFF(YEAR,tanggal_mulai_bekerja,CURDATE()) BETWEEN 6 AND 10',
                    '11–15 tahun' => 'TIMESTAMPDIFF(YEAR,tanggal_mulai_bekerja,CURDATE()) BETWEEN 11 AND 15',
                    '16–20 tahun' => 'TIMESTAMPDIFF(YEAR,tanggal_mulai_bekerja,CURDATE()) BETWEEN 16 AND 20',
                    '21–25 tahun' => 'TIMESTAMPDIFF(YEAR,tanggal_mulai_bekerja,CURDATE()) BETWEEN 21 AND 25',
                    '> 26 tahun'  => 'TIMESTAMPDIFF(YEAR,tanggal_mulai_bekerja,CURDATE()) >= 26',
                ];

                if (isset($map5_masa[$value])) {
                    $query->whereRaw($map5_masa[$value]);
                    break;
                }

                // ── Bucket 3 atau 2 tahun: "X–Y tahun" ────────────────────
                if (preg_match('/^(\d+)–(\d+) tahun$/', $value, $m)) {
                    $query->whereRaw(
                        'TIMESTAMPDIFF(YEAR,tanggal_mulai_bekerja,CURDATE()) BETWEEN ? AND ?',
                        [(int)$m[1], (int)$m[2]]
                    );
                    break;
                }

                break;

            case 'departemen':
                $query->where('is_active', 1)
                    ->where('departemen', $value)
                    ->selectRaw("
                        id,
                        nik_karyawan,
                        nama_lengkap,
                        departemen,
                        status_karyawan,
                        tanggal_mulai_bekerja,
                        tanggal_masuk_anggota,
                        tanggal_keluar_anggota,
                        TIMESTAMPDIFF(YEAR, tanggal_mulai_bekerja, CURDATE()) AS tahun_kerja,
                        NULL AS keterangan
                    ");
                break;
    
            default:
                return response()->json(['data' => [], 'recordsTotal' => 0, 'recordsFiltered' => 0]);
        }
    
        return DataTables::eloquent($query)
            ->addIndexColumn()
            ->toJson();
    }
}