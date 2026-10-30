<?php

namespace App\Http\Controllers\Public\Admin;

use App\Helpers\RfidHelper;
use App\Helpers\SeragamHelper;
use App\Http\Controllers\Controller;
use App\Models\Anggota;
use App\Models\PengambilanSeragam;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

/**
 * Pencatatan pengambilan kaos/kemeja di sekretariat PUK lewat scan kartu RFID / barcode / NIK.
 *
 * Aturan inti (anggota.jumlah_kaos / jumlah_kemeja = jumlah TOTAL yang dimiliki):
 *   - Belum punya (kosong / 0)  → langsung dicatat, jumlah menjadi 1.
 *   - Sudah punya (≥ 1)         → server minta konfirmasi dulu; kalau "tetap berikan", jumlah +1.
 *   - Ukuran belum terisi        → server minta ukuran dulu (disimpan juga ke data anggota).
 *   - Scan ulang kartu yang sama dalam beberapa menit → dianggap ketukan ganda, tidak dicatat lagi.
 *
 * Konfirmasi dua langkah: scan pertama TIDAK mencatat apa pun bila ada yang perlu dikonfirmasi.
 * Pencatatan baru terjadi pada kiriman kedua (membawa `konfirmasi`/`ukuran`), atomik untuk
 * semua jenis yang dipilih, di dalam transaksi dengan kunci baris anggota.
 */
class PengambilanSeragamController extends Controller
{
    private const JENIS = ['kaos' => 'Kaos', 'kemeja' => 'Kemeja'];

    /** Scan ulang kartu yang sama dalam rentang ini = ketukan ganda, bukan pengambilan baru. */
    private const JEDA_KETUKAN_GANDA_DETIK = 120;

    private const BATAS_DAFTAR = 300;

    /*
    |--------------------------------------------------------------------------
    | HALAMAN
    |--------------------------------------------------------------------------
    */

    public function index(Request $request): View
    {
        return view('landing-page.admin.pengambilan-seragam.index', [
            'tanggal'    => $this->tanggalDariRequest($request),
            'hariIni'    => now()->toDateString(),
            'ukuranList' => SeragamHelper::UKURAN,
        ]);
    }

    /** Daftar pengambilan pada satu tanggal + ringkasan — dipanggil berkala oleh halaman. */
    public function entries(Request $request): JsonResponse
    {
        $tanggal = $this->tanggalDariRequest($request);

        $rows = PengambilanSeragam::aktif()
            ->whereDate('created_at', $tanggal)
            ->with($this->relasiAnggota())
            ->orderByDesc('id')
            ->limit(self::BATAS_DAFTAR)
            ->get();

        $ringkasan = PengambilanSeragam::aktif()
            ->whereDate('created_at', $tanggal)
            ->selectRaw('jenis, COUNT(*) as total')
            ->groupBy('jenis')
            ->pluck('total', 'jenis');

        return response()->json([
            'tanggal'   => $tanggal,
            'list'      => $this->serialisasi($rows),
            'ringkasan' => [
                'kaos'   => (int) ($ringkasan['kaos'] ?? 0),
                'kemeja' => (int) ($ringkasan['kemeja'] ?? 0),
            ],
            'statistik' => $this->statistikKepemilikan(),
        ]);
    }

