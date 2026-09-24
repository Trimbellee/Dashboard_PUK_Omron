<?php

namespace App\Http\Controllers;

use App\Models\Anggota;
use Carbon\Carbon;
use Illuminate\Database\QueryException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class AnggotaRfidPairingController extends Controller
{
    private const SIG_KEY    = 'rfid_pairing:signature';
    private const SIG_TTL    = 2;   // detik — 10 device polling tetap cuma ±1 query berat / 2 detik
    private const LIST_LIMIT = 20;

    private const COLS = [
        'id', 'nama_lengkap', 'nik_karyawan', 'departemen', 'rfid_code', 'rfid_paired_at',
    ];

    public function index(): View
    {
        return view('anggota.rfid-pairing');
    }

    /**
     * Dipanggil begitu kartu di-tap / kode diketik.
     */
    public function check(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'kode' => ['required', 'string', 'max:100'],
        ]);

        $kode = $this->normalize($validated['kode']);

        if ($kode === '') {
            return response()->json(['ok' => false, 'message' => 'Kode tidak valid.'], 422);
        }

        $anggota = Anggota::where('rfid_code', $kode)
            ->first(['id', 'nama_lengkap', 'nik_karyawan', 'departemen']);

        if (!$anggota) {
            return response()->json(['exists' => false, 'kode' => $kode]);
        }

        return response()->json([
            'exists'  => true,
            'kode'    => $kode,
            'anggota' => [
                'id'         => $anggota->id,
                'nama'       => $anggota->nama_lengkap,
                'nik'        => $anggota->nik_karyawan,
                'departemen' => $anggota->departemen ?? '-',
            ],
        ]);
    }

    /**
     * Pencarian anggota aktif untuk dipasangkan dengan kode yang baru di-tap.
     */
    public function search(Request $request): JsonResponse
    {
        $q = trim((string) $request->query('q', ''));

        if (mb_strlen($q) < 2) {
            return response()->json([]);
        }

        $like = '%' . $this->escapeLike($q) . '%';

        $rows = Anggota::query()
            ->where('is_active', 1)
            ->where(fn ($s) => $s->where('nama_lengkap', 'like', $like)
                                 ->orWhere('nik_karyawan', 'like', $like))
            ->orderBy('nama_lengkap')
            ->limit(10)
            ->get(['id', 'nama_lengkap', 'nik_karyawan', 'departemen', 'rfid_code'])
            ->map(fn ($a) => [
                'id'           => $a->id,
                'nama_lengkap' => $a->nama_lengkap,
                'nik_karyawan' => $a->nik_karyawan,
                'departemen'   => $a->departemen ?? '-',
                'punya_kartu'  => filled($a->rfid_code),
            ])
            ->values();

        return response()->json($rows);
    }

    /**
     * Simpan kode ke anggota terpilih.
     * Aman untuk banyak device: row di-lock + unique index sebagai pengaman terakhir.
     */
    public function assign(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'kode'       => ['required', 'string', 'max:100'],
            'anggota_id' => ['required', 'integer'],
        ]);

        $kode = $this->normalize($validated['kode']);

        if ($kode === '') {
            return response()->json(['ok' => false, 'message' => 'Kode tidak valid.'], 422);
        }

        $id = (int) $validated['anggota_id'];

        try {
            [$anggota, $dilepasId] = DB::transaction(function () use ($kode, $id) {
                $anggota = Anggota::lockForUpdate()->find($id);

                if (!$anggota) {
                    return [null, null];
                }

                // Lepas kode dari pemilik lama (kalau ada & bukan anggota yang sama).
                // DB::table → tidak kena scope soft-delete, jadi pemilik lama yang sudah
                // dihapus pun ikut dilepas (kalau tidak, unique index akan menolak terus).
                $dilepasId = DB::table('anggota')
                    ->where('rfid_code', $kode)
                    ->where('id', '!=', $id)
                    ->lockForUpdate()
                    ->value('id');

                if ($dilepasId) {
                    DB::table('anggota')->where('id', $dilepasId)->update([
                        'rfid_code'      => null,
                        'rfid_paired_at' => null,
                    ]);
                }

                $anggota->forceFill([
                    'rfid_code'      => $kode,
                    'rfid_paired_at' => now(),
                ])->save();

                return [$anggota, $dilepasId];
            }, 3); // retry 3x kalau deadlock
        } catch (QueryException $e) {
            // Selain unique violation (SQLSTATE 23000) → biarkan error asli muncul di log
            if (($e->errorInfo[0] ?? null) !== '23000') {
                throw $e;
            }
            // Kartu yang sama baru saja dipasang dari device lain
            return response()->json([
                'ok'      => false,
                'message' => 'Kartu ini baru saja dipasangkan dari perangkat lain. Tap ulang untuk cek.',
            ], 409);
        }

        if (!$anggota) {
            return response()->json(['ok' => false, 'message' => 'Anggota tidak ditemukan.'], 404);
        }

        // Paksa hitung ulang versi supaya total & version langsung akurat
        Cache::forget(self::SIG_KEY);
        [$total, $version] = $this->signature();

        return response()->json([
            'ok'         => true,
            'message'    => "Kartu berhasil dipasangkan ke {$anggota->nama_lengkap}.",
            'total'      => $total,
            'version'    => $version,
            'dilepas_id' => $dilepasId,
            'anggota'    => $this->row($anggota),
        ]);
    }

    /**
     * Live list anggota yang punya kartu RFID.
     *  - ?v=TOKEN  → kalau tidak ada perubahan, balas kecil tanpa list.
     *  - ?q=TEXT   → cari anggota ber-kartu (min. 2 karakter).
     *  - Selalu maksimal 20 baris.
     */
    public function pairedList(Request $request): JsonResponse
    {
        $q        = trim((string) $request->query('q', ''));
        $isSearch = mb_strlen($q) >= 2;

        [$total, $version] = $this->signature();

        if (!$isSearch && $request->query('v') === $version) {
            return response()->json([
                'changed' => false,
                'version' => $version,
                'total'   => $total,
            ]);
        }

        $query = Anggota::query()
            ->whereNotNull('rfid_code')
            ->where('rfid_code', '!=', '');

        if ($isSearch) {
            $like = '%' . $this->escapeLike($q) . '%';
            $query->where(fn ($s) => $s->where('nama_lengkap', 'like', $like)
                                       ->orWhere('nik_karyawan', 'like', $like));
        }

        $list = $query
            ->orderByDesc('rfid_paired_at')
            ->orderByDesc('id')
            ->limit(self::LIST_LIMIT)
            ->get(self::COLS)
            ->map(fn ($a) => $this->row($a))
            ->values();

        return response()->json([
            'changed' => true,
            'version' => $version,
            'total'   => $total,
            'list'    => $list,
        ]);
    }

    // ══════════════════════════════════════════════════════════
    // Helpers
    // ══════════════════════════════════════════════════════════

    /**
     * Total + token versi. Di-cache singkat supaya polling banyak device tidak membebani DB.
     * SUM(id) ikut di hash supaya perpindahan kartu antar anggota selalu terdeteksi.
     *
     * @return array{0:int,1:string}
     */
    private function signature(): array
    {
        return Cache::remember(self::SIG_KEY, self::SIG_TTL, function () {
            $s = Anggota::query()
                ->whereNotNull('rfid_code')
                ->where('rfid_code', '!=', '')
                ->selectRaw('COUNT(*) as total, MAX(rfid_paired_at) as last_paired, SUM(id) as ids')
                ->toBase()
                ->first();

            $total = (int) ($s->total ?? 0);

            return [$total, md5($total . '|' . ($s->last_paired ?? '') . '|' . ($s->ids ?? 0))];
        });
    }

    private function row(Anggota $a): array
    {
        $t = $a->rfid_paired_at ? Carbon::parse($a->rfid_paired_at) : null;

        return [
            'id'                 => $a->id,
            'nama'               => $a->nama_lengkap,
            'nik'                => $a->nik_karyawan,
            'departemen'         => $a->departemen ?? '-',
            'kode'               => $a->rfid_code,
            'dipasangkan_pretty' => $t?->translatedFormat('d M Y, H:i') ?? '-',
        ];
    }

    private function escapeLike(string $s): string
    {
        return addcslashes($s, '%_\\');
    }

    /**
     * Normalisasi kode — buang separator, uppercase.
     */
    private function normalize(string $kode): string
    {
        return strtoupper(preg_replace('/[^A-Za-z0-9]/', '', trim($kode)));
    }
}