    /**
     * Sebaran ukuran kaos / kemeja di antara anggota AKTIF, berdasarkan ukuran yang
     * tercatat di data anggota, lengkap dengan jumlah yang belum punya per ukuran.
     * Dimuat saat tombol "Detail" diklik, bukan ikut polling.
     *
     * Tiap baris (ukuran terisi, urut S → 5XL; `belum_diisi` = ukuran masih kosong):
     *   anggota     : jumlah anggota berukuran itu
     *   belum_punya : dari mereka, yang jumlah kaos/kemejanya 0 ATAU masih kosong
     *
     * Definisi "belum punya" sama persis dengan kartu statistik.
     * `total` = jumlah seluruh baris (termasuk `belum_diisi`) = total anggota aktif.
     */
    public function ukuran(string $jenis): JsonResponse
    {
        abort_unless(array_key_exists($jenis, self::JENIS), 404);

        // Nama kolom dari daftar tetap di atas, bukan input pengguna.
        $kolom  = "ukuran_{$jenis}";
        $jumlah = "jumlah_{$jenis}";
        $urutan = array_flip(SeragamHelper::UKURAN);

        $sebaran = Anggota::active()
            ->selectRaw(
                "{$kolom} as ukuran, COUNT(*) as total, "
                . "SUM(CASE WHEN COALESCE({$jumlah}, 0) = 0 THEN 1 ELSE 0 END) as belum_punya"
            )
            ->groupBy($kolom)
            ->get();

        $kosong     = fn () => ['anggota' => 0, 'belum_punya' => 0];
        $rincian    = [];
        $belumDiisi = $kosong();
        $total      = $kosong();

        foreach ($sebaran as $baris) {
            $angka = [
                'anggota'     => (int) $baris->total,
                'belum_punya' => (int) $baris->belum_punya,
            ];

            foreach ($angka as $k => $n) {
                $total[$k] += $n;
            }

            if ($baris->ukuran === null || $baris->ukuran === '') {
                foreach ($angka as $k => $n) {
                    $belumDiisi[$k] += $n;
                }
            } else {
                $rincian[] = ['ukuran' => $baris->ukuran] + $angka;
            }
        }

        // Urutan baku (S, M, L, ...); ukuran di luar daftar (mis. diubah langsung di DB) ditaruh paling akhir.
        usort($rincian, fn ($a, $b) =>
            ($urutan[$a['ukuran']] ?? PHP_INT_MAX) <=> ($urutan[$b['ukuran']] ?? PHP_INT_MAX)
            ?: strcmp($a['ukuran'], $b['ukuran'])
        );

        return response()->json([
            'jenis'       => $jenis,
            'label'       => self::JENIS[$jenis],
            'rincian'     => $rincian,
            'belum_diisi' => $belumDiisi,
            'total'       => $total,
        ]);
    }

    /*
    |--------------------------------------------------------------------------
    | SCAN
    |--------------------------------------------------------------------------
    */

    public function scan(Request $request): JsonResponse
    {
        $ukuranValid = 'in:' . implode(',', SeragamHelper::UKURAN);

        $validated = $request->validate([
            'kode'           => ['required', 'string', 'max:100'],
            'metode'         => ['required', 'in:web,scanner,nfc'],
            'jenis'          => ['required', 'array', 'min:1'],
            'jenis.*'        => ['in:kaos,kemeja'],
            'konfirmasi'     => ['nullable', 'array'],
            'konfirmasi.*'   => ['in:kaos,kemeja'],
            'ukuran'         => ['nullable', 'array'],
            'ukuran.kaos'    => ['nullable', $ukuranValid],
            'ukuran.kemeja'  => ['nullable', $ukuranValid],
        ]);

        $jenisDipilih = array_values(array_unique($validated['jenis']));
        $konfirmasi   = $validated['konfirmasi'] ?? [];
        $ukuranManual = array_filter($validated['ukuran'] ?? []);

        $kodeRaw        = trim($validated['kode']);
        $kodeNormalized = RfidHelper::normalize($kodeRaw);

        $anggotaId = Anggota::where(function ($q) use ($kodeRaw, $kodeNormalized) {
            $q->where('nik_karyawan', $kodeRaw)->orWhere('ulid', $kodeRaw);

            if (filled($kodeNormalized)) {
                $q->orWhere('rfid_code', $kodeNormalized);
            }
        })->value('id');

        if (! $anggotaId) {
            return response()->json([
                'status'  => 'tidak_dikenali',
                'message' => 'Kartu/kode tidak dikenali.',
            ], 404);
        }

        return DB::transaction(function () use ($anggotaId, $jenisDipilih, $konfirmasi, $ukuranManual, $validated) {

            // Kunci baris anggota: dua petugas yang scan kartu yang sama bersamaan akan antre,
            // sehingga yang kedua melihat hasil yang pertama (dan jatuh ke "ketukan ganda").
            $anggota = Anggota::whereKey($anggotaId)->lockForUpdate()->first();

            $items      = [];
            $perluAksi  = false;

            foreach ($jenisDipilih as $jenis) {
                $sebelum = (int) ($anggota->{"jumlah_{$jenis}"} ?? 0);
                $ukuran  = $anggota->{"ukuran_{$jenis}"} ?: ($ukuranManual[$jenis] ?? null);

                $item = [
                    'jenis'            => $jenis,
                    'label'            => self::JENIS[$jenis],
                    'ukuran'           => $ukuran,
                    'jumlah_sebelum'   => $sebelum,
                    'perlu_ukuran'     => false,
                    'perlu_konfirmasi' => false,
                ];

                $baruSaja = PengambilanSeragam::aktif()
                    ->where('anggota_id', $anggota->id)
                    ->where('jenis', $jenis)
                    ->where('created_at', '>=', now()->subSeconds(self::JEDA_KETUKAN_GANDA_DETIK))
                    ->orderByDesc('id')
                    ->first();

                if ($baruSaja) {
                    $item['status'] = 'baru_saja';
                    $item['jam']    = $baruSaja->created_at->format('H:i');
                } else {
                    $item['perlu_ukuran']     = ! $ukuran;
                    $item['perlu_konfirmasi'] = $sebelum >= 1 && ! in_array($jenis, $konfirmasi, true);

                    $item['status'] = ($item['perlu_ukuran'] || $item['perlu_konfirmasi'])
                        ? 'perlu_tindakan'
                        : 'siap';
                }

                $perluAksi = $perluAksi || $item['status'] === 'perlu_tindakan';
                $items[]   = $item;
            }

            $ringkasAnggota = [
                'id'         => $anggota->id,
                'nama'       => $anggota->nama_lengkap,
                'nik'        => $anggota->nik_karyawan,
                'departemen' => $anggota->departemen,
            ];

            // Ada yang perlu dikonfirmasi / dilengkapi → belum ada yang dicatat.
            if ($perluAksi) {
                return response()->json([
                    'status'  => 'perlu_konfirmasi',
                    'message' => "{$anggota->nama_lengkap}: perlu konfirmasi sebelum dicatat.",
                    'anggota' => $ringkasAnggota,
                    'items'   => $items,
                ]);
            }

            $siap = array_values(array_filter($items, fn ($i) => $i['status'] === 'siap'));

            // Semua jenis yang dipilih baru saja diambil → ketukan ganda.
            if (empty($siap)) {
                $ket = collect($items)
                    ->map(fn ($i) => "{$i['label']} (jam {$i['jam']})")
                    ->implode(' dan ');

                return response()->json([
                    'status'  => 'sudah_diambil',
                    'message' => "{$anggota->nama_lengkap} baru saja mengambil {$ket}. Tidak dicatat lagi.",
                    'anggota' => $ringkasAnggota,
                    'items'   => $items,
                ]);
            }

            // ── CATAT ─────────────────────────────────────────────────────────────
            $petugas = session('anggota.nama');
            $update  = [];
            $dibuat  = [];

            foreach ($items as &$item) {
                if ($item['status'] !== 'siap') {
                    continue;
                }

                $jenis = $item['jenis'];

                $dibuat[] = PengambilanSeragam::create([
                    'anggota_id'        => $anggota->id,
                    'jenis'             => $jenis,
                    'ukuran'            => $item['ukuran'],
                    'walau_sudah_punya' => $item['jumlah_sebelum'] >= 1,
                    'metode'            => $validated['metode'],
                    'dicatat_oleh'      => $petugas,
                ]);

                $update["jumlah_{$jenis}"] = $item['jumlah_sebelum'] + 1;

                // Ukuran yang dipilih petugas di tempat ikut melengkapi data anggota.
                if (! $anggota->{"ukuran_{$jenis}"}) {
                    $update["ukuran_{$jenis}"] = $item['ukuran'];
                }

                $item['status']         = 'dicatat';
                $item['jumlah_sesudah'] = $item['jumlah_sebelum'] + 1;
            }
            unset($item);

            $anggota->update($update);

            $ket = collect($items)
                ->where('status', 'dicatat')
                ->map(fn ($i) => "{$i['label']} ({$i['ukuran']})")
                ->implode(' dan ');

            $rows = collect($dibuat)->each->setRelation('anggota', $anggota)->reverse()->values();

            return response()->json([
                'status'  => 'berhasil',
                'message' => "Tercatat: {$anggota->nama_lengkap} mengambil {$ket}.",
                'anggota' => $ringkasAnggota,
                'items'   => $items,
                'rows'    => $this->serialisasi($rows),
            ]);
        });
    }

    /*
    |--------------------------------------------------------------------------
    | BATAL
    |--------------------------------------------------------------------------
    */

    /**
     * Batalkan satu pengambilan (salah scan). Baris riwayat tidak dihapus, hanya
     * ditandai dibatalkan, dan jumlah anggota dikurangi 1 (tidak kurang dari 0).
     */
    public function batal(PengambilanSeragam $pengambilan): JsonResponse
    {
        return DB::transaction(function () use ($pengambilan) {

            // Urutan kunci sama dengan scan(): anggota dulu, baru baris riwayat.
            $anggota = Anggota::withTrashed()->whereKey($pengambilan->anggota_id)->lockForUpdate()->first();
            $row     = PengambilanSeragam::whereKey($pengambilan->id)->lockForUpdate()->first();

            if (! $row || $row->dibatalkan_at) {
                return response()->json([
                    'status'  => 'sudah_dibatalkan',
                    'message' => 'Pengambilan ini sudah dibatalkan sebelumnya.',
                ], 409);
            }

            $label  = self::JENIS[$row->jenis] ?? ucfirst($row->jenis);
            $nama   = $anggota?->nama_lengkap ?? 'Anggota';
            $jumlah = null;

            if ($anggota) {
                $kolom  = "jumlah_{$row->jenis}";
                $jumlah = max(0, (int) ($anggota->{$kolom} ?? 0) - 1);
                $anggota->update([$kolom => $jumlah]);
            }

            $row->update([
                'dibatalkan_at'   => now(),
                'dibatalkan_oleh' => session('anggota.nama'),
            ]);

            return response()->json([
                'status'  => 'dibatalkan',
                'message' => "Pengambilan {$label} oleh {$nama} dibatalkan"
                    . ($jumlah !== null ? " (jumlah sekarang: {$jumlah})." : '.'),
            ]);
        });
    }

    /*
    |--------------------------------------------------------------------------
    | INTERNAL
    |--------------------------------------------------------------------------
    */

    /**
     * Sebaran kepemilikan kaos & kemeja di antara anggota AKTIF (is_active = 1).
     *
     *   belum_punya : jumlah = 0 ATAU masih kosong (NULL) — kosong dianggap belum punya
     *   sudah_punya : jumlah >= 1  (total, dan dipecah di `rincian`: punya 1, punya 2, dst.)
     *
     * belum_punya + sudah_punya = total anggota aktif.
     */
    private function statistikKepemilikan(): array
    {
        $hasil = ['total' => Anggota::active()->count()];

        foreach (array_keys(self::JENIS) as $jenis) {
            $kolom = "jumlah_{$jenis}";   // dari daftar tetap di atas, bukan input pengguna

            $sebaran = Anggota::active()
                ->selectRaw("{$kolom} as jumlah, COUNT(*) as total")
                ->groupBy($kolom)
                ->get();

            $belumPunya = 0;
            $rincian    = [];

            foreach ($sebaran as $baris) {
                $anggota = (int) $baris->total;

                if ($baris->jumlah === null || (int) $baris->jumlah === 0) {
                    $belumPunya += $anggota;
                } else {
                    $rincian[] = ['jumlah' => (int) $baris->jumlah, 'anggota' => $anggota];
                }
            }

            usort($rincian, fn ($a, $b) => $a['jumlah'] <=> $b['jumlah']);

            $hasil[$jenis] = [
                'belum_punya' => $belumPunya,
                'sudah_punya' => array_sum(array_column($rincian, 'anggota')),
                'rincian'     => $rincian,
            ];
        }

        return $hasil;
    }

    private function relasiAnggota(): array
    {
        return [
            'anggota' => fn ($q) => $q->withTrashed()->select(
                'id', 'nama_lengkap', 'nik_karyawan', 'jumlah_kaos', 'jumlah_kemeja'
            ),
        ];
    }

    private function serialisasi($rows): array
    {
        return $rows->map(function (PengambilanSeragam $r) {
            $a = $r->anggota;

            return [
                'id'                => $r->id,
                'jam'               => $r->created_at->format('H:i'),
                'nama'              => $a?->nama_lengkap ?? '-',
                'nik'               => $a?->nik_karyawan ?? '-',
                'jenis'             => $r->jenis,
                'jenis_label'       => self::JENIS[$r->jenis] ?? ucfirst($r->jenis),
                'ukuran'            => $r->ukuran,
                'jumlah'            => $a ? $a->{"jumlah_{$r->jenis}"} : null,   // total dimiliki SEKARANG
                'walau_sudah_punya' => (bool) $r->walau_sudah_punya,
                'oleh'              => $r->dicatat_oleh,
            ];
        })->values()->all();
    }

    private function tanggalDariRequest(Request $request): string
    {
        $tanggal = (string) $request->query('tanggal', '');

        try {
            return $tanggal !== ''
                ? Carbon::createFromFormat('Y-m-d', $tanggal)->toDateString()
                : now()->toDateString();
        } catch (\Throwable) {
            return now()->toDateString();
        }
    }
